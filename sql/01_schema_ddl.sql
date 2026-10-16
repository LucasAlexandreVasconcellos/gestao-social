-- =============================================================================
-- SISTEMA DE GESTÃO SOCIAL E ASSISTENCIAL
-- Script DDL Master: Tabelas, Restrições, Triggers e Integridade Avançada
-- Versão: 14.0 (Versão de Produção Refinada: pgcrypto nativo, Regex sem escape,
--          Validação de Dígitos de CPF, Fechamento de Lote Fantasma, Minimização LGPD)
-- SGBD: PostgreSQL 12+
-- Schema: gestao_social
-- =============================================================================

CREATE SCHEMA IF NOT EXISTS gestao_social;
SET search_path TO gestao_social;

-- Extensão criptográfica para hashes seguros bcrypt instalada no schema gestao_social
CREATE EXTENSION IF NOT EXISTS pgcrypto WITH SCHEMA gestao_social;

-- -----------------------------------------------------------------------------
-- 1. SEGURANÇA, USUÁRIOS E AUDITORIA UNIVERSAL SANITIZADA (LGPD)
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS gestao_social.perfis (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nome VARCHAR(50) NOT NULL UNIQUE,
    descricao TEXT,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    data_criacao TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS gestao_social.usuarios (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    login VARCHAR(50) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    perfil_id BIGINT NOT NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    ultimo_acesso TIMESTAMP WITH TIME ZONE,
    data_criacao TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuarios_perfil FOREIGN KEY (perfil_id) 
        REFERENCES gestao_social.perfis(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_usuarios_email CHECK (email ~* '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+[.][A-Za-z]{2,}$')
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_usuarios_email_lower ON gestao_social.usuarios (LOWER(email));

-- Tabela de Auditoria Imutável (Append-Only)
CREATE TABLE IF NOT EXISTS gestao_social.auditoria (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    usuario_id BIGINT,
    usuario_identificador VARCHAR(150) NOT NULL DEFAULT CURRENT_USER,
    data_hora TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    operacao VARCHAR(20) NOT NULL, -- INSERT, UPDATE, DELETE
    tabela_afetada VARCHAR(100) NOT NULL,
    registro_id BIGINT,
    dados_anteriores JSONB,
    dados_novos JSONB,
    ip_origem VARCHAR(45),
    CONSTRAINT fk_auditoria_usuario FOREIGN KEY (usuario_id) 
        REFERENCES gestao_social.usuarios(id) ON UPDATE CASCADE ON DELETE RESTRICT
);

-- Restrição de Acesso aos Registros de Auditoria (LGPD): leitura e escrita direta restritas ao DBA
-- A gravação ocorre exclusivamente via triggers executados por funções SECURITY DEFINER
REVOKE ALL ON gestao_social.auditoria FROM PUBLIC;

-- Bloqueio rigoroso de UPDATE, DELETE e TRUNCATE na auditoria
CREATE OR REPLACE FUNCTION gestao_social.trg_bloqueia_alteracao_auditoria()
RETURNS TRIGGER AS $$
BEGIN
    RAISE EXCEPTION 'Violação de Segurança: Registros de auditoria são estritamente imutáveis. Operações de UPDATE, DELETE ou TRUNCATE são bloqueadas.';
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS tg_auditoria_bloqueio ON gestao_social.auditoria;
CREATE TRIGGER tg_auditoria_bloqueio
    BEFORE UPDATE OR DELETE ON gestao_social.auditoria
    FOR EACH ROW
    EXECUTE FUNCTION gestao_social.trg_bloqueia_alteracao_auditoria();

DROP TRIGGER IF EXISTS tg_auditoria_bloqueio_truncate ON gestao_social.auditoria;
CREATE TRIGGER tg_auditoria_bloqueio_truncate
    BEFORE TRUNCATE ON gestao_social.auditoria
    FOR EACH STATEMENT
    EXECUTE FUNCTION gestao_social.trg_bloqueia_alteracao_auditoria();

-- Função de Auditoria com Sanitização e Minimização de Dados Sensíveis (LGPD)
-- SECURITY DEFINER garante que usuários com permissões restritas consigam disparar o trigger
CREATE OR REPLACE FUNCTION gestao_social.trg_auditar_operacao()
RETURNS TRIGGER 
SECURITY DEFINER
SET search_path = gestao_social, pg_temp
AS $$
DECLARE
    v_operacao VARCHAR(20) := TG_OP;
    v_tabela VARCHAR(100) := TG_TABLE_SCHEMA || '.' || TG_TABLE_NAME;
    v_registro_id BIGINT;
    v_antigo JSONB := NULL;
    v_novo JSONB := NULL;
    v_usuario_app VARCHAR(150);
    v_usuario_id BIGINT := NULL;
    v_ip_origem VARCHAR(45);
BEGIN
    v_usuario_app := NULLIF(current_setting('app.usuario_logado', true), '');
    v_ip_origem := inet_client_addr()::varchar;

    -- Suporte à autenticação na camada de aplicação via variáveis de sessão:
    IF NULLIF(current_setting('app.usuario_logado_id', true), '') IS NOT NULL THEN
        BEGIN
            v_usuario_id := current_setting('app.usuario_logado_id', true)::bigint;
        EXCEPTION WHEN OTHERS THEN
            v_usuario_id := NULL;
        END;
    END IF;

    IF v_usuario_id IS NULL AND v_usuario_app IS NOT NULL THEN
        SELECT id INTO v_usuario_id 
          FROM gestao_social.usuarios 
         WHERE login = v_usuario_app LIMIT 1;
    END IF;

    IF TG_OP = 'DELETE' THEN
        v_registro_id := OLD.id;
        v_antigo := to_jsonb(OLD);
    ELSIF TG_OP = 'UPDATE' THEN
        v_registro_id := NEW.id;
        v_antigo := to_jsonb(OLD);
        v_novo := to_jsonb(NEW);
    ELSIF TG_OP = 'INSERT' THEN
        v_registro_id := NEW.id;
        v_novo := to_jsonb(NEW);
    END IF;

    -- Sanitização e Minimização de dados sensíveis (LGPD)
    IF TG_TABLE_NAME = 'usuarios' THEN
        -- Expurgar credenciais e hashes de senha
        IF v_antigo IS NOT NULL THEN v_antigo := v_antigo - 'senha_hash'; END IF;
        IF v_novo IS NOT NULL THEN v_novo := v_novo - 'senha_hash'; END IF;

    ELSIF TG_TABLE_NAME = 'pessoas' THEN
        -- Expurgar CPF, telefone, dados de saúde sensíveis e observações
        IF v_antigo IS NOT NULL THEN 
            v_antigo := v_antigo - 'cpf' - 'telefone' - 'tipo_deficiencia' - 'observacoes'; 
        END IF;
        IF v_novo IS NOT NULL THEN 
            v_novo := v_novo - 'cpf' - 'telefone' - 'tipo_deficiencia' - 'observacoes'; 
        END IF;

    ELSIF TG_TABLE_NAME = 'doadores' THEN
        -- Expurgar documentos pessoais (CPF/CNPJ), telefone, e-mail e anotações
        IF v_antigo IS NOT NULL THEN 
            v_antigo := v_antigo - 'documento' - 'telefone' - 'email' - 'observacoes'; 
        END IF;
        IF v_novo IS NOT NULL THEN 
            v_novo := v_novo - 'documento' - 'telefone' - 'email' - 'observacoes'; 
        END IF;

    ELSIF TG_TABLE_NAME = 'avaliacoes_sociais' THEN
        -- Expurgar parecer descritivo sociofamiliar confidencial
        IF v_antigo IS NOT NULL THEN v_antigo := v_antigo - 'parecer_social'; END IF;
        IF v_novo IS NOT NULL THEN v_novo := v_novo - 'parecer_social'; END IF;

    ELSIF TG_TABLE_NAME = 'avaliacao_carencias' THEN
        -- Expurgar justificativas descritivas de carências
        IF v_antigo IS NOT NULL THEN v_antigo := v_antigo - 'descricao'; END IF;
        IF v_novo IS NOT NULL THEN v_novo := v_novo - 'descricao'; END IF;

    ELSIF TG_TABLE_NAME = 'atendimentos' THEN
        -- Expurgar histórico descritivo e providências do atendimento
        IF v_antigo IS NOT NULL THEN v_antigo := v_antigo - 'descricao' - 'providencias'; END IF;
        IF v_novo IS NOT NULL THEN v_novo := v_novo - 'descricao' - 'providencias'; END IF;

    ELSIF TG_TABLE_NAME = 'encaminhamentos' THEN
        -- Expurgar motivo, resultado e observações sigilosas de encaminhamento (LGPD)
        IF v_antigo IS NOT NULL THEN v_antigo := v_antigo - 'motivo' - 'resultado' - 'observacoes'; END IF;
        IF v_novo IS NOT NULL THEN v_novo := v_novo - 'motivo' - 'resultado' - 'observacoes'; END IF;

    ELSIF TG_TABLE_NAME = 'familia_integrantes' THEN
        -- Expurgar observações particulares de integrantes da família (LGPD)
        IF v_antigo IS NOT NULL THEN v_antigo := v_antigo - 'observacoes'; END IF;
        IF v_novo IS NOT NULL THEN v_novo := v_novo - 'observacoes'; END IF;

    ELSIF TG_TABLE_NAME = 'entregas' THEN
        -- Expurgar anotações e observações de entregas de auxílio (LGPD)
        IF v_antigo IS NOT NULL THEN v_antigo := v_antigo - 'observacoes'; END IF;
        IF v_novo IS NOT NULL THEN v_novo := v_novo - 'observacoes'; END IF;

    ELSIF TG_TABLE_NAME = 'acao_beneficiarios' THEN
        -- Expurgar anotações e observações de participantes de ações sociais (LGPD)
        IF v_antigo IS NOT NULL THEN v_antigo := v_antigo - 'observacoes'; END IF;
        IF v_novo IS NOT NULL THEN v_novo := v_novo - 'observacoes'; END IF;

    ELSIF TG_TABLE_NAME = 'acoes_sociais' THEN
        -- Expurgar descritivo livre de projetos e ações
        IF v_antigo IS NOT NULL THEN v_antigo := v_antigo - 'descricao'; END IF;
        IF v_novo IS NOT NULL THEN v_novo := v_novo - 'descricao'; END IF;

    ELSIF TG_TABLE_NAME = 'doacoes' THEN
        -- Expurgar descritivo livre de doações
        IF v_antigo IS NOT NULL THEN v_antigo := v_antigo - 'descricao'; END IF;
        IF v_novo IS NOT NULL THEN v_novo := v_novo - 'descricao'; END IF;
    END IF;

    INSERT INTO gestao_social.auditoria (
        usuario_id,
        usuario_identificador,
        data_hora,
        operacao,
        tabela_afetada,
        registro_id,
        dados_anteriores,
        dados_novos,
        ip_origem
    ) VALUES (
        v_usuario_id,
        COALESCE(v_usuario_app, CURRENT_USER),
        CURRENT_TIMESTAMP,
        v_operacao,
        v_tabela,
        v_registro_id,
        v_antigo,
        v_novo,
        v_ip_origem
    );

    IF TG_OP = 'DELETE' THEN
        RETURN OLD;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- -----------------------------------------------------------------------------
-- 2. CADASTRO DE FAMÍLIAS E INTEGRANTES (PESSOAS)
-- -----------------------------------------------------------------------------

-- Função Validadora dos Dígitos Verificadores do CPF (Algoritmo Módulo 11)
CREATE OR REPLACE FUNCTION gestao_social.fn_valida_cpf(p_cpf VARCHAR)
RETURNS BOOLEAN AS $$
DECLARE
    v_cpf VARCHAR;
    v_soma INTEGER := 0;
    v_resto INTEGER;
    i INTEGER;
BEGIN
    IF p_cpf IS NULL THEN
        RETURN TRUE; -- Nulo permitido quando o campo for opcional
    END IF;

    -- Limpa pontuação se houver
    v_cpf := regexp_replace(p_cpf, '[^0-9]', '', 'g');

    -- Deve ter exatamente 11 dígitos
    IF length(v_cpf) != 11 THEN
        RETURN FALSE;
    END IF;

    -- Rejeita sequências de dígitos idênticos repetidos (ex: 11111111111)
    IF v_cpf IN ('00000000000', '11111111111', '22222222222', '33333333333', 
                 '44444444444', '55555555555', '66666666666', '77777777777', 
                 '88888888888', '99999999999') THEN
        RETURN FALSE;
    END IF;

    -- Cálculo do primeiro dígito verificador
    FOR i IN 1..9 LOOP
        v_soma := v_soma + substr(v_cpf, i, 1)::integer * (11 - i);
    END LOOP;
    v_resto := (v_soma * 10) % 11;
    IF v_resto = 10 THEN v_resto := 0; END IF;
    IF v_resto != substr(v_cpf, 10, 1)::integer THEN
        RETURN FALSE;
    END IF;

    -- Cálculo do segundo dígito verificador
    v_soma := 0;
    FOR i IN 1..10 LOOP
        v_soma := v_soma + substr(v_cpf, i, 1)::integer * (12 - i);
    END LOOP;
    v_resto := (v_soma * 10) % 11;
    IF v_resto = 10 THEN v_resto := 0; END IF;
    IF v_resto != substr(v_cpf, 11, 1)::integer THEN
        RETURN FALSE;
    END IF;

    RETURN TRUE;
END;
$$ LANGUAGE plpgsql IMMUTABLE;

CREATE TABLE IF NOT EXISTS gestao_social.familias (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    codigo_familia VARCHAR(20) NOT NULL UNIQUE,
    nome_referencia VARCHAR(150) NOT NULL,
    cep VARCHAR(8), -- 8 dígitos numéricos estritos
    logradouro VARCHAR(200) NOT NULL,
    numero VARCHAR(20) NOT NULL,
    complemento VARCHAR(100),
    bairro VARCHAR(100) NOT NULL,
    municipio VARCHAR(100) NOT NULL,
    uf CHAR(2) NOT NULL,
    renda_familiar NUMERIC(12,2), -- NULL = não informada/pendente; 0.00 = renda zero confirmada
    despesas_mensais NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    situacao_moradia VARCHAR(30) NOT NULL DEFAULT 'Própria',
    status VARCHAR(20) NOT NULL DEFAULT 'Em análise',
    data_cadastro TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_familias_valores CHECK ((renda_familiar IS NULL OR renda_familiar >= 0) AND despesas_mensais >= 0),
    CONSTRAINT chk_familias_cep CHECK (cep IS NULL OR cep ~ '^[0-9]{8}$'),
    CONSTRAINT chk_familias_status CHECK (status IN ('Em análise', 'Ativa', 'Suspensa', 'Encerrada')),
    CONSTRAINT chk_familias_moradia CHECK (situacao_moradia IN ('Própria', 'Alugada', 'Cedida', 'Ocupação', 'Precária', 'Situação de rua', 'Outra'))
);

CREATE TABLE IF NOT EXISTS gestao_social.pessoas (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nome_completo VARCHAR(150) NOT NULL,
    nome_social VARCHAR(150),
    data_nascimento DATE,
    cpf VARCHAR(11) UNIQUE, -- 11 dígitos numéricos com validação matemática de dígitos verificadores
    sexo VARCHAR(30) NOT NULL DEFAULT 'Não informado',
    telefone VARCHAR(20),
    escolaridade VARCHAR(50),
    ocupacao VARCHAR(100),
    situacao_ocupacional VARCHAR(30) NOT NULL DEFAULT 'Desempregado',
    possui_deficiencia BOOLEAN NOT NULL DEFAULT FALSE,
    tipo_deficiencia VARCHAR(100),
    observacoes TEXT,
    status VARCHAR(20) NOT NULL DEFAULT 'Ativo',
    data_cadastro TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_pessoas_cpf CHECK (cpf IS NULL OR gestao_social.fn_valida_cpf(cpf)),
    CONSTRAINT chk_pessoas_telefone CHECK (telefone IS NULL OR telefone ~ '^[0-9]{10,11}$'),
    CONSTRAINT chk_pessoas_status CHECK (status IN ('Ativo', 'Inativo', 'Falecido')),
    CONSTRAINT chk_pessoas_sexo CHECK (sexo IN ('Feminino', 'Masculino', 'Outro', 'Não informado')),
    CONSTRAINT chk_pessoas_ocupacao CHECK (situacao_ocupacional IN ('Empregado', 'Desempregado', 'Aposentado', 'Autônomo', 'Informal', 'Do lar', 'Estudante', 'Outra'))
);

CREATE OR REPLACE FUNCTION gestao_social.trg_atualiza_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    NEW.data_atualizacao = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS tg_familias_timestamp ON gestao_social.familias;
CREATE TRIGGER tg_familias_timestamp
    BEFORE UPDATE ON gestao_social.familias
    FOR EACH ROW
    EXECUTE FUNCTION gestao_social.trg_atualiza_timestamp();

DROP TRIGGER IF EXISTS tg_pessoas_timestamp ON gestao_social.pessoas;
CREATE TRIGGER tg_pessoas_timestamp
    BEFORE UPDATE ON gestao_social.pessoas
    FOR EACH ROW
    EXECUTE FUNCTION gestao_social.trg_atualiza_timestamp();

-- Histórico de Vínculos Familiares
CREATE TABLE IF NOT EXISTS gestao_social.familia_integrantes (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    familia_id BIGINT NOT NULL,
    pessoa_id BIGINT NOT NULL,
    parentesco VARCHAR(50) NOT NULL,
    responsavel_familiar BOOLEAN NOT NULL DEFAULT FALSE,
    data_inicio DATE NOT NULL DEFAULT CURRENT_DATE,
    data_fim DATE,
    observacoes TEXT,
    CONSTRAINT fk_integrante_familia FOREIGN KEY (familia_id) 
        REFERENCES gestao_social.familias(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_integrante_pessoa FOREIGN KEY (pessoa_id) 
        REFERENCES gestao_social.pessoas(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_periodo_integrante CHECK (data_fim IS NULL OR data_fim >= data_inicio),
    CONSTRAINT chk_integrante_parentesco CHECK (parentesco IN ('Responsável Familiar', 'Cônjuge/Companheiro(a)', 'Filho(a)', 'Enteado(a)', 'Neto(a)', 'Pai/Mãe', 'Avô/Avó', 'Irmão/Irmã', 'Outro'))
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_integrante_vinculo_ativo
ON gestao_social.familia_integrantes (pessoa_id)
WHERE data_fim IS NULL;

CREATE UNIQUE INDEX IF NOT EXISTS uq_familia_responsavel_ativo
ON gestao_social.familia_integrantes (familia_id)
WHERE responsavel_familiar IS TRUE AND data_fim IS NULL;

CREATE OR REPLACE FUNCTION gestao_social.trg_valida_sobreposicao_vinculo()
RETURNS TRIGGER AS $$
DECLARE
    v_conflito BOOLEAN;
BEGIN
    PERFORM id FROM gestao_social.pessoas WHERE id = NEW.pessoa_id FOR UPDATE;

    SELECT EXISTS (
        SELECT 1 
          FROM gestao_social.familia_integrantes fi
         WHERE fi.pessoa_id = NEW.pessoa_id
           AND fi.id <> COALESCE(NEW.id, -1)
           AND daterange(fi.data_inicio, COALESCE(fi.data_fim, '9999-12-31'::date), '[]') &&
               daterange(NEW.data_inicio, COALESCE(NEW.data_fim, '9999-12-31'::date), '[]')
    ) INTO v_conflito;

    IF v_conflito THEN
        RAISE EXCEPTION 'Conflito de vigência: A pessoa (ID %) já possui vínculo que se sobrepõe ao período de % até %.', 
            NEW.pessoa_id, NEW.data_inicio, COALESCE(NEW.data_fim::text, 'em aberto');
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS tg_valida_sobreposicao ON gestao_social.familia_integrantes;
CREATE TRIGGER tg_valida_sobreposicao
    BEFORE INSERT OR UPDATE ON gestao_social.familia_integrantes
    FOR EACH ROW
    EXECUTE FUNCTION gestao_social.trg_valida_sobreposicao_vinculo();

-- -----------------------------------------------------------------------------
-- 3. AVALIAÇÃO SOCIOASSISTENCIAL COM HISTÓRICO FIEL DE RENDA
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS gestao_social.avaliacoes_sociais (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    familia_id BIGINT NOT NULL,
    responsavel_id BIGINT NOT NULL,
    data_avaliacao TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    renda_familiar_declarada NUMERIC(12,2), -- Renda histórica na data da avaliação (NULL = não informada; 0.00 = renda zero confirmada)
    renda_per_capita NUMERIC(12,2), -- Snapshot histórico per capita recalculado
    quantidade_integrantes INT NOT NULL DEFAULT 1,
    condicao_moradia VARCHAR(100),
    situacao_alimentar VARCHAR(100),
    situacao_emprego VARCHAR(100),
    necessita_acompanhamento BOOLEAN NOT NULL DEFAULT TRUE,
    pontuacao INT, -- Pontuação calculada ou ponderada (NULL se omitida; não assume 0 artificial)
    classificacao VARCHAR(20) NOT NULL DEFAULT 'Média',
    parecer_social TEXT,
    data_reavaliacao DATE,
    data_criacao TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_avaliacao_familia FOREIGN KEY (familia_id) 
        REFERENCES gestao_social.familias(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_avaliacao_responsavel FOREIGN KEY (responsavel_id) 
        REFERENCES gestao_social.usuarios(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_avaliacao_classificacao CHECK (classificacao IN ('Crítica', 'Alta', 'Média', 'Baixa')),
    CONSTRAINT chk_avaliacao_reavaliacao CHECK (data_reavaliacao IS NULL OR data_reavaliacao >= data_avaliacao::date),
    CONSTRAINT chk_avaliacao_valores CHECK ((renda_familiar_declarada IS NULL OR renda_familiar_declarada >= 0) AND (renda_per_capita IS NULL OR renda_per_capita >= 0) AND quantidade_integrantes > 0 AND (pontuacao IS NULL OR pontuacao >= 0))
);

CREATE TABLE IF NOT EXISTS gestao_social.tipos_carencia (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descricao TEXT,
    ativo BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS gestao_social.avaliacao_carencias (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    avaliacao_id BIGINT NOT NULL,
    tipo_carencia_id BIGINT NOT NULL,
    nivel VARCHAR(20) NOT NULL DEFAULT 'Médio',
    descricao TEXT,
    necessita_intervencao BOOLEAN NOT NULL DEFAULT TRUE,
    status_atendimento VARCHAR(30) NOT NULL DEFAULT 'Pendente',
    CONSTRAINT fk_carencia_avaliacao FOREIGN KEY (avaliacao_id) 
        REFERENCES gestao_social.avaliacoes_sociais(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_carencia_tipo FOREIGN KEY (tipo_carencia_id) 
        REFERENCES gestao_social.tipos_carencia(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_carencia_nivel CHECK (nivel IN ('Crítico', 'Alto', 'Médio', 'Baixo')),
    CONSTRAINT chk_carencia_status CHECK (status_atendimento IN ('Pendente', 'Em atendimento', 'Atendido', 'Não aplicável'))
);

-- Validação de Autorização, Membros e Renda Histórica (Distingue NULL de Renda 0.00)
CREATE OR REPLACE FUNCTION gestao_social.trg_valida_autorizacao_avaliacao()
RETURNS TRIGGER AS $$
DECLARE
    v_perfil VARCHAR(50);
    v_usuario_ativo BOOLEAN;
    v_perfil_ativo BOOLEAN;
    v_data_ref DATE;
    v_membros_historicos INT;
    v_renda_atual_familia NUMERIC(12,2);
BEGIN
    SELECT p.nome, u.ativo, p.ativo 
      INTO v_perfil, v_usuario_ativo, v_perfil_ativo
      FROM gestao_social.usuarios u
      JOIN gestao_social.perfis p ON p.id = u.perfil_id
     WHERE u.id = NEW.responsavel_id;

    -- Em UPDATE mantendo o mesmo responsável histórico, não bloqueia por inativação superveniente
    IF TG_OP = 'UPDATE' AND OLD.responsavel_id = NEW.responsavel_id THEN
        NULL;
    ELSE
        IF v_usuario_ativo IS NOT TRUE THEN
            RAISE EXCEPTION 'Acesso negado: O usuário responsável (ID %) encontra-se inativo no sistema.', NEW.responsavel_id;
        END IF;

        IF v_perfil_ativo IS NOT TRUE THEN
            RAISE EXCEPTION 'Acesso negado: O perfil "%" encontra-se inativo no sistema.', v_perfil;
        END IF;

        IF v_perfil NOT IN ('Assistente Social', 'Coordenação', 'Administrador') THEN
            RAISE EXCEPTION 'Permissão insuficiente: Usuários com perfil "%" não podem emitir pareceres de avaliação social.', v_perfil;
        END IF;
    END IF;

    v_data_ref := COALESCE(NEW.data_avaliacao::date, CURRENT_DATE);

    -- Composição familiar válida na data da avaliação
    SELECT COUNT(*) INTO v_membros_historicos
      FROM gestao_social.familia_integrantes fi
     WHERE fi.familia_id = NEW.familia_id
       AND fi.data_inicio <= v_data_ref
       AND (fi.data_fim IS NULL OR fi.data_fim >= v_data_ref);

    -- Regra explícita: bloqueia avaliações sem integrantes válidos na data da avaliação
    IF v_membros_historicos = 0 THEN
        RAISE EXCEPTION 'Operação negada: A família (ID %) não possui nenhum integrante com vínculo ativo/válido na data da avaliação (%). Registre a composição familiar antes de emitir a avaliação.', 
            NEW.familia_id, v_data_ref;
    ELSE
        NEW.quantidade_integrantes := v_membros_historicos;
    END IF;

    -- Harmonização de Pontuação e Classificação:
    -- Só recalibra a classificação se uma pontuação válida foi informada (se NULL, mantém classificação declarada ou padrão)
    IF NEW.pontuacao IS NOT NULL THEN
        IF NEW.pontuacao >= 80 THEN
            NEW.classificacao := 'Crítica';
        ELSIF NEW.pontuacao >= 60 THEN
            NEW.classificacao := 'Alta';
        ELSIF NEW.pontuacao >= 40 THEN
            NEW.classificacao := 'Média';
        ELSE
            NEW.classificacao := 'Baixa';
        END IF;
    END IF;

    -- Tratamento de Renda: Distingue explicitamente NULL (não informada) de 0.00 (renda zero comprovada)
    IF NEW.renda_familiar_declarada IS NULL THEN
        IF v_data_ref >= CURRENT_DATE THEN
            -- Avaliação atual: herda a renda cadastrada atual da família (se existir)
            SELECT renda_familiar INTO v_renda_atual_familia 
              FROM gestao_social.familias 
             WHERE id = NEW.familia_id;
            NEW.renda_familiar_declarada := v_renda_atual_familia;
        ELSE
            -- Avaliação retroativa: se a renda per capita histórica foi informada, deduz a renda total
            IF NEW.renda_per_capita IS NOT NULL THEN
                NEW.renda_familiar_declarada := ROUND(NEW.renda_per_capita * NEW.quantidade_integrantes, 2);
            ELSE
                RAISE EXCEPTION 'Para avaliações retroativas (feitas no passado: %), informe explicitamente a renda_familiar_declarada (informe 0.00 se a família não possuía renda) ou a renda_per_capita da época.', v_data_ref;
            END IF;
        END IF;
    END IF;

    -- Cálculo e consistência matemática estrita da renda per capita:
    -- Garante recálculo em INSERT e em UPDATE sempre que a renda declarada ou número de membros mudar
    IF NEW.renda_familiar_declarada IS NOT NULL THEN
        NEW.renda_per_capita := ROUND(NEW.renda_familiar_declarada / GREATEST(NEW.quantidade_integrantes, 1), 2);
    END IF;

    -- Atualiza a renda atual da família no cadastro se a avaliação for atual
    -- e a renda declarada foi especificada (inclusive se for 0.00 comprovado)
    IF v_data_ref >= CURRENT_DATE AND NEW.renda_familiar_declarada IS NOT NULL THEN
        UPDATE gestao_social.familias 
           SET renda_familiar = NEW.renda_familiar_declarada 
         WHERE id = NEW.familia_id;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS tg_avaliacoes_autorizacao ON gestao_social.avaliacoes_sociais;
CREATE TRIGGER tg_avaliacoes_autorizacao
    BEFORE INSERT OR UPDATE ON gestao_social.avaliacoes_sociais
    FOR EACH ROW
    EXECUTE FUNCTION gestao_social.trg_valida_autorizacao_avaliacao();

-- -----------------------------------------------------------------------------
-- 4. ATENDIMENTOS E ENCAMINHAMENTOS
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS gestao_social.atendimentos (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    familia_id BIGINT NOT NULL,
    pessoa_id BIGINT,
    responsavel_id BIGINT NOT NULL,
    data_atendimento TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    tipo_atendimento VARCHAR(50) NOT NULL,
    descricao TEXT NOT NULL,
    providencias TEXT,
    retorno_previsto DATE,
    status VARCHAR(30) NOT NULL DEFAULT 'Aberto',
    data_conclusao TIMESTAMP WITH TIME ZONE,
    CONSTRAINT fk_atendimento_familia FOREIGN KEY (familia_id) 
        REFERENCES gestao_social.familias(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_atendimento_pessoa FOREIGN KEY (pessoa_id) 
        REFERENCES gestao_social.pessoas(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_atendimento_responsavel FOREIGN KEY (responsavel_id) 
        REFERENCES gestao_social.usuarios(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_atendimento_status CHECK (status IN ('Aberto', 'Em acompanhamento', 'Concluído', 'Cancelado')),
    CONSTRAINT chk_atendimento_tipo CHECK (tipo_atendimento IN ('Acolhida/Entrevista', 'Visita Domiciliar', 'Atendimento Emergencial', 'Orientação', 'Reunião de Acompanhamento'))
);

CREATE TABLE IF NOT EXISTS gestao_social.encaminhamentos (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    atendimento_id BIGINT NOT NULL,
    responsavel_id BIGINT NOT NULL,
    servico_destino VARCHAR(150) NOT NULL,
    motivo TEXT NOT NULL,
    data_encaminhamento DATE NOT NULL DEFAULT CURRENT_DATE,
    status VARCHAR(30) NOT NULL DEFAULT 'Encaminhado',
    data_retorno DATE,
    resultado TEXT,
    CONSTRAINT fk_encaminhamento_atendimento FOREIGN KEY (atendimento_id) 
        REFERENCES gestao_social.atendimentos(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_encaminhamento_responsavel FOREIGN KEY (responsavel_id) 
        REFERENCES gestao_social.usuarios(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_encaminhamento_status CHECK (status IN ('Encaminhado', 'Em andamento', 'Retorno recebido', 'Concluído')),
    CONSTRAINT chk_encaminhamento_datas CHECK (data_retorno IS NULL OR data_retorno >= data_encaminhamento)
);

CREATE OR REPLACE FUNCTION gestao_social.trg_valida_pessoa_familia_status()
RETURNS TRIGGER AS $$
DECLARE
    v_status_pessoa VARCHAR(20);
    v_status_familia VARCHAR(20);
    v_pertence_vigente BOOLEAN;
    v_data_fato DATE;
BEGIN
    -- Se for UPDATE e os sujeitos não foram alterados, permite evolução de ciclo
    -- (ex: Concluir atendimento, dar retorno de encaminhamento, registrar ausência)
    IF TG_OP = 'UPDATE' THEN
        IF NEW.familia_id = OLD.familia_id AND NEW.pessoa_id IS NOT DISTINCT FROM OLD.pessoa_id THEN
            RETURN NEW;
        END IF;
    END IF;

    SELECT status INTO v_status_familia 
      FROM gestao_social.familias 
     WHERE id = NEW.familia_id;

    -- Acolhida/Entrevista inicial permite famílias 'Em análise' ou 'Ativa'
    IF TG_TABLE_NAME = 'atendimentos' THEN
        IF NEW.tipo_atendimento = 'Acolhida/Entrevista' THEN
            IF v_status_familia NOT IN ('Ativa', 'Em análise') THEN
                RAISE EXCEPTION 'Operação bloqueada: A família (ID %) possui status "%" e não pode receber acolhida.', 
                    NEW.familia_id, v_status_familia;
            END IF;
        ELSE
            IF v_status_familia <> 'Ativa' THEN
                RAISE EXCEPTION 'Operação bloqueada: A família (ID %) não está com o status "Ativa" (Status atual: "%").', 
                    NEW.familia_id, v_status_familia;
            END IF;
        END IF;
    ELSE
        -- Em acao_beneficiarios, a família deve estar Ativa
        IF v_status_familia <> 'Ativa' THEN
            RAISE EXCEPTION 'Operação bloqueada: A família (ID %) não está com o status "Ativa" (Status atual: "%").', 
                NEW.familia_id, v_status_familia;
        END IF;
    END IF;

    IF NEW.pessoa_id IS NOT NULL THEN
        SELECT status INTO v_status_pessoa 
          FROM gestao_social.pessoas 
         WHERE id = NEW.pessoa_id;

        IF v_status_pessoa = 'Falecido' THEN
            RAISE EXCEPTION 'Operação bloqueada: A pessoa (ID %) consta no sistema com o status "Falecido".', NEW.pessoa_id;
        END IF;

        -- Validação de vigência na data do fato (data_atendimento ou CURRENT_DATE)
        v_data_fato := CURRENT_DATE;
        IF TG_TABLE_NAME = 'atendimentos' AND NEW.data_atendimento IS NOT NULL THEN
            v_data_fato := NEW.data_atendimento::date;
        END IF;

        SELECT EXISTS (
            SELECT 1 
              FROM gestao_social.familia_integrantes fi
             WHERE fi.familia_id = NEW.familia_id 
               AND fi.pessoa_id = NEW.pessoa_id
               AND fi.data_inicio <= v_data_fato
               AND (fi.data_fim IS NULL OR fi.data_fim >= v_data_fato)
        ) INTO v_pertence_vigente;

        IF NOT v_pertence_vigente THEN
            RAISE EXCEPTION 'Inconsistência cadastral: A pessoa (ID %) não possui vínculo vigente na data (%) com a família (ID %).', 
                NEW.pessoa_id, v_data_fato, NEW.familia_id;
        END IF;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS tg_atendimentos_valida_pessoa ON gestao_social.atendimentos;
CREATE TRIGGER tg_atendimentos_valida_pessoa
    BEFORE INSERT OR UPDATE ON gestao_social.atendimentos
    FOR EACH ROW
    EXECUTE FUNCTION gestao_social.trg_valida_pessoa_familia_status();

-- -----------------------------------------------------------------------------
-- 5. PRODUTOS, TABELA CENTRAL DE LOTES E DOAÇÕES
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS gestao_social.categorias_produtos (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descricao TEXT
);

CREATE TABLE IF NOT EXISTS gestao_social.produtos (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    categoria_id BIGINT NOT NULL,
    nome VARCHAR(150) NOT NULL UNIQUE,
    unidade_medida VARCHAR(20) NOT NULL DEFAULT 'UN',
    descricao TEXT,
    controla_validade BOOLEAN NOT NULL DEFAULT FALSE,
    estoque_minimo NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    data_criacao TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_produtos_categoria FOREIGN KEY (categoria_id) 
        REFERENCES gestao_social.categorias_produtos(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_produtos_estoque_min CHECK (estoque_minimo >= 0),
    CONSTRAINT chk_produtos_unidade CHECK (unidade_medida IN ('UN', 'KG', 'LITRO', 'PCT', 'CX', 'LATA', 'KIT'))
);

CREATE TABLE IF NOT EXISTS gestao_social.lotes_produto (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    produto_id BIGINT NOT NULL,
    numero_lote VARCHAR(50) NOT NULL,
    data_validade DATE,
    data_cadastro TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_lote_produto FOREIGN KEY (produto_id) 
        REFERENCES gestao_social.produtos(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT uq_produto_lote UNIQUE (produto_id, numero_lote)
);

CREATE TABLE IF NOT EXISTS gestao_social.doadores (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    tipo_doador VARCHAR(2) NOT NULL DEFAULT 'PF',
    nome VARCHAR(150) NOT NULL,
    documento VARCHAR(14) UNIQUE, -- CPF (11 dígitos) ou CNPJ (14 dígitos numéricos)
    telefone VARCHAR(20),
    email VARCHAR(150),
    cidade VARCHAR(100),
    uf CHAR(2),
    observacoes TEXT,
    data_cadastro TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_doadores_tipo CHECK (tipo_doador IN ('PF', 'PJ')),
    CONSTRAINT chk_doadores_documento CHECK (documento IS NULL OR documento ~ '^[0-9]{11}$|^[0-9]{14}$'),
    CONSTRAINT chk_doadores_telefone CHECK (telefone IS NULL OR telefone ~ '^[0-9]{10,11}$'),
    CONSTRAINT chk_doadores_email CHECK (email IS NULL OR email ~* '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+[.][A-Za-z]{2,}$')
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_doadores_email_lower ON gestao_social.doadores (LOWER(email)) WHERE email IS NOT NULL;

CREATE TABLE IF NOT EXISTS gestao_social.doacoes (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    doador_id BIGINT,
    responsavel_id BIGINT NOT NULL,
    data_doacao TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    tipo_doacao VARCHAR(30) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'Pendente',
    valor_estimado NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    descricao TEXT,
    documento_comprovante VARCHAR(200),
    CONSTRAINT fk_doacao_doador FOREIGN KEY (doador_id) 
        REFERENCES gestao_social.doadores(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_doacao_responsavel FOREIGN KEY (responsavel_id) 
        REFERENCES gestao_social.usuarios(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_doacao_tipo CHECK (tipo_doacao IN ('Alimentos', 'Vestuário', 'Higiene', 'Financeira', 'Serviços', 'Outros')),
    CONSTRAINT chk_doacao_status CHECK (status IN ('Pendente', 'Confirmada', 'Cancelada')),
    CONSTRAINT chk_doacao_valor CHECK (valor_estimado >= 0)
);

CREATE OR REPLACE FUNCTION gestao_social.trg_valida_insercao_doacao()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.status <> 'Pendente' THEN
        RAISE EXCEPTION 'Violação de Fluxo: Toda doação deve ser cadastrada inicialmente com o status "Pendente". A confirmação deve ocorrer após o cadastro dos itens.';
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS tg_doacao_status_inicial ON gestao_social.doacoes;
CREATE TRIGGER tg_doacao_status_inicial
    BEFORE INSERT ON gestao_social.doacoes
    FOR EACH ROW
    EXECUTE FUNCTION gestao_social.trg_valida_insercao_doacao();

CREATE TABLE IF NOT EXISTS gestao_social.doacao_itens (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    doacao_id BIGINT NOT NULL,
    produto_id BIGINT NOT NULL,
    quantidade NUMERIC(10,2) NOT NULL,
    valor_unitario_estimado NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    lote VARCHAR(50),
    validade DATE,
    CONSTRAINT fk_doacao_itens_doacao FOREIGN KEY (doacao_id) 
        REFERENCES gestao_social.doacoes(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_doacao_itens_produto FOREIGN KEY (produto_id) 
        REFERENCES gestao_social.produtos(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_doacao_itens_qtd CHECK (quantidade > 0),
    CONSTRAINT chk_doacao_itens_valor CHECK (valor_unitario_estimado >= 0)
);

-- NOTA ARQUITETURAL (Eliminação de Lote Fantasma):
-- Itens de doação pendente são manifestos declaratórios e não inserem lotes prematuramente na tabela central.
-- O cadastro oficial em lotes_produto ocorre exclusivamente no gatilho de ciclo de vida quando a doação é CONFIRMADA.

CREATE OR REPLACE FUNCTION gestao_social.trg_bloqueia_item_doacao_confirmada()
RETURNS TRIGGER AS $$
DECLARE
    v_status VARCHAR(30);
    v_doacao_id BIGINT;
BEGIN
    v_doacao_id := CASE WHEN TG_OP = 'DELETE' THEN OLD.doacao_id ELSE NEW.doacao_id END;

    SELECT status INTO v_status 
      FROM gestao_social.doacoes 
     WHERE id = v_doacao_id
       FOR UPDATE;

    IF v_status IN ('Confirmada', 'Cancelada') THEN
        RAISE EXCEPTION 'Violação de Estoque: A doação (ID %) já está "%" e seus itens estão congelados contra inserção, alteração ou exclusão.', v_doacao_id, v_status;
    END IF;

    IF TG_OP = 'DELETE' THEN
        RETURN OLD;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS tg_doacao_itens_bloqueio ON gestao_social.doacao_itens;
CREATE TRIGGER tg_doacao_itens_bloqueio
    BEFORE INSERT OR UPDATE OR DELETE ON gestao_social.doacao_itens
    FOR EACH ROW
    EXECUTE FUNCTION gestao_social.trg_bloqueia_item_doacao_confirmada();

-- -----------------------------------------------------------------------------
-- 6. AÇÕES SOCIAIS E PROJETOS
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS gestao_social.acoes_sociais (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    publico_alvo VARCHAR(100) NOT NULL DEFAULT 'Geral',
    descricao TEXT,
    local VARCHAR(200),
    responsavel_id BIGINT NOT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE,
    orcamento_previsto NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    valor_realizado NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    meta_beneficiarios INT DEFAULT 0,
    status VARCHAR(30) NOT NULL DEFAULT 'Planejada',
    data_criacao TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_acao_responsavel FOREIGN KEY (responsavel_id) 
        REFERENCES gestao_social.usuarios(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_acao_status CHECK (status IN ('Planejada', 'Em andamento', 'Concluída', 'Cancelada')),
    CONSTRAINT chk_acao_tipo CHECK (tipo IN ('Campanha', 'Evento', 'Projeto', 'Mutirão', 'Distribuição Coletiva')),
    CONSTRAINT chk_acao_publico CHECK (publico_alvo IN ('Famílias', 'Crianças e Adolescentes', 'Idosos', 'Pessoas com Deficiência', 'Geral')),
    CONSTRAINT chk_acao_datas CHECK (data_fim IS NULL OR data_fim >= data_inicio),
    CONSTRAINT chk_acao_valores CHECK (orcamento_previsto >= 0 AND valor_realizado >= 0 AND meta_beneficiarios >= 0)
);

CREATE TABLE IF NOT EXISTS gestao_social.acao_beneficiarios (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    acao_id BIGINT NOT NULL,
    familia_id BIGINT NOT NULL,
    pessoa_id BIGINT,
    data_participacao TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    situacao VARCHAR(30) NOT NULL DEFAULT 'Inscrito',
    observacoes TEXT,
    CONSTRAINT fk_beneficiario_acao FOREIGN KEY (acao_id) 
        REFERENCES gestao_social.acoes_sociais(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_beneficiario_familia FOREIGN KEY (familia_id) 
        REFERENCES gestao_social.familias(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_beneficiario_pessoa FOREIGN KEY (pessoa_id) 
        REFERENCES gestao_social.pessoas(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_beneficiario_situacao CHECK (situacao IN ('Inscrito', 'Convocado', 'Atendido', 'Ausente'))
);

-- Regras de Unicidade de Inscrição em Ações Sociais:
-- 1. Permite múltiplos integrantes da mesma família na mesma ação, mas impede duplicidade do mesmo indivíduo:
CREATE UNIQUE INDEX IF NOT EXISTS uq_acao_beneficiario_pessoa 
    ON gestao_social.acao_beneficiarios (acao_id, pessoa_id) 
    WHERE pessoa_id IS NOT NULL;

-- 2. Quando a inscrição for puramente familiar (sem membro individual), impede duplicidade da família:
CREATE UNIQUE INDEX IF NOT EXISTS uq_acao_beneficiario_familia 
    ON gestao_social.acao_beneficiarios (acao_id, familia_id) 
    WHERE pessoa_id IS NULL;

DROP TRIGGER IF EXISTS tg_beneficiarios_valida_pessoa ON gestao_social.acao_beneficiarios;
CREATE TRIGGER tg_beneficiarios_valida_pessoa
    BEFORE INSERT OR UPDATE ON gestao_social.acao_beneficiarios
    FOR EACH ROW
    EXECUTE FUNCTION gestao_social.trg_valida_pessoa_familia_status();

-- -----------------------------------------------------------------------------
-- 7. ENTREGAS, ITENS E MOVIMENTAÇÕES DE ESTOQUE
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS gestao_social.entregas (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    familia_id BIGINT NOT NULL,
    responsavel_id BIGINT NOT NULL,
    acao_id BIGINT,
    data_entrega TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    tipo_beneficio VARCHAR(50) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'Preparada',
    comprovante VARCHAR(200),
    observacoes TEXT,
    CONSTRAINT fk_entrega_familia FOREIGN KEY (familia_id) 
        REFERENCES gestao_social.familias(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_entrega_responsavel FOREIGN KEY (responsavel_id) 
        REFERENCES gestao_social.usuarios(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_entrega_acao FOREIGN KEY (acao_id) 
        REFERENCES gestao_social.acoes_sociais(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_entrega_status CHECK (status IN ('Preparada', 'Entregue', 'Cancelada')),
    CONSTRAINT chk_entrega_tipo CHECK (tipo_beneficio IN ('Cesta Básica', 'Kit Higiene', 'Vestuário', 'Cobertor/Agasalho', 'Medicamento', 'Emergencial', 'Outro'))
);

CREATE OR REPLACE FUNCTION gestao_social.trg_valida_insercao_entrega()
RETURNS TRIGGER AS $$
DECLARE
    v_status_familia VARCHAR(20);
BEGIN
    SELECT status INTO v_status_familia 
      FROM gestao_social.familias 
     WHERE id = NEW.familia_id;

    IF v_status_familia <> 'Ativa' THEN
        RAISE EXCEPTION 'Operação bloqueada: Não é permitido criar entrega para a família (ID %) com status "%". Apenas famílias com status "Ativa" podem receber benefícios.', 
            NEW.familia_id, v_status_familia;
    END IF;

    IF NEW.status <> 'Preparada' THEN
        RAISE EXCEPTION 'Violação de Fluxo: Toda entrega deve ser criada com o status "Preparada".';
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS tg_entrega_status_inicial ON gestao_social.entregas;
CREATE TRIGGER tg_entrega_status_inicial
    BEFORE INSERT ON gestao_social.entregas
    FOR EACH ROW
    EXECUTE FUNCTION gestao_social.trg_valida_insercao_entrega();

CREATE OR REPLACE FUNCTION gestao_social.trg_bloqueia_exclusao_entrega()
RETURNS TRIGGER AS $$
BEGIN
    IF OLD.status IN ('Entregue', 'Cancelada') OR EXISTS (SELECT 1 FROM gestao_social.movimentacoes_estoque WHERE entrega_id = OLD.id) THEN
        RAISE EXCEPTION 'Operação negada: Entregas com movimentações de estoque não podem ser excluídas fisicamente. Utilize o cancelamento.';
    END IF;
    RETURN OLD;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS tg_entrega_bloqueia_delete ON gestao_social.entregas;
CREATE TRIGGER tg_entrega_bloqueia_delete
    BEFORE DELETE ON gestao_social.entregas
    FOR EACH ROW
    EXECUTE FUNCTION gestao_social.trg_bloqueia_exclusao_entrega();

CREATE TABLE IF NOT EXISTS gestao_social.entrega_itens (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    entrega_id BIGINT NOT NULL,
    produto_id BIGINT NOT NULL,
    quantidade NUMERIC(10,2) NOT NULL,
    lote VARCHAR(50),
    validade DATE,
    CONSTRAINT fk_entrega_itens_entrega FOREIGN KEY (entrega_id) 
        REFERENCES gestao_social.entregas(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_entrega_itens_produto FOREIGN KEY (produto_id) 
        REFERENCES gestao_social.produtos(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_entrega_itens_lote FOREIGN KEY (produto_id, lote) 
        REFERENCES gestao_social.lotes_produto(produto_id, numero_lote) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_entrega_itens_qtd CHECK (quantidade > 0)
);

CREATE OR REPLACE FUNCTION gestao_social.trg_bloqueia_item_entrega_finalizada()
RETURNS TRIGGER AS $$
DECLARE
    v_status VARCHAR(30);
    v_id BIGINT;
    v_produto_ativo BOOLEAN;
BEGIN
    v_id := CASE WHEN TG_OP = 'DELETE' THEN OLD.entrega_id ELSE NEW.entrega_id END;

    SELECT status INTO v_status 
      FROM gestao_social.entregas 
     WHERE id = v_id 
       FOR UPDATE;

    IF v_status IN ('Entregue', 'Cancelada') THEN
        RAISE EXCEPTION 'Operação negada: A entrega (ID %) já está % e seus itens estão congelados contra alterações.', v_id, v_status;
    END IF;

    IF TG_OP IN ('INSERT', 'UPDATE') THEN
        SELECT ativo INTO v_produto_ativo 
          FROM gestao_social.produtos 
         WHERE id = NEW.produto_id;

        IF v_produto_ativo IS NOT TRUE THEN
            RAISE EXCEPTION 'Operação negada: O produto (ID %) está inativo e não pode ser adicionado a entregas.', NEW.produto_id;
        END IF;
    END IF;

    IF TG_OP = 'DELETE' THEN
        RETURN OLD;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS tg_bloqueia_item_finalizado ON gestao_social.entrega_itens;
CREATE TRIGGER tg_bloqueia_item_finalizado
    BEFORE INSERT OR UPDATE OR DELETE ON gestao_social.entrega_itens
    FOR EACH ROW
    EXECUTE FUNCTION gestao_social.trg_bloqueia_item_entrega_finalizada();

-- Livro-Razão de Movimentações de Estoque
CREATE TABLE IF NOT EXISTS gestao_social.movimentacoes_estoque (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    produto_id BIGINT NOT NULL,
    responsavel_id BIGINT NOT NULL,
    tipo VARCHAR(20) NOT NULL,
    quantidade NUMERIC(10,2) NOT NULL,
    data_movimentacao TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    lote VARCHAR(50),
    validade DATE,
    origem_destino VARCHAR(150),
    documento_referencia VARCHAR(100),
    entrega_id BIGINT,
    doacao_id BIGINT,
    CONSTRAINT fk_mov_produto FOREIGN KEY (produto_id) 
        REFERENCES gestao_social.produtos(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_mov_responsavel FOREIGN KEY (responsavel_id) 
        REFERENCES gestao_social.usuarios(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_mov_entrega FOREIGN KEY (entrega_id) 
        REFERENCES gestao_social.entregas(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_mov_doacao FOREIGN KEY (doacao_id) 
        REFERENCES gestao_social.doacoes(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_mov_lote FOREIGN KEY (produto_id, lote) 
        REFERENCES gestao_social.lotes_produto(produto_id, numero_lote) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_mov_tipo CHECK (tipo IN ('Entrada', 'Saída', 'Ajuste Positivo', 'Ajuste Negativo', 'Perda')),
    CONSTRAINT chk_mov_quantidade CHECK (quantidade > 0)
);

CREATE OR REPLACE FUNCTION gestao_social.trg_bloqueia_alteracao_movimentacao_estoque()
RETURNS TRIGGER AS $$
BEGIN
    RAISE EXCEPTION 'Violação de Livro-Razão: A tabela de movimentações de estoque é estritamente imutável (append-only). Não é permitido alterar ou excluir movimentações.';
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS tg_mov_estoque_imutavel ON gestao_social.movimentacoes_estoque;
CREATE TRIGGER tg_mov_estoque_imutavel
    BEFORE UPDATE OR DELETE ON gestao_social.movimentacoes_estoque
    FOR EACH ROW
    EXECUTE FUNCTION gestao_social.trg_bloqueia_alteracao_movimentacao_estoque();

CREATE OR REPLACE FUNCTION gestao_social.trg_bloqueia_truncate_movimentacao_estoque()
RETURNS TRIGGER AS $$
BEGIN
    RAISE EXCEPTION 'Violação de Livro-Razão: A tabela de movimentações de estoque é estritamente imutável. Operações de TRUNCATE são bloqueadas.';
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS tg_mov_estoque_bloqueia_truncate ON gestao_social.movimentacoes_estoque;
CREATE TRIGGER tg_mov_estoque_bloqueia_truncate
    BEFORE TRUNCATE ON gestao_social.movimentacoes_estoque
    FOR EACH STATEMENT
    EXECUTE FUNCTION gestao_social.trg_bloqueia_truncate_movimentacao_estoque();

-- Validação de Estoque
CREATE OR REPLACE FUNCTION gestao_social.trg_valida_movimentacao_estoque()
RETURNS TRIGGER AS $$
DECLARE
    v_saldo_total NUMERIC(10,2);
    v_saldo_lote NUMERIC(10,2);
    v_controla_validade BOOLEAN;
    v_produto_ativo BOOLEAN;
    v_nome_produto VARCHAR(150);
    v_validade_lote DATE;
BEGIN
    SELECT nome, controla_validade, ativo 
      INTO v_nome_produto, v_controla_validade, v_produto_ativo
      FROM gestao_social.produtos 
     WHERE id = NEW.produto_id
       FOR UPDATE;

    IF v_produto_ativo IS NOT TRUE AND NEW.tipo IN ('Saída', 'Ajuste Negativo') THEN
        RAISE EXCEPTION 'Operação bloqueada: O produto "%" está inativo e não pode ser movimentado para saída.', v_nome_produto;
    END IF;

    IF v_controla_validade THEN
        IF NEW.lote IS NULL OR NEW.validade IS NULL THEN
            RAISE EXCEPTION 'O produto "%" exige controle de validade: número do lote e validade são obrigatórios.', v_nome_produto;
        END IF;
    END IF;

    IF NEW.lote IS NOT NULL THEN
        IF NEW.tipo IN ('Entrada', 'Ajuste Positivo') THEN
            INSERT INTO gestao_social.lotes_produto (produto_id, numero_lote, data_validade)
            VALUES (NEW.produto_id, NEW.lote, NEW.validade)
            ON CONFLICT (produto_id, numero_lote) 
            DO UPDATE SET data_validade = EXCLUDED.data_validade
            WHERE gestao_social.lotes_produto.data_validade IS NULL 
              AND EXCLUDED.data_validade IS NOT NULL;
        END IF;

        SELECT data_validade INTO v_validade_lote
          FROM gestao_social.lotes_produto
         WHERE produto_id = NEW.produto_id 
           AND numero_lote = NEW.lote;

        IF v_validade_lote IS NOT NULL AND NEW.validade IS NOT NULL AND v_validade_lote <> NEW.validade THEN
            RAISE EXCEPTION 'Inconsistência de Lote: O lote "%" do produto "%" possui a validade oficial % registrada em lotes_produto, divergente de % informada.',
                NEW.lote, v_nome_produto, v_validade_lote, NEW.validade;
        END IF;

        IF v_validade_lote IS NOT NULL THEN
            NEW.validade := v_validade_lote;
        END IF;
    END IF;

    IF NEW.tipo = 'Saída' OR (NEW.tipo = 'Ajuste Negativo' AND NEW.doacao_id IS NULL) THEN
        IF NEW.validade IS NOT NULL AND NEW.validade < CURRENT_DATE THEN
            RAISE EXCEPTION 'Operação bloqueada: O lote "%" do produto "%" está VENCIDO desde % e não pode ser distribuído.', 
                NEW.lote, v_nome_produto, NEW.validade;
        END IF;
    END IF;

    IF NEW.tipo IN ('Saída', 'Ajuste Negativo', 'Perda') THEN
        SELECT COALESCE(SUM(
            CASE 
                WHEN tipo IN ('Entrada', 'Ajuste Positivo') THEN quantidade
                WHEN tipo IN ('Saída', 'Ajuste Negativo', 'Perda') THEN -quantidade
                ELSE 0 
            END
        ), 0.00) INTO v_saldo_total
        FROM gestao_social.movimentacoes_estoque
        WHERE produto_id = NEW.produto_id;

        IF v_saldo_total < NEW.quantidade THEN
            RAISE EXCEPTION 'Saldo insuficiente do produto "%". Saldo disponível: %, Quantidade solicitada: %.', 
                v_nome_produto, v_saldo_total, NEW.quantidade;
        END IF;

        IF NEW.lote IS NOT NULL THEN
            SELECT COALESCE(SUM(
                CASE 
                    WHEN tipo IN ('Entrada', 'Ajuste Positivo') THEN quantidade
                    WHEN tipo IN ('Saída', 'Ajuste Negativo', 'Perda') THEN -quantidade
                    ELSE 0 
                END
            ), 0.00) INTO v_saldo_lote
            FROM gestao_social.movimentacoes_estoque
            WHERE produto_id = NEW.produto_id AND lote = NEW.lote;

            IF v_saldo_lote < NEW.quantidade THEN
                RAISE EXCEPTION 'Saldo insuficiente no LOTE "%" do produto "%". Saldo do lote: %, Solicitado: %.', 
                    NEW.lote, v_nome_produto, v_saldo_lote, NEW.quantidade;
            END IF;
        END IF;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS tg_valida_estoque ON gestao_social.movimentacoes_estoque;
CREATE TRIGGER tg_valida_estoque
    BEFORE INSERT ON gestao_social.movimentacoes_estoque
    FOR EACH ROW
    EXECUTE FUNCTION gestao_social.trg_valida_movimentacao_estoque();

-- Ciclo de Status da Entrega
CREATE OR REPLACE FUNCTION gestao_social.trg_ciclo_status_entrega_estoque()
RETURNS TRIGGER AS $$
DECLARE
    v_nome_familia VARCHAR(150);
    v_status_familia VARCHAR(20);
    r_saida RECORD;
BEGIN
    IF OLD.status = NEW.status THEN
        RETURN NEW;
    END IF;

    SELECT f.nome_referencia, f.status INTO v_nome_familia, v_status_familia
      FROM gestao_social.familias f
     WHERE f.id = NEW.familia_id;

    IF OLD.status = 'Preparada' AND NEW.status = 'Entregue' THEN
        IF v_status_familia <> 'Ativa' THEN
            RAISE EXCEPTION 'Operação bloqueada: Não é permitido efetivar entregas para famílias com status "%" (Família ID %). Apenas famílias "Ativa" podem receber benefícios.', 
                v_status_familia, NEW.familia_id;
        END IF;

        IF NOT EXISTS (SELECT 1 FROM gestao_social.entrega_itens WHERE entrega_id = NEW.id) THEN
            RAISE EXCEPTION 'Operação inválida: A entrega (ID %) não possui itens cadastrados e não pode ser efetivada.', NEW.id;
        END IF;

        INSERT INTO gestao_social.movimentacoes_estoque (
            produto_id, responsavel_id, tipo, quantidade,
            data_movimentacao, lote, validade, origem_destino,
            documento_referencia, entrega_id
        )
        SELECT 
            ei.produto_id, NEW.responsavel_id, 'Saída', ei.quantidade,
            CURRENT_TIMESTAMP, ei.lote, ei.validade,
            'Entrega efetivada à família: ' || v_nome_familia,
            'ENTREGA #' || NEW.id, NEW.id
        FROM gestao_social.entrega_itens ei
        WHERE ei.entrega_id = NEW.id;

    ELSIF OLD.status = 'Entregue' AND NEW.status = 'Cancelada' THEN
        FOR r_saida IN 
            SELECT m.produto_id, m.quantidade, m.lote, m.validade
              FROM gestao_social.movimentacoes_estoque m
             WHERE m.entrega_id = NEW.id AND m.tipo = 'Saída'
        LOOP
            INSERT INTO gestao_social.movimentacoes_estoque (
                produto_id, responsavel_id, tipo, quantidade,
                data_movimentacao, lote, validade, origem_destino,
                documento_referencia, entrega_id
            ) VALUES (
                r_saida.produto_id, NEW.responsavel_id, 'Ajuste Positivo', r_saida.quantidade,
                CURRENT_TIMESTAMP, r_saida.lote, r_saida.validade,
                'Estorno de devolução da Entrega #' || NEW.id,
                'ESTORNO ENTREGA #' || NEW.id, NEW.id
            );

            IF r_saida.validade IS NOT NULL AND r_saida.validade < CURRENT_DATE THEN
                INSERT INTO gestao_social.movimentacoes_estoque (
                    produto_id, responsavel_id, tipo, quantidade,
                    data_movimentacao, lote, validade, origem_destino,
                    documento_referencia, entrega_id
                ) VALUES (
                    r_saida.produto_id, NEW.responsavel_id, 'Perda', r_saida.quantidade,
                    CURRENT_TIMESTAMP, r_saida.lote, r_saida.validade,
                    'Descarte por vencimento de item estornado da entrega #' || NEW.id,
                    'DESCARTE VENCIDO #' || NEW.id, NEW.id
                );
            END IF;
        END LOOP;

    ELSIF OLD.status = 'Preparada' AND NEW.status = 'Cancelada' THEN
        NULL;

    ELSE
        RAISE EXCEPTION 'Transição de status inválida para a entrega: de "%" para "%".', OLD.status, NEW.status;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS tg_ciclo_entrega_status ON gestao_social.entregas;
CREATE TRIGGER tg_ciclo_entrega_status
    AFTER UPDATE OF status ON gestao_social.entregas
    FOR EACH ROW
    EXECUTE FUNCTION gestao_social.trg_ciclo_status_entrega_estoque();

-- Ciclo de Doação com Suporte a Doações Financeiras/Serviços e Validação Agrupada por Lote
CREATE OR REPLACE FUNCTION gestao_social.trg_ciclo_status_doacao_estoque()
RETURNS TRIGGER AS $$
DECLARE
    v_nome_doador VARCHAR(150);
    v_nome_produto VARCHAR(150);
    v_saldo_disponivel NUMERIC(10,2);
    r_agrupado RECORD;
BEGIN
    IF OLD.status = NEW.status THEN
        RETURN NEW;
    END IF;

    SELECT COALESCE(dor.nome, 'Doador Anônimo') INTO v_nome_doador
      FROM gestao_social.doacoes d
      LEFT JOIN gestao_social.doadores dor ON dor.id = d.doador_id
     WHERE d.id = NEW.id;

    IF OLD.status = 'Pendente' AND NEW.status = 'Confirmada' THEN
        -- Doações físicas (Alimentos, Vestuário, Higiene, Materiais) movimentam estoque
        IF NEW.tipo_doacao NOT IN ('Financeira', 'Serviços') THEN
            IF NOT EXISTS (SELECT 1 FROM gestao_social.doacao_itens WHERE doacao_id = NEW.id) THEN
                RAISE EXCEPTION 'Operação inválida: A doação física (ID %) não possui itens cadastrados e não pode ser confirmada.', NEW.id;
            END IF;

            -- 1. Oficializa os novos lotes recebidos na tabela central lotes_produto somente após a confirmação efetiva
            INSERT INTO gestao_social.lotes_produto (produto_id, numero_lote, data_validade)
            SELECT DISTINCT di.produto_id, di.lote, di.validade
              FROM gestao_social.doacao_itens di
             WHERE di.doacao_id = NEW.id 
               AND di.lote IS NOT NULL
            ON CONFLICT (produto_id, numero_lote) 
            DO UPDATE SET data_validade = EXCLUDED.data_validade
            WHERE gestao_social.lotes_produto.data_validade IS NULL 
              AND EXCLUDED.data_validade IS NOT NULL;

            -- 2. Registra as movimentações oficiais de entrada no estoque
            INSERT INTO gestao_social.movimentacoes_estoque (
                produto_id, responsavel_id, tipo, quantidade,
                data_movimentacao, lote, validade, origem_destino,
                documento_referencia, doacao_id
            )
            SELECT 
                di.produto_id, NEW.responsavel_id, 'Entrada', di.quantidade,
                CURRENT_TIMESTAMP, di.lote, di.validade,
                'Doação confirmada de: ' || v_nome_doador,
                'DOACAO #' || NEW.id, NEW.id
            FROM gestao_social.doacao_itens di
            WHERE di.doacao_id = NEW.id;
        END IF;

    -- Validação de Saldo antes de Estornar Doação (Isolamento estrito entre Lote e Sem Lote)
    ELSIF OLD.status = 'Confirmada' AND NEW.status = 'Cancelada' THEN
        IF NEW.tipo_doacao NOT IN ('Financeira', 'Serviços') THEN
            -- 1. Validação global por produto (soma todas as quantidades da doação para o produto, independente de lote)
            FOR r_agrupado IN 
                SELECT m.produto_id, SUM(m.quantidade) AS total_produto
                  FROM gestao_social.movimentacoes_estoque m
                 WHERE m.doacao_id = NEW.id AND m.tipo = 'Entrada'
                 GROUP BY m.produto_id
            LOOP
                SELECT COALESCE(SUM(
                    CASE 
                        WHEN tipo IN ('Entrada', 'Ajuste Positivo') THEN quantidade
                        WHEN tipo IN ('Saída', 'Ajuste Negativo', 'Perda') THEN -quantidade
                        ELSE 0 
                    END
                ), 0.00) INTO v_saldo_disponivel
                FROM gestao_social.movimentacoes_estoque
                WHERE produto_id = r_agrupado.produto_id;

                IF v_saldo_disponivel < r_agrupado.total_produto THEN
                    SELECT nome INTO v_nome_produto FROM gestao_social.produtos WHERE id = r_agrupado.produto_id;
                    RAISE EXCEPTION 'Cancelamento negado: A doação #% forneceu % unidades do produto "%", mas o saldo total em estoque é de apenas %. Os itens já foram parcial ou totalmente distribuídos.',
                        NEW.id, r_agrupado.total_produto, v_nome_produto, v_saldo_disponivel;
                END IF;
            END LOOP;

            -- 2. Validação estrita por lote (usando IS NOT DISTINCT FROM para não misturar saldos com lote e sem lote)
            FOR r_agrupado IN 
                SELECT m.produto_id, m.lote, SUM(m.quantidade) AS total_lote
                  FROM gestao_social.movimentacoes_estoque m
                 WHERE m.doacao_id = NEW.id AND m.tipo = 'Entrada'
                 GROUP BY m.produto_id, m.lote
            LOOP
                SELECT COALESCE(SUM(
                    CASE 
                        WHEN tipo IN ('Entrada', 'Ajuste Positivo') THEN quantidade
                        WHEN tipo IN ('Saída', 'Ajuste Negativo', 'Perda') THEN -quantidade
                        ELSE 0 
                    END
                ), 0.00) INTO v_saldo_disponivel
                FROM gestao_social.movimentacoes_estoque
                WHERE produto_id = r_agrupado.produto_id 
                  AND lote IS NOT DISTINCT FROM r_agrupado.lote;

                IF v_saldo_disponivel < r_agrupado.total_lote THEN
                    SELECT nome INTO v_nome_produto FROM gestao_social.produtos WHERE id = r_agrupado.produto_id;
                    RAISE EXCEPTION 'Cancelamento negado: A doação #% forneceu % unidades do produto "%" no Lote "%", mas restam apenas % neste lote em estoque.',
                        NEW.id, r_agrupado.total_lote, v_nome_produto, COALESCE(r_agrupado.lote, 'Sem Lote'), v_saldo_disponivel;
                END IF;
            END LOOP;

            INSERT INTO gestao_social.movimentacoes_estoque (
                produto_id, responsavel_id, tipo, quantidade,
                data_movimentacao, lote, validade, origem_destino,
                documento_referencia, doacao_id
            )
            SELECT 
                m.produto_id, NEW.responsavel_id, 'Ajuste Negativo', m.quantidade,
                CURRENT_TIMESTAMP, m.lote, m.validade,
                'Estorno por cancelamento da Doação #' || NEW.id,
                'ESTORNO DOACAO #' || NEW.id, NEW.id
            FROM gestao_social.movimentacoes_estoque m
            WHERE m.doacao_id = NEW.id AND m.tipo = 'Entrada';
        END IF;

    ELSIF OLD.status = 'Pendente' AND NEW.status = 'Cancelada' THEN
        NULL;

    ELSE
        RAISE EXCEPTION 'Transição de status inválida para a doação: de "%" para "%".', OLD.status, NEW.status;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS tg_ciclo_doacao_status ON gestao_social.doacoes;
CREATE TRIGGER tg_ciclo_doacao_status
    AFTER UPDATE OF status ON gestao_social.doacoes
    FOR EACH ROW
    EXECUTE FUNCTION gestao_social.trg_ciclo_status_doacao_estoque();

-- -----------------------------------------------------------------------------
-- 8. AUDITORIA EM TODAS AS TABELAS DE NEGÓCIO
-- -----------------------------------------------------------------------------

DROP TRIGGER IF EXISTS tg_audit_perfis ON gestao_social.perfis;
CREATE TRIGGER tg_audit_perfis AFTER INSERT OR UPDATE OR DELETE ON gestao_social.perfis FOR EACH ROW EXECUTE FUNCTION gestao_social.trg_auditar_operacao();

DROP TRIGGER IF EXISTS tg_audit_usuarios ON gestao_social.usuarios;
CREATE TRIGGER tg_audit_usuarios AFTER INSERT OR UPDATE OR DELETE ON gestao_social.usuarios FOR EACH ROW EXECUTE FUNCTION gestao_social.trg_auditar_operacao();

DROP TRIGGER IF EXISTS tg_audit_familias ON gestao_social.familias;
CREATE TRIGGER tg_audit_familias AFTER INSERT OR UPDATE OR DELETE ON gestao_social.familias FOR EACH ROW EXECUTE FUNCTION gestao_social.trg_auditar_operacao();

DROP TRIGGER IF EXISTS tg_audit_pessoas ON gestao_social.pessoas;
CREATE TRIGGER tg_audit_pessoas AFTER INSERT OR UPDATE OR DELETE ON gestao_social.pessoas FOR EACH ROW EXECUTE FUNCTION gestao_social.trg_auditar_operacao();

DROP TRIGGER IF EXISTS tg_audit_integrantes ON gestao_social.familia_integrantes;
CREATE TRIGGER tg_audit_integrantes AFTER INSERT OR UPDATE OR DELETE ON gestao_social.familia_integrantes FOR EACH ROW EXECUTE FUNCTION gestao_social.trg_auditar_operacao();

DROP TRIGGER IF EXISTS tg_audit_avaliacoes ON gestao_social.avaliacoes_sociais;
CREATE TRIGGER tg_audit_avaliacoes AFTER INSERT OR UPDATE OR DELETE ON gestao_social.avaliacoes_sociais FOR EACH ROW EXECUTE FUNCTION gestao_social.trg_auditar_operacao();

DROP TRIGGER IF EXISTS tg_audit_tipos_carencia ON gestao_social.tipos_carencia;
CREATE TRIGGER tg_audit_tipos_carencia AFTER INSERT OR UPDATE OR DELETE ON gestao_social.tipos_carencia FOR EACH ROW EXECUTE FUNCTION gestao_social.trg_auditar_operacao();

DROP TRIGGER IF EXISTS tg_audit_avaliacao_carencias ON gestao_social.avaliacao_carencias;
CREATE TRIGGER tg_audit_avaliacao_carencias AFTER INSERT OR UPDATE OR DELETE ON gestao_social.avaliacao_carencias FOR EACH ROW EXECUTE FUNCTION gestao_social.trg_auditar_operacao();

DROP TRIGGER IF EXISTS tg_audit_atendimentos ON gestao_social.atendimentos;
CREATE TRIGGER tg_audit_atendimentos AFTER INSERT OR UPDATE OR DELETE ON gestao_social.atendimentos FOR EACH ROW EXECUTE FUNCTION gestao_social.trg_auditar_operacao();

DROP TRIGGER IF EXISTS tg_audit_encaminhamentos ON gestao_social.encaminhamentos;
CREATE TRIGGER tg_audit_encaminhamentos AFTER INSERT OR UPDATE OR DELETE ON gestao_social.encaminhamentos FOR EACH ROW EXECUTE FUNCTION gestao_social.trg_auditar_operacao();

DROP TRIGGER IF EXISTS tg_audit_categorias_produtos ON gestao_social.categorias_produtos;
CREATE TRIGGER tg_audit_categorias_produtos AFTER INSERT OR UPDATE OR DELETE ON gestao_social.categorias_produtos FOR EACH ROW EXECUTE FUNCTION gestao_social.trg_auditar_operacao();

DROP TRIGGER IF EXISTS tg_audit_produtos ON gestao_social.produtos;
CREATE TRIGGER tg_audit_produtos AFTER INSERT OR UPDATE OR DELETE ON gestao_social.produtos FOR EACH ROW EXECUTE FUNCTION gestao_social.trg_auditar_operacao();

DROP TRIGGER IF EXISTS tg_audit_lotes ON gestao_social.lotes_produto;
CREATE TRIGGER tg_audit_lotes AFTER INSERT OR UPDATE OR DELETE ON gestao_social.lotes_produto FOR EACH ROW EXECUTE FUNCTION gestao_social.trg_auditar_operacao();

DROP TRIGGER IF EXISTS tg_audit_doadores ON gestao_social.doadores;
CREATE TRIGGER tg_audit_doadores AFTER INSERT OR UPDATE OR DELETE ON gestao_social.doadores FOR EACH ROW EXECUTE FUNCTION gestao_social.trg_auditar_operacao();

DROP TRIGGER IF EXISTS tg_audit_doacoes ON gestao_social.doacoes;
CREATE TRIGGER tg_audit_doacoes AFTER INSERT OR UPDATE OR DELETE ON gestao_social.doacoes FOR EACH ROW EXECUTE FUNCTION gestao_social.trg_auditar_operacao();

DROP TRIGGER IF EXISTS tg_audit_doacao_itens ON gestao_social.doacao_itens;
CREATE TRIGGER tg_audit_doacao_itens AFTER INSERT OR UPDATE OR DELETE ON gestao_social.doacao_itens FOR EACH ROW EXECUTE FUNCTION gestao_social.trg_auditar_operacao();

DROP TRIGGER IF EXISTS tg_audit_acoes_sociais ON gestao_social.acoes_sociais;
CREATE TRIGGER tg_audit_acoes_sociais AFTER INSERT OR UPDATE OR DELETE ON gestao_social.acoes_sociais FOR EACH ROW EXECUTE FUNCTION gestao_social.trg_auditar_operacao();

DROP TRIGGER IF EXISTS tg_audit_acao_beneficiarios ON gestao_social.acao_beneficiarios;
CREATE TRIGGER tg_audit_acao_beneficiarios AFTER INSERT OR UPDATE OR DELETE ON gestao_social.acao_beneficiarios FOR EACH ROW EXECUTE FUNCTION gestao_social.trg_auditar_operacao();

DROP TRIGGER IF EXISTS tg_audit_entregas ON gestao_social.entregas;
CREATE TRIGGER tg_audit_entregas AFTER INSERT OR UPDATE OR DELETE ON gestao_social.entregas FOR EACH ROW EXECUTE FUNCTION gestao_social.trg_auditar_operacao();

DROP TRIGGER IF EXISTS tg_audit_entrega_itens ON gestao_social.entrega_itens;
CREATE TRIGGER tg_audit_entrega_itens AFTER INSERT OR UPDATE OR DELETE ON gestao_social.entrega_itens FOR EACH ROW EXECUTE FUNCTION gestao_social.trg_auditar_operacao();

DROP TRIGGER IF EXISTS tg_audit_movimentacoes ON gestao_social.movimentacoes_estoque;
CREATE TRIGGER tg_audit_movimentacoes AFTER INSERT OR UPDATE OR DELETE ON gestao_social.movimentacoes_estoque FOR EACH ROW EXECUTE FUNCTION gestao_social.trg_auditar_operacao();

-- -----------------------------------------------------------------------------
-- 9. ÍNDICES DE PERFORMANCE E INTEGRIDADE RELACIONAL
-- -----------------------------------------------------------------------------

CREATE INDEX IF NOT EXISTS idx_familias_bairro ON gestao_social.familias(bairro);
CREATE INDEX IF NOT EXISTS idx_familias_status ON gestao_social.familias(status);
CREATE INDEX IF NOT EXISTS idx_pessoas_nome ON gestao_social.pessoas(nome_completo);
CREATE INDEX IF NOT EXISTS idx_familia_integrantes_fam ON gestao_social.familia_integrantes(familia_id);
CREATE INDEX IF NOT EXISTS idx_familia_integrantes_pes ON gestao_social.familia_integrantes(pessoa_id);
CREATE INDEX IF NOT EXISTS idx_avaliacoes_familia ON gestao_social.avaliacoes_sociais(familia_id);
CREATE INDEX IF NOT EXISTS idx_carencias_avaliacao ON gestao_social.avaliacao_carencias(avaliacao_id);
CREATE INDEX IF NOT EXISTS idx_atendimentos_familia ON gestao_social.atendimentos(familia_id);
CREATE INDEX IF NOT EXISTS idx_atendimentos_pessoa ON gestao_social.atendimentos(pessoa_id);
CREATE INDEX IF NOT EXISTS idx_atendimentos_data ON gestao_social.atendimentos(data_atendimento);
CREATE INDEX IF NOT EXISTS idx_encaminhamentos_atend ON gestao_social.encaminhamentos(atendimento_id);
CREATE INDEX IF NOT EXISTS idx_doacoes_doador ON gestao_social.doacoes(doador_id);
CREATE INDEX IF NOT EXISTS idx_doacao_itens_doacao ON gestao_social.doacao_itens(doacao_id);
CREATE INDEX IF NOT EXISTS idx_acao_beneficiarios_fam ON gestao_social.acao_beneficiarios(familia_id);
CREATE INDEX IF NOT EXISTS idx_entregas_familia ON gestao_social.entregas(familia_id);
CREATE INDEX IF NOT EXISTS idx_entregas_acao ON gestao_social.entregas(acao_id);
CREATE INDEX IF NOT EXISTS idx_entrega_itens_entrega ON gestao_social.entrega_itens(entrega_id);
CREATE INDEX IF NOT EXISTS idx_mov_produto_lote ON gestao_social.movimentacoes_estoque(produto_id, lote);
CREATE INDEX IF NOT EXISTS idx_mov_entrega ON gestao_social.movimentacoes_estoque(entrega_id);
CREATE INDEX IF NOT EXISTS idx_mov_doacao ON gestao_social.movimentacoes_estoque(doacao_id);
CREATE INDEX IF NOT EXISTS idx_mov_data ON gestao_social.movimentacoes_estoque(data_movimentacao);
