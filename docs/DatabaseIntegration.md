# Guia de Integração com o Banco de Dados (Database Integration Blueprint)

> **ATENÇÃO AO RESPONSÁVEL PELO BANCO DE DADOS:**  
> A aplicação em PHP foi intencionalmente desenvolvida **SEM QUALQUER BANCO DE DADOS CONECTADO**.  
> O código foi estruturado utilizando o padrão **Repository Pattern** com **Inversão de Dependências (IoC)**, garantindo que você possa projetar livremente as tabelas físicas, índices, relacionamentos e estratégias de persistência sem precisar reescrever nenhuma regra de negócio ou tela do sistema.

---

## 1. Módulos que Demandam Persistência Real

A futura camada de persistência deverá armazenar e consultar informações para os seguintes módulos e entidades:

1. **Famílias (`Familia`):** Dados cadastrais, endereço padronizado, situação de moradia, renda familiar e despesas mensais.
2. **Pessoas e Vínculos Familiares (`Pessoa`, `VinculoFamiliar`):** Cidadãos atendidos, histórico de vínculos familiares ao longo do tempo (ativo/inativo com datas de início e término) e indicação de pessoa de referência.
3. **Avaliações Socioassistenciais (`AvaliacaoSocial`):** Pareceres sociais, cálculos per capita, presença de vulnerabilidades críticas, necessidades prioritárias e datas de reavaliação.
4. **Atendimentos e Encaminhamentos (`Atendimento`, `Encaminhamento`):** Histórico de acolhimentos, visitas domiciliares, providências tomadas e encaminhamentos para rede pública/parceira.
5. **Benefícios e Itens Entregues (`BeneficioEntrega`, `ItemEntrega`):** Registro de distribuições de cestas, vestuário, fraldas e kits com comprovantes e status.
6. **Estoque e Movimentações (`Produto`, `MovimentacaoEstoque`):** Catálogo de itens assistenciais, saldos físicos, lotes, validades e histórico de entradas, saídas, perdas e ajustes.
7. **Doações (`Doacao`, `ItemDoacao`):** Registro de mantenedores/doadores (PF/PJ/Anônimo), doações financeiras e doações de produtos em espécie.
8. **Projetos e Campanhas Sociais (`ProjetoSocial`, `ParticipanteProjeto`):** Ações sociais coletivas, oficinas, orçamentos e lista de participantes com progressão de status.
9. **Usuários e Autenticação (`Usuario`):** Credenciais de acesso institucional, perfis de permissão (RBAC) e timestamps de login.
10. **Trilha de Auditoria (`RegistroAuditoria`):** Log append-only das operações críticas realizadas no sistema com conformidade LGPD.

---

## 2. Interfaces de Repositório Implementadas

Todas as consultas e manipulações de dados da aplicação são definidas formalmente por contratos PHP no diretório `app/Contracts/Repositories/`:

| Interface | Arquivo | Responsabilidade |
|---|---|---|
| `FamiliaRepositoryInterface` | `app/Contracts/Repositories/FamiliaRepositoryInterface.php` | CRUD de famílias, integrantes, busca por código e contadores |
| `PessoaRepositoryInterface` | `app/Contracts/Repositories/PessoaRepositoryInterface.php` | Cadastro de indivíduos e histórico de núcleos familiares |
| `AvaliacaoRepositoryInterface` | `app/Contracts/Repositories/AvaliacaoRepositoryInterface.php` | Avaliações socioassistenciais e reavaliações pendentes |
| `AtendimentoRepositoryInterface` | `app/Contracts/Repositories/AtendimentoRepositoryInterface.php` | Atendimentos, acompanhamentos e encaminhamentos externos |
| `BeneficioRepositoryInterface` | `app/Contracts/Repositories/BeneficioRepositoryInterface.php` | Distribuição de mantimentos e itens assistenciais |
| `EstoqueRepositoryInterface` | `app/Contracts/Repositories/EstoqueRepositoryInterface.php` | Catálogo de produtos, movimentações e alertas de estoque |
| `DoacaoRepositoryInterface` | `app/Contracts/Repositories/DoacaoRepositoryInterface.php` | Cadastro de doações físicas e financeiras |
| `ProjetoRepositoryInterface` | `app/Contracts/Repositories/ProjetoRepositoryInterface.php` | Campanhas, oficinas e lista de participantes |
| `UsuarioRepositoryInterface` | `app/Contracts/Repositories/UsuarioRepositoryInterface.php` | Usuários, autenticação e verificação de perfil |
| `AuditoriaRepositoryInterface` | `app/Contracts/Repositories/AuditoriaRepositoryInterface.php` | Persistência e filtros da trilha de auditoria |

---

## 3. Métodos que Deverão Ser Implementados por Interface

Abaixo estão os contratos exatos de métodos esperados pela camada de serviços:

