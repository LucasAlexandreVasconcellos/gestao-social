# Regras de Negócio, Validações e Fluxos Operacionais

Este documento consolida todas as regras de negócio, validações no servidor e ciclos de vida de entidades implementadas no Sistema de Gestão Social e Assistencial.

---

## 1. Catálogo das 16 Regras Fundamentais de Negócio

### Regra 1: Composição Familiar Multimembro
- **Definição:** Uma família pode possuir múltiplos integrantes vinculados (cônjuge, filhos, idosos, dependentes).
- **Implementação:** Classe de serviço `FamiliaService` gerencia a entidade `VinculoFamiliar`, que une `Familia` e `Pessoa`.
- **Validação:** Cada núcleo possui exatamente uma pessoa indicada como "Responsável Familiar" de referência.

### Regra 2: Preservação de Histórico de Vínculos de Pessoas
- **Definição:** Uma pessoa pode pertencer a diferentes composições familiares ao longo de sua vida (ex: separações, emancipação de filhos, acolhimento de parentes), sem perder o registro de seus vínculos passados.
- **Implementação:** Ao desvincular um indivíduo de uma família em `FamiliaService::desvincularIntegrante()`, o registro em `VinculoFamiliar` **NÃO é deletado**. Ele é marcado com `ativo = false`, data de término (`dataTermino`) e motivo arquivado.
- **Consulta:** O método `PessoaService::obterHistoricoVinculos()` lista todos os vínculos (atuais e históricos) da pessoa.

### Regra 3: Histórico Preservado de Avaliações Sociais
- **Definição:** Cada nova avaliação socioassistencial realizada pela assistente social gera um novo registro histórico imutável para a família.
- **Implementação:** `AvaliacaoService::registrar()` adiciona uma nova `AvaliacaoSocial`. O histórico completo da família é consultado na ficha consolidada da família (`FamiliaService::obterFichaCompleta()`), permitindo comparar a evolução do núcleo vulnerável ao longo do tempo.

### Regra 4: Rastreabilidade de Alterações Cadastrais
- **Definição:** Alterações cadastrais relevantes de endereço, renda familiar ou situação de moradia mantêm uma linha do tempo das modificações.
- **Implementação:** A entidade `Familia` mantém a propriedade `historicoAlteracoes`, acrescida a cada chamada de `FamiliaService::atualizar()`.

### Regra 5: Identificação nos Atendimentos
- **Definição:** Todo atendimento social ou visita domiciliar deve obrigatoriamente identificar a família receptora e, quando específico, a pessoa/membro que recebeu a escuta técnica.
- **Implementação:** Entidade `Atendimento` armazena `familiaId`, `pessoaId` (opcional), profissional técnico responsável, data e providências adotadas.

### Regra 6: Encaminhamentos Múltiplos por Atendimento
- **Definição:** Uma única sessão de atendimento pode gerar múltiplos encaminhamentos para serviços da rede pública (CRAS, CREAS, Posto de Saúde, Defensoria) ou entidades parceiras.
- **Implementação:** Entidade `Encaminhamento` é vinculada a `atendimentoId`, com serviço de destino, motivo, data e status do retorno.

### Regra 7: Benefícios com Composição de Múltiplos Itens
- **Definição:** Uma entrega de benefícios pode discriminar múltiplos produtos físicos com suas respectivas quantidades e lotes (ex: 1 Cesta Básica, 2 Pacotes de Fraldas e 1 Cobertor).
- **Implementação:** A entidade `BeneficioEntrega` possui um array de `ItemEntrega`, garantindo transparência na prestação de contas.

### Regra 8: Proibição de Entrega sem Itens e Baixa de Estoque
- **Definição:** É terminantemente proibido confirmar ou efetivar uma entrega de benefício que não possua itens e quantidades cadastradas. A baixa física de estoque ocorre única e exclusivamente quando a entrega atinge o status de "Efetivada".
- **Implementação:** `BeneficioService::registrarEntrega()` rejeita com erro qualquer tentativa de efetivação sem itens. Quando efetivada, gera automaticamente movimentações de `Saída` no almoxarifado via `EstoqueService`.

### Regra 9: Segregação e Entrada de Doações de Produtos
- **Definição:** Doações financeiras (recursos monetários) e doações em espécie (produtos) são segregadas conceitualmente. Doações de produtos devem gerar entradas no estoque.
- **Implementação:** `DoacaoService::registrar()` trata o tipo `Financeira` sem demandar almoxarifado e o tipo `Produtos` gerando movimentações de `Entrada` nos itens correspondentes.

