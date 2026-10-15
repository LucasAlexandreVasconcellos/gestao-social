-- =============================================================================
-- SISTEMA DE GESTÃO SOCIAL E ASSISTENCIAL
-- Script de Carga Inicial e Demonstração (Seeds 100% Idempotentes e Seguros)
-- Versão: 8.0 (Hashes Reais com pgcrypto, CPFs Matematicamente Válidos e FEFO)
-- SGBD: PostgreSQL 12+
-- Schema: gestao_social
--
-- NOTA DE HOMOLOGAÇÃO:
-- - Documentos e registros são fictícios para testes de desenvolvimento e homologação.
-- - Os hashes de senha são reais gerados via pgcrypto (bcrypt / bf).
--   Credenciais de acesso para testes:
--   * admin / Admin@123
--   * maria.social / Social@123
--   * carlos.estoque / Estoque@123
-- - Script totalmente idempotente: pode ser executado múltiplas vezes com segurança.
-- =============================================================================

SET search_path TO gestao_social;

-- -----------------------------------------------------------------------------
-- 1. PERFIS DE ACESSO (Idempotente via ON CONFLICT)
-- -----------------------------------------------------------------------------
INSERT INTO gestao_social.perfis (nome, descricao) VALUES
('Administrador', 'Acesso irrestrito a configurações, auditoria e cadastros do sistema'),
('Coordenação', 'Acesso a relatórios estratégicos, ações sociais e gestão de equipe'),
('Assistente Social', 'Acesso completo ao cadastro social, avaliações, atendimentos e pareceres técnicos'),
('Estoque', 'Acesso à gestão de entradas, saídas, produtos e registro de doações físicas'),
('Atendimento / Recepção', 'Acesso à recepção de famílias e triagem inicial de atendimentos')
ON CONFLICT (nome) DO NOTHING;

-- -----------------------------------------------------------------------------
-- 2. USUÁRIOS DE DEMONSTRAÇÃO (Idempotente via ON CONFLICT no login)
-- -----------------------------------------------------------------------------
INSERT INTO gestao_social.usuarios (nome, email, login, senha_hash, perfil_id) VALUES
('Administrador Geral', 'admin@entidade.org.br', 'admin', 
 gestao_social.crypt('Admin@123', gestao_social.gen_salt('bf', 10)), 
 (SELECT id FROM gestao_social.perfis WHERE nome = 'Administrador')),

('Maria Clara Santos', 'maria.clara@entidade.org.br', 'maria.social', 
 gestao_social.crypt('Social@123', gestao_social.gen_salt('bf', 10)), 
 (SELECT id FROM gestao_social.perfis WHERE nome = 'Assistente Social')),

('Carlos Eduardo Lima', 'carlos.estoque@entidade.org.br', 'carlos.estoque', 
 gestao_social.crypt('Estoque@123', gestao_social.gen_salt('bf', 10)), 
 (SELECT id FROM gestao_social.perfis WHERE nome = 'Estoque'))
ON CONFLICT (login) DO NOTHING;

-- -----------------------------------------------------------------------------
-- 3. TIPOS DE CARÊNCIA SOCIAL (Idempotente via ON CONFLICT no nome)
-- -----------------------------------------------------------------------------
INSERT INTO gestao_social.tipos_carencia (nome, descricao) VALUES
('Insegurança Alimentar', 'Falta de acesso regular e permanente a alimentos de qualidade e em quantidade suficiente'),
('Moradia Precária / Vulnerável', 'Habitação em área de risco, sem saneamento básico, cedida irregularmente ou com superlotação'),
('Desemprego / Ausência de Renda', 'Responsável ou membros em idade ativa sem ocupação formal ou renda fixa comprovada'),
('Saúde e Doença Crônica', 'Presença de membro com comorbidade grave ou dependência de medicamentos contínuos'),
('Pessoa com Deficiência / Mobilidade Reduzida', 'Necessidade de cuidados contínuos, adaptação ou barreiras de acessibilidade'),
('Vulnerabilidade na Primeira Infância', 'Presença de crianças de 0 a 6 anos sem suporte escolar ou alimentar adequado'),
('Idoso em Situação de Dependência', 'Idosos sozinhos ou que demandam auxílio de terceiros para atividades básicas')
ON CONFLICT (nome) DO NOTHING;