### 3.1. `FamiliaRepositoryInterface`
- `findAll(array $filters = []): array`: Retorna array de instâncias de `Familia`. Suporta filtros por `search`, `bairro`, `situacao`.
- `findById(int $id): ?Familia`: Retorna a família pelo ID primário.
- `findByCodigo(string $codigo): ?Familia`: Busca por código institucional (ex: `FAM-001`).
- `save(Familia $familia): Familia`: Insere uma nova família (deve preencher o ID gerado e gerar código sequencial).
- `update(Familia $familia): bool`: Atualiza os dados da família.
- `getIntegrantes(int $familiaId, bool $apenasAtivos = true): array`: Retorna array de `VinculoFamiliar` da família.
- `addIntegrante(VinculoFamiliar $vinculo): VinculoFamiliar`: Registra novo integrante na família.
- `updateIntegrante(VinculoFamiliar $vinculo): bool`: Atualiza situação do vínculo (ex: inativação).
- `countTotal(array $filters = []): int`: Contagem para indicadores do dashboard.
- `countBySituacao(string $situacao): int`: Contagem por situação cadastral.
- `getBairros(): array`: Retorna lista de nomes distintos de bairros atendidos.

### 3.2. `PessoaRepositoryInterface`
- `findAll(array $filters = []): array`: Lista de pessoas cadastradas.
- `findById(int $id): ?Pessoa`: Retorna a pessoa pelo ID.
- `findByCpf(string $cpf): ?Pessoa`: Busca pessoa pelo documento CPF formatado ou numérico.
- `save(Pessoa $pessoa): Pessoa`: Insere nova pessoa.
- `update(Pessoa $pessoa): bool`: Atualiza cadastro.
- `getHistoricoVinculos(int $pessoaId): array`: Retorna array de registros históricos de vínculos familiares da pessoa com a família correspondente.
- `countTotal(): int`: Total de pessoas atendidas.

### 3.3. `BeneficioRepositoryInterface`
- `findAll(array $filters = []): array`: Retorna entregas de benefícios cadastradas.
- `findById(int $id): ?BeneficioEntrega`: Retorna a entrega com seus respectivos itens.
- `findByFamiliaId(int $familiaId): array`: Histórico de entregas da família.
- `save(BeneficioEntrega $entrega): BeneficioEntrega`: Grava cabeçalho da entrega.
- `update(BeneficioEntrega $entrega): bool`: Atualiza situação (ex: de Preparada para Efetivada ou Cancelada).
- `addItem(ItemEntrega $item): ItemEntrega`: Adiciona item discriminado com lote e quantidade.
- `countTotal(array $filters = []): int`: Totalizador de entregas.
- `countEntregasHoje(): int`: Entregas realizadas na data atual.

### 3.4. `EstoqueRepositoryInterface`
- `findAllProdutos(array $filters = []): array`: Catálogo de produtos.
- `findProdutoById(int $id): ?Produto`: Produto pelo ID.
- `saveProduto(Produto $produto): Produto`: Salva produto novo.
- `updateProduto(Produto $produto): bool`: Atualiza produto.
- `findMovimentacoes(array $filters = []): array`: Histórico de movimentações (Entrada, Saída, Ajuste, Perda).
- `saveMovimentacao(MovimentacaoEstoque $mov): MovimentacaoEstoque`: Registra movimentação física.
- `getProdutosEstoqueBaixo(): array`: Lista produtos onde `estoqueAtual <= estoqueMinimo`.
- `getProdutosProximoVencimento(int $dias = 30): array`: Lista produtos que vencem nos próximos N dias.
- `countProdutosCriticos(): int`: Contador para cartão do dashboard.
- `countProdutosVencendo(): int`: Contador de vencimentos do dashboard.

---

## 4. Operações Críticas que Exigem Transações Atômicas (ACID)

No banco de dados definitivo, as seguintes operações **NUNCA devem ser executadas em auto-commit solto**, exigindo blocos transacionais explícitos (`BEGIN TRANSACTION ... COMMIT / ROLLBACK`):

### 4.1. Efetivação de Entrega de Benefício e Baixa de Saldo de Estoque
Ao efetivar uma entrega (`BeneficioEntrega`), a inserção dos registros de movimentação de saída (`MovimentacaoEstoque`) e a dedução do saldo disponível em cada produto (`Produto.estoqueAtual`) **DEVEM pertencer à mesma transação atômica**:
- Se houver falta de saldo físico em qualquer um dos itens, ou falha de rede durante a baixa, a entrega não pode ser marcada como 'Efetivada'.
- O banco deverá garantir `FOR UPDATE` ou lock otimista/pessimista na linha de saldo do produto durante a baixa.

### 4.2. Recebimento de Doação de Produtos e Entrada no Almoxarifado
Ao registrar uma doação física (`Doacao`), o cabeçalho da doação, os itens doados (`ItemDoacao`) e as respectivas movimentações de 'Entrada' no estoque com lotes e validades associadas devem ser confirmados simultaneamente.

