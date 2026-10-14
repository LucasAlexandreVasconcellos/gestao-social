<?php

declare(strict_types=1);

namespace App\Models;

final class Familia
{
    public function __construct(
        public int $id,
        public string $codigo,
        public string $pessoaReferenciaNome,
        public ?int $pessoaReferenciaId,
        public string $cep,
        public string $logradouro,
        public string $numero,
        public ?string $complemento,
        public string $bairro,
        public string $municipio,
        public string $uf,
        public float $rendaFamiliar,
        public float $despesasMensais,
        public string $situacaoMoradia, // Própria, Alugada, Cedida, Ocupação, Outro
        public string $dataCadastro,
        public string $situacaoCadastral, // Em Análise, Ativa, Suspensa, Encerrada
        public ?string $observacoes = null,
        public array $historicoAlteracoes = []
    ) {}

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
