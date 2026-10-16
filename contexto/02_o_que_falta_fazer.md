# 📝 02. O Que Falta Fazer / Backlog de Próximos Passos

Este documento detalha as pendências técnicas, tarefas em aberto e oportunidades de evolução do **Sistema de Gestão Social e Assistencial** para que você ou outro desenvolvedor possa dar continuidade direta ao trabalho em outra máquina.

---

## 1. Pendências Imediatas de Infraestrutura

- [ ] **Configurar as Credenciais do PostgreSQL Local:**
  - O sistema já possui o script automatizado `scripts/setup_database.php`.
  - Na máquina onde o PostgreSQL estiver rodando, basta executar:
    ```bash
    php scripts/setup_database.php [sua_senha_do_postgres]
    ```
    Isso criará a base `gestao_social`, executará os 3 scripts SQL em ordem e gerará o arquivo `.env`.
  - Uma vez configurado, o sistema passa automaticamente a salvar tudo no PostgreSQL.

---

## 2. Funcionalidades Recomendadas para Expansão

### 📄 A. Geração de Documentos em PDF Físico
- **Situação Atual:** Relatórios podem ser exportados em **CSV** e as páginas possuem formatação para impressão em folha A4 via navegador (`window.print()` e `@media print`).
- **O que implementar:**
  - Integrar uma biblioteca como **Dompdf** ou script nativo para gerar PDFs binários com cabeçalho oficial do município/instituição.
  - Modelos prioritários para PDF:
    - **Termo de Declaração e Recebimento de Benefício** (com campo para assinatura física ou digital do beneficiário).
    - **Ficha de Prontuário Socioassistencial Completo** com QR Code para validação de autenticidade.
    - **Recibo de Doação Financeira** oficial para dedução ou prestação de contas.

### 📎 B. Upload Seguro de Documentos e Laudos
- **Situação Atual:** Os dados de documentos (número de RG, CPF, NIS) são registrados em texto estruturado.
- **O que implementar:**
  - Módulo de anexação de arquivos (PDF, JPG, PNG) para prontuários:
    - Comprovantes de residência e renda.
    - Laudos médicos e diagnósticos para comprovação de deficiência (PCD).
    - Termos de guarda ou decisões judiciais de acolhimento.
  - **Requisito de Segurança & LGPD:** Os arquivos devem ser salvos fora do diretório público (`storage/documentos/`), com nomes hash aleatórios (SHA-256), servidos apenas via endpoint autenticado com verificação de perfil (`/documentos/{id}/download`).

### 🔐 C. Autenticação de Dois Fatores (2FA / TOTP)
- **Situação Atual:** Autenticação por e-mail e senha com hash bcrypt.
- **O que implementar:**
  - Adicionar suporte a 2FA baseado em tempo (TOTP — Google Authenticator / Microsoft Authenticator), tornando obrigatório para operadores com perfil `Admin` e `AssistenteSocial` que manipulam pareceres sigilosos e relatórios de vulnerabilidade.

### 📲 D. Mensageria e Notificações aos Cidadãos
- **Situação Atual:** Agendamentos e convocações são registrados no sistema, mas exigem contato telefônico manual.
- **O que implementar:**
  - Integração com provedor de mensageria (API de WhatsApp Business ou SMS) para envio de avisos automatizados:
    - Confirmação e lembrete de atendimento social no dia anterior.
    - Aviso de disponibilidade de cesta básica ou benefício para retirada.
    - Convocação de participantes para início de oficinas e cursos.

### 🛡️ E. Ferramenta de Anonimização e Descarte (LGPD Art. 18)
- **Situação Atual:** Os relatórios já possuem mascaramento (`maskCpf`, `anonymizeName`) e a trilha de auditoria expurga dados sensíveis automaticamente.
- **O que implementar:**
  - Criar funcionalidade administrativa para atendimento a solicitações de titulares (Direito ao Esquecimento / Anonimização após o prazo legal de retenção do SUAS).
  - Rotina de pseudonimização permanente de registros inativos há mais de 5 anos.

### 🚀 F. Automação CI/CD no GitHub
- **Situação Atual:** O repositório git está configurado e rastreado no GitHub.
- **O que implementar:**
  - Adicionar arquivo `.github/workflows/ci.yml` para rodar automaticamente a cada `push`:
    - `php tests/check_syntax.php`
    - `php tests/run_tests.php`
    - `php tests/test_database_repositories.php`
