<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\UsuarioRepositoryInterface;
use App\Core\Database;
use App\Core\Env;
use App\Core\QrCode;
use App\Core\Session;
use App\Core\Totp;
use App\Helpers\SecurityHelper;
use App\Models\Usuario;
use App\Validators\FormValidator;

final class AuthService
{
    private const MAX_2FA_ATTEMPTS = 5;
    private ?string $lastLoginError = null;

    public function __construct(
        private UsuarioRepositoryInterface $usuarioRepo,
        private AuditLogService $auditLog
    ) {}

    public function getLastLoginError(): ?string
    {
        return $this->lastLoginError;
    }

    public function getCurrentUser(): ?Usuario
    {
        $userData = Session::get('user');
        if (!$userData || !is_array($userData)) {
            return null;
        }

        return new Usuario(
            (int)($userData['id'] ?? 0),
            (string)($userData['nome'] ?? ''),
            (string)($userData['email'] ?? ''),
            (string)($userData['perfil'] ?? Usuario::PERFIL_CONSULTA),
            (bool)($userData['ativo'] ?? true),
            $userData['ultimoAcesso'] ?? null,
            null,
            (bool)($userData['doisFatoresAtivo'] ?? false),
            null,
            [],
            (string)($userData['login'] ?? ''),
            (bool)($userData['trocarSenhaObrigatorio'] ?? false)
        );
    }

    public function getCurrentUserFull(): ?Usuario
    {
        $userData = Session::get('user');
        if (!$userData || empty($userData['id'])) {
            return null;
        }
        return $this->usuarioRepo->findById((int)$userData['id']);
    }

    public function check(): bool
    {
        return $this->getCurrentUser() !== null;
    }

    public function user(): ?Usuario
    {
        return $this->getCurrentUser();
    }

    public function login(string $emailOrLogin, string $password): bool
    {
        $this->lastLoginError = null;
        $identifier = trim($emailOrLogin);

        if ($identifier === '' || $password === '') {
            $this->lastLoginError = 'Informe seu e-mail/usuário e senha.';
            return false;
        }

        $user = $this->usuarioRepo->findByEmail($identifier);
        if (!$user) {
            $this->auditLog->log('Falha de Login', 'Usuario', 0, "Tentativa de login com identificador inexistente: {$identifier}");
            $this->lastLoginError = 'Credenciais inválidas.';
            return false;
        }

        if (!$user->ativo) {
            $this->auditLog->log('Login Recusado (Inativo)', 'Usuario', $user->id, "Tentativa de acesso em conta inativa ({$user->email}).");
            $this->lastLoginError = 'Esta conta de usuário está desativada. Procure a Administração do sistema.';
            return false;
        }

        if ($user->isLocked()) {
            $this->auditLog->log('Login Bloqueado', 'Usuario', $user->id, "Conta temporariamente bloqueada por excesso de tentativas ({$user->email}).");
            $this->lastLoginError = 'Conta temporariamente bloqueada devido a múltiplas tentativas inválidas. Aguarde alguns minutos.';
            return false;
        }

        if (empty($user->senhaHash) || !password_verify($password, $user->senhaHash)) {
            $this->usuarioRepo->recordFailedLogin($user->id);
            $this->auditLog->log('Falha de Login', 'Usuario', $user->id, "Senha inválida informada para {$user->email}.");
            $this->lastLoginError = 'Credenciais inválidas.';
            return false;
        }

        $this->usuarioRepo->resetLoginAttempts($user->id);

        if ($user->doisFatoresAtivo) {
            Session::set('2fa_pending_user_id', $user->id);
            Session::set('2fa_attempts', 0);
            $this->auditLog->log('Login Pendente 2FA', 'Usuario', $user->id, "Credenciais validadas para {$user->nome}. Aguardando código 2FA.");
            return true;
        }

        $this->completeAuthenticatedSession($user);
        $this->auditLog->log('Login', 'Usuario', $user->id, "Acesso autenticado para {$user->nome} ({$user->perfil})");
        return true;
    }

    private function completeAuthenticatedSession(Usuario $user): void
    {
        $this->usuarioRepo->updateLastAccess($user->id);
        $user->ultimoAcesso = date('Y-m-d H:i:s');

        Session::regenerate(true);
        Session::set('user', $user->toArray());

        if (Database::isConnected()) {
            Database::setAuditContext($user->id, $user->login ?: $user->email);
        }
    }

    public function hasPending2fa(): bool
    {
        return Session::has('2fa_pending_user_id');
    }

    public function getPending2faUser(): ?Usuario
    {
        $id = Session::get('2fa_pending_user_id');
        return $id ? $this->usuarioRepo->findById((int)$id) : null;
    }

