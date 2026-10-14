# Arquitetura do Sistema — Plataforma de Gestão Social e Assistencial

Este documento descreve as decisões arquiteturais, padrões de projeto, fluxo de execução e convenções adotadas no desenvolvimento do sistema em PHP.

---

## 1. Visão Geral e Princípios Fundamentais

A plataforma foi concebida para atender entidades socioassistenciais que necessitam gerenciar famílias, indivíduos vulneráveis, atendimentos, benefícios materiais, estoque e projetos sociais.

### Princípios Norteadores:

1. **Separação Estrita de Responsabilidades:** Camadas desacopladas (Apresentação, Roteamento, Regras de Negócio, Abstração de Acesso a Dados).
2. **Isolamento Total do Banco de Dados:** O sistema não possui dependências de persistência física (sem arquivos SQL, sem conexões PDO/MySQLi ativas). O acesso a dados é mediado por **Interfaces de Repositório**.
3. **Nativo e Zero Dependências Externas:** Não requer Node.js, Composer, npm ou bibliotecas compiladas. Utiliza recursos nativos do PHP 8.2+.
4. **Segurança por Padrão (Security by Design):** Proteção ativa contra XSS, CSRF, autenticação baseada em sessão com hash seguro e controle de acesso baseado em papéis (RBAC).

---

## 2. Estrutura de Diretórios

```
gestao_social/
├── app/
│   ├── Contracts/             # Contratos e Interfaces de Repositório
│   │   └── Repositories/      # Interfaces formais de acesso aos dados (10 contratos)
│   ├── Controllers/           # Controladores HTTP (coordenação de fluxo)
│   ├── Core/                  # Núcleo da aplicação (Autoloader, Router, Session, Container, etc.)
│   ├── Helpers/               # Utilitários de formatação (moeda, data, badges) e segurança
│   ├── Middleware/            # Filtros de requisição (Autenticação, CSRF, Perfis/RBAC)
│   ├── Models/                # Entidades de domínio ricas (16 modelos)
│   ├── Repositories/          # Implementações concretas de Repositório
│   │   └── Demo/              # Repositórios demonstrativos em memória (Session-based)
│   ├── Services/              # Camada de Serviços (concentra todas as regras de negócio)
│   ├── Validators/            # Validações reutilizáveis no servidor (FormValidator)
│   └── Views/                 # Templates HTML5 e interfaces (11 módulos + layouts e erros)
│       ├── atendimentos/
│       ├── auth/
│       ├── avaliacoes/
│       ├── beneficios/
│       ├── dashboard/
│       ├── doacoes/
│       ├── errors/
│       ├── estoque/
│       ├── familias/
│       ├── layouts/
│       ├── partials/
│       ├── pessoas/
│       ├── projetos/
│       ├── relatorios/
│       └── usuarios/
├── config/                    # Configurações globais e mapeamento do DI Container
│   └── app.php
├── docs/                      # Documentação técnica e manuais de integração
│   ├── ARCHITECTURE.md
│   ├── DatabaseIntegration.md
│   ├── BUSINESS_RULES.md
│   └── INSTALLATION.md
├── public/                    # Diretório público (Web Root)
│   ├── assets/
│   │   ├── css/               # Folhas de estilo puras (app.css, print.css)
│   │   └── js/                # Vanilla JavaScript (app.js)
│   ├── .htaccess              # Regras de reescrita Apache
│   └── index.php              # Ponto de entrada único (Front Controller)
├── routes/                    # Definições organizadas de rotas
│   └── web.php
└── tests/                     # Suíte de testes automatizados executável via CLI
    └── run_tests.php
```

---

## 3. Padrões de Projeto e Camadas de Software

### 3.1. Front Controller (`public/index.php`)
Toda requisição HTTP passa por um único ponto de entrada. O Front Controller:
1. Registra o Autoloader PSR-4 nativo (`App\Core\Autoloader`).
2. Inicializa a sessão segura e gera o token CSRF (`App\Core\Session`).
3. Carrega o Container de Injeção de Dependências (`config/app.php`).
4. Inicializa o motor de visualização (`App\Core\View`).
5. Carrega o mapa de rotas (`routes/web.php`) e delega o processamento ao roteador (`App\Core\Router`).

