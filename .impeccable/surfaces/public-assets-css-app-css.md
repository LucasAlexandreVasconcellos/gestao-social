---
version: 1
slug: "public-assets-css-app-css"
primary_target: "public/assets/css/app.css"
related_targets: []
---

# Surface Brief — Plataforma de Gestão Social e Assistencial

- **Scope:** Toda a aplicação web (`layouts/main.php`, `layouts/auth.php`, `public/assets/css/app.css`, `dashboard/index.php` e todas as telas operacionais de módulos + nova tela pública `/validar/{tipo}/{protocolo}` e CRUD `/usuarios`).
- **Mode:** Operate (aplicação operacional diária para servidores públicos do SUAS/CRAS).
- **Audience & Job:** Assistentes sociais, recepção, almoxarifes, coordenadores, administradores e auditores cadastrando famílias, registrando avaliações/atendimentos, efetivando entregas com baixa FEFO, anexando laudos e emitindo relatórios LGPD.

## Direction contract

- **THESIS:** Substituir o visual genérico escuro-plano atual por uma estação de trabalho cívica brasileira de alta clareza ("Civic Moderno"), onde hierarquia tipográfica, sinais de vulnerabilidade/estoque semânticos e fluxo operacional por perfil eliminam atrito cognitivo sem perder sobriedade governamental.
- **OWN-WORLD:** Superfície base alabastro cívico (`#F5F7FA`), cartões brancos nítidos (`#FFFFFF`) com borda hairline (`#DDE3EC`), rail lateral azul-atlântico (`#0B2545`), azul cívico primário (`#1351B4`), esmeralda social (`#0E8A5D`), âmbar institucional (`#D97706`) e carmesim de risco (`#C81E1E`); tipografia `Plus Jakarta Sans` + `IBM Plex Mono` com `tabular-nums`, ícones SVG uniformes (sem emojis decorativos na UI) e elevação declarada uma única vez.
- **STORY:** Ao entrar no painel, o servidor identifica imediatamente pendências críticas (reavaliações vencidas, vulnerabilidade crítica, estoque abaixo do mínimo ou vencendo), enxerga apenas os módulos autorizados para o seu cargo e executa registros/auditorias com confiança de rastreabilidade real.
- **FIRST VIEWPORT:** Topbar limpa com unidade/órgão configurável, busca rápida e crachá de perfil; no Dashboard, faixa de saudação operacional + ações rápidas do perfil, grade de 4 KPIs principais e painel de **Vigilância Socioassistencial & Estoque** com barras de distribuição por vulnerabilidade e tabelas de acompanhamento recente.
- **FORM:** Shell operacional Civic Moderno com rail lateral recolhível, tabelas tabulares paginadas, abas horizontais limpas em prontuários e tela de login institucional dividida (`split civic panel`) + portal público de validação de autenticidade (`/validar`). `seed: none (pinned)` — direção explicitamente escolhida pelo usuário ("Civic Moderno inspirado em Gov.br + plataformas cívicas modernas").
- **FINISH:** unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance
