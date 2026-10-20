<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\AtendimentoRepositoryInterface;
use App\Contracts\Repositories\AvaliacaoRepositoryInterface;
use App\Contracts\Repositories\BeneficioRepositoryInterface;
use App\Contracts\Repositories\DoacaoRepositoryInterface;
use App\Contracts\Repositories\FamiliaRepositoryInterface;
use App\Contracts\Repositories\PessoaRepositoryInterface;
use App\Contracts\Repositories\ProdutoRepositoryInterface;
use App\Core\Pdf;
use App\Core\QrCode;
use App\Helpers\FormatHelper;
use App\Helpers\SecurityHelper;

final class PdfService
{
    public function __construct(
        private BeneficioRepositoryInterface $beneficioRepo,
        private FamiliaRepositoryInterface $familiaRepo,
        private PessoaRepositoryInterface $pessoaRepo,
        private DoacaoRepositoryInterface $doacaoRepo,
        private ?AvaliacaoRepositoryInterface $avaliacaoRepo = null,
        private ?AtendimentoRepositoryInterface $atendimentoRepo = null
    ) {}

    /**
     * Gera o Termo de Declaração e Recebimento de Benefício em PDF oficial.
     */
    public function gerarTermoBeneficio(int $beneficioId): Pdf
    {
        $beneficio = $this->beneficioRepo->findById($beneficioId);
        if (!$beneficio) {
            throw new \InvalidArgumentException("Benefício #{$beneficioId} não localizado.");
        }

        $familia = $this->familiaRepo->findById($beneficio->familiaId);
        $integrantes = $this->familiaRepo->getIntegrantes($beneficio->familiaId);

        // Identifica o Responsável Familiar
        $responsavel = null;
        foreach ($integrantes as $vinculo) {
            if ($vinculo->isResponsavel) {
                $responsavel = $this->pessoaRepo->findById($vinculo->pessoaId);
                break;
            }
        }
        if (!$responsavel && !empty($integrantes)) {
            $responsavel = $this->pessoaRepo->findById($integrantes[0]->pessoaId);
        }

        $protocolo = sprintf('BEN-%s-%04d', date('Y', strtotime($beneficio->dataEntrega)), $beneficio->id);
        $hashValidacao = hash('sha256', "BENEFICIO:{$protocolo}:{$beneficio->dataEntrega}:{$beneficio->familiaId}");
        $shortHash = strtoupper(substr($hashValidacao, 0, 16));

        $qrCode = QrCode::create("https://gestaosocial.gov.br/validar/beneficio/{$protocolo}?h={$shortHash}");

        $pdf = Pdf::create("Termo de Recebimento de Benefício — {$protocolo}");
        $pdf->drawHeader(
            'TERMO DE CONCESSÃO E RECEBIMENTO DE BENEFÍCIO',
            'Secretaria Municipal de Assistência Social e Cidadania — SUAS',
            $protocolo
        );

        $pdf->addHeading('1. Identificação do Beneficiário / Família Atendida', 1);
        $nomeTitular = $responsavel ? $responsavel->nomeCompleto : ($familia->codigo ?? 'Família Cadastrada');
        $cpfTitular = $responsavel && $responsavel->cpf ? SecurityHelper::maskCpf($responsavel->cpf) : 'Não informado';
        $nisTitular = $responsavel && $responsavel->nis ? $responsavel->nis : 'Não informado';
        $endereco = $familia ? sprintf('%s, %s - %s, %s/%s', $familia->logradouro, $familia->numero ?: 'S/N', $familia->bairro, $familia->municipio, $familia->uf) : 'Endereço não cadastrado';

        $pdf->addKeyValueGrid([
            'Beneficiário(a)' => $nomeTitular,
            'CPF do Titular' => $cpfTitular,
            'NIS / CadÚnico' => $nisTitular,
            'Prontuário' => $familia->codigo ?? "FAM-{$beneficio->familiaId}",
            'Endereço' => $endereco,
            'Data da Concessão' => FormatHelper::date($beneficio->dataEntrega),
            'Tipo de Benefício' => $beneficio->tipoBeneficio,
            'Responsável Entrega' => $beneficio->responsavelDistribuicao,
        ], 2);

        $pdf->addHeading('2. Relação de Itens Concedidos', 1);

        $tableHeaders = ['Produto / Item', 'Quantidade', 'Unidade de Medida', 'Situação'];
        $tableRows = [];

        if (!empty($beneficio->itens)) {
            foreach ($beneficio->itens as $item) {
                $tableRows[] = [
                    $item->produtoNome ?? 'Item socioassistencial',
                    (string)$item->quantidade,
                    $item->unidadeMedida ?? 'UN',
                    $beneficio->situacao ?? 'Efetivada'
                ];
            }
        } else {
            $tableRows[] = [
                $beneficio->tipoBeneficio,
                '1',
                'Kit/Cesta',
                $beneficio->situacao
            ];
        }

        $pdf->addTable($tableHeaders, $tableRows, [45, 15, 20, 20]);

        $pdf->addHeading('3. Declaração Legal e Compromisso', 1);
        $termoLegal = "Declaro sob as penas da lei que resido no endereço acima indicado, encontro-me em situação de vulnerabilidade socioassistencial e recebi integralmente os itens descritos neste termo em perfeitas condições para consumo/utilização familiar, assumindo total compromisso de não destiná-los à comercialização ou cessão ilícita.";
        $pdf->addAlertBox($termoLegal, 'info');

        $pdf->addQrCodeValidation(
            $qrCode,
            $shortHash,
            "gestaosocial.gov.br/validar/{$protocolo}"
        );

        $pdf->addSignatures(
            $nomeTitular,
            'Assinatura do(a) Beneficiário(a) / Recebedor(a)',
            $beneficio->responsavelDistribuicao,
            'Assistente Social / Responsável pela Entrega'
        );

        return $pdf;
    }

