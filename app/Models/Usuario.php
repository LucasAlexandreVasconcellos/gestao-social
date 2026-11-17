<?php

declare(strict_types=1);

namespace App\Models;

final class Usuario
{
    public const PERFIL_ADMIN = 'Administrador';
    public const PERFIL_COORDENACAO = 'Coordenação';
    public const PERFIL_ASSISTENTE_SOCIAL = 'Assistente Social';
    public const PERFIL_ESTOQUE = 'Estoque';
    public const PERFIL_ATENDIMENTO = 'Atendimento';
    public const PERFIL_CONSULTA = 'Consulta';

    public const PERFIS_DISPONIVEIS = [
        self::PERFIL_ADMIN,
        self::PERFIL_COORDENACAO,
        self::PERFIL_ASSISTENTE_SOCIAL,
        self::PERFIL_ESTOQUE,
        self::PERFIL_ATENDIMENTO,
        self::PERFIL_CONSULTA,
    ];

    public function __construct(
        public int $id,
        public string $nome,
        public string $email,
        public string $perfil,
        public bool $ativo = true,
        public ?string $ultimoAcesso = null,
        public ?string $senhaHash = null,
        public bool $doisFatoresAtivo = false,
        public ?string $doisFatoresSecret = null,
        public array $doisFatoresBackupCodes = [],
        public string $login = '',
        public bool $trocarSenhaObrigatorio = false,
        public int $tentativasLogin = 0,
        public ?string $bloqueadoAte = null
    ) {}

    public function can(string $permission): bool
    {
        return match ($this->perfil) {
            self::PERFIL_ADMIN => true,
            self::PERFIL_COORDENACAO => !in_array($permission, [
                'system.manage_users_hard',
                'usuarios.manage',
            ], true),
            self::PERFIL_ASSISTENTE_SOCIAL => in_array($permission, [
                'dashboard.view',
                'familias.view', 'familias.create', 'familias.edit', 'familias.integrantes', 'familias.pdf',
                'pessoas.view', 'pessoas.create', 'pessoas.edit',
                'avaliacoes.view', 'avaliacoes.create', 'avaliacoes.edit', 'avaliacoes.sensitive',
                'atendimentos.view', 'atendimentos.create', 'atendimentos.edit', 'atendimentos.encaminhar',
                'beneficios.view', 'beneficios.create', 'beneficios.pdf',
                'projetos.view', 'projetos.create', 'projetos.edit', 'projetos.participantes',
                'relatorios.view', 'relatorios.export', 'relatorios.nominal',
                'documentos.view', 'documentos.upload', 'documentos.delete',
                'notificacoes.view', 'notificacoes.send',
            ], true),
            self::PERFIL_ESTOQUE => in_array($permission, [
                'dashboard.view',
                'estoque.view', 'estoque.create', 'estoque.edit', 'estoque.move',
                'beneficios.view', 'beneficios.efetivar', 'beneficios.cancelar', 'beneficios.pdf',
                'doacoes.view', 'doacoes.create', 'doacoes.pdf',
                'relatorios.view', 'relatorios.export',
                'notificacoes.view', 'notificacoes.send',
            ], true),
            self::PERFIL_ATENDIMENTO => in_array($permission, [
                'dashboard.view',
                'familias.view', 'familias.create', 'familias.edit', 'familias.integrantes',
                'pessoas.view', 'pessoas.create', 'pessoas.edit',
                'atendimentos.view', 'atendimentos.create', 'atendimentos.encaminhar',
                'beneficios.view',
                'projetos.view', 'projetos.participantes',
                'documentos.view', 'documentos.upload',
                'notificacoes.view', 'notificacoes.send',
            ], true),
            self::PERFIL_CONSULTA => in_array($permission, [
                'dashboard.view',
                'familias.view', 'pessoas.view', 'beneficios.view',
                'estoque.view', 'doacoes.view', 'projetos.view',
                'relatorios.view', 'relatorios.export',
            ], true),
            default => false,
        };
    }

    public function isLocked(): bool
    {
        if (empty($this->bloqueadoAte)) {
            return false;
        }
        $ts = strtotime($this->bloqueadoAte);
        return $ts !== false && $ts > time();
    }

    public function toArray(): array
    {
        $data = get_object_vars($this);
        unset($data['senhaHash'], $data['doisFatoresSecret'], $data['doisFatoresBackupCodes']);
        return $data;
    }
}