-- -----------------------------------------------------------------------------
-- 4. CATEGORIAS E PRODUTOS (Idempotente via ON CONFLICT)
-- -----------------------------------------------------------------------------
INSERT INTO gestao_social.categorias_produtos (nome, descricao) VALUES
('Alimentos e Cestas', 'Itens da cesta básica e gêneros alimentícios não perecíveis'),
('Higiene Pessoal', 'Sabonetes, pastas de dente, escovas, absorventes e fraldas'),
('Limpeza Doméstica', 'Detergentes, desinfetantes, sabão em pó e água sanitária'),
('Vestuário e Calçados', 'Roupas infantis, adultas, agasalhos, cobertores e calçados'),
('Material Escolar', 'Cadernos, lápis, mochilas e materiais didáticos')
ON CONFLICT (nome) DO NOTHING;

INSERT INTO gestao_social.produtos (categoria_id, nome, unidade_medida, descricao, controla_validade, estoque_minimo) VALUES
((SELECT id FROM gestao_social.categorias_produtos WHERE nome = 'Alimentos e Cestas'), 
 'Cesta Básica Completa Tipo A', 'UN', 'Cesta com 14 itens alimentícios não perecíveis', TRUE, 20.00),

((SELECT id FROM gestao_social.categorias_produtos WHERE nome = 'Alimentos e Cestas'), 
 'Arroz Tipo 1 - 5kg', 'PCT', 'Pacote de 5kg de arroz branco polido', TRUE, 50.00),

((SELECT id FROM gestao_social.categorias_produtos WHERE nome = 'Alimentos e Cestas'), 
 'Feijão Carioca - 1kg', 'PCT', 'Pacote de 1kg feijão novo', TRUE, 50.00),

((SELECT id FROM gestao_social.categorias_produtos WHERE nome = 'Alimentos e Cestas'), 
 'Leite em Pó Integral - 400g', 'LATA', 'Lata de leite em pó fortificado para crianças', TRUE, 30.00),

((SELECT id FROM gestao_social.categorias_produtos WHERE nome = 'Higiene Pessoal'), 
 'Kit Higiene Familiar', 'KIT', 'Contém 4 sabonetes, 2 cremes dentais, 2 escovas e 1 pacote absorvente', FALSE, 15.00),

((SELECT id FROM gestao_social.categorias_produtos WHERE nome = 'Vestuário e Calçados'), 
 'Cobertor Térmico / Manta', 'UN', 'Cobertor tamanho casal/solteiro para campanha do agasalho', FALSE, 25.00)
ON CONFLICT (nome) DO NOTHING;

-- -----------------------------------------------------------------------------
-- 5. DOADORES E DOAÇÃO CONTROLADA (Idempotente, sem pontuação em documentos)
-- -----------------------------------------------------------------------------
INSERT INTO gestao_social.doadores (tipo_doador, nome, documento, telefone, email, cidade, uf) VALUES
('PJ', 'Supermercado Central Ltda', '12345678000199', '11987654321', 'contato@supercentral.com.br', 'São Paulo', 'SP'),
('PF', 'Ana Paula Ferreira', '12345678900', '11912345678', 'ana.paula@email.com', 'São Paulo', 'SP')
ON CONFLICT (documento) DO NOTHING;

-- Bloco transacional anônimo que usa RETURNING id e verifica existência para não duplicar estoque
DO $$
DECLARE
    v_doacao_id BIGINT;
    v_doador_id BIGINT;
    v_resp_estoque_id BIGINT;
    v_prod_cesta_id BIGINT;
    v_prod_arroz_id BIGINT;
    v_prod_feijao_id BIGINT;
    v_prod_manta_id BIGINT;