    /**
     * Gera o Prontuário Socioassistencial Completo da Família com QR Code.
     */
    public function gerarProntuarioFamilia(int $familiaId): Pdf
    {
        $familia = $this->familiaRepo->findById($familiaId);
        if (!$familia) {
            throw new \InvalidArgumentException("Família #{$familiaId} não localizada.");
        }

        $vinculos = $this->familiaRepo->getIntegrantes($familiaId);
        $membros = [];
        foreach ($vinculos as $v) {
            $p = $this->pessoaRepo->findById($v->pessoaId);
            if ($p) {
                $membros[] = (object)[
                    'nomeCompleto' => $p->nomeCompleto,
                    'parentesco' => $v->parentesco ?: ($v->isResponsavel ? 'Responsável Familiar' : 'Membro'),
                    'responsavelFamiliar' => $v->isResponsavel,
                    'cpf' => $p->cpf,
                    'dataNascimento' => $p->dataNascimento,
                    'rendaIndividual' => 0.0
                ];
            }
        }
        $beneficios = $this->beneficioRepo->findAll(['familiaId' => $familiaId]);

        $protocolo = $familia->codigo ?: sprintf('FAM-%d', $familia->id);
        $hashValidacao = hash('sha256', "PRONTUARIO:{$protocolo}:{$familiaId}");
        $shortHash = strtoupper(substr($hashValidacao, 0, 16));

        $qrCode = QrCode::create("https://gestaosocial.gov.br/validar/prontuario/{$protocolo}?h={$shortHash}");

        $pdf = Pdf::create("Prontuário Socioassistencial — {$protocolo}");
        $pdf->drawHeader(
            'PRONTUÁRIO SOCIOASSISTENCIAL COMPLETO',
            'Centro de Referência de Assistência Social (CRAS) — Registro Unificado',
            $protocolo
        );

        // 1. Dados Cadastrais e Domiciliares
        $pdf->addHeading('1. Dados Cadastrais e Condições de Moradia', 1);
        $endereco = sprintf('%s, %s%s', $familia->logradouro, $familia->numero ?: 'S/N', $familia->complemento ? " ({$familia->complemento})" : '');

        $pdf->addKeyValueGrid([
            'Código do Prontuário' => $protocolo,
            'Data de Cadastro' => FormatHelper::date($familia->dataCadastro ?? date('Y-m-d')),
            'Endereço' => $endereco,
            'Bairro / Município' => "{$familia->bairro} - {$familia->municipio}/{$familia->uf}",
            'CEP' => $familia->cep ?: 'Não informado',
            'Situação da Moradia' => $familia->situacaoMoradia ?? 'Própria / Alugada',
            'Situação Cadastral' => $familia->situacaoCadastral ?? 'Ativa',
            'Despesas Mensais' => FormatHelper::money($familia->despesasMensais ?? 0.0),
        ], 2);

        // 2. Situação Socioeconômica
        $pdf->addHeading('2. Diagnóstico Socioeconômico e Vulnerabilidade', 1);
        $rendaTotal = $familia->rendaFamiliar ?? ($familia->rendaTotal ?? 0.0);
        $numIntegrantes = max(1, count($membros));
        $rendaPerCapita = $rendaTotal / $numIntegrantes;

        $classificacao = 'Baixa Renda';
        if ($rendaPerCapita <= 105.0) {
            $classificacao = 'Extrema Pobreza';
        } elseif ($rendaPerCapita <= 218.0) {
            $classificacao = 'Pobreza';
        }

        $pdf->addKeyValueGrid([
            'Renda Familiar Total' => FormatHelper::currency($rendaTotal),
            'Integrantes da Família' => "{$numIntegrantes} pessoa(s)",
            'Renda Per Capita' => FormatHelper::currency($rendaPerCapita),
            'Classificação Social' => $classificacao,
        ], 2);

        // 3. Composição Familiar
        $pdf->addHeading('3. Composição Familiar e Integrantes', 1);
        $headersMembros = ['Nome do Integrante', 'Parentesco', 'CPF', 'Nascimento', 'Renda (R$)'];
        $rowsMembros = [];

        foreach ($membros as $m) {
            $cpf = $m->cpf ? SecurityHelper::maskCpf($m->cpf) : 'Não informado';
            $nasc = $m->dataNascimento ? FormatHelper::date($m->dataNascimento) : '—';
            $rowsMembros[] = [
                $m->nomeCompleto,
                $m->parentesco ?: ($m->responsavelFamiliar ? 'Responsável' : 'Membro'),
                $cpf,
                $nasc,
                FormatHelper::currency($m->rendaIndividual ?? 0.0)
            ];
        }

        if (empty($rowsMembros)) {
            $rowsMembros[] = ['Nenhum integrante vinculado', '—', '—', '—', 'R$ 0,00'];
        }
        $pdf->addTable($headersMembros, $rowsMembros, [32, 20, 18, 15, 15]);

        // 4. Histórico de Benefícios Concedidos
        $pdf->addHeading('4. Histórico de Concessão de Benefícios', 1);
        $headersBen = ['Data', 'Tipo de Benefício', 'Responsável pela Entrega', 'Situação'];
        $rowsBen = [];

        foreach (array_slice($beneficios, 0, 8) as $b) {
            $rowsBen[] = [
                FormatHelper::date($b->dataEntrega),
                $b->tipoBeneficio,
                $b->responsavelDistribuicao,
                $b->situacao
            ];
        }

        if (empty($rowsBen)) {
            $rowsBen[] = ['—', 'Nenhum benefício registrado até o momento', '—', '—'];
        }
        $pdf->addTable($headersBen, $rowsBen, [20, 35, 30, 15]);

        // Autenticidade e Assinatura
        $pdf->addQrCodeValidation(
            $qrCode,
            $shortHash,
            "gestaosocial.gov.br/validar/{$protocolo}"
        );

        $pdf->addSignatures(
            'Coordenação Técnica de Serviço Social',
            'Assistente Social Responsável — CRESS',
            'Gestão Municipal de Assistência Social',
            'Visto da Coordenação do CRAS'
        );

        return $pdf;
    }