    public function verify2faLogin(string $code, bool $isBackup = false): bool
    {
        $user = $this->getPending2faUser();
        if (!$user || !$user->ativo) {
            return false;
        }

        $attempts = (int)Session::get('2fa_attempts', 0);
        if ($attempts >= self::MAX_2FA_ATTEMPTS) {
            $this->cancelPending2fa();
            $this->usuarioRepo->recordFailedLogin($user->id);
            $this->auditLog->log('Bloqueio 2FA', 'Usuario', $user->id, "Excesso de tentativas inválidas de 2FA para {$user->nome}.");
            return false;
        }

        $valid = false;
        if ($isBackup) {
            $valid = $this->usuarioRepo->consumeBackupCode($user->id, $code);
        } else {
            if (!empty($user->doisFatoresSecret)) {
                $secret = SecurityHelper::decryptSecret($user->doisFatoresSecret) ?? $user->doisFatoresSecret;
                $valid = Totp::verify($secret, $code);
            }
        }

        if ($valid) {
            Session::remove('2fa_pending_user_id');
            Session::remove('2fa_attempts');
            $this->completeAuthenticatedSession($user);

            $tipoStr = $isBackup ? 'Código de Backup' : 'Aplicativo Autenticador TOTP';
            $this->auditLog->log('Login 2FA Concluído', 'Usuario', $user->id, "Autenticação 2FA via {$tipoStr} concluída para {$user->nome}");
            return true;
        }

        Session::set('2fa_attempts', $attempts + 1);
        $this->auditLog->log('Falha 2FA', 'Usuario', $user->id, "Tentativa de 2FA falhou para {$user->nome} (" . ($attempts + 1) . "/" . self::MAX_2FA_ATTEMPTS . ")");
        return false;
    }

    public function cancelPending2fa(): void
    {
        Session::remove('2fa_pending_user_id');
        Session::remove('2fa_attempts');
    }

    public function prepareTwoFactorSetup(int $userId): ?array
    {
        $user = $this->usuarioRepo->findById($userId);
        if (!$user) {
            return null;
        }

        $issuer = Env::get('APP_UNIT_NAME', 'Gestão Social') ?: 'Gestão Social';
        $secret = Totp::generateSecret();
        $uri = Totp::getProvisioningUri($user->email, $secret, $issuer);
        $backupCodes = Totp::generateBackupCodes(8);
        $svg = QrCode::svg($uri, 180);

        Session::set('_2fa_setup_' . $userId, [
            'secret' => $secret,
            'backup_codes' => $backupCodes,
            'uri' => $uri
        ]);

        return [
            'secret' => $secret,
            'uri' => $uri,
            'svg' => $svg,
            'backup_codes' => $backupCodes
        ];
    }

    public function confirmTwoFactorSetup(int $userId, string $code): bool
    {
        $setup = Session::get('_2fa_setup_' . $userId);
        if (!$setup || empty($setup['secret'])) {
            return false;
        }

        if (!Totp::verify($setup['secret'], $code)) {
            return false;
        }

        $encryptedSecret = SecurityHelper::encryptSecret($setup['secret']);
        $this->usuarioRepo->setTwoFactorSecret($userId, $encryptedSecret, $setup['backup_codes']);
        $this->usuarioRepo->enableTwoFactor($userId);

        $currentUser = Session::get('user');
        if ($currentUser && (int)$currentUser['id'] === $userId) {
            $currentUser['doisFatoresAtivo'] = true;
            Session::set('user', $currentUser);
        }

        Session::remove('_2fa_setup_' . $userId);
        $this->auditLog->log('2FA Habilitado', 'Usuario', $userId, "Usuário ativou o Segundo Fator de Autenticação (2FA).");
        return true;
    }

