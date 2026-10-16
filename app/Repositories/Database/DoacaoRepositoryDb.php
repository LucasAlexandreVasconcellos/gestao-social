<?php

declare(strict_types=1);

namespace App\Repositories\Database;

use App\Contracts\Repositories\DoacaoRepositoryInterface;
use App\Core\Database;
use App\Models\Doacao;
use App\Models\ItemDoacao;
use PDO;

final class DoacaoRepositoryDb implements DoacaoRepositoryInterface
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function findAll(array $filters = []): array
    {
        $sql = "SELECT d.*, 
                       dor.nome AS doador_nome, dor.tipo AS doador_tipo, 
                       dor.documento AS doador_doc, dor.telefone AS doador_tel, 
                       dor.email AS doador_email,
                       u.nome AS responsavel_nome
                  FROM gestao_social.doacoes d
                  LEFT JOIN gestao_social.doadores dor ON dor.id = d.doador_id
                  JOIN gestao_social.usuarios u ON u.id = d.responsavel_id
                 WHERE 1=1";
        $params = [];

        if (!empty($filters['tipo'])) {
            $sql .= " AND (d.tipo_doacao = :tipo OR (:tipo = 'Produtos' AND d.tipo_doacao IN ('Alimentos', 'Vestuário', 'Higiene', 'Outros')))";
            $params['tipo'] = $filters['tipo'];
        }

        if (!empty($filters['search'])) {
            $term = '%' . mb_strtolower(trim($filters['search']), 'UTF-8') . '%';
            $sql .= " AND (LOWER(COALESCE(dor.nome, '')) LIKE :search OR LOWER(COALESCE(d.descricao, '')) LIKE :search)";
            $params['search'] = $term;
        }

        $sql .= " ORDER BY d.data_doacao DESC, d.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $doacoes = [];
        foreach ($rows as $row) {
            $doacao = $this->mapRowToModel($row);
            $doacao->itens = $this->getItens((int)$row['id']);
            $doacoes[] = $doacao;
        }

        return $doacoes;
    }

    public function findById(int $id): ?Doacao
    {
        $stmt = $this->db->prepare("
            SELECT d.*, 
                   dor.nome AS doador_nome, dor.tipo AS doador_tipo, 
                   dor.documento AS doador_doc, dor.telefone AS doador_tel, 
                   dor.email AS doador_email,
                   u.nome AS responsavel_nome
              FROM gestao_social.doacoes d
              LEFT JOIN gestao_social.doadores dor ON dor.id = d.doador_id
              JOIN gestao_social.usuarios u ON u.id = d.responsavel_id
             WHERE d.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $doacao = $this->mapRowToModel($row);
        $doacao->itens = $this->getItens($id);
        return $doacao;
    }

    public function save(Doacao $doacao): Doacao
    {
        $responsavelId = $this->resolveResponsavelId($doacao->responsavel);
        $doadorId = $this->resolveDoadorId($doacao);

        $sql = "INSERT INTO gestao_social.doacoes (
                    doador_id, responsavel_id, data_doacao, tipo_doacao,
                    status, valor_estimado, descricao, documento_comprovante
                ) VALUES (
                    :doador_id, :responsavel_id, :data_doacao, :tipo,
                    'Pendente', :valor, :descricao, :comprovante
                ) RETURNING id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'doador_id' => $doadorId,
            'responsavel_id' => $responsavelId,
            'data_doacao' => $doacao->dataRecebimento ?: date('Y-m-d H:i:s'),
            'tipo' => $this->normalizeTipo($doacao->tipoDoacao),
            'valor' => $doacao->valorEstimado ?: 0.00,
            'descricao' => $doacao->descricao,
            'comprovante' => $doacao->comprovante,
        ]);

        $doacao->id = (int)$stmt->fetchColumn();

        // Se houver itens já anexados, insere-os
        if (!empty($doacao->itens)) {
            foreach ($doacao->itens as $item) {
                if ($item instanceof ItemDoacao) {
                    $item->doacaoId = $doacao->id;
                    $this->addItem($item);
                }
            }
        }

        // Confirma a doação para disparar entrada no estoque e registrar lotes
        $this->confirmarDoacao($doacao->id);

        return $doacao;
    }

    public function addItem(ItemDoacao $item): ItemDoacao
    {
        // Se o produto_id não veio mas o nome sim, resolve o produto
        $produtoId = $item->produtoId;
        if (empty($produtoId)) {
            $stmtProd = $this->db->prepare("SELECT id FROM gestao_social.produtos WHERE LOWER(nome) = LOWER(:nome) LIMIT 1");
            $stmtProd->execute(['nome' => trim($item->nomeProduto)]);
            $produtoId = (int)$stmtProd->fetchColumn();

            if (!$produtoId) {
                $stmtInsertProd = $this->db->prepare("INSERT INTO gestao_social.produtos (categoria_id, codigo, nome, unidade_medida) VALUES (1, :cod, :nome, :unidade) RETURNING id");
                $stmtInsertProd->execute([
                    'cod' => 'PROD-AUTO-' . time(),
                    'nome' => $item->nomeProduto,
                    'unidade' => $item->unidadeMedida ?: 'Unidade'
                ]);
                $produtoId = (int)$stmtInsertProd->fetchColumn();
            }
            $item->produtoId = $produtoId;
        }

        $sql = "INSERT INTO gestao_social.doacao_itens (
                    doacao_id, produto_id, quantidade, valor_unitario_estimado, lote, validade
                ) VALUES (
                    :doacao_id, :produto_id, :quantidade, :valor, :lote, :validade
                ) RETURNING id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'doacao_id' => $item->doacaoId,
            'produto_id' => $produtoId,
            'quantidade' => $item->quantidade,
            'valor' => 0.00,
            'lote' => $item->lote,
            'validade' => $item->dataValidade ?: null,
        ]);

        $item->id = (int)$stmt->fetchColumn();
        return $item;
    }

    public function sumValorFinanceiro(string $mesAno = ''): float
    {
        $sql = "SELECT COALESCE(SUM(valor_estimado), 0.00) FROM gestao_social.doacoes WHERE tipo_doacao = 'Financeira' AND status = 'Confirmada'";
        $params = [];

        if (!empty($mesAno)) {
            $sql .= " AND TO_CHAR(data_doacao, 'YYYY-MM') = :mes_ano";
            $params['mes_ano'] = $mesAno;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (float)$stmt->fetchColumn();
    }

    public function countTotal(array $filters = []): int
    {
        return count($this->findAll($filters));
    }

    private function getItens(int $doacaoId): array
    {
        $sql = "SELECT di.*, p.nome AS produto_nome, p.unidade_medida
                  FROM gestao_social.doacao_itens di
                  JOIN gestao_social.produtos p ON p.id = di.produto_id
                 WHERE di.doacao_id = :doacao_id
                 ORDER BY di.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['doacao_id' => $doacaoId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $itens = [];
        foreach ($rows as $row) {
            $itens[] = new ItemDoacao(
                (int)$row['id'],
                (int)$row['doacao_id'],
                (int)$row['produto_id'],
                $row['produto_nome'] ?? 'Produto Doado',
                (float)$row['quantidade'],
                $row['unidade_medida'] ?? 'Unidade',
                $row['lote'],
                $row['validade']
            );
        }

        return $itens;
    }

    private function confirmarDoacao(int $doacaoId): void
    {
        $stmt = $this->db->prepare("UPDATE gestao_social.doacoes SET status = 'Confirmada' WHERE id = :id");
        $stmt->execute(['id' => $doacaoId]);
    }

    private function mapRowToModel(array $row): Doacao
    {
        $tipoDoador = match ($row['doador_tipo'] ?? 'Anônimo') {
            'Física' => 'Pessoa Física',
            'Jurídica' => 'Pessoa Jurídica',
            default => 'Anônimo'
        };

        $tipoDoacao = match ($row['tipo_doacao']) {
            'Financeira' => 'Financeira',
            'Serviços' => 'Serviços/Outros',
            default => 'Produtos'
        };

        return new Doacao(
            (int)$row['id'],
            $tipoDoador,
            $row['doador_nome'] ?? 'Anônimo',
            $row['doador_doc'],
            $row['doador_tel'],
            $row['doador_email'],
            $tipoDoacao,
            substr((string)$row['data_doacao'], 0, 10),
            (float)($row['valor_estimado'] ?? 0.0),
            $row['descricao'] ?? '',
            $row['documento_comprovante'],
            $row['responsavel_nome'] ?? 'Coordenação',
            []
        );
    }

    private function normalizeTipo(string $tipo): string
    {
        $t = mb_strtolower(trim($tipo), 'UTF-8');
        if (str_contains($t, 'finan')) return 'Financeira';
        if (str_contains($t, 'serviço') || str_contains($t, 'servico')) return 'Serviços';
        if (str_contains($t, 'higiene')) return 'Higiene';
        if (str_contains($t, 'vestuário') || str_contains($t, 'vestuario')) return 'Vestuário';
        if (str_contains($t, 'outro')) return 'Outros';
        return 'Alimentos';
    }

    private function resolveDoadorId(Doacao $doacao): ?int
    {
        if (mb_strtolower(trim($doacao->nomeDoador), 'UTF-8') === 'anônimo' || mb_strtolower(trim($doacao->tipoDoador), 'UTF-8') === 'anônimo') {
            return null;
        }

        $cleanDoc = !empty($doacao->documento) ? preg_replace('/\D/', '', $doacao->documento) : null;
        if (!empty($cleanDoc)) {
            $stmt = $this->db->prepare("SELECT id FROM gestao_social.doadores WHERE documento = :doc LIMIT 1");
            $stmt->execute(['doc' => $cleanDoc]);
            $id = $stmt->fetchColumn();
            if ($id) return (int)$id;
        }

        $tipoDoadorDb = str_contains(mb_strtolower($doacao->tipoDoador, 'UTF-8'), 'jurídica') ? 'Jurídica' : 'Física';
        $stmtInsert = $this->db->prepare("
            INSERT INTO gestao_social.doadores (tipo, nome, documento, telefone, email)
            VALUES (:tipo, :nome, :doc, :tel, :email)
            RETURNING id
        ");
        $stmtInsert->execute([
            'tipo' => $tipoDoadorDb,
            'nome' => $doacao->nomeDoador,
            'doc' => $cleanDoc,
            'tel' => $doacao->telefone,
            'email' => $doacao->email,
        ]);

        return (int)$stmtInsert->fetchColumn();
    }

    private function resolveResponsavelId(string $nomeOuIdentificador): int
    {
        $stmt = $this->db->prepare("SELECT id FROM gestao_social.usuarios WHERE LOWER(nome) LIKE :busca OR LOWER(login) = :login LIMIT 1");
        $stmt->execute([
            'busca' => '%' . mb_strtolower(trim($nomeOuIdentificador), 'UTF-8') . '%',
            'login' => mb_strtolower(trim($nomeOuIdentificador), 'UTF-8')
        ]);
        $id = $stmt->fetchColumn();

        if ($id) {
            return (int)$id;
        }

        $stmtDefault = $this->db->query("
            SELECT u.id 
              FROM gestao_social.usuarios u
              JOIN gestao_social.perfis p ON p.id = u.perfil_id
             WHERE p.nome IN ('Coordenação', 'Estoque', 'Administrador')
             ORDER BY u.id ASC
             LIMIT 1
        ");
        $defaultId = $stmtDefault->fetchColumn();

        return $defaultId ? (int)$defaultId : 1;
    }
}