BEGIN
    SELECT id INTO v_doador_id FROM gestao_social.doadores WHERE documento = '12345678000199';
    SELECT id INTO v_resp_estoque_id FROM gestao_social.usuarios WHERE login = 'carlos.estoque';
    SELECT id INTO v_prod_cesta_id FROM gestao_social.produtos WHERE nome = 'Cesta Básica Completa Tipo A';
    SELECT id INTO v_prod_arroz_id FROM gestao_social.produtos WHERE nome = 'Arroz Tipo 1 - 5kg';
    SELECT id INTO v_prod_feijao_id FROM gestao_social.produtos WHERE nome = 'Feijão Carioca - 1kg';
    SELECT id INTO v_prod_manta_id FROM gestao_social.produtos WHERE nome = 'Cobertor Térmico / Manta';

    -- Só insere a doação se o documento de controle não existir
    IF NOT EXISTS (SELECT 1 FROM gestao_social.doacoes WHERE documento_comprovante = 'RECIBO-DOA-2026-001') THEN
        -- Passo A: Cadastra como 'Pendente'
        INSERT INTO gestao_social.doacoes (
            doador_id, responsavel_id, tipo_doacao, status, valor_estimado, 
            descricao, documento_comprovante
        ) VALUES (
            v_doador_id, v_resp_estoque_id, 'Alimentos', 'Pendente', 4500.00, 
            'Doação de mantimentos da campanha', 'RECIBO-DOA-2026-001'
        ) RETURNING id INTO v_doacao_id;

        -- Passo B: Itens associados diretamente ao ID da doação em aberto (reutiliza validade se lote já existir)
        INSERT INTO gestao_social.doacao_itens (doacao_id, produto_id, quantidade, valor_unitario_estimado, lote, validade) VALUES
        (v_doacao_id, v_prod_cesta_id, 50.00, 70.00, 'LOTE-2026A', 
            COALESCE((SELECT data_validade FROM gestao_social.lotes_produto WHERE produto_id = v_prod_cesta_id AND numero_lote = 'LOTE-2026A'), CURRENT_DATE + INTERVAL '180 days')),
        (v_doacao_id, v_prod_arroz_id, 80.00, 25.00, 'LOTE-ARROZ-01', 
            COALESCE((SELECT data_validade FROM gestao_social.lotes_produto WHERE produto_id = v_prod_arroz_id AND numero_lote = 'LOTE-ARROZ-01'), CURRENT_DATE + INTERVAL '240 days')),
        (v_doacao_id, v_prod_feijao_id, 80.00, 8.50, 'LOTE-FEIJAO-01', 
            COALESCE((SELECT data_validade FROM gestao_social.lotes_produto WHERE produto_id = v_prod_feijao_id AND numero_lote = 'LOTE-FEIJAO-01'), CURRENT_DATE + INTERVAL '200 days')),
        (v_doacao_id, v_prod_manta_id, 30.00, 35.00, 'LOTE-MANTA-01', NULL);

        -- Passo C: Confirma a doação (dispara o trigger tg_ciclo_doacao_status que alimenta o estoque!)
        UPDATE gestao_social.doacoes 
           SET status = 'Confirmada' 
         WHERE id = v_doacao_id;
    END IF;
END $$;

-- -----------------------------------------------------------------------------
-- 6. CADASTRO DE FAMÍLIA E INTEGRANTES (CPFs com 11 dígitos estritos)
-- -----------------------------------------------------------------------------
INSERT INTO gestao_social.familias (codigo_familia, nome_referencia, cep, logradouro, numero, bairro, municipio, uf, renda_familiar, despesas_mensais, situacao_moradia, status) VALUES
('FAM-2026-001', 'Maria das Dores da Silva', '08400000', 'Rua das Flores', '142', 'Jardim Primavera', 'São Paulo', 'SP', 600.00, 850.00, 'Cedida', 'Ativa'),
('FAM-2026-002', 'José Antônio de Souza', '08400150', 'Travessa Esperança', '18', 'Vila Nova', 'São Paulo', 'SP', NULL, 400.00, 'Ocupação', 'Em análise')
ON CONFLICT (codigo_familia) DO NOTHING;

INSERT INTO gestao_social.pessoas (nome_completo, data_nascimento, cpf, sexo, telefone, escolaridade, ocupacao, situacao_ocupacional, status) VALUES
('Maria das Dores da Silva', '1985-05-12', '22233344405', 'Feminino', '11988887777', 'Ensino Fundamental Incompleto', 'Diarista autônoma', 'Informal', 'Ativo'),
('Lucas da Silva', '2015-08-20', '11122233396', 'Masculino', NULL, 'Ensino Fundamental', 'Estudante', 'Estudante', 'Ativo'),
('Helena da Silva', '2019-02-10', '55566677720', 'Feminino', NULL, 'Educação Infantil', 'Não se aplica', 'Estudante', 'Ativo'),
('José Antônio de Souza', '1962-11-03', '33344455508', 'Masculino', '11977776666', 'Ensino Fundamental Incompleto', 'Sem ocupação', 'Desempregado', 'Ativo')
ON CONFLICT (cpf) DO NOTHING;

-- Vínculos Familiares Ativos (Idempotente via verificação de existência de vínculo ATIVO)
DO $$
DECLARE
    v_fam1_id BIGINT;
    v_fam2_id BIGINT;
    v_p1_id BIGINT;
    v_p2_id BIGINT;
    v_p3_id BIGINT;
    v_p4_id BIGINT;
