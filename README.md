# Sistema de Gestão Social e Assistencial (PostgreSQL)

[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-12%2B-blue.svg)](https://www.postgresql.org/)
[![DBeaver](https://img.shields.io/badge/DBeaver-Compatible-brightgreen.svg)](https://dbeaver.io/)
[![Conformidade](https://img.shields.io/badge/LGPD-Compliant-blueviolet.svg)](#segurança-e-lgpd)
[![Padrão](https://img.shields.io/badge/SUAS-CadÚnico-orange.svg)](#visão-geral)

Banco de dados relacional completo e robusto desenvolvido em **PostgreSQL** para gestão integrada de entidades socioassistenciais, CRAS/CREAS e instituições filantrópicas.

O projeto abrange todo o ciclo de vida do acolhimento familiar, diagnósticos de vulnerabilidade, atendimentos, concessão de benefícios eventuais e controle de estoque de donativos com rastreamento de validade por **FEFO** (*First Expired, First Out*).

---

## 📁 Estrutura do Repositório

```text
├── 01_schema_ddl.sql                       # DDL Master: Tabelas, FKs, restrições, funções e triggers
├── 02_views_triggers.sql                   # Views analíticas, alertas de estoque e painéis gerenciais
├── 03_seed_data.sql                        # Carga de demonstração inicial (100% idempotente)
├── documentacao_sistema_gestao_social.md   # Especificação e arquitetura técnica detalhada
├── documentacao_sistema_gestao_social.pdf  # Manual técnico formatado para impressão/apresentação
└── README.md                               # Este arquivo de apresentação
```

---

## 🏛️ Arquitetura dos 7 Módulos (22 Tabelas)

O banco está organizado em 7 macro-módulos funcionais no schema `gestao_social`:

1. **Segurança e Auditoria Universal LGPD:** `perfis`, `usuarios`, `auditoria`.
   * Trilha de auditoria em livro-razão imutável (*append-only*).
   * Expurgo automático de dados pessoais sensíveis (saúde, documentos, relatos familiares) dos logs JSONB.
   * Senhas criptografadas nativamente com `pgcrypto` (bcrypt).
2. **Cadastro Sociofamiliar:** `familias`, `pessoas`, `familia_integrantes`.
   * Validação matemática oficial de CPF por Módulo 11 (função `fn_valida_cpf`).
   * Controle de vigência temporal de vínculos sem sobreposição.
3. **Avaliação de Vulnerabilidade Social:** `tipos_carencia`, `avaliacoes_sociais`, `avaliacao_carencias`.
   * Cálculo automático e sincronizado de renda per capita familiar.
   * Ponderação de carências e classificação qualitativa do risco familiar.
4. **Atendimento e Encaminhamentos:** `atendimentos`, `encaminhamentos`.
   * Acolhida e triagem inicial de famílias (inclusive em análise).
   * Encaminhamentos para a rede intersetorial (CRAS, CREAS, Saúde, Emprego).
5. **Ações Coletivas e Projetos:** `acoes_sociais`, `acao_beneficiarios`.
   * Inscrição de participantes permitindo múltiplos membros da mesma família sem duplicidades.
6. **Almoxarifado e Rastreabilidade FEFO:** `categorias_produtos`, `produtos`, `lotes_produto`, `movimentacoes_estoque`.
   * Livro-razão contábil e físico de estoque.
   * Controle estrito por lote e bloqueio de distribuição de produtos vencidos.
   * Arquitetura anti-lote fantasma: lotes só são oficializados com doações confirmadas.
7. **Captação de Doações e Distribuição:** `doadores`, `doacoes`, `doacao_itens`, `entregas`, `entrega_itens`.
   * Gestão de doadores físicos e jurídicos.
   * Baixa física no estoque disparada na entrega efetiva.

---

## 🚀 Como Executar o Projeto

### Pré-requisitos
* PostgreSQL 12 ou superior
* DBeaver Community ou CLI `psql`

### Ordem de Execução dos Scripts
Execute os arquivos SQL na ordem rigorosa abaixo:

1. **`01_schema_ddl.sql`** — Cria o schema `gestao_social`, extensões (`pgcrypto`), tabelas, triggers e validações.
2. **`02_views_triggers.sql`** — Compila as 5 views analíticas (`vw_saldo_estoque`, `vw_saldo_lotes_vencimento`, `vw_painel_familias_vulnerabilidade`, etc.).
3. **`03_seed_data.sql`** — Insere os perfis, usuários de teste, famílias, doações e movimentações iniciais.

> **Nota:** Todos os scripts são **100% idempotentes** (`IF NOT EXISTS`, `ON CONFLICT DO NOTHING`) e podem ser reexecutados sem causar erros ou duplicidades.

---

## 🔑 Credenciais de Demonstração (Seed Data)

| Usuário | Login | Senha | Perfil |
| :--- | :--- | :--- | :--- |
| **Administrador Geral** | `admin` | `Admin@123` | Administrador |
| **Maria Clara Santos** | `maria.social` | `Social@123` | Assistente Social |
| **Carlos Eduardo Lima** | `carlos.estoque` | `Estoque@123` | Estoque / Almoxarifado |

---

## 📊 Principais Views de Consulta

```sql
-- 1. Saldo Geral de Estoque e Alerta de Reposição
SELECT * FROM gestao_social.vw_saldo_estoque;

-- 2. Controle de Perecíveis e Validade por Lote (FEFO)
SELECT * FROM gestao_social.vw_saldo_lotes_vencimento;

-- 3. Painel de Vulnerabilidade das Famílias Atendidas
SELECT * FROM gestao_social.vw_painel_familias_vulnerabilidade;

-- 4. Fila de Retornos e Atendimentos Pendentes
SELECT * FROM gestao_social.vw_atendimentos_pendentes;

-- 5. Histórico e Prestação de Contas de Entregas
SELECT * FROM gestao_social.vw_historico_entregas_detalhado;
```

---

## 📄 Documentação Técnica Completa

Para detalhes aprofundados sobre regras de negócio, modelagem matemática, conformidade com a LGPD e respostas para bancas técnicas, consulte o arquivo [**`documentacao_sistema_gestao_social.pdf`**](./documentacao_sistema_gestao_social.pdf) ou o markdown [**`documentacao_sistema_gestao_social.md`**](./documentacao_sistema_gestao_social.md).