### 3.2. Router e Middlewares (`app/Core/Router.php`, `app/Middleware/`)
- Mapeia métodos HTTP (`GET`, `POST`) e caminhos URI (suportando parâmetros dinâmicos `{id}`).
- Executa a cadeia de Middlewares:
  - `AuthMiddleware`: Impede acesso não autenticado a rotas privadas.
  - `CsrfMiddleware`: Valida tokens CSRF em todas as requisições `POST`, `PUT`, `DELETE`.
  - `RoleMiddleware`: Restringe o acesso a determinados recursos conforme o papel do usuário (RBAC).

### 3.3. Injeção de Dependências e Inversão de Controle (`app/Core/Container.php`)
O sistema utiliza um Container com auto-wiring baseado em reflexão (`ReflectionClass`).
Os Controllers nunca instanciam repositórios ou serviços diretamente:
```php
public function __construct(
    private FamiliaService $familiaService,
    private AuditLogService $auditLogService
) {}
```
No arquivo `config/app.php`, as interfaces são associadas às implementações:
```php
Container::singleton(FamiliaRepositoryInterface::class, FamiliaRepositoryDemo::class);
```
> **Ponto de Extensão:** Quando a camada de banco de dados for criada, basta apontar a interface para a nova classe de banco (ex: `FamiliaRepositoryDb`), sem alterar nenhuma linha de Controller ou Service.

### 3.4. Camada de Domínio e Serviços (`app/Services/`)
Concentra **100% da lógica de negócio**. Nenhuma regra de validação complexa ou cálculo social reside em Controllers ou Views.
- Exemplo 1: `BeneficioService::registrarEntrega()` valida que uma entrega não pode ser criada sem itens e calcula o impacto no estoque.
- Exemplo 2: `AvaliacaoService::calcularSugestaoVulnerabilidade()` processa renda per capita, indicadores alimentares e habitacionais para sugerir prioridade técnica.
- Exemplo 3: `PessoaService::obterHistoricoVinculos()` preserva todos os vínculos familiares passados, mesmo quando uma pessoa se desvincula de uma família.

### 3.5. Camada de Repositórios e Contratos (`app/Contracts/Repositories/`)
Define contratos estritos em PHP usando interfaces:
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

### 3.6. Motor de Visualização (`app/Core/View.php`)
- Renderização limpa de templates em PHP nativo.
- Suporte a layouts mestres (`layouts/main.php`, `layouts/auth.php`) e parciais (`partials/csrf.php`).
- Disponibiliza automaticamente a função de escape `$e($var)` para evitar injeções de script (XSS).

---

## 4. Ciclo de Vida de uma Requisição (Request Lifecycle)

```
[Navegador / Cliente]
         │  HTTP GET/POST
         ▼
[public/index.php]  (Front Controller)
         │
         ├── Autoloader::loadClass()
         ├── Session::start()
         ├── Container::resolve()
         ▼
[app/Core/Router.php]
         │
         ├── Middleware Pipeline:
         │     ├── CsrfMiddleware  (Valida _csrf_token em POSTs)
         │     ├── AuthMiddleware  (Verifica login do usuário)
         │     └── RoleMiddleware  (Verifica permissão do perfil)
         ▼
[app/Controllers/FamiliaController.php]
         │
         ▼ (Delega regra de negócio)
[app/Services/FamiliaService.php]
         │
         ├── Valida entradas com FormValidator
         ├── Aplica regras e auditoria operacional
         ▼ (Consulta/Manipulação de dados)
[app/Contracts/Repositories/FamiliaRepositoryInterface]
         │  (Implementação Atual: FamiliaRepositoryDemo em memória)
         ▼
[app/Core/View.php]
         │  Renderiza HTML escapado com layout institucional
         ▼
[Resposta HTTP 200/302] -> Cliente
```

---

## 5. Convenções de Código

1. **Tipagem Estrita:** Todos os arquivos PHP declaram `declare(strict_types=1);` no topo.
2. **Propriedades Promovidas em Construtores:** Sintaxe moderna do PHP 8.2+ para declarar e inicializar atributos (`public function __construct(public string $nome) {}`).
3. **Imutabilidade e Classes Finais:** Classes de serviço, modelos e controllers são marcados como `final` para prevenir acoplamentos indevidos por herança.
4. **Sanitização de Logs:** O serviço `AuditLogService` remove automaticamente chaves sensíveis (senhas, tokens) de qualquer detalhe antes do registro.
