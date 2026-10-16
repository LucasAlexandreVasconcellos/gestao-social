-- =============================================================================
-- SISTEMA DE GESTÃO SOCIAL E ASSISTENCIAL
-- Script de Views Analíticas para o Painel Gerencial e Relatórios
-- Versão: 8.0 (Views Analíticas, Alertas, FEFO por Lote e Painéis Sociais)
-- SGBD: PostgreSQL 12+
-- Schema: gestao_social
-- =============================================================================

SET search_path TO gestao_social;

-- -----------------------------------------------------------------------------
-- 1. VIEW: SALDO ATUAL DO ESTOQUE E ALERTA DE REPOSIÇÃO (Otimizado sem repetição de SUM)
-- -----------------------------------------------------------------------------
CREATE OR REPLACE VIEW gestao_social.vw_saldo_estoque AS
WITH calculo_saldo AS (
    SELECT 
        p.id AS produto_id,
        p.nome AS produto_nome,
        c.nome AS categoria,
        p.unidade_medida,
        p.estoque_minimo,
        p.controla_validade,
        COALESCE(SUM(
            CASE 
                WHEN m.tipo IN ('Entrada', 'Ajuste Positivo') THEN m.quantidade
                WHEN m.tipo IN ('Saída', 'Ajuste Negativo', 'Perda') THEN -m.quantidade
                ELSE 0 
            END
        ), 0.00) AS saldo_atual
    FROM gestao_social.produtos p
    JOIN gestao_social.categorias_produtos c ON c.id = p.categoria_id
    LEFT JOIN gestao_social.movimentacoes_estoque m ON m.produto_id = p.id
    WHERE p.ativo = TRUE
    GROUP BY p.id, p.nome, c.nome, p.unidade_medida, p.estoque_minimo, p.controla_validade
)
SELECT 
    produto_id,
    produto_nome,
    categoria,
    unidade_medida,
    estoque_minimo,
    controla_validade,
    saldo_atual,
    CASE 
        WHEN saldo_atual < estoque_minimo THEN 'ABAIXO DO MÍNIMO'
        WHEN saldo_atual = estoque_minimo THEN 'NO LIMITE MÍNIMO'
        ELSE 'REGULAR'
    END AS status_estoque
FROM calculo_saldo;

-- -----------------------------------------------------------------------------
-- 2. VIEW: SALDO POR LOTE E CONTROLE DE VENCIMENTO (FEFO - Gestão de Perecíveis)
-- -----------------------------------------------------------------------------
CREATE OR REPLACE VIEW gestao_social.vw_saldo_lotes_vencimento AS
WITH calculo_lote AS (
    SELECT 
        p.id AS produto_id,
        p.nome AS produto_nome,
        c.nome AS categoria,
        p.unidade_medida,
        lp.numero_lote,
        lp.data_validade,
        COALESCE(SUM(
            CASE 
                WHEN m.tipo IN ('Entrada', 'Ajuste Positivo') THEN m.quantidade
                WHEN m.tipo IN ('Saída', 'Ajuste Negativo', 'Perda') THEN -m.quantidade
                ELSE 0 
            END
        ), 0.00) AS saldo_lote
    FROM gestao_social.lotes_produto lp
    JOIN gestao_social.produtos p ON p.id = lp.produto_id
    LEFT JOIN gestao_social.categorias_produtos c ON c.id = p.categoria_id
    LEFT JOIN gestao_social.movimentacoes_estoque m ON m.produto_id = lp.produto_id AND m.lote = lp.numero_lote
    WHERE p.ativo = TRUE
    GROUP BY p.id, p.nome, c.nome, p.unidade_medida, lp.numero_lote, lp.data_validade
)
SELECT 
    produto_id,
    produto_nome,
    categoria,
    unidade_medida,
    numero_lote,
    data_validade,
    saldo_lote,
    CASE 
        WHEN data_validade IS NULL THEN NULL
        ELSE data_validade - CURRENT_DATE
    END AS dias_para_vencer,
    CASE 
        WHEN data_validade IS NULL THEN 'SEM CONTROLE DE VALIDADE'
        WHEN data_validade < CURRENT_DATE THEN 'LOTE VENCIDO'
        WHEN (data_validade - CURRENT_DATE) <= 30 THEN 'CRÍTICO (< 30 DIAS)'
        WHEN (data_validade - CURRENT_DATE) <= 60 THEN 'ATENÇÃO (< 60 DIAS)'
        ELSE 'REGULAR'
    END AS status_validade
FROM calculo_lote
WHERE saldo_lote > 0
ORDER BY data_validade ASC NULLS LAST;

