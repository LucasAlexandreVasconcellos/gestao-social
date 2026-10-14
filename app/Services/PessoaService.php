<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\FamiliaRepositoryInterface;
use App\Contracts\Repositories\PessoaRepositoryInterface;
use App\Models\Pessoa;
use App\Validators\FormValidator;

final class PessoaService
{
    public function __construct(
        private PessoaRepositoryInterface $pessoaRepo,
        private FamiliaRepositoryInterface $familiaRepo,
        private AuditLogService $auditLog
    ) {}

    public function listar(array $filters = []): array
    {
        return $this->pessoaRepo->findAll($filters);
    }

    public function obterPorId(int $id): ?Pessoa
    {
        return $this->pessoaRepo->findById($id);
    }

    public function obterHistoricoVinculos(int $pessoaId): array
    {
        $vinculos = $this->pessoaRepo->getHistoricoVinculosFamiliares($pessoaId);
        $historico = [];

        foreach ($vinculos as $v) {
            $familia = $this->familiaRepo->findById($v->familiaId);
            $historico[] = [
                'vinculo' => $v,
                'familia' => $familia
            ];
        }

        return $historico;
    }

    public function salvar(array $dados, ?int $id = null): array
    {
        $validator = new FormValidator($dados);
        $validator
            ->required('nomeCompleto', 'Nome Completo')
            ->minLength('nomeCompleto', 3, 'Nome Completo')
            ->required('dataNascimento', 'Data de Nascimento')
            ->date('dataNascimento', 'Data de Nascimento')
            ->cpf('cpf', 'CPF', false);

        if (!$validator->isValid()) {
            return ['success' => false, 'errors' => $validator->getErrors()];
        }

        $dataNasc = trim($dados['dataNascimento']);
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $dataNasc, $m)) {
            $dataNasc = "{$m[3]}-{$m[2]}-{$m[1]}";
        }

        if ($id !== null && $id > 0) {
            $pessoa = $this->pessoaRepo->findById($id);
            if (!$pessoa) {
                return ['success' => false, 'errors' => ['geral' => 'Pessoa não encontrada.']];
            }

            $pessoa->nomeCompleto = trim($dados['nomeCompleto']);
            $pessoa->nomeSocial = trim($dados['nomeSocial'] ?? '') ?: null;
            $pessoa->dataNascimento = $dataNasc;
            $pessoa->cpf = trim($dados['cpf'] ?? '') ?: null;
            $pessoa->telefone = trim($dados['telefone'] ?? '') ?: null;
            $pessoa->escolaridade = trim($dados['escolaridade'] ?? 'Não informada');
            $pessoa->ocupacao = trim($dados['ocupacao'] ?? '') ?: null;
            $pessoa->situacaoOcupacional = trim($dados['situacaoOcupacional'] ?? 'Não informada');
            $pessoa->observacoes = trim($dados['observacoes'] ?? '') ?: null;
            $pessoa->situacaoCadastro = trim($dados['situacaoCadastro'] ?? 'Ativo');

            $this->pessoaRepo->update($pessoa);
            $this->auditLog->log('Editar', 'Pessoa', $pessoa->id, "Atualização cadastral de {$pessoa->nomeCompleto}");

            return ['success' => true, 'pessoa' => $pessoa];
        }

        $pessoa = new Pessoa(
            0,
            trim($dados['nomeCompleto']),
            trim($dados['nomeSocial'] ?? '') ?: null,
            $dataNasc,
            trim($dados['cpf'] ?? '') ?: null,
            trim($dados['telefone'] ?? '') ?: null,
            trim($dados['escolaridade'] ?? 'Não informada'),
            trim($dados['ocupacao'] ?? '') ?: null,
            trim($dados['situacaoOcupacional'] ?? 'Não informada'),
            trim($dados['observacoes'] ?? '') ?: null,
            'Ativo'
        );

        $saved = $this->pessoaRepo->save($pessoa);
        $this->auditLog->log('Criar', 'Pessoa', $saved->id, "Cadastro de pessoa {$saved->nomeCompleto}");

        return ['success' => true, 'pessoa' => $saved];
    }
}
