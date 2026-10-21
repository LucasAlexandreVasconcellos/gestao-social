<?php

declare(strict_types=1);

namespace App\Repositories\Database;

use App\Contracts\Repositories\EstoqueRepositoryInterface;
use App\Core\Database;
use App\Models\MovimentacaoEstoque;
use App\Models\Produto;
use PDO;

final class EstoqueRepositoryDb implements EstoqueRepositoryInterface
{
    private ?PDO $dbInstance = null;

    public function __construct(?PDO $db = null)
    {
        $this->dbInstance = $db;
    }

    public function __get(string $name): mixed
    {
        if ($name === 'db') {
            return $this->dbInstance ??= Database::getConnection();
        }
        return null;
    }

    public function findAllProdutos(array $filters = []): array
    {
        $sql = "
            SELECT p.id, p.codigo, p.nome, c.nome AS categoria_nome, p.unidade_medida,
                   p.estoque_minimo, p.ativo,
                   COALESCE(se.saldo_atual, 0.00) AS saldo_atual,
                   (
                       SELECT MIN(lp.data_validade)
                         FROM gestao_social.lotes_produto lp
                        WHERE lp.produto_id = p.id AND lp.data_validade >= CURRENT_DATE
                   ) AS proximo_vencimento
              FROM gestao_social.produtos p
              JOIN gestao_social.categorias_produtos c ON c.id = p.categoria_id
              LEFT JOIN gestao_social.vw_saldo_estoque se ON se.produto_id = p.id
             WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (LOWER(p.nome) LIKE :search OR LOWER(p.codigo) LIKE :search)";
            $params['search'] = '%' . mb_strtolower(trim($filters['search']), 'UTF-8') . '%';
        }

        if (!empty($filters['categoria'])) {
            $sql .= " AND c.nome = :categoria";
            $params['categoria'] = $filters['categoria'];
        }

        if (isset($filters['ativo'])) {
            $sql .= " AND p.ativo = :ativo";
            $params['ativo'] = $filters['ativo'] ? 'true' : 'false';
        }