-- -----------------------------------------------------------------------------
-- 3. VIEW: PAINEL DE FAMÍLIAS, VULNERABILIDADE E ÚLTIMA AVALIAÇÃO (Com Desempate id DESC)
-- -----------------------------------------------------------------------------
CREATE OR REPLACE VIEW gestao_social.vw_painel_familias_vulnerabilidade AS
WITH ultima_avaliacao AS (
    SELECT DISTINCT ON (familia_id)
        id AS avaliacao_id,
        familia_id,
        data_avaliacao,
        classificacao,
        pontuacao,
        renda_per_capita,
        necessita_acompanhamento,
        data_reavaliacao
    FROM gestao_social.avaliacoes_sociais
    ORDER BY familia_id, data_avaliacao DESC, id DESC
),
qtd_membros AS (
    SELECT 
        familia_id, 
        COUNT(*) AS total_integrantes
    FROM gestao_social.familia_integrantes
    WHERE data_fim IS NULL
    GROUP BY familia_id
)
SELECT 
    f.id AS familia_id,
    f.codigo_familia,
    f.nome_referencia,
    f.bairro,
    f.municipio,
    f.status AS status_familia,
    f.renda_familiar,
    COALESCE(qm.total_integrantes, 0) AS total_integrantes_ativos,
    ua.classificacao AS classificacao_vulnerabilidade,
    ua.pontuacao,
    ua.renda_per_capita,
    ua.data_avaliacao AS data_ultima_avaliacao,
    ua.data_reavaliacao,
    CASE 
        WHEN ua.avaliacao_id IS NULL THEN 'SEM AVALIAÇÃO'
        WHEN ua.data_reavaliacao IS NOT NULL AND ua.data_reavaliacao < CURRENT_DATE THEN 'REAVALIAÇÃO VENCIDA'
        WHEN ua.classificacao = 'Crítica' THEN 'PRIORIDADE MÁXIMA'
        ELSE 'EM DIA'
    END AS status_alerta
FROM gestao_social.familias f
LEFT JOIN qtd_membros qm ON qm.familia_id = f.id
LEFT JOIN ultima_avaliacao ua ON ua.familia_id = f.id;

-- -----------------------------------------------------------------------------
-- 4. VIEW: HISTÓRICO CONSOLIDADO DE ENTREGAS (Com LEFT JOIN para cobrir todas)
-- -----------------------------------------------------------------------------
CREATE OR REPLACE VIEW gestao_social.vw_historico_entregas_detalhado AS
SELECT 
    e.id AS entrega_id,
    e.data_entrega,
    f.codigo_familia,
    f.nome_referencia AS familia_beneficiada,
    f.bairro,
    e.tipo_beneficio,
    e.status AS status_entrega,
    u.nome AS entregue_por,
    COALESCE(p.nome, 'Nenhum item vinculado') AS produto,
    ei.quantidade,
    p.unidade_medida,
    ei.lote,
    ei.validade,
    a.nome AS acao_social_vinculada
FROM gestao_social.entregas e
JOIN gestao_social.familias f ON f.id = e.familia_id
JOIN gestao_social.usuarios u ON u.id = e.responsavel_id
LEFT JOIN gestao_social.entrega_itens ei ON ei.entrega_id = e.id
LEFT JOIN gestao_social.produtos p ON p.id = ei.produto_id
LEFT JOIN gestao_social.acoes_sociais a ON a.id = e.acao_id;

-- -----------------------------------------------------------------------------
-- 5. VIEW: ATENDIMENTOS PENDENTES E PRAZOS
-- -----------------------------------------------------------------------------
CREATE OR REPLACE VIEW gestao_social.vw_atendimentos_pendentes AS
SELECT 
    a.id AS atendimento_id,
    a.data_atendimento,
    a.tipo_atendimento,
    f.codigo_familia,
    f.nome_referencia AS familia,
    pes.nome_completo AS pessoa_atendida,
    u.nome AS responsavel_tecnico,
    a.retorno_previsto,
    a.status,
    CASE 
        WHEN a.retorno_previsto IS NOT NULL AND a.retorno_previsto < CURRENT_DATE THEN 'ATRASADO'
        WHEN a.retorno_previsto = CURRENT_DATE THEN 'HOJE'
        ELSE 'NO PRAZO'
    END AS situacao_prazo
FROM gestao_social.atendimentos a
JOIN gestao_social.familias f ON f.id = a.familia_id
LEFT JOIN gestao_social.pessoas pes ON pes.id = a.pessoa_id
JOIN gestao_social.usuarios u ON u.id = a.responsavel_id
WHERE a.status IN ('Aberto', 'Em acompanhamento');
