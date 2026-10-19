# 💻 03. Guia Prático: Como Rodar em Outra Máquina

Este tutorial passo a passo explica exatamente como clonar, configurar e executar o **Sistema de Gestão Social e Assistencial** em qualquer computador (Windows, Linux ou macOS).

---

## 1. Pré-Requisitos do Sistema

- **PHP 8.2 ou superior**
  - Extensões recomendadas no `php.ini`:
    - `pdo`
    - `pdo_pgsql` e `pgsql` (se for conectar ao PostgreSQL)
    - `mbstring`
    - `openssl`
    - `json`
    - `session`
- **Git** instalado.
- *(Opcional para modo banco)* **PostgreSQL 12 ou superior** (versão recomendada: 15+ ou 18).

---

## 2. Clonando o Projeto

Abra o terminal na pasta onde deseja salvar o projeto e execute:

```bash
git clone https://github.com/LucasAlexandreVasconcellos/gestao-social.git
cd gestao-social
```

---

## 3. Modo de Execução Rápida (Modo Demonstração em Memória)

Se você estiver em um computador novo e **não quiser instalar ou configurar o PostgreSQL imediatamente**, você pode rodar o sistema instantaneamente em **modo demonstração**:

1. Inicie o servidor interno do PHP:
   ```bash
   php -S localhost:8000 -t public
   ```

2. Abra o navegador em:
   ```
   http://localhost:8000
   ```

O sistema detectará que o banco não está configurado e utilizará automaticamente os **10 Repositórios de Demonstração em Memória**, permitindo navegar por todos os 11 módulos, cadastrar famílias, simular entregas e visualizar relatórios imediatamente.

---

## 4. Modo de Execução Completa com PostgreSQL (Produção / Persistência Real)

Para conectar ao banco de dados relacional e persistir todos os dados:

> [!IMPORTANT]
> **Atenção ao diretório do terminal:** Certifique-se de que o seu terminal esteja **dentro da pasta do projeto** (`gestao_social`). Se você estiver na pasta pai (ex: `Downloads`), entre na pasta primeiro:
> ```powershell
> cd "c:\Users\seu_usuario\Downloads\Gestão Social\gestao_social"
> ```
> *(No Windows PowerShell ou Prompt de Comando, execute o comando PHP diretamente, **não digite `bash`**).*

### Opção A: Executar o Script Automatizado (Recomendado)
Criamos um script que faz tudo por você em 1 único comando:

**No Windows (PowerShell ou Prompt de Comando):**
```powershell
php scripts/setup_database.php sua_senha_do_postgres
```

**No Linux ou macOS (Bash/Zsh):**
```bash
php scripts/setup_database.php sua_senha_do_postgres
```

O script realizará automaticamente:
1. Conexão ao servidor PostgreSQL local;
2. Criação automática do banco de dados `gestao_social` (se ainda não existir);
3. Aplicação do DDL de tabelas e índices (`sql/01_schema_ddl.sql`);
4. Aplicação de triggers, views e regras LGPD (`sql/02_views_triggers.sql`);
5. Carga de sementes demonstrativas (`sql/03_seed_data.sql`);
6. Criação automática do arquivo `.env` com a senha e parâmetros corretos;
7. Teste de integridade de conexão via `App\Core\Database`.

### Opção B: Configuração Manual via `.env`
Caso prefira criar o banco manualmente via psql ou pgAdmin:
1. Copie o arquivo de exemplo:
   ```bash
   cp .env.example .env
   ```
2. Abra o `.env` e preencha suas credenciais:
   ```ini
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=gestao_social
   DB_USERNAME=postgres
   DB_PASSWORD=sua_senha_aqui
   DB_SCHEMA=gestao_social
   ```
3. Execute os arquivos SQL no banco `gestao_social`:
   - `sql/01_schema_ddl.sql`
   - `sql/02_views_triggers.sql`
   - `sql/03_seed_data.sql`

---

## 5. Iniciando a Aplicação

Com o banco pronto ou no modo demo, inicie o servidor:

```bash
php -S localhost:8000 -t public
```

---

## 6. Credenciais de Acesso para Testes

Na tela de login (`/login`) há botões de **Acesso Rápido em 1-Clique**, ou você pode digitar manualmente:

| Perfil | E-mail | Senha | Acesso |
| :--- | :--- | :--- | :--- |
| **Administrador** | `admin@gestaosocial.org` | `demo123` | Total (usuários, auditoria, configurações) |
| **Coordenação** | `coordenacao@gestaosocial.org` | `demo123` | Gestão de programas, aprovações, relatórios |
| **Assistente Social** | `social@gestaosocial.org` | `demo123` | Famílias, prontuários, pareceres, avaliações |
| **Estoque** | `estoque@gestaosocial.org` | `demo123` | Almoxarifado, entradas, saídas, conferência |
| **Atendimento** | `atendimento@gestaosocial.org` | `demo123` | Recepção, triagem e agendamentos |
| **Consulta** | `consulta@gestaosocial.org` | `demo123` | Visualização de prontuários (somente leitura) |

---

## 7. Como Executar a Suíte de Testes Automatizados

A qualquer momento, para validar a integridade do código na nova máquina:

- **Testes de Regras de Negócio e Segurança:**
  ```bash
  php tests/run_tests.php
  ```
  *(Deve retornar: 17 testes executados, 17 sucessos, 0 falhas)*

- **Validação dos Repositórios de Banco de Dados:**
  ```bash
  php tests/test_database_repositories.php
  ```
  *(Valida os 10 repositórios contra os contratos das interfaces via Reflection)*

- **Verificação de Sintaxe de Todos os Arquivos PHP:**
  ```bash
  php tests/check_syntax.php
  ```
  *(Verifica todos os mais de 130 arquivos PHP do projeto)*

- **Auditoria de Rotas e Fluxo Web (E2E):**
  ```bash
  php tests/verify_web_flow.php
  ```
  *(Simula requisições HTTP e valida status 200 em todas as rotas)*