### 4.3. Criação de Núcleo Familiar com Responsável
Ao cadastrar uma família e seu responsável de referência, a criação da entidade `Familia`, da entidade `Pessoa` e a criação do registro relacional `VinculoFamiliar` com `isResponsavel = true` devem ser transacionadas juntas.

---

## 5. Relacionamentos e Integridade Referencial Esperados

A futura modelagem de tabelas deverá respeitar as seguintes diretrizes relacionais:

```
[familias] 1 ──────────── N [vinculos_familiares] N ──────────── 1 [pessoas]
   │                               │
   ├─ 1:N ─ [avaliacoes_sociais]   └─ Preserva histórico (ativo/inativo)
   ├─ 1:N ─ [atendimentos] ── 1:N ── [encaminhamentos]
   └─ 1:N ─ [beneficios_entregas] ── 1:N ── [itens_entrega]
                                                   │
                                            N:1 com [produtos]
                                                   │
                                         1:N com [movimentacoes_estoque]
```

1. **Histórico de Pessoas e Vínculos Familiares (Regra Crítica):**
   - Uma pessoa **NÃO** possui foreign key direta engessada para uma única família.
   - A relação é intermediada pela entidade associativa `vinculos_familiares` (contendo `familia_id`, `pessoa_id`, `parentesco`, `is_responsavel`, `data_inicio`, `data_termino`, `ativo`, `observacoes`).
   - Se uma família mudar de composição ou um jovem se emancipar, o registro em `vinculos_familiares` é encerrado (`ativo = false`, `data_termino = NOW()`), **sem apagar a pessoa e preservando todo o histórico de vínculos passados**.
2. **Exclusões Lógicas (Soft Delete) vs Integridade Histórica:**
   - Registros de famílias, pessoas, entregas de benefícios ou movimentações de estoque com histórico associado **NÃO devem ser excluídos com DELETE físico**, devendo ser inativados ou cancelados para resguardar a rastreabilidade perante auditorias institucionais.
3. **Auditoria Imutável (Append-Only):**
   - A tabela correspondente a `RegistroAuditoria` deve operar estritamente em modo append-only (somente `INSERT` e `SELECT`), sem permissão de `UPDATE` ou `DELETE` para os usuários da aplicação.

---

## 6. Como Conectar o Banco sem Alterar os Controladores ou Serviços

Graças ao padrão adotado, o responsável pelo banco não precisará mexer nos arquivos de Controllers, Services ou Views.

### Passo a Passo:

1. **Criar a classe de conexão (ex: `Database.php`):**
   Criar um wrapper seguro em `app/Core/Database.php` utilizando `PDO` com tratamento de exceções, charset UTF-8 e prepared statements obrigatórios.
2. **Implementar os repositórios reais:**
   No diretório `app/Repositories/Database/`, crie as classes implementando as interfaces existentes:
   ```php
   namespace App\Repositories\Database;

   use App\Contracts\Repositories\FamiliaRepositoryInterface;
   use App\Models\Familia;
   use PDO;

   final class FamiliaRepositoryDb implements FamiliaRepositoryInterface
   {
       public function __construct(private PDO $db) {}

       public function findById(int $id): ?Familia
       {
           $stmt = $this->db->prepare("SELECT * FROM familias WHERE id = :id");
           $stmt->execute(['id' => $id]);
           $row = $stmt->fetch(PDO::FETCH_ASSOC);
           return $row ? $this->mapRowToModel($row) : null;
       }
       // ... implementar os demais métodos da interface
   }
   ```
3. **Alternar a Injeção de Dependências no `config/app.php`:**
   Substitua as classes `Demo` pelas novas classes `Db`:
   ```php
   // ANTES (Demonstração em memória):
   // Container::singleton(FamiliaRepositoryInterface::class, FamiliaRepositoryDemo::class);

   // DEPOIS (Banco de dados real):
   Container::singleton(FamiliaRepositoryInterface::class, FamiliaRepositoryDb::class);
   ```
   **Pronto!** Toda a aplicação passará a consultar e gravar no banco de dados automaticamente.

---

## 7. Decisões Técnicas que Dependem da Definição do Banco

As seguintes definições ficam sob responsabilidade do arquiteto de dados:
- Escolha do SGBD (PostgreSQL ou MySQL/MariaDB).
- Estratégia de tipos monetários (recomendado: `DECIMAL(12, 2)` para rendas e despesas).
- Convenção de chaves primárias (Auto-increment `BIGINT` ou `UUIDv7`).
- Estrutura de índices compostos (ex: índice em `(familia_id, ativo)` na tabela de vínculos; índice em `(categoria, estoque_atual)` para produtos).
- Criação de triggers ou views para relatórios analíticos, caso julgue necessário.
