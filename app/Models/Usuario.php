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
        public array $doisFatoresBackupCodes = []
    ) {}

    public function can(string $permission): bool
    {
        return match ($this->perfil) {
            self::PERFIL_ADMIN => true,
            self::PERFIL_COORDENACAO => !in_array($permission, ['system.manage_users_hard'], true),
            self::PERFIL_ASSISTENTE_SOCIAL => in_array($permission, [
                'familias.view', 'familias.create', 'familias.edit',
                'pessoas.view', 'pessoas.create', 'pessoas.edit',
                'avaliacoes.view', 'avaliacoes.create', 'avaliacoes.edit', 'avaliacoes.sensitive',
                'atendimentos.view', 'atendimentos.create', 'atendimentos.edit',
                'beneficios.view', 'beneficios.create',
                'projetos.view', 'projetos.create', 'projetos.edit',
                'relatorios.view'
            ], true),
            self::PERFIL_ESTOQUE => in_array($permission, [
                'estoque.view', 'estoque.create', 'estoque.move',
                'beneficios.view', 'beneficios.efetivar',
                'doacoes.view', 'doacoes.create',
                'relatorios.view'
            ], true),
            self::PERFIL_ATENDIMENTO => in_array($permission, [
                'familias.view', 'familias.create',
                'pessoas.view', 'pessoas.create',
                'atendimentos.view', 'atendimentos.create',
                'beneficios.view',
                'projetos.view'
            ], true),
            self::PERFIL_CONSULTA => in_array($permission, [
                'familias.view', 'pessoas.view', 'beneficios.view',
                'estoque.view', 'doacoes.view', 'projetos.view', 'relatorios.view'
            ], true),
            default => false,
        };
    }

    public function toArray(): array
    {
        $data = get_object_vars($this);
        unset($data['senhaHash'], $data['doisFatoresSecret'], $data['doisFatoresBackupCodes']);
        return $data;
    }
}