    /**
     * Gera o Recibo Oficial de Doação Financeira ou Material.
     */
    public function gerarReciboDoacao(int $doacaoId): Pdf
    {
        $doacao = $this->doacaoRepo->findById($doacaoId);
        if (!$doacao) {
            throw new \InvalidArgumentException("Doação #{$doacaoId} não localizada.");
        }

        $ano = date('Y', strtotime($doacao->dataRecebimento));
        $protocolo = sprintf('REC-DOA-%s-%04d', $ano, $doacao->id);
        $valorEstimado = (float)($doacao->valorEstimado ?? 0.0);
        $hashValidacao = hash('sha256', "DOACAO:{$protocolo}:{$valorEstimado}:{$doacao->nomeDoador}");
        $shortHash = strtoupper(substr($hashValidacao, 0, 16));

        $qrCode = QrCode::create("https://gestaosocial.gov.br/validar/doacao/{$protocolo}?h={$shortHash}");

        $pdf = Pdf::create("Recibo de Doação — {$protocolo}");
        $pdf->drawHeader(
            'RECIBO OFICIAL DE DOAÇÃO SOCIOASSISTENCIAL',
            'Entidade Beneficente e Assistencial de Amparo Social',
            $protocolo
        );

        $pdf->addHeading('1. Dados do Doador(a)', 1);
        $cpfDoador = $doacao->documento ? SecurityHelper::maskCpf($doacao->documento) : 'Não informado';
        $contatoDoador = $doacao->telefone ?: ($doacao->email ?: 'Não informado');

        $pdf->addKeyValueGrid([
            'Doador(a)' => $doacao->nomeDoador,
            'CPF / CNPJ' => $cpfDoador,
            'Contato / Telefone' => $contatoDoador,
            'Data da Doação' => FormatHelper::date($doacao->dataRecebimento),
            'Tipo de Doação' => $doacao->tipoDoacao === 'Financeira' ? 'Recursos Financeiros' : 'Produtos / Mantimentos',
            'Finalidade / Descrição' => $doacao->descricao ?: 'Ações Gerais de Amparo Social',
        ], 2);

        $pdf->addHeading('2. Discriminação dos Valores / Bens Recebidos', 1);

        if ($doacao->tipoDoacao === 'Financeira') {
            $valorFormatado = FormatHelper::currency($valorEstimado);
            $extenso = $this->valorPorExtenso($valorEstimado);

            $pdf->addAlertBox("Recebemos a quantia líquida de {$valorFormatado} ({$extenso}), creditada em conta oficial para aplicação nas ações socioassistenciais da entidade.", 'success');
        } else {
            $headersItens = ['Produto / Item', 'Quantidade', 'Unidade', 'Lote / Validade'];
            $rowsItens = [];

            if (!empty($doacao->itens)) {
                foreach ($doacao->itens as $it) {
                    $loteVal = ($it->lote ? "Lote: {$it->lote}" : '') . ($it->dataValidade ? ' Val: ' . FormatHelper::date($it->dataValidade) : '');
                    $rowsItens[] = [
                        $it->nomeProduto ?? 'Item doado',
                        (string)$it->quantidade,
                        $it->unidadeMedida ?? 'UN',
                        $loteVal ?: 'Padrão'
                    ];
                }
            } else {
                $rowsItens[] = ['Mantimentos / Produtos Diversos', '1', 'Lote', FormatHelper::currency($valorEstimado)];
            }

            $pdf->addTable($headersItens, $rowsItens, [40, 20, 20, 20]);
        }

        $pdf->addHeading('3. Quitação e Efeitos Legais / Fiscais', 1);
        $termoQuitacao = "Pelo presente instrumento, a instituição dá plena, rasa e irrevogável quitação dos valores/bens acima discriminados, declarando que os mesmos serão aplicados com estrita observância da legislação vigente, dos princípios de transparência pública e das normas contábeis de entidades sem fins lucrativos.";
        $pdf->addAlertBox($termoQuitacao, 'info');

        $pdf->addQrCodeValidation(
            $qrCode,
            $shortHash,
            "gestaosocial.gov.br/validar/{$protocolo}"
        );

        $pdf->addSignatures(
            'Coordenação de Captação e Doações',
            'Responsável pelo Recebimento',
            'Tesouraria / Diretoria Financeira',
            'Visto da Controladoria Social'
        );

        return $pdf;
    }

