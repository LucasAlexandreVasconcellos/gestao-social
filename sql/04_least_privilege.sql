-- =============================================================================
-- PROVISIONAMENTO DE ROLE DE MENOR PRIVILÉGIO PARA A APLICAÇÃO (POSTGRESQL)
-- =============================================================================
-- Nota: Crie ou altere a senha da role 'gestao_app' antes de rodar em produção.
-- O script scripts/setup_database.php automatiza a criação da role com senha aleatória.

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'gestao_app') THEN
        CREATE ROLE gestao_app WITH LOGIN PASSWORD 'TROCAR_SENHA_FORTE_AQUI' NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT;
    END IF;
END $$;

GRANT USAGE ON SCHEMA gestao_social TO gestao_app;

-- Acesso operacional nas tabelas de negócio
GRANT SELECT, INSERT, UPDATE, DELETE ON
    gestao_social.perfis,
    gestao_social.usuarios,
    gestao_social.familias,
    gestao_social.pessoas,
    gestao_social.familia_integrantes,
    gestao_social.avaliacoes_sociais,
    gestao_social.tipos_carencia,
    gestao_social.avaliacao_carencias,
    gestao_social.atendimentos,
    gestao_social.encaminhamentos,
    gestao_social.categorias_produtos,
    gestao_social.produtos,
    gestao_social.lotes_produto,
    gestao_social.doadores,
    gestao_social.doacoes,
    gestao_social.doacao_itens,
    gestao_social.acoes_sociais,
    gestao_social.acao_beneficiarios,
    gestao_social.entregas,
    gestao_social.entrega_itens,
    gestao_social.documentos,
    gestao_social.notificacoes,
    gestao_social.lgpd_solicitacoes
TO gestao_app;

-- Livro-razão append-only de movimentações de estoque: sem UPDATE/DELETE/TRUNCATE
REVOKE UPDATE, DELETE, TRUNCATE ON gestao_social.movimentacoes_estoque FROM gestao_app;
GRANT SELECT, INSERT ON gestao_social.movimentacoes_estoque TO gestao_app;

-- Trilha de auditoria append-only: apenas SELECT e INSERT (logs de leitura/exportação/login)
-- UPDATE, DELETE e TRUNCATE são estritamente bloqueados por privilégio e triggers
REVOKE ALL ON gestao_social.auditoria FROM PUBLIC;
REVOKE UPDATE, DELETE, TRUNCATE ON gestao_social.auditoria FROM gestao_app;
GRANT SELECT, INSERT ON gestao_social.auditoria TO gestao_app;

-- Views analíticas
GRANT SELECT ON
    gestao_social.vw_saldo_estoque,
    gestao_social.vw_saldo_lotes_vencimento,
    gestao_social.vw_painel_familias_vulnerabilidade,
    gestao_social.vw_historico_entregas_detalhado,
    gestao_social.vw_atendimentos_pendentes
TO gestao_app;

-- Sequências (IDENTITY columns)
GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA gestao_social TO gestao_app;

