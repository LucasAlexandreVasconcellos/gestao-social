<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\AtendimentoRepositoryInterface;
use App\Contracts\Repositories\AvaliacaoRepositoryInterface;
use App\Contracts\Repositories\BeneficioRepositoryInterface;
use App\Contracts\Repositories\FamiliaRepositoryInterface;
use App\Contracts\Repositories\PessoaRepositoryInterface;
use App\Models\Familia;
use App\Models\VinculoFamiliar;
use App\Validators\FormValidator;

final class FamiliaService
{
    public function __construct(
        private FamiliaRepositoryInterface $familiaRepo,
        private PessoaRepositoryInterface $pessoaRepo,
        private AvaliacaoRepositoryInterface $avaliacaoRepo,
        private AtendimentoRepositoryInterface $atendimentoRepo,
        private BeneficioRepositoryInterface $beneficioRepo,
        private AuditLogService $auditLog
    ) {}

    public function listar(array $filters = []): array
    {
        return $this->familiaRepo->findAll($filters);
    }

    public function obterPorId(int $id): ?Familia
    {
        return $this->familiaRepo->findById($id);
    }

    public function obterFichaCompleta(int $id): ?array
    {
        $familia = $this->familiaRepo->findById($id);
        if (!$familia) {
            return null;
        }

        $integrantes = $this->familiaRepo->getIntegrantes($id, false);
        $avaliacoes = $this->avaliacaoRepo->findByFamiliaId($id);
        $atendimentos = $this->atendimentoRepo->findByFamiliaId($id);
        $beneficios = $this->beneficioRepo->findByFamiliaId($id);

        return [
            'familia' => $familia,
            'integrantes' => $integrantes,
            'avaliacoes' => $avaliacoes,
            'ultimaAvaliacao' => !empty($avaliacoes) ? $avaliacoes[0] : null,
            'atendimentos' => $atendimentos,
            'beneficios' => $beneficios
        ];
    }

    public function criar(array $dados): array
    {
        $validator = new FormValidator($dados);
        $validator
            ->required('pessoaReferenciaNome', 'Nome do Responsável Familiar')
            ->minLength('pessoaReferenciaNome', 3, 'Nome do Responsável Familiar')
            ->required('logradouro', 'Logradouro')
            ->required('numero', 'Número')
            ->required('bairro', 'Bairro')
            ->required('municipio', 'Município')
            ->required('uf', 'UF')
            ->cep('cep', 'CEP')
            ->numeric('rendaFamiliar', 'Renda Familiar')
            ->numeric('despesasMensais', 'Despesas Mensais')
            ->inList('situacaoMoradia', ['Própria', 'Alugada', 'Cedida', 'Ocupação', 'Outro'], 'Situação de Moradia')
            ->inList('situacaoCadastral', ['Em Análise', 'Ativa', 'Suspensa', 'Encerrada'], 'Situação Cadastral');

        if (!$validator->isValid()) {
            return ['success' => false, 'errors' => $validator->getErrors()];
        }

        $renda = (float)str_replace(',', '.', (string)($dados['rendaFamiliar'] ?? 0));
        $despesas = (float)str_replace(',', '.', (string)($dados['despesasMensais'] ?? 0));

        $familia = new Familia(
            0,
            '', // Código gerado pelo repositório
            trim($dados['pessoaReferenciaNome']),
            null,
            trim($dados['cep'] ?? ''),
            trim($dados['logradouro']),
            trim($dados['numero']),
            trim($dados['complemento'] ?? '') ?: null,
            trim($dados['bairro']),
            trim($dados['municipio']),
            strtoupper(trim($dados['uf'])),
            $renda,
            $despesas,
            $dados['situacaoMoradia'] ?? 'Outro',
            date('Y-m-d'),
            $dados['situacaoCadastral'] ?? 'Em Análise',
            trim($dados['observacoes'] ?? '') ?: null,
            [
                [
                    'data' => date('Y-m-d H:i:s'),
                    'tipo' => 'Cadastro Inicial',
                    'descricao' => 'Família cadastrada no sistema'
                ]
            ]
        );

        $saved = $this->familiaRepo->save($familia);

        $this->auditLog->log('Criar', 'Familia', $saved->id, "Cadastro da família {$saved->codigo} ({$saved->pessoaReferenciaNome})");

        return ['success' => true, 'familia' => $saved];
    }

