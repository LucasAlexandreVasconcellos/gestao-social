# 📦 01. O Que Já Foi Implementado no Projeto

O **Sistema de Gestão Social e Assistencial** foi desenvolvido como uma solução empresarial em **PHP 8.2+** nativo (sem dependências externas de pacotes para o núcleo), orientada à arquitetura limpa (Clean Architecture), desacoplamento por contratos (Interfaces), separação rigorosa de responsabilidades (MVC) e conformidade estrita com a **Lei Geral de Proteção de Dados (LGPD — Lei Federal nº 13.709/2018)**.

---

## 1. Arquitetura do Núcleo da Aplicação (`app/Core/`)

A base estrutural da aplicação foi construída do zero, sem acoplamento a frameworks pesados:

| Componente | Arquivo | Responsabilidade |
| :--- | :--- | :--- |
| **Autoloader PSR-4** | `app/Core/Autoloader.php` | Carregamento automático de classes no padrão PSR-4 para o namespace `App\`. |
| **Container de Injeção** | `app/Core/Container.php` | Container de inversão de controle (IoC) para registro e resolução de singletons e serviços. |
| **Roteador HTTP** | `app/Core/Router.php` | Roteador RESTful com suporte a métodos GET/POST, extração de parâmetros de URL (`/familias/{id}`) e middleware pipeline. |
| **Camada de Requisição** | `app/Core/Request.php` | Abstração de requisição HTTP com sanitização de inputs, headers, método e query strings. |
| **Camada de Resposta** | `app/Core/Response.php` | Resposta HTTP tipada (HTML, JSON, redirecionamentos e códigos de status). |
| **Motor de Renderização** | `app/Core/View.php` | Motor de templates nativo com suporte a layouts mestres (`app.php`, `auth.php`), seções e partials. |
| **Gerenciador de Sessão** | `app/Core/Session.php` | Sessões seguras com cookies `httponly`, `samesite=Lax`, regeneração de ID contra fixation e mensagens flash. |
| **Carregador de Ambiente** | `app/Core/Env.php` | Carregador leve e seguro de arquivos `.env` para `putenv()`, `$_ENV` e `$_SERVER`. |
| **Gerenciador de Banco (PDO)** | `app/Core/Database.php` | Conexão singleton via PDO PostgreSQL, desativação de emulação de prepared statements, controle de transações ACID (`Database::transaction`), injeção de contexto de auditoria de usuário logado (`app.usuario_logado`, `app.usuario_logado_id`) e sanitização estrita de erros para evitar vazamento de credenciais. |

---

## 2. Os 11 Módulos Funcionais Completos

Todos os 11 módulos possuem Controllers, Services, Entidades, Contratos, Repositórios e Telas (Views) completas:

1. **Autenticação & Controle de Acesso (RBAC):**
   - Login por e-mail e senha com hash bcrypt (`password_verify`).
   - Acesso rápido por perfil em 1-clique para agilizar homologação.
   - 6 perfis implementados com matriz de permissões (`Admin`, `Coordenacao`, `AssistenteSocial`, `Estoque`, `Atendimento`, `Consulta`).
   - Middlewares `AuthMiddleware` e `RoleMiddleware` protegendo todas as rotas.

2. **Famílias (`/familias`):**
   - Criação e manutenção de prontuários familiares com código sequencial automático (`FAM-YYYY-XXXX`).
   - Endereço completo (com validação de CEP brasileiro) e situação de moradia.
   - Cálculo dinâmico e em tempo real da renda total e **renda per capita** da família.
   - Classificação socioeconômica automática (Extrema Pobreza, Pobreza, Baixa Renda, etc.).
   - Visualização de prontuário consolidado com abas de membros, avaliações, atendimentos e benefícios.

3. **Pessoas & Vínculos (`/pessoas`):**
   - Cadastro detalhado de membros da família (Responsável Familiar, Cônjuge, Filho, etc.).
   - Validação matemática de CPF via algoritmo de Módulo 11 (dígitos verificadores).
   - Preservação de vínculos sociofamiliares históricos: ao desvincular uma pessoa, o vínculo anterior é arquivado com data de término e motivo, nunca sobrescrito.
   - Suporte a condições de saúde, trabalho, renda individual e escolaridade.

4. **Avaliações Socioeconômicas (`/avaliacoes`):**
   - Diagnósticos sociais realizados por assistentes sociais.
   - Critérios de pontuação de vulnerabilidade social (habitação, saneamento, renda, presença de idosos/PCDs).
   - Parecer social técnico com arquivamento sigiloso.
   - Sugestão automática de status de vulnerabilidade com base na pontuação ponderada.

5. **Atendimentos & Triagens (`/atendimentos`):**
   - Registro de acolhimento e agendamentos de famílias e indivíduos.
   - Tipos de atendimento (Plantão Social, Visita Domiciliar, Acolhimento, Retorno).
   - Controle de status (Agendado, Em Andamento, Concluído, Cancelado).

6. **Benefícios Sociais (`/beneficios`):**
   - Gestão de solicitações e concessões de auxílios (Cesta Básica, Auxílio Gás, Kit Higiene, Vestuário, etc.).
   - Regra de Negócio Crítica: **Proibição de entrega vazia** (validação obrigatória de itens antes de efetivar).
   - **Baixa automática no estoque:** ao efetivar uma entrega, os itens vinculados sofrem baixa imediata no estoque com registro do lote e conferência de validade.

7. **Estoque de Donativos (`/estoque`):**
   - Controle completo de almoxarifado de donativos e itens assistenciais.
   - Entradas, saídas manuais, devoluções e baixas automáticas por entrega de benefício.
   - Rastreamento por lote e data de validade com alertas visuais de estoque baixo.

8. **Doações (`/doacoes`):**
   - Segregação conceitual entre **Doações em Produtos** (integradas à entrada de estoque) e **Doações Financeiras** (com registro de conta bancária, método de pagamento e recibo).
   - Cadastro de doadores (Pessoa Física com CPF ou Pessoa Jurídica com CNPJ).

9. **Projetos, Cursos & Oficinas (`/projetos`):**
   - Gestão de programas socioeducativos e oficinas comunitárias.
   - Inscrição de participantes vinculados aos cadastros de famílias/pessoas.
   - Controle de ciclo de vida de participação: `Inscrito` ➔ `Convocado` ➔ `Atendido` ou `Ausente`.

10. **Relatórios Gerenciais & Indicadores (`/relatorios`):**
    - Painel de indicadores gerenciais para tomada de decisão e prestação de contas.
    - Distribuição de benefícios por período, evolução de atendimentos e famílias por faixa de renda.
    - Exportação de dados estruturados em formato **CSV**.
    - Aplicação automática de regras de **minimização e anonimização de dados pessoais** (LGPD).

11. **Gestão de Usuários & Trilha de Auditoria (`/usuarios` e `/auditoria`):**
    - Cadastro, ativação e inativação de operadores do sistema.
    - Trilha de auditoria universal registrando: Ação, Módulo, Usuário, IP, Data/Hora, Dados Anteriores e Dados Novos.
    - Proteção de dados sensíveis na trilha de auditoria.

---

## 3. Camada de Persistência Desacoplada (Interfaces & 2 Drivers)

A persistência segue o padrão **Repository Pattern**:

### Contratos Definidos (`app/Contracts/Repositories/`):
- `FamiliaRepositoryInterface`
- `PessoaRepositoryInterface`
- `AvaliacaoRepositoryInterface`
- `AtendimentoRepositoryInterface`
- `BeneficioRepositoryInterface`
- `EstoqueRepositoryInterface`
- `DoacaoRepositoryInterface`
- `ProjetoRepositoryInterface`
- `UsuarioRepositoryInterface`
- `AuditoriaRepositoryInterface`

### Implementações Concluídas:
1. **Driver Demo (`app/Repositories/Demo/*`):** 10 repositórios em memória baseados em sessão, com 100% de cobertura, ideal para testes instantâneos sem necessidade de banco de dados.
2. **Driver Database (`app/Repositories/Database/*`):** 10 repositórios com código SQL nativo para PostgreSQL, prepared statements obrigatórios e manipulação transacional.
3. **Injeção Dinâmica em `config/app.php`:** Chaveamento transparente: se o PostgreSQL estiver acessível via `Database::isConnected()`, a aplicação utiliza automaticamente o driver `Database`; se não, utiliza o driver `Demo`.

---

## 4. Banco de Dados PostgreSQL & Scripts SQL (`sql/`)

Os scripts fornecidos foram integrados e ajustados com os mais elevados padrões de integridade:

- **`sql/01_schema_ddl.sql`:**
  - 22 tabelas relacionais com chaves primárias `BIGINT GENERATED ALWAYS AS IDENTITY`.
  - Extensão `pgcrypto` instalada no schema `gestao_social` para criptografia segura de senhas em bcrypt.
  - Validação matemática de CPF via função PL/pgSQL com módulo 11 (`fn_valida_cpf`).
  - Prevenção de conflito de vigência temporal de vínculos familiares via operador de sobreposição de intervalos (`&&` em `daterange`).
  - Triggers para validação de validade de itens de estoque e congelamento de lotes após entrega.
- **`sql/02_views_triggers.sql`:**
  - Trilha de auditoria universal `trg_auditar_operacao` com `SECURITY DEFINER` e `search_path = gestao_social, pg_temp;` (imune a ataques de sequestro de schema).
  - **Poda automática de dados sensíveis da LGPD:** triggers removem campos de CPF, telefone, tipo de deficiência, parecer social e senhas antes de gravar o snapshot JSONB na tabela de auditoria.
  - Imutabilidade da tabela `gestao_social.auditoria` (bloqueio total de `UPDATE`, `DELETE` e `TRUNCATE`).
  - 5 Views analíticas seguras para relatórios e dashboards.
- **`sql/03_seed_data.sql`:**
  - Carga inicial completa de perfis, usuários de teste, famílias, pessoas, estoque e itens de benefício.

---

## 5. Validação e Qualidade de Código (100% Aprovado)

Todos os testes automatizados foram executados e validados:
- **`php tests/run_tests.php`:** **17/17 testes passaram (100%)** cobrindo regras de negócio essenciais, validações de formulário, RBAC, CSRF e LGPD.
- **`php tests/test_database_repositories.php`:** **10/10 repositórios Db** verificados via Reflection contra os contratos oficiais das interfaces.
- **`php tests/check_syntax.php`:** **132/132 arquivos PHP** analisados sem qualquer erro de sintaxe.
- **`php tests/verify_web_flow.php`:** Auditoria E2E via requisições HTTP retornou **Status 200 OK** em todos os 11 módulos e telas do sistema.
