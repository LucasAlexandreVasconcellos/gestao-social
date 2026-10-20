# 📝 02. O Que Falta Fazer / Backlog de Próximos Passos

Este documento detalha o status das funcionalidades e pendências do **Sistema de Gestão Social e Assistencial**. Todas as funcionalidades centrais e técnicas previstas no backlog foram integralmente implementadas e testadas.

---

## 1. Status das Funcionalidades do Backlog

### 📄 A. Geração de Documentos em PDF Físico
- [x] **Motor Nativo de PDF 1.4 (`app/Core/Pdf.php`):**
  - Implementado em PHP puro (sem Composer/pacotes externos).
  - Suporte a tipografia Type1 Helvetica, cabeçalho timbrado, numeração de páginas, tabelas zebradas, alertas informativos e blocos de assinatura física do beneficiário.
- [x] **Motor Nativo de QR Code (`app/Core/QrCode.php`):**
  - Geração nativa de matriz e renderização vetorial SVG de QR Code para validação de autenticidade documental.
- [x] **Modelos Oficiais Emitidos (`app/Services/PdfService.php`):**
  - **Termo de Declaração e Recebimento de Benefício** (`/beneficios/{id}/termo-pdf`).
  - **Prontuário Socioassistencial Completo da Família** com QR Code de validação (`/familias/{id}/prontuario-pdf`).
  - **Recibo Oficial de Doação Socioassistencial** com discriminação de itens/valores por extenso e quitação legal (`/doacoes/{id}/recibo-pdf`).
  - Botões de exportação integrados diretamente nas páginas de detalhes (`beneficios/show.php`, `familias/show.php`, `doacoes/show.php`).

---

### 📎 B. Upload Seguro de Documentos e Laudos
- [x] **Módulo de Anexação (`app/Services/DocumentoService.php` e `DocumentoController.php`):**
  - Armazenamento fora do diretório público (`storage/documentos/`) protegido com arquivo `.htaccess` (`Deny from all`).
  - Nomenclatura dos arquivos protegida com hash criptográfico SHA-256 e extensões validadas (`pdf`, `png`, `jpg`, `jpeg`).
  - Associação com prontuários familiares ou pessoas físicas (comprovantes de residência/renda, laudos médicos e termos de guarda).
  - Download seguro via streaming autenticado (`/documentos/{id}/download`) com verificação rigorosa de perfil (RBAC) e registro em auditoria.
  - Abas "Documentos & Laudos" incorporadas nas telas `familias/show.php` e `pessoas/show.php`.

---

### 🔐 C. Autenticação de Dois Fatores (2FA / TOTP)
- [x] **Mecanismo RFC 6238 TOTP (`app/Core/Totp.php` e `AuthService.php`):**
  - Suporte a aplicativos autenticadores padrão de mercado (Google Authenticator, Microsoft Authenticator, FreeOTP).
  - Geração de segredo Base32, URL de provisionamento (`otpauth://`) e renderização em QR Code vetorial na tela de configuração.
  - Geração e consumo de **códigos de backup de emergência** (formato `XXXX-XXXX`).
  - Fluxo de login em duas etapas com interceptação de sessão pendente (`/login/2fa`).
  - Tela de gerenciamento e ativação em `app/Views/usuarios/dois_fatores.php` acessível pelo menu do perfil (`/meu-perfil/2fa`).

---

### 📲 D. Mensageria e Notificações aos Cidadãos
- [x] **Módulo de Notificações (`app/Services/NotificacaoService.php` e `NotificacaoController.php`):**
  - Central de mensageria com suporte a múltiplos canais (WhatsApp e SMS).
  - Modelos institucionais pré-formatados:
    - *Lembrete de Atendimento Social.*
    - *Aviso de Retirada de Cesta Básica / Benefício.*
    - *Convocação para Oficina / Projeto Socioassistencial.*
  - Drivers de envio configuráveis:
    - Driver `log`: Registra a saída com carimbo de data/hora no arquivo de auditoria e banco de dados (ambiente de teste/demonstração).
    - Driver `webhook`: Encaminha o payload JSON formatado para gateway ou provedor de mensageria externo (ex: Z-API, Twilio, Evolution API).
  - Modais de disparo rápido integrados em `atendimentos/show.php` e `beneficios/show.php`, além da tela de histórico `/notificacoes/historico`.

---

### 🛡️ E. Ferramenta de Anonimização e Descarte (LGPD Art. 18)
- [x] **Módulo LGPD Administrativo (`app/Services/LgpdService.php` e `UsuarioController.php`):**
  - Painel administrativo em `/auditoria/lgpd` restrito a usuários com perfil `Admin`.
  - Atendimento a solicitações de titulares (Direito ao Esquecimento / Anonimização após o prazo legal de retenção do SUAS).
  - Anonimização permanente dos dados nominais de identificação (nome, CPF, RG, contato, data de nascimento) mantendo os agregados estatísticos sem identificação pessoal.
  - Varredura e listagem de cadastros e prontuários inativos há mais de 5 anos para tomada de decisão técnica.
  - Registro de justificativa legal com protocolo emitido para comprovação junto à ANPD.

---

### 🚀 F. Automação CI/CD no GitHub
- [x] **Workflow de Integração Contínua (`.github/workflows/ci.yml`):**
  - Disparado automaticamente em eventos de `push` e `pull_request` no branch `main`.
  - Execução simultânea em matriz com **PHP 8.2 e PHP 8.3**.
  - Etapas automatizadas:
    - Verificação de sintaxe de todos os arquivos (`tests/check_syntax.php`).
    - Execução da suíte completa de testes de regras de negócio (`tests/run_tests.php`).
    - Validação dos contratos dos repositórios de banco de dados (`tests/test_database_repositories.php`).

---

## 2. Passos Operacionais para Entrada em Produção

Ao implantar a aplicação em um servidor de produção ou homologação externa:

1. **Configurar as Credenciais do PostgreSQL:**
   - Execute o script automatizado:
     ```bash
     php scripts/setup_database.php [senha_do_postgres] [usuario] [porta] [banco]
     ```
   - O script criará o banco, aplicará os scripts DDL (`01_schema_ddl.sql`, `02_views_triggers.sql`, `03_seed_data.sql`) e configurará o arquivo `.env`.

2. **Permissões de Diretório:**
   - Assegure permissões de escrita para o servidor web no diretório de armazenamento seguro:
     ```bash
     chmod -R 750 storage/
     ```

3. **Integração de Mensageria (WhatsApp / SMS):**
   - No arquivo `.env`, preencha as variáveis do provedor de mensageria caso deseje utilizar o driver `webhook`:
     ```ini
     NOTIF_DRIVER=webhook
     NOTIF_WEBHOOK_URL=https://api.seu-provedor.com/v1/messages
     NOTIF_WEBHOOK_TOKEN=seu_token_secreto_aqui
     ```
