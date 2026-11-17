# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

- **Primary Users:** Servidores públicos e técnicos de órgãos municipais/estaduais de Assistência Social (Assistentes Sociais, Recepção/Atendimento, Almoxarifes de Estoque, Coordenação, Administração e Auditores/Consulta) operando em unidades como CRAS, CREAS e Secretarias de Assistência Social.
- **Secondary / External:** Cidadãos e autoridades que validam a autenticidade de termos de benefício, prontuários e recibos de doação emitidos em PDF via QR Code.

## Product Purpose

Plataforma institucional completa em PHP 8.2+ e PostgreSQL 12+ para gestão socioassistencial (SUAS/CadÚnico): cadastro unificado de famílias e cidadãos com histórico de vínculos, avaliações técnicas de vulnerabilidade, atendimentos e encaminhamentos à rede, concessão e controle de benefícios eventuais com baixa automática de estoque por lote/validade (FEFO), captação de doações, gestão de projetos/oficinas, emissão de documentos oficiais em PDF com QR Code verificável, anexação segura de laudos, notificações ao cidadão, trilha de auditoria imutável e governança LGPD (Art. 18).

## Positioning

Sistema soberano e auditável para o setor público brasileiro (zero dependências externas de Node/Composer), com persistência relacional imutável em PostgreSQL, validação criptográfica de documentos emitidos e conformidade estrutural com a LGPD (Lei nº 13.709/2018) e diretrizes do SUAS.

## Operating Context

- Uso diário em estações de trabalho de secretarias, CRAS e almoxarifados sob iluminação de escritório padrão e dispositivos móveis em visitas domiciliares.
- Fluxos críticos exigem clareza imediata de status cadastral, níveis de vulnerabilidade, saldos de estoque/lotes e conformidade legal (exibição mascarada/LGPD por padrão).
- Documentos impressos (Termos de Concessão, Prontuários, Recibos de Doação) circulam fisicamente e exigem QR Code de validação pública.

## Capabilities and Constraints

- Stack: PHP 8.2+ nativo (MVC + Services + Repository Pattern) e PostgreSQL 12+ (schema `gestao_social`), com modo Demo em memória exclusivo para testes automatizados (`APP_ENV=testing`).
- Autenticação local segura com política de senha forte, CRUD administrativo de usuários, 2FA (TOTP RFC 6238), RBAC rigoroso em todas as rotas GET/POST e proteção anti-IDOR.
- Identificação do órgão, unidade (Secretaria/CRAS) e URL pública de validação configuráveis via `.env`.
- Paginação obrigatória em listagens operacionais e trilha de auditoria ligada ao operador autenticado.

## Brand Commitments

- Direção estética confirmada pelo usuário: **Civic Moderno** (inspirado no Gov.br Design System e plataformas cívicas modernas: tipografia `Plus Jakarta Sans` + `IBM Plex Mono`, paleta azul-cívico e esmeralda vibrante, dashboard com visualizações gráficas, menu dinâmico por perfil e micro-interações limpas).

## Product Principles

1. **Segurança e Menor Privilégio por Padrão:** Nenhum atalho de homologação permanece ativo em produção; cada servidor opera estritamente no limite do seu perfil e com autoria rastreável.
2. **Transparência e Honestidade Operacional:** Indicadores, status de notificações e validações documentais refletem estritamente a realidade persistida.
3. **Privacidade e Sigilo Socioassistencial (LGPD):** Pareceres técnicos, laudos e dados nominais são protegidos por perfil e anonimizados por padrão em relatórios.
4. **Eficiência e Clareza para o Servidor Público:** Operações frequentes (acolhimento, entrega de cesta, consulta de estoque) são diretas, responsivas e legíveis sob carga real de trabalho.

## Accessibility & Inclusion

- Contraste mínimo WCAG AA (>= 4.5:1 para textos e >= 3:1 para títulos/elementos gráficos), navegação por teclado com foco visível, semântica HTML5 e respeito a `prefers-reduced-motion`.