BEGIN
    SELECT id INTO v_fam1_id FROM gestao_social.familias WHERE codigo_familia = 'FAM-2026-001';
    SELECT id INTO v_fam2_id FROM gestao_social.familias WHERE codigo_familia = 'FAM-2026-002';
    SELECT id INTO v_p1_id FROM gestao_social.pessoas WHERE cpf = '22233344405';
    SELECT id INTO v_p2_id FROM gestao_social.pessoas WHERE cpf = '11122233396';
    SELECT id INTO v_p3_id FROM gestao_social.pessoas WHERE cpf = '55566677720';
    SELECT id INTO v_p4_id FROM gestao_social.pessoas WHERE cpf = '33344455508';

    IF NOT EXISTS (SELECT 1 FROM gestao_social.familia_integrantes WHERE familia_id = v_fam1_id AND pessoa_id = v_p1_id AND data_fim IS NULL) THEN
        INSERT INTO gestao_social.familia_integrantes (familia_id, pessoa_id, parentesco, responsavel_familiar, data_inicio) 
        VALUES (v_fam1_id, v_p1_id, 'Responsável Familiar', TRUE, '2026-01-10');
    END IF;

    IF NOT EXISTS (SELECT 1 FROM gestao_social.familia_integrantes WHERE familia_id = v_fam1_id AND pessoa_id = v_p2_id AND data_fim IS NULL) THEN
        INSERT INTO gestao_social.familia_integrantes (familia_id, pessoa_id, parentesco, responsavel_familiar, data_inicio) 
        VALUES (v_fam1_id, v_p2_id, 'Filho(a)', FALSE, '2026-01-10');
    END IF;

    IF NOT EXISTS (SELECT 1 FROM gestao_social.familia_integrantes WHERE familia_id = v_fam1_id AND pessoa_id = v_p3_id AND data_fim IS NULL) THEN
        INSERT INTO gestao_social.familia_integrantes (familia_id, pessoa_id, parentesco, responsavel_familiar, data_inicio) 
        VALUES (v_fam1_id, v_p3_id, 'Filho(a)', FALSE, '2026-01-10');
    END IF;

    IF NOT EXISTS (SELECT 1 FROM gestao_social.familia_integrantes WHERE familia_id = v_fam2_id AND pessoa_id = v_p4_id AND data_fim IS NULL) THEN
        INSERT INTO gestao_social.familia_integrantes (familia_id, pessoa_id, parentesco, responsavel_familiar, data_inicio) 
        VALUES (v_fam2_id, v_p4_id, 'Responsável Familiar', TRUE, '2026-02-01');
    END IF;
END $$;

-- -----------------------------------------------------------------------------
-- 7. AVALIAÇÃO DE VULNERABILIDADE (Idempotente)
-- -----------------------------------------------------------------------------
DO $$
DECLARE
    v_fam1_id BIGINT;
    v_resp_social_id BIGINT;
    v_aval_id BIGINT;
    v_car_alim_id BIGINT;
    v_car_desemp_id BIGINT;
BEGIN
    SELECT id INTO v_fam1_id FROM gestao_social.familias WHERE codigo_familia = 'FAM-2026-001';
    SELECT id INTO v_resp_social_id FROM gestao_social.usuarios WHERE login = 'maria.social';
    SELECT id INTO v_car_alim_id FROM gestao_social.tipos_carencia WHERE nome = 'Insegurança Alimentar';
    SELECT id INTO v_car_desemp_id FROM gestao_social.tipos_carencia WHERE nome = 'Desemprego / Ausência de Renda';

    IF NOT EXISTS (SELECT 1 FROM gestao_social.avaliacoes_sociais WHERE familia_id = v_fam1_id) THEN
        INSERT INTO gestao_social.avaliacoes_sociais (
            familia_id, responsavel_id, renda_familiar_declarada, renda_per_capita, quantidade_integrantes, 
            condicao_moradia, situacao_alimentar, situacao_emprego, pontuacao, 
            classificacao, parecer_social, data_reavaliacao
        ) VALUES (
            v_fam1_id, v_resp_social_id, 600.00, 200.00, 3, 
            'Imóvel de alvenaria em encosta cedido por familiares', 
            'Refeições insuficientes, sem acesso a proteínas', 
            'Trabalho informal sem garantia de renda', 
            85, 'Crítica', 
            'Família monoparental com crianças. Prioridade para alimentação.',
            CURRENT_DATE + INTERVAL '90 days'
        ) RETURNING id INTO v_aval_id;

        INSERT INTO gestao_social.avaliacao_carencias (avaliacao_id, tipo_carencia_id, nivel, descricao, necessita_intervencao, status_atendimento) VALUES
        (v_aval_id, v_car_alim_id, 'Crítico', 'Falta de alimentos básicos em domicílio', TRUE, 'Em atendimento'),
        (v_aval_id, v_car_desemp_id, 'Alto', 'Mãe com renda oscilante e informal', TRUE, 'Pendente');
    END IF;