        $sql .= " ORDER BY p.nome ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn(array $row) => $this->mapProdutoRow($row), $rows);
    }

    public function findProdutoById(int $id): ?Produto
    {
        $sql = "
            SELECT p.id, p.codigo, p.nome, c.nome AS categoria_nome, p.unidade_medida,
                   p.estoque_minimo, p.ativo,
                   COALESCE(se.saldo_atual, 0.00) AS saldo_atual,
                   (
                       SELECT MIN(lp.data_validade)
                         FROM gestao_social.lotes_produto lp
                        WHERE lp.produto_id = p.id AND lp.data_validade >= CURRENT_DATE
                   ) AS proximo_vencimento
              FROM gestao_social.produtos p
              JOIN gestao_social.categorias_produtos c ON c.id = p.categoria_id
              LEFT JOIN gestao_social.vw_saldo_estoque se ON se.produto_id = p.id
             WHERE p.id = :id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->mapProdutoRow($row) : null;
    }

    public function saveProduto(Produto $produto): Produto
    {
        $categoriaId = $this->resolveCategoriaId($produto->categoria);

        if (empty($produto->codigo)) {
            $stmtSeq = $this->db->query("SELECT COALESCE(MAX(id), 0) + 1 FROM gestao_social.produtos");
            $next = (int)$stmtSeq->fetchColumn();
            $produto->codigo = sprintf('PROD-%03d', $next);
        }

        $sql = "INSERT INTO gestao_social.produtos (
                    categoria_id, codigo, nome, unidade_medida, estoque_minimo, controla_validade, ativo
                ) VALUES (
                    :cat_id, :codigo, :nome, :unidade, :minimo, :controla_validade, :ativo
                ) RETURNING id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'cat_id' => $categoriaId,
            'codigo' => $produto->codigo,
            'nome' => $produto->nome,
            'unidade' => $produto->unidadeMedida ?: 'Unidade',
            'minimo' => $produto->estoqueMinimo,
            'controla_validade' => !empty($produto->proximoVencimento) ? 'true' : 'false',
            'ativo' => $produto->ativo ? 'true' : 'false',
        ]);

        $produto->id = (int)$stmt->fetchColumn();
        return $produto;
    }

    public function updateProduto(Produto $produto): bool
    {
        $categoriaId = $this->resolveCategoriaId($produto->categoria);

        $sql = "UPDATE gestao_social.produtos SET
                    categoria_id = :cat_id,
                    codigo = :codigo,
                    nome = :nome,
                    unidade_medida = :unidade,
                    estoque_minimo = :minimo,
                    ativo = :ativo
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $produto->id,
            'cat_id' => $categoriaId,
            'codigo' => $produto->codigo,
            'nome' => $produto->nome,
            'unidade' => $produto->unidadeMedida,
            'minimo' => $produto->estoqueMinimo,
            'ativo' => $produto->ativo ? 'true' : 'false',
        ]);
    }

    public function findMovimentacoes(array $filters = []): array
    {
        $sql = "SELECT m.*, p.nome AS produto_nome, u.nome AS responsavel_nome
                  FROM gestao_social.movimentacoes_estoque m
                  JOIN gestao_social.produtos p ON p.id = m.produto_id
                  JOIN gestao_social.usuarios u ON u.id = m.responsavel_id
                 WHERE 1=1";
        $params = [];

        if (!empty($filters['produtoId'])) {
            $sql .= " AND m.produto_id = :produto_id";
            $params['produto_id'] = (int)$filters['produtoId'];
        }

        if (!empty($filters['tipo'])) {
            $sql .= " AND m.tipo = :tipo";
            $params['tipo'] = $filters['tipo'];
        }

        $sql .= " ORDER BY m.data_movimentacao DESC, m.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $movs = [];
        foreach ($rows as $row) {
            $movs[] = new MovimentacaoEstoque(
                (int)$row['id'],
                substr((string)$row['data_movimentacao'], 0, 10),
                (int)$row['produto_id'],
                $row['produto_nome'] ?? 'Produto',
                $row['tipo'],
                (float)$row['quantidade'],
                $row['lote'],
                $row['validade'],
                $row['origem_destino'],
                $row['documento_referencia'] ?? '',
                $row['responsavel_nome'] ?? 'Almoxarife'
            );
        }

        return $movs;
    }

    public function saveMovimentacao(MovimentacaoEstoque $mov): MovimentacaoEstoque
    {
        $responsavelId = $this->resolveResponsavelId($mov->responsavel);

        $sql = "INSERT INTO gestao_social.movimentacoes_estoque (
                    produto_id, responsavel_id, tipo, quantidade,
                    data_movimentacao, lote, validade, origem_destino, documento_referencia
                ) VALUES (
                    :produto_id, :responsavel_id, :tipo, :quantidade,
                    :data_mov, :lote, :validade, :origem_destino, :doc
                ) RETURNING id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'produto_id' => $mov->produtoId,
            'responsavel_id' => $responsavelId,
            'tipo' => $mov->tipo,
            'quantidade' => $mov->quantidade,
            'data_mov' => $mov->data ?: date('Y-m-d H:i:s'),
            'lote' => $mov->lote,
            'validade' => $mov->dataValidade ?: null,
            'origem_destino' => $mov->origemDestino,
            'doc' => $mov->justificativa,
        ]);

        $mov->id = (int)$stmt->fetchColumn();
        return $mov;
    }

    public function getProdutosEstoqueBaixo(): array
    {
        $produtos = $this->findAllProdutos();
        return array_values(array_filter($produtos, fn(Produto $p) => $p->isEstoqueBaixo()));
    }

    public function getProdutosProximoVencimento(int $dias = 30): array
    {
        $sql = "
            SELECT DISTINCT p.id, p.codigo, p.nome, c.nome AS categoria_nome, p.unidade_medida,
                   p.estoque_minimo, p.ativo,
                   COALESCE(se.saldo_atual, 0.00) AS saldo_atual,
                   lv.data_validade AS proximo_vencimento
              FROM gestao_social.vw_saldo_lotes_vencimento lv
              JOIN gestao_social.produtos p ON p.id = lv.produto_id
              JOIN gestao_social.categorias_produtos c ON c.id = p.categoria_id
              LEFT JOIN gestao_social.vw_saldo_estoque se ON se.produto_id = p.id
             WHERE lv.data_validade <= CURRENT_DATE + CAST(:dias AS INTEGER)
               AND lv.saldo_lote > 0
             ORDER BY lv.data_validade ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['dias' => $dias]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn(array $row) => $this->mapProdutoRow($row), $rows);
    }

    public function countProdutosCriticos(): int
    {
        return count($this->getProdutosEstoqueBaixo());
    }

    public function countProdutosVencendo(): int
    {
        return count($this->getProdutosProximoVencimento(30));
    }

    private function mapProdutoRow(array $row): Produto
    {
        return new Produto(
            (int)$row['id'],
            $row['codigo'],
            $row['nome'],
            $row['categoria_nome'] ?? 'Geral',
            $row['unidade_medida'] ?? 'Unidade',
            (float)($row['saldo_atual'] ?? 0.0),
            (float)($row['estoque_minimo'] ?? 0.0),
            $row['proximo_vencimento'] ?? null,
            (bool)($row['ativo'] ?? true)
        );
    }

    private function resolveCategoriaId(string $categoria): int
    {
        $stmt = $this->db->prepare("SELECT id FROM gestao_social.categorias_produtos WHERE LOWER(nome) = LOWER(:cat) LIMIT 1");
        $stmt->execute(['cat' => trim($categoria)]);
        $id = $stmt->fetchColumn();

        if ($id) {
            return (int)$id;
        }

        // Se não existir, cria a categoria
        $stmtInsert = $this->db->prepare("INSERT INTO gestao_social.categorias_produtos (nome) VALUES (:nome) ON CONFLICT (nome) DO NOTHING RETURNING id");
        $stmtInsert->execute(['nome' => trim($categoria)]);
        $newId = $stmtInsert->fetchColumn();

        if ($newId) {
            return (int)$newId;
        }

        // Fallback para primeira categoria
        $stmtFirst = $this->db->query("SELECT id FROM gestao_social.categorias_produtos ORDER BY id ASC LIMIT 1");
        return (int)($stmtFirst->fetchColumn() ?: 1);
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
             WHERE p.nome IN ('Estoque', 'Administrador')
             ORDER BY u.id ASC
             LIMIT 1
        ");
        $defaultId = $stmtDefault->fetchColumn();

        return $defaultId ? (int)$defaultId : 1;
    }
}