    /**
     * Converte valor monetário em reais para texto por extenso.
     */
    private function valorPorExtenso(float $valor): string
    {
        $inteiro = (int)floor($valor);
        $centavos = (int)round(($valor - $inteiro) * 100);

        $extensoReais = $this->numeroPorExtenso($inteiro);
        $moeda = ($inteiro === 1) ? 'real' : 'reais';
        $resultado = "{$extensoReais} {$moeda}";

        if ($centavos > 0) {
            $extensoCentavos = $this->numeroPorExtenso($centavos);
            $moedaCent = ($centavos === 1) ? 'centavo' : 'centavos';
            $resultado .= " e {$extensoCentavos} {$moedaCent}";
        }

        return mb_strtolower($resultado);
    }

    private function numeroPorExtenso(int $numero): string
    {
        if ($numero === 0) return 'zero';

        $unidades = ['', 'um', 'dois', 'três', 'quatro', 'cinco', 'seis', 'sete', 'oito', 'nove', 'dez', 'onze', 'doze', 'treze', 'quatorze', 'quinze', 'dezesseis', 'dezessete', 'dezoito', 'dezenove'];
        $dezenas = ['', '', 'vinte', 'trinta', 'quarenta', 'cinquenta', 'sessenta', 'setenta', 'oitenta', 'noventa'];
        $centenas = ['', 'cento', 'duzentos', 'trezentos', 'quatrocentos', 'quinhentos', 'seiscentos', 'setecentos', 'oitocentos', 'novecentos'];

        if ($numero === 100) return 'cem';
        if ($numero < 20) return $unidades[$numero];
        if ($numero < 100) {
            $u = $numero % 10;
            return $dezenas[(int)floor($numero / 10)] . ($u ? " e " . $unidades[$u] : '');
        }
        if ($numero < 1000) {
            $c = (int)floor($numero / 100);
            $r = $numero % 100;
            return $centenas[$c] . ($r ? " e " . $this->numeroPorExtenso($r) : '');
        }
        if ($numero < 1000000) {
            $mil = (int)floor($numero / 1000);
            $r = $numero % 1000;
            $txtMil = ($mil === 1) ? 'um mil' : $this->numeroPorExtenso($mil) . ' mil';
            return $txtMil . ($r ? ($r < 100 || $r % 100 === 0 ? " e " : ", ") . $this->numeroPorExtenso($r) : '');
        }

        return (string)$numero;
    }
}