END $$;

-- -----------------------------------------------------------------------------
-- 8. ATENDIMENTO E ENCAMINHAMENTO SOCIAL (Idempotente)
-- -----------------------------------------------------------------------------
DO $$
DECLARE
    v_fam1_id BIGINT;
    v_p1_id BIGINT;
    v_resp_social_id BIGINT;
    v_atend_id BIGINT;
BEGIN
    SELECT id INTO v_fam1_id FROM gestao_social.familias WHERE codigo_familia = 'FAM-2026-001';
    SELECT id INTO v_p1_id FROM gestao_social.pessoas WHERE cpf = '22233344405';
    SELECT id INTO v_resp_social_id FROM gestao_social.usuarios WHERE login = 'maria.social';

    IF NOT EXISTS (SELECT 1 FROM gestao_social.atendimentos WHERE familia_id = v_fam1_id AND tipo_atendimento = 'Acolhida/Entrevista') THEN
        INSERT INTO gestao_social.atendimentos (
            familia_id, pessoa_id, responsavel_id, tipo_atendimento, 
            descricao, providencias, retorno_previsto, status
        ) VALUES (
            v_fam1_id, v_p1_id, v_resp_social_id, 'Acolhida/Entrevista', 
            'Atendimento de acolhida para demandas emergenciais.', 
            'Encaminhada ao CRAS para benefício eventual e inclusão no plano de cestas.', 
            CURRENT_DATE + INTERVAL '30 days', 'Em acompanhamento'
        ) RETURNING id INTO v_atend_id;

        INSERT INTO gestao_social.encaminhamentos (atendimento_id, responsavel_id, servico_destino, motivo, data_encaminhamento, status) VALUES
        (v_atend_id, v_resp_social_id, 'CRAS - Centro de Referência de Assistência Social', 'Solicitação de inserção no Cadastro Único', CURRENT_DATE, 'Encaminhado');
    END IF;
END $$;

-- -----------------------------------------------------------------------------
-- 9. CICLO DE VIDA DA ENTREGA (Idempotente, com RETURNING id e Mudança de Status)
-- -----------------------------------------------------------------------------
DO $$
DECLARE
    v_fam1_id BIGINT;
    v_resp_social_id BIGINT;
    v_prod_cesta_id BIGINT;
    v_prod_manta_id BIGINT;
    v_entrega_id BIGINT;
BEGIN
    SELECT id INTO v_fam1_id FROM gestao_social.familias WHERE codigo_familia = 'FAM-2026-001';
    SELECT id INTO v_resp_social_id FROM gestao_social.usuarios WHERE login = 'maria.social';
    SELECT id INTO v_prod_cesta_id FROM gestao_social.produtos WHERE nome = 'Cesta Básica Completa Tipo A';
    SELECT id INTO v_prod_manta_id FROM gestao_social.produtos WHERE nome = 'Cobertor Térmico / Manta';

    IF NOT EXISTS (SELECT 1 FROM gestao_social.entregas WHERE comprovante = 'RECIBO-ENT-2026-001') THEN
        -- Passo A: Cadastra como 'Preparada'
        INSERT INTO gestao_social.entregas (
            familia_id, responsavel_id, tipo_beneficio, status, comprovante, observacoes
        ) VALUES (
            v_fam1_id, v_resp_social_id, 'Cesta Básica', 'Preparada', 
            'RECIBO-ENT-2026-001', 'Entrega mensal emergencial'
        ) RETURNING id INTO v_entrega_id;

        -- Passo B: Consulta a validade efetivamente registrada no lote para reaproveitá-la
        INSERT INTO gestao_social.entrega_itens (entrega_id, produto_id, quantidade, lote, validade) VALUES
        (
            v_entrega_id, 
            v_prod_cesta_id, 
            1.00, 
            'LOTE-2026A', 
            (SELECT data_validade FROM gestao_social.lotes_produto WHERE produto_id = v_prod_cesta_id AND numero_lote = 'LOTE-2026A')
        ),
        (
            v_entrega_id, 
            v_prod_manta_id, 
            2.00, 
            'LOTE-MANTA-01', 
            (SELECT data_validade FROM gestao_social.lotes_produto WHERE produto_id = v_prod_manta_id AND numero_lote = 'LOTE-MANTA-01')
        );

        -- Passo C: Atualiza status para 'Entregue' (o trigger dispara e efetiva a saída do estoque por lote!)
        UPDATE gestao_social.entregas 
           SET status = 'Entregue' 
         WHERE id = v_entrega_id;
    END IF;
END $$;