### Regra 10: Diferenciação no Ciclo de Projetos Sociais
- **Definição:** Em campanhas coletivas, oficinas e cursos, o sistema diferencia formalmente beneficiários em quatro estados: **Inscritos**, **Convocados**, **Efetivamente Atendidos** e **Ausentes**.
- **Implementação:** Entidade `ParticipanteProjeto` registra o status atual e a data de presença/conclusão através do método `ProjetoService::atualizarStatusParticipante()`.

### Regra 11: Inativação / Cancelamento em Vez de Exclusão Destrutiva
- **Definição:** Registros que possuem movimentações associadas (entregas de cestas, movimentações de estoque, atendimentos registrados) não podem ser excluídos definitivamente para não corromper o histórico fiscal e institucional.
- **Implementação:** Ações de cancelamento marcam a situação como `Cancelada`, `Suspensa` ou `Encerrada`, registrando a justificativa técnica.

### Regra 12: Classificação de Vulnerabilidade Orientativa e Revisável
- **Definição:** A pontuação algorítmica de vulnerabilidade social é uma ferramenta de apoio/sugestão e **NUNCA substitui o parecer técnico soberano do profissional de Serviço Social**.
- **Implementação:** O método `AvaliacaoService::calcularSugestaoVulnerabilidade()` fornece uma recomendação preliminar, mas o formulário permite à assistente social definir o nível técnico oficial (`Baixa`, `Média`, `Alta`, `Crítica`) e registrar sua justificativa profissional.

### Regra 13: Opcionalidade de CPF para Pessoas em Vulnerabilidade
- **Definição:** O sistema socioassistencial atende cidadãos em situação de extrema exclusão ou primeira infância que podem não possuir CPF no momento do acolhimento.
- **Implementação:** O campo `cpf` na entidade `Pessoa` é **opcional**. Quando preenchido, é submetido à validação matemática estrita dos dois dígitos verificadores (Módulo 11) em `FormValidator::cpf()`.

### Regra 14: Restrição a Dados Sensíveis e LGPD
- **Definição:** Pareceres sociais técnicos, relatos de violência ou vulnerabilidades críticas possuem restrição de visualização, não sendo visíveis para perfis operacionais básicos (como Almoxarifado ou Consulta).
- **Implementação:** Protegido pelo método `Usuario::can('avaliacoes.sensitive')` e `RoleMiddleware`.

### Regra 15: Transparência e Honestidade nos Indicadores
- **Definição:** Nenhum dado fictício pode ser mascarado como estatística institucional real.
- **Implementação:** Todas as telas de dashboard, relatórios e cabeçalhos possuem alertas explícitos de que operam com dados demonstrativos em memória aguardando a conexão com o banco de dados.

### Regra 16: Transparência quanto à Persistência em Memória
- **Definição:** Nenhuma mensagem do sistema deve afirmar ao usuário que gravou permanentemente em disco/banco quando a operação estiver retida na sessão PHP.
- **Implementação:** Notificações do sistema esclarecem que a operação foi executada na sessão demonstrativa local.

---

## 2. Validações no Servidor (`FormValidator`)

Todos os formulários submetidos ao sistema passam por validações reutilizáveis no servidor:

1. **Obrigatoriedade (`required`):** Garante que campos vitais (nome, logradouro, número, bairro, data, responsabilidade) não sejam enviados em branco.
2. **Comprimento Mínimo (`minLength`):** Garante integridade textual com suporte a UTF-8 e fallback em ambientes sem extensão `mbstring`.
3. **Formato e Validade de CPF (`cpf`):**
   - Rejeita sequências de 11 dígitos repetidos (ex: `111.111.111-11`).
   - Calcula os dois dígitos verificadores (D1 e D2) multiplicando pelas bases regressivas de 10 e 11.
   - Trata o campo como opcional quando não informado.
4. **Formato de CEP (`cep`):** Valida exatamente 8 dígitos numéricos, aceitando máscara `00000-000`.
5. **Datas (`date`):** Valida coerência cronológica de formatos `YYYY-MM-DD` com `strtotime()`.
6. **Valores Monetários (`numeric`, `min`):** Converte vírgula decimal brasileira para ponto e valida números positivos.
7. **Listas Fechadas (`inList`):** Valida se opções de `select` ou `radio` pertencem às opções institucionais autorizadas (prevenindo manipulação de payload HTTP).
