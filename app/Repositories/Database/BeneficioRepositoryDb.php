<?php

declare(strict_types=1);

namespace App\Repositories\Database;

use App\Contracts\Repositories\BeneficioRepositoryInterface;
use App\Core\Database;
use App\Models\BeneficioEntrega;
use App\Models\ItemEntrega;
use PDO;

final class BeneficioRepositoryDb implements BeneficioRepositoryInterface
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

    public function findAll(array $filters = []): array
    {
        $sql = "SELECT e.*, u.nome AS responsavel_nome
                  FROM gestao_social.entregas e
                  JOIN gestao_social.usuarios u ON u.id = e.responsavel_id
                 WHERE 1=1";
        $params = [];

        if (!empty($filters['familiaId'])) {
            $sql .= " AND e.familia_id = :familia_id";
            $params['familia_id'] = (int)$filters['familiaId'];
        }

        if (!empty($filters['situacao'])) {
            $sql .= " AND e.status = :status";
            $params['status'] = $this->normalizeStatus($filters['situacao']);
        }

        if (!empty($filters['tipo'])) {
            $sql .= " AND e.tipo_beneficio = :tipo";
            $params['tipo'] = $this->normalizeTipo($filters['tipo']);
        }

        $sql .= " ORDER BY e.data_entrega DESC, e.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $beneficios = [];
        foreach ($rows as $row) {
            $entrega = $this->mapRowToModel($row);
            $entrega->itens = $this->getItens((int)$row['id']);
            $beneficios[] = $entrega;
        }

        return $beneficios;
    }

    public function findById(int $id): ?BeneficioEntrega
    {
        $stmt = $this->db->prepare("
            SELECT e.*, u.nome AS responsavel_nome
              FROM gestao_social.entregas e
              JOIN gestao_social.usuarios u ON u.id = e.responsavel_id
             WHERE e.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $entrega = $this->mapRowToModel($row);
        $entrega->itens = $this->getItens($id);
        return $entrega;
    }

    public function findByFamiliaId(int $familiaId): array
    {
        return $this->findAll(['familiaId' => $familiaId]);
    }

    public function save(BeneficioEntrega $entrega): BeneficioEntrega
    {
        $responsavelId = $this->resolveResponsavelId($entrega->responsavelDistribuicao);

        // No PostgreSQL, a trigger exige status inicial 'Preparada'
        $initialStatus = 'Preparada';
        $finalStatus = $this->normalizeStatus($entrega->situacao);

        $sql = "INSERT INTO gestao_social.entregas (
                    familia_id, responsavel_id, acao_id, data_entrega,
                    tipo_beneficio, status, comprovante, observacoes
                ) VALUES (
                    :familia_id, :responsavel_id, :acao_id, :data_entrega,
                    :tipo, :status, :comprovante, :obs
                ) RETURNING id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'familia_id' => $entrega->familiaId,
            'responsavel_id' => $responsavelId,
            'acao_id' => $entrega->campanhaId,
            'data_entrega' => $entrega->dataEntrega ?: date('Y-m-d H:i:s'),
            'tipo' => $this->normalizeTipo($entrega->tipoBeneficio),
            'status' => $initialStatus,
            'comprovante' => $entrega->comprovante,
            'obs' => $entrega->observacoes,
        ]);

        $entrega->id = (int)$stmt->fetchColumn();

        // Se houver itens já definidos no objeto, insere-os
        if (!empty($entrega->itens)) {
            foreach ($entrega->itens as $item) {
                if ($item instanceof ItemEntrega) {
                    $item->entregaId = $entrega->id;
                    $this->addItem($item);
                }
            }
        }

        // Se a situação desejada era 'Entregue' (Efetivada), atualiza para disparar o gatilho de baixa no estoque
        if ($finalStatus === 'Entregue' && !empty($entrega->itens)) {
            $this->updateStatus($entrega->id, 'Entregue');
            $entrega->situacao = 'Efetivada';
        }

        return $entrega;
    }

    public function update(BeneficioEntrega $entrega): bool
    {
        $sql = "UPDATE gestao_social.entregas SET
                    tipo_beneficio = :tipo,
                    status = :status,
                    comprovante = :comprovante,
                    observacoes = :obs
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $entrega->id,
            'tipo' => $this->normalizeTipo($entrega->tipoBeneficio),
            'status' => $this->normalizeStatus($entrega->situacao),
            'comprovante' => $entrega->comprovante,
            'obs' => $entrega->observacoes,
        ]);
    }

    public function addItem(ItemEntrega $item): ItemEntrega
    {
        // Se lote não foi informado, busca o lote FEFO ativo do produto
        $lote = $item->lote;
        $validade = null;

        if (!empty($lote)) {
            $stmtLote = $this->db->prepare("SELECT data_validade FROM gestao_social.lotes_produto WHERE produto_id = :p_id AND numero_lote = :lote LIMIT 1");
            $stmtLote->execute(['p_id' => $item->produtoId, 'lote' => $lote]);
            $validade = $stmtLote->fetchColumn() ?: null;
        } else {
            // Busca o primeiro lote disponível pelo FEFO
            $stmtFefo = $this->db->prepare("SELECT numero_lote, data_validade FROM gestao_social.lotes_produto WHERE produto_id = :p_id ORDER BY data_validade ASC NULLS LAST LIMIT 1");
            $stmtFefo->execute(['p_id' => $item->produtoId]);
            $loteRow = $stmtFefo->fetch(PDO::FETCH_ASSOC);
            if ($loteRow) {
                $lote = $loteRow['numero_lote'];
                $validade = $loteRow['data_validade'];
                $item->lote = $lote;
            }
        }

        $sql = "INSERT INTO gestao_social.entrega_itens (
                    entrega_id, produto_id, quantidade, lote, validade
                ) VALUES (
                    :entrega_id, :produto_id, :quantidade, :lote, :validade
                ) RETURNING id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'entrega_id' => $item->entregaId,
            'produto_id' => $item->produtoId,
            'quantidade' => $item->quantidade,
            'lote' => $lote,
            'validade' => $validade,
        ]);

        $item->id = (int)$stmt->fetchColumn();
        return $item;
    }

    public function countEfetivadas(string $mesAno = ''): int
    {
        $sql = "SELECT COUNT(*) FROM gestao_social.entregas WHERE status = 'Entregue'";
        $params = [];

        if (!empty($mesAno)) {
            $sql .= " AND TO_CHAR(data_entrega, 'YYYY-MM') = :mes_ano";
            $params['mes_ano'] = $mesAno;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function countTotal(array $filters = []): int
    {
        return count($this->findAll($filters));
    }

    private function getItens(int $entregaId): array
    {
        $sql = "SELECT ei.*, p.nome AS produto_nome, p.unidade_medida
                  FROM gestao_social.entrega_itens ei
                  JOIN gestao_social.produtos p ON p.id = ei.produto_id
                 WHERE ei.entrega_id = :entrega_id
                 ORDER BY ei.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['entrega_id' => $entregaId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $itens = [];
        foreach ($rows as $row) {
            $itens[] = new ItemEntrega(
                (int)$row['id'],
                (int)$row['entrega_id'],
                (int)$row['produto_id'],
                $row['produto_nome'] ?? 'Produto Assistencial',
                (float)$row['quantidade'],
                $row['unidade_medida'] ?? 'Unidade',
                $row['lote']
            );
        }

        return $itens;
    }

    private function updateStatus(int $id, string $status): void
    {
        $stmt = $this->db->prepare("UPDATE gestao_social.entregas SET status = :status WHERE id = :id");
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    private function mapRowToModel(array $row): BeneficioEntrega
    {
        return new BeneficioEntrega(
            (int)$row['id'],
            (int)$row['familia_id'],
            substr((string)$row['data_entrega'], 0, 10),
            $row['tipo_beneficio'],
            $row['responsavel_nome'] ?? 'Equipe Assistencial',
            $row['acao_id'] !== null ? (int)$row['acao_id'] : null,
            [],
            $row['comprovante'],
            $row['observacoes'],
            $this->denormalizeStatus($row['status'])
        );
    }

    private function normalizeStatus(string $situacao): string
    {
        return match (mb_strtolower(trim($situacao), 'UTF-8')) {
            'efetivada', 'entregue' => 'Entregue',
            'cancelada', 'cancelado' => 'Cancelada',
            default => 'Preparada'
        };
    }

    private function denormalizeStatus(string $status): string
    {
        return match ($status) {
            'Entregue' => 'Efetivada',
            'Preparada' => 'Preparada',
            'Cancelada' => 'Cancelada',
            default => $status
        };
    }

    private function normalizeTipo(string $tipo): string
    {
        $t = mb_strtolower(trim($tipo), 'UTF-8');
        if (str_contains($t, 'cesta') || str_contains($t, 'aliment')) return 'Cesta Básica';
        if (str_contains($t, 'higiene')) return 'Kit Higiene';
        if (str_contains($t, 'vestuário') || str_contains($t, 'vestuario') || str_contains($t, 'roupa')) return 'Vestuário';
        if (str_contains($t, 'cobertor') || str_contains($t, 'agasalho')) return 'Cobertor/Agasalho';
        if (str_contains($t, 'medicamento')) return 'Medicamento';
        if (str_contains($t, 'emergencial')) return 'Emergencial';
        return 'Outro';
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
             WHERE p.nome IN ('Estoque', 'Assistente Social', 'Administrador')
             ORDER BY u.id ASC
             LIMIT 1
        ");
        $defaultId = $stmtDefault->fetchColumn();

        return $defaultId ? (int)$defaultId : 1;
    }
}