    public function atualizar(int $id, array $dados): array
    {
        $familia = $this->familiaRepo->findById($id);
        if (!$familia) {
            return ['success' => false, 'errors' => ['geral' => 'Família não encontrada.']];
        }

        $validator = new FormValidator($dados);
        $validator
            ->required('pessoaReferenciaNome', 'Nome do Responsável Familiar')
            ->minLength('pessoaReferenciaNome', 3, 'Nome do Responsável Familiar')
            ->required('logradouro', 'Logradouro')
            ->required('numero', 'Número')
            ->required('bairro', 'Bairro')
            ->required('municipio', 'Município')
            ->required('uf', 'UF')
            ->cep('cep', 'CEP')
            ->numeric('rendaFamiliar', 'Renda Familiar')
            ->numeric('despesasMensais', 'Despesas Mensais')
            ->inList('situacaoMoradia', ['Própria', 'Alugada', 'Cedida', 'Ocupação', 'Outro'], 'Situação de Moradia')
            ->inList('situacaoCadastral', ['Em Análise', 'Ativa', 'Suspensa', 'Encerrada'], 'Situação Cadastral');

        if (!$validator->isValid()) {
            return ['success' => false, 'errors' => $validator->getErrors()];
        }

        $renda = (float)str_replace(',', '.', (string)($dados['rendaFamiliar'] ?? 0));
        $despesas = (float)str_replace(',', '.', (string)($dados['despesasMensais'] ?? 0));

        $historico = $familia->historicoAlteracoes;
        $historico[] = [
            'data' => date('Y-m-d H:i:s'),
            'tipo' => 'Atualização Cadastral',
            'descricao' => 'Dados cadastrais e socioeconômicos atualizados'
        ];

        $familia->pessoaReferenciaNome = trim($dados['pessoaReferenciaNome']);
        $familia->cep = trim($dados['cep'] ?? '');
        $familia->logradouro = trim($dados['logradouro']);
        $familia->numero = trim($dados['numero']);
        $familia->complemento = trim($dados['complemento'] ?? '') ?: null;
        $familia->bairro = trim($dados['bairro']);
        $familia->municipio = trim($dados['municipio']);
        $familia->uf = strtoupper(trim($dados['uf']));
        $familia->rendaFamiliar = $renda;
        $familia->despesasMensais = $despesas;
        $familia->situacaoMoradia = $dados['situacaoMoradia'] ?? $familia->situacaoMoradia;
        $familia->situacaoCadastral = $dados['situacaoCadastral'] ?? $familia->situacaoCadastral;
        $familia->observacoes = trim($dados['observacoes'] ?? '') ?: null;
        $familia->historicoAlteracoes = $historico;

        $this->familiaRepo->update($familia);

        $this->auditLog->log('Editar', 'Familia', $familia->id, "Atualização dos dados da família {$familia->codigo}");

        return ['success' => true, 'familia' => $familia];
    }

    public function vincularIntegrante(int $familiaId, array $dados): array
    {
        $familia = $this->familiaRepo->findById($familiaId);
        if (!$familia) {
            return ['success' => false, 'errors' => ['geral' => 'Família não encontrada.']];
        }

        $validator = new FormValidator($dados);
        $validator
            ->required('nomeCompleto', 'Nome Completo')
            ->minLength('nomeCompleto', 3, 'Nome Completo')
            ->required('dataNascimento', 'Data de Nascimento')
            ->date('dataNascimento', 'Data de Nascimento')
            ->cpf('cpf', 'CPF', false)
            ->required('parentesco', 'Parentesco');

        if (!$validator->isValid()) {
            return ['success' => false, 'errors' => $validator->getErrors()];
        }

        // Cria a Pessoa
        $pessoa = new \App\Models\Pessoa(
            0,
            trim($dados['nomeCompleto']),
            trim($dados['nomeSocial'] ?? '') ?: null,
            trim($dados['dataNascimento']),
            trim($dados['cpf'] ?? '') ?: null,
            trim($dados['telefone'] ?? '') ?: null,
            trim($dados['escolaridade'] ?? 'Não informada'),
            trim($dados['ocupacao'] ?? '') ?: null,
            trim($dados['situacaoOcupacional'] ?? 'Não informada'),
            trim($dados['observacoes'] ?? '') ?: null,
            'Ativo'
        );

        $savedPessoa = $this->pessoaRepo->save($pessoa);

        $isResponsavel = !empty($dados['isResponsavel']);

        $vinculo = new VinculoFamiliar(
            0,
            $familiaId,
            $savedPessoa->id,
            trim($dados['parentesco']),
            $isResponsavel,
            date('Y-m-d'),
            null,
            true,
            trim($dados['observacoesVinculo'] ?? '') ?: null,
            $savedPessoa
        );

        $savedVinculo = $this->familiaRepo->addIntegrante($vinculo);

        $this->auditLog->log('Vincular Integrante', 'Familia', $familiaId, "Vinculado integrante {$savedPessoa->nomeCompleto} ({$dados['parentesco']}) à família {$familia->codigo}");

        return ['success' => true, 'vinculo' => $savedVinculo];
    }

    public function desvincularIntegrante(int $vinculoId, string $motivo = ''): bool
    {
        $vinculos = $this->familiaRepo->getIntegrantes(0, false);
        // Busca o vínculo
        foreach ($vinculos as $v) {
            if ($v->id === $vinculoId) {
                $v->ativo = false;
                $v->dataTermino = date('Y-m-d');
                $v->observacoes = trim($v->observacoes . ' | Vínculo encerrado: ' . $motivo);
                $this->familiaRepo->updateIntegrante($v);
                $this->auditLog->log('Desvincular Integrante', 'VinculoFamiliar', $vinculoId, "Vínculo familiar desativado (histórico preservado). Motivo: {$motivo}");
                return true;
            }
        }
        return false;
    }

    public function getBairros(): array
    {
        return $this->familiaRepo->getBairros();
    }
}