    public function disableTwoFactor(int $userId, ?string $currentPassword = null): array
    {
        $user = $this->usuarioRepo->findById($userId);
        if (!$user) {
            return ['success' => false, 'error' => 'Usuário não encontrado.'];
        }

        if ($currentPassword !== null) {
            if (empty($user->senhaHash) || !password_verify($currentPassword, $user->senhaHash)) {
                return ['success' => false, 'error' => 'Senha atual incorreta. Confirme sua senha para desativar o 2FA.'];
            }
        }

        $this->usuarioRepo->disableTwoFactor($userId);

        $currentUser = Session::get('user');
        if ($currentUser && (int)$currentUser['id'] === $userId) {
            $currentUser['doisFatoresAtivo'] = false;
            Session::set('user', $currentUser);
        }

        $this->auditLog->log('2FA Desabilitado', 'Usuario', $userId, "Usuário desativou o Segundo Fator de Autenticação.");
        return ['success' => true];
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword, string $confirmPassword, bool $verifyCurrent = true): array
    {
        $user = $this->usuarioRepo->findById($userId);
        if (!$user) {
            return ['success' => false, 'errors' => ['geral' => 'Usuário não encontrado.']];
        }

        if ($verifyCurrent) {
            if (empty($user->senhaHash) || !password_verify($currentPassword, $user->senhaHash)) {
                return ['success' => false, 'errors' => ['senhaAtual' => 'A senha atual informada está incorreta.']];
            }
        }

        $validator = new FormValidator([
            'novaSenha' => $newPassword,
            'confirmarSenha' => $confirmPassword
        ]);
        $validator->strongPassword('novaSenha', 'Nova Senha', true);

        if (!$validator->isValid()) {
            return ['success' => false, 'errors' => $validator->getErrors()];
        }

        if ($newPassword !== $confirmPassword) {
            return ['success' => false, 'errors' => ['confirmarSenha' => 'A confirmação de senha não confere com a nova senha.']];
        }

        if (!empty($user->senhaHash) && password_verify($newPassword, $user->senhaHash)) {
            return ['success' => false, 'errors' => ['novaSenha' => 'A nova senha não pode ser idêntica à senha atual.']];
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $this->usuarioRepo->updatePassword($userId, $hash, false);

        $sessionUser = Session::get('user');
        if ($sessionUser && (int)$sessionUser['id'] === $userId) {
            $sessionUser['trocarSenhaObrigatorio'] = false;
            Session::set('user', $sessionUser);
        }

        $this->auditLog->log('Alteração de Senha', 'Usuario', $userId, "Senha alterada para o usuário {$user->nome} ({$user->email}).");
        return ['success' => true];
    }

    public function createUser(array $dados): array
    {
        $validator = new FormValidator($dados);
        $validator
            ->required('nome', 'Nome Completo')
            ->minLength('nome', 3, 'Nome Completo')
            ->required('email', 'E-mail Institucional')
            ->email('email', 'E-mail Institucional')
            ->inList('perfil', Usuario::PERFIS_DISPONIVEIS, 'Perfil de Acesso')
            ->strongPassword('senha', 'Senha Inicial', true);

        if (!$validator->isValid()) {
            return ['success' => false, 'errors' => $validator->getErrors()];
        }

        if (($dados['senha'] ?? '') !== ($dados['confirmarSenha'] ?? '')) {
            return ['success' => false, 'errors' => ['confirmarSenha' => 'A confirmação de senha não confere.']];
        }

        $email = mb_strtolower(trim((string)$dados['email']), 'UTF-8');
        if ($this->usuarioRepo->findByEmail($email)) {
            return ['success' => false, 'errors' => ['email' => 'Já existe um usuário cadastrado com este e-mail.']];
        }

        $login = trim((string)($dados['login'] ?? ''));
        if ($login === '') {
            $login = explode('@', $email)[0];
        }
        $login = preg_replace('/[^a-z0-9._-]/', '', mb_strtolower($login, 'UTF-8')) ?: 'usuario_' . random_int(100, 999);
        if ($this->usuarioRepo->findByEmail($login)) {
            return ['success' => false, 'errors' => ['login' => 'Este login já está em uso por outro usuário.']];
        }

        $hash = password_hash((string)$dados['senha'], PASSWORD_BCRYPT, ['cost' => 12]);
        $forcarTroca = !empty($dados['trocarSenhaObrigatorio']);

        $usuario = new Usuario(
            id: 0,
            nome: trim((string)$dados['nome']),
            email: $email,
            perfil: (string)$dados['perfil'],
            ativo: !empty($dados['ativo']),
            ultimoAcesso: null,
            senhaHash: $hash,
            doisFatoresAtivo: false,
            doisFatoresSecret: null,
            doisFatoresBackupCodes: [],
            login: $login,
            trocarSenhaObrigatorio: $forcarTroca
        );

        try {
            $saved = $this->usuarioRepo->save($usuario);
        } catch (\Throwable $e) {
            return ['success' => false, 'errors' => ['geral' => 'Erro ao salvar usuário no banco de dados: ' . $e->getMessage()]];
        }

        $this->auditLog->log('Criar Usuário', 'Usuario', $saved->id, "Usuário {$saved->nome} ({$saved->email}) criado com perfil [{$saved->perfil}].");
        return ['success' => true, 'usuario' => $saved];
    }

    public function updateUser(int $id, array $dados, int $actingUserId): array
    {
        $usuario = $this->usuarioRepo->findById($id);
        if (!$usuario) {
            return ['success' => false, 'errors' => ['geral' => 'Usuário não encontrado.']];
        }

        $validator = new FormValidator($dados);
        $validator
            ->required('nome', 'Nome Completo')
            ->minLength('nome', 3, 'Nome Completo')
            ->required('email', 'E-mail Institucional')
            ->email('email', 'E-mail Institucional')
            ->inList('perfil', Usuario::PERFIS_DISPONIVEIS, 'Perfil de Acesso');

        if (!$validator->isValid()) {
            return ['success' => false, 'errors' => $validator->getErrors()];
        }

        $email = mb_strtolower(trim((string)$dados['email']), 'UTF-8');
        $existingEmail = $this->usuarioRepo->findByEmail($email);
        if ($existingEmail && $existingEmail->id !== $id) {
            return ['success' => false, 'errors' => ['email' => 'Este e-mail já está em uso por outro usuário.']];
        }

        $ativo = !empty($dados['ativo']);
        if ($id === $actingUserId && !$ativo) {
            return ['success' => false, 'errors' => ['ativo' => 'Você não pode desativar sua própria conta enquanto estiver autenticado.']];
        }

        $usuario->nome = trim((string)$dados['nome']);
        $usuario->email = $email;
        $usuario->perfil = (string)$dados['perfil'];
        $usuario->ativo = $ativo;

        if (!empty($dados['login'])) {
            $login = preg_replace('/[^a-z0-9._-]/', '', mb_strtolower(trim((string)$dados['login']), 'UTF-8'));
            if ($login !== '') {
                $existingLogin = $this->usuarioRepo->findByEmail($login);
                if ($existingLogin && $existingLogin->id !== $id) {
                    return ['success' => false, 'errors' => ['login' => 'Este login já pertence a outro usuário.']];
                }
                $usuario->login = $login;
            }
        }

        try {
            $this->usuarioRepo->update($usuario);
        } catch (\Throwable $e) {
            return ['success' => false, 'errors' => ['geral' => 'Erro ao atualizar usuário: ' . $e->getMessage()]];
        }

        if ($id === $actingUserId) {
            $sessionUser = Session::get('user');
            if ($sessionUser) {
                $sessionUser['nome'] = $usuario->nome;
                $sessionUser['email'] = $usuario->email;
                $sessionUser['perfil'] = $usuario->perfil;
                $sessionUser['login'] = $usuario->login;
                Session::set('user', $sessionUser);
            }
        }

        $this->auditLog->log('Editar Usuário', 'Usuario', $id, "Dados atualizados para {$usuario->nome} (Perfil: {$usuario->perfil}, Ativo: " . ($usuario->ativo ? 'Sim' : 'Não') . ").");
        return ['success' => true, 'usuario' => $usuario];
    }

    public function adminResetPassword(int $targetUserId, string $newPassword, string $confirmPassword): array
    {
        $user = $this->usuarioRepo->findById($targetUserId);
        if (!$user) {
            return ['success' => false, 'errors' => ['geral' => 'Usuário não encontrado.']];
        }

        $validator = new FormValidator(['novaSenha' => $newPassword]);
        $validator->strongPassword('novaSenha', 'Nova Senha Temporária', true);
        if (!$validator->isValid()) {
            return ['success' => false, 'errors' => $validator->getErrors()];
        }

        if ($newPassword !== $confirmPassword) {
            return ['success' => false, 'errors' => ['confirmarSenha' => 'As senhas informadas não conferem.']];
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $this->usuarioRepo->updatePassword($targetUserId, $hash, true);
        $this->usuarioRepo->resetLoginAttempts($targetUserId);

        $this->auditLog->log('Redefinição Administrativa de Senha', 'Usuario', $targetUserId, "Senha temporária redefinida para {$user->nome} ({$user->email}) com troca obrigatória no próximo acesso.");
        return ['success' => true];
    }

    public function toggleUserStatus(int $targetUserId, int $actingUserId): array
    {
        if ($targetUserId === $actingUserId) {
            return ['success' => false, 'error' => 'Não é permitido bloquear ou inativar a própria conta autenticada.'];
        }

        $user = $this->usuarioRepo->findById($targetUserId);
        if (!$user) {
            return ['success' => false, 'error' => 'Usuário não encontrado.'];
        }

        $novoStatus = !$user->ativo;
        $this->usuarioRepo->toggleActive($targetUserId, $novoStatus);
        $this->auditLog->log('Alterar Status Usuário', 'Usuario', $targetUserId, "Usuário {$user->nome} marcado como " . ($novoStatus ? 'Ativo' : 'Inativo') . ".");
        return ['success' => true, 'ativo' => $novoStatus];
    }

    public function findUserById(int $id): ?Usuario
    {
        return $this->usuarioRepo->findById($id);
    }

    public function logout(): void
    {
        $user = $this->getCurrentUser();
        if ($user) {
            $this->auditLog->log('Logout', 'Usuario', $user->id, "Encerramento de sessão");
        }
        Session::destroy();
    }

    public function checkPermission(string $permission): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return false;
        }

        return $user->can($permission);
    }

    public function getAllUsers(): array
    {
        return $this->usuarioRepo->findAll();
    }
}
