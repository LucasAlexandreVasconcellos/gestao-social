<?php

declare(strict_types=1);

namespace App\Repositories\Database;

use App\Contracts\Repositories\AvaliacaoRepositoryInterface;
use App\Core\Database;
use App\Models\AvaliacaoSocial;
use PDO;

final class AvaliacaoRepositoryDb implements AvaliacaoRepositoryInterface
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
        $sql = "SELECT a.*, u.nome AS responsavel_nome
                  FROM gestao_social.avaliacoes_sociais a
                  JOIN gestao_social.usuarios u ON u.id = a.responsavel_id
                 WHERE 1=1";
        $params = [];

        if (!empty($filters['familiaId'])) {
            $sql .= " AND a.familia_id = :familia_id";
            $params['familia_id'] = (int)$filters['familiaId'];
        }

        if (!empty($filters['nivelVulnerabilidade'])) {
            $sql .= " AND a.classificacao = :nivel";
            $params['nivel'] = $this->normalizeNivel($filters['nivelVulnerabilidade']);
        }

        $sql .= " ORDER BY a.data_avaliacao DESC, a.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn(array $row) => $this->mapRowToModel($row), $rows);
    }

    public function findById(int $id): ?AvaliacaoSocial
    {
        $stmt = $this->db->prepare("
            SELECT a.*, u.nome AS responsavel_nome
              FROM gestao_social.avaliacoes_sociais a
              JOIN gestao_social.usuarios u ON u.id = a.responsavel_id
             WHERE a.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->mapRowToModel($row) : null;
    }

    public function findByFamiliaId(int $familiaId): array
    {
        return $this->findAll(['familiaId' => $familiaId]);
    }

    public function getUltimaAvaliacao(int $familiaId): ?AvaliacaoSocial
    {
        $stmt = $this->db->prepare("
            SELECT a.*, u.nome AS responsavel_nome
              FROM gestao_social.avaliacoes_sociais a
              JOIN gestao_social.usuarios u ON u.id = a.responsavel_id
             WHERE a.familia_id = :familia_id
             ORDER BY a.data_avaliacao DESC, a.id DESC
             LIMIT 1
        ");
        $stmt->execute(['familia_id' => $familiaId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->mapRowToModel($row) : null;
    }

    public function save(AvaliacaoSocial $avaliacao): AvaliacaoSocial
    {
        // Resolve o responsavel_id a partir do nome ou primeiro assistente social / admin
        $responsavelId = $this->resolveResponsavelId($avaliacao->responsavelAvaliacao);

        $sql = "INSERT INTO gestao_social.avaliacoes_sociais (
                    familia_id, responsavel_id, data_avaliacao, renda_familiar_declarada,
                    renda_per_capita, quantidade_integrantes, condicao_moradia,
                    situacao_alimentar, situacao_emprego, necessita_acompanhamento,
                    classificacao, parecer_social, data_reavaliacao
                ) VALUES (
                    :familia_id, :responsavel_id, :data_avaliacao, :renda_declarada,
                    :renda_per_capita, :integrantes, :moradia,
                    :alimentar, :emprego, :acompanhamento,
                    :classificacao, :parecer, :reavaliacao
                ) RETURNING id, data_avaliacao, renda_per_capita";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'familia_id' => $avaliacao->familiaId,
            'responsavel_id' => $responsavelId,
            'data_avaliacao' => $avaliacao->dataAvaliacao ?: date('Y-m-d H:i:s'),
            'renda_declarada' => $avaliacao->rendaFamiliar,
            'renda_per_capita' => $avaliacao->rendaPerCapita,
            'integrantes' => max(1, $avaliacao->quantidadeIntegrantes),
            'moradia' => $avaliacao->condicoesMoradia,
            'alimentar' => $avaliacao->situacaoAlimentar,
            'emprego' => $avaliacao->situacaoEmprego,
            'acompanhamento' => $avaliacao->necessidadeAcompanhamento ? 'true' : 'false',
            'classificacao' => $this->normalizeNivel($avaliacao->nivelVulnerabilidade),
            'parecer' => $avaliacao->parecerSocial,
            'reavaliacao' => $avaliacao->dataPrevistaReavaliacao ?: null,
        ]);

        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        $avaliacao->id = (int)$res['id'];
        if (isset($res['renda_per_capita'])) {
            $avaliacao->rendaPerCapita = (float)$res['renda_per_capita'];
        }

        return $avaliacao;
    }

    public function getReavaliacoesPendentes(int $diasAntecedencia = 30): array
    {
        $sql = "SELECT a.*, u.nome AS responsavel_nome
                  FROM gestao_social.avaliacoes_sociais a
                  JOIN gestao_social.usuarios u ON u.id = a.responsavel_id
                 WHERE a.data_reavaliacao IS NOT NULL
                   AND a.data_reavaliacao <= CURRENT_DATE + CAST(:dias AS INTEGER)
                 ORDER BY a.data_reavaliacao ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['dias' => $diasAntecedencia]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn(array $row) => $this->mapRowToModel($row), $rows);
    }

    public function countByVulnerabilidade(string $nivel): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM gestao_social.avaliacoes_sociais WHERE classificacao = :nivel");
        $stmt->execute(['nivel' => $this->normalizeNivel($nivel)]);
        return (int)$stmt->fetchColumn();
    }

    private function mapRowToModel(array $row): AvaliacaoSocial
    {
        return new AvaliacaoSocial(
            (int)$row['id'],
            (int)$row['familia_id'],
            substr((string)$row['data_avaliacao'], 0, 10),
            $row['responsavel_nome'] ?? 'Assistente Social',
            (float)($row['renda_familiar_declarada'] ?? 0.0),
            (float)($row['renda_per_capita'] ?? 0.0),
            (int)($row['quantidade_integrantes'] ?? 1),
            $row['condicao_moradia'] ?? 'Regular',
            $row['situacao_alimentar'] ?? 'Segura',
            $row['situacao_emprego'] ?? 'Renda estável',
            false,
            false,
            false,
            ['alimentacao', 'habitacao'],
            (bool)($row['necessita_acompanhamento'] ?? true),
            $row['classificacao'] ?? 'Média',
            'Classificação fundamentada no parecer socioassistencial',
            $row['parecer_social'] ?? '',
            $row['data_reavaliacao']
        );
    }

    private function normalizeNivel(string $nivel): string
    {
        return match (mb_strtolower(trim($nivel), 'UTF-8')) {
            'crítica', 'critica' => 'Crítica',
            'alta' => 'Alta',
            'média', 'media' => 'Média',
            'baixa' => 'Baixa',
            default => 'Média'
        };
    }

    private function resolveResponsavelId(string $nomeOuIdentificador): int
    {
        // 1. Tenta buscar usuário por nome ou login
        $stmt = $this->db->prepare("SELECT id FROM gestao_social.usuarios WHERE LOWER(nome) LIKE :busca OR LOWER(login) = :login LIMIT 1");
        $stmt->execute([
            'busca' => '%' . mb_strtolower(trim($nomeOuIdentificador), 'UTF-8') . '%',
            'login' => mb_strtolower(trim($nomeOuIdentificador), 'UTF-8')
        ]);
        $id = $stmt->fetchColumn();

        if ($id) {
            return (int)$id;
        }

        // 2. Se não encontrar, busca o primeiro assistente social ou administrador
        $stmtDefault = $this->db->query("
            SELECT u.id 
              FROM gestao_social.usuarios u
              JOIN gestao_social.perfis p ON p.id = u.perfil_id
             WHERE p.nome IN ('Assistente Social', 'Administrador', 'Coordenação')
             ORDER BY u.id ASC
             LIMIT 1
        ");
        $defaultId = $stmtDefault->fetchColumn();

        return $defaultId ? (int)$defaultId : 1;
    }
}
