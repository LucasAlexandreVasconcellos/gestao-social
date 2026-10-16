<?php

declare(strict_types=1);

namespace App\Repositories\Database;

use App\Contracts\Repositories\UsuarioRepositoryInterface;
use App\Core\Database;
use App\Models\Usuario;
use PDO;

final class UsuarioRepositoryDb implements UsuarioRepositoryInterface
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function findAll(): array
    {
        $sql = "SELECT u.*, p.nome AS perfil_nome
                  FROM gestao_social.usuarios u
                  JOIN gestao_social.perfis p ON p.id = u.perfil_id
                 ORDER BY u.id ASC";

        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn(array $row) => $this->mapRowToModel($row), $rows);
    }

    public function findById(int $id): ?Usuario
    {
        $stmt = $this->db->prepare("
            SELECT u.*, p.nome AS perfil_nome
              FROM gestao_social.usuarios u
              JOIN gestao_social.perfis p ON p.id = u.perfil_id
             WHERE u.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->mapRowToModel($row) : null;
    }

    public function findByEmail(string $email): ?Usuario
    {
        $stmt = $this->db->prepare("
            SELECT u.*, p.nome AS perfil_nome
              FROM gestao_social.usuarios u
              JOIN gestao_social.perfis p ON p.id = u.perfil_id
             WHERE LOWER(u.email) = LOWER(:email) OR LOWER(u.login) = LOWER(:email)
        ");
        $stmt->execute(['email' => trim($email)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->mapRowToModel($row) : null;
    }

    public function save(Usuario $usuario): Usuario
    {
        $perfilId = $this->resolvePerfilId($usuario->perfil);
        $login = $this->generateLogin($usuario->email);
        $defaultHash = password_hash('demo123', PASSWORD_BCRYPT);

        $sql = "INSERT INTO gestao_social.usuarios (
                    nome, email, login, senha_hash, perfil_id, ativo, ultimo_acesso
                ) VALUES (
                    :nome, :email, :login, :senha, :perfil_id, :ativo, :acesso
                ) RETURNING id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'nome' => $usuario->nome,
            'email' => strtolower($usuario->email),
            'login' => $login,
            'senha' => $defaultHash,
            'perfil_id' => $perfilId,
            'ativo' => $usuario->ativo ? 'true' : 'false',
            'acesso' => $usuario->ultimoAcesso ?: null,
        ]);

        $usuario->id = (int)$stmt->fetchColumn();
        return $usuario;
    }

    public function update(Usuario $usuario): bool
    {
        $perfilId = $this->resolvePerfilId($usuario->perfil);

        $sql = "UPDATE gestao_social.usuarios SET
                    nome = :nome,
                    email = :email,
                    perfil_id = :perfil_id,
                    ativo = :ativo,
                    ultimo_acesso = :acesso
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $usuario->id,
            'nome' => $usuario->nome,
            'email' => strtolower($usuario->email),
            'perfil_id' => $perfilId,
            'ativo' => $usuario->ativo ? 'true' : 'false',
            'acesso' => $usuario->ultimoAcesso ?: null,
        ]);
    }

    private function mapRowToModel(array $row): Usuario
    {
        $perfil = $this->denormalizePerfil($row['perfil_nome'] ?? 'Consulta');

        return new Usuario(
            (int)$row['id'],
            $row['nome'],
            $row['email'],
            $perfil,
            (bool)$row['ativo'],
            $row['ultimo_acesso'] ? substr((string)$row['ultimo_acesso'], 0, 19) : null
        );
    }

    private function resolvePerfilId(string $perfil): int
    {
        $norm = $this->normalizePerfil($perfil);
        $stmt = $this->db->prepare("SELECT id FROM gestao_social.perfis WHERE nome = :nome LIMIT 1");
        $stmt->execute(['nome' => $norm]);
        $id = $stmt->fetchColumn();

        if ($id) {
            return (int)$id;
        }

        $stmtFirst = $this->db->query("SELECT id FROM gestao_social.perfis ORDER BY id ASC LIMIT 1");
        return (int)($stmtFirst->fetchColumn() ?: 1);
    }

    private function normalizePerfil(string $perfil): string
    {
        return match ($perfil) {
            Usuario::PERFIL_ADMIN => 'Administrador',
            Usuario::PERFIL_COORDENACAO => 'Coordenação',
            Usuario::PERFIL_ASSISTENTE_SOCIAL => 'Assistente Social',
            Usuario::PERFIL_ESTOQUE => 'Estoque',
            Usuario::PERFIL_ATENDIMENTO => 'Atendimento / Recepção',
            default => 'Coordenação'
        };
    }

    private function denormalizePerfil(string $perfil): string
    {
        if (str_contains($perfil, 'Recepção') || str_contains($perfil, 'Atendimento')) {
            return Usuario::PERFIL_ATENDIMENTO;
        }
        return match ($perfil) {
            'Administrador' => Usuario::PERFIL_ADMIN,
            'Coordenação' => Usuario::PERFIL_COORDENACAO,
            'Assistente Social' => Usuario::PERFIL_ASSISTENTE_SOCIAL,
            'Estoque' => Usuario::PERFIL_ESTOQUE,
            default => Usuario::PERFIL_CONSULTA
        };
    }

    private function generateLogin(string $email): string
    {
        $parts = explode('@', $email);
        $base = preg_replace('/[^a-z0-9.]/', '', strtolower($parts[0]));
        return !empty($base) ? $base : 'user.' . time();
    }
}
