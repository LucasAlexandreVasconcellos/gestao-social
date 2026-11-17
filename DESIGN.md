---
name: Gestão Social — Plataforma Socioassistencial SUAS
description: Sistema cívico governamental de gestão socioassistencial com hierarquia tipográfica Public Sans + IBM Plex Mono, rail atlântico e superfícies nítidas.
colors:
  civic-rail: "#0B2545"
  civic-rail-hover: "#13315C"
  primary: "#1351B4"
  primary-hover: "#0C3D8E"
  primary-soft: "#EEF4FF"
  success: "#0E8A5D"
  success-soft: "#EBF9F3"
  warning: "#B45309"
  warning-soft: "#FEF6E6"
  danger: "#C92A2A"
  danger-soft: "#FDF0F0"
  info: "#0E6BA8"
  info-soft: "#EEF6FC"
  bg-body: "#F4F6F9"
  bg-card: "#FFFFFF"
  bg-muted: "#EEF2F7"
  bg-subtle: "#F8FAFC"
  border: "#DCE3EC"
  border-strong: "#CBD5E1"
  text-main: "#0F1E36"
  text-secondary: "#3E4C63"
  text-muted: "#64748B"
typography:
  display:
    fontFamily: "Public Sans, -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif"
    fontSize: "1.55rem"
    fontWeight: 700
    lineHeight: 1.2
    letterSpacing: "-0.02em"
  headline:
    fontFamily: "Public Sans, -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif"
    fontSize: "1.25rem"
    fontWeight: 700
    lineHeight: 1.25
  title:
    fontFamily: "Public Sans, -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif"
    fontSize: "0.98rem"
    fontWeight: 700
    lineHeight: 1.35
  body:
    fontFamily: "Public Sans, -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif"
    fontSize: "14px"
    fontWeight: 400
    lineHeight: 1.55
  label:
    fontFamily: "IBM Plex Mono, SFMono-Regular, Consolas, monospace"
    fontSize: "0.78rem"
    fontWeight: 600
    letterSpacing: "0.04em"
rounded:
  sm: "6px"
  md: "10px"
  lg: "14px"
spacing:
  sm: "8px"
  md: "16px"
  lg: "24px"
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.bg-card}"
    rounded: "{rounded.sm}"
    padding: "8px 15px"
  button-primary-hover:
    backgroundColor: "{colors.primary-hover}"
  button-secondary:
    backgroundColor: "{colors.bg-card}"
    textColor: "{colors.text-main}"
    rounded: "{rounded.sm}"
    padding: "8px 15px"
  card-surface:
    backgroundColor: "{colors.bg-card}"
    textColor: "{colors.text-main}"
    rounded: "{rounded.md}"
    padding: "20px"
---

# Design System: Gestão Social — Plataforma Socioassistencial SUAS

## Overview

**Creative North Star: "Autoridade Cívica de Alta Legibilidade"**

O sistema visual adota o padrão **Civic Moderno**, inspirado na autoridade institucional do Gov.br combinada à ergonomia de operação contínua de equipamentos públicos (CRAS, CREAS, Secretarias Municipais de Assistência Social). A barra lateral em azul-marinho atlântico profundo (`#0B2545`) ancora a navegação por módulos e permissões RBAC sem competir com os formulários e prontuários sobre fundo alabastro (`#F4F6F9`).

Superfícies internas utilizam branco puro (`#FFFFFF`) delimitado por bordas finas de alto contraste (`1px solid #DCE3EC`), privilegiando leitura rápida de CPFs, protocolos, valores monetários e indicadores com alinhamento tabular (`tabular-nums`).

**Key Characteristics:**
- Paleta institucional de alto contraste validada para leitura contínua em estações de atendimento.
- Iconografia geométrica SVG uniforme (`1.75px` de espessura de traço) via `IconHelper`, sem emojis Unicode na interface.
- Números, protocolos, códigos HMAC e CPFs renderizados com `IBM Plex Mono` e `font-variant-numeric: tabular-nums`.
- Sem tarjas laterais espessas (`border-left: 4px`) em alertas, cartões ou pareceres.

## Colors

A paleta equilibra autoridade institucional azul-cívico (`#1351B4` e `#0B2545`) com semântica funcional discreta em superfícies claras.

### Primary
- **Atlantic Rail** (`#0B2545`): Fundo da barra lateral de navegação fixa e painéis institucionais de autenticação (`layouts/auth`).
- **Civic Blue** (`#1351B4`): Botão primário, estado ativo na navegação lateral, aba selecionada e barras territoriais.

### Neutral
- **Alabaster Ground** (`#F4F6F9`): Plano de fundo da área de trabalho.
- **Crisp Card** (`#FFFFFF`): Superfície principal de cartões, tabelas e formulários.
- **Hairline Border** (`#DCE3EC`) / **Strong Border** (`#CBD5E1`): Delimitação estrutural única de cards e campos de entrada.
- **Deep Slate Ink** (`#0F1E36`) / **Muted Ink** (`#64748B`): Tipografia principal e metadados secundários.

### Named Rules
**The Civic Contrast Rule.** Alertas e badges usam fundo suavizado (`*-soft`) em conjunto com borda uniforme de `1px` (`*-border`) e texto escuro de alto contraste — nunca texto claro sobre fundo pastel ou tarjas laterais pesadas.

## Typography

**Display & Body Font:** `Public Sans` (com fallback `-apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif`)
**Label/Mono Font:** `IBM Plex Mono` (com fallback `SFMono-Regular, Consolas, monospace`)

**Character:** Tipografia cívica desenhada para formulários governamentais em português, combinada com monoespaçamento tabular para protocolos (`BEN-YYYY-NNNN`), CPFs e códigos HMAC.

### Hierarchy
- **Display** (`700`, `1.55rem`, `line-height: 1.2`, `letter-spacing: -0.02em`): Títulos principais `.page-title`.
- **Headline** (`700`, `1.25rem`, `line-height: 1.25`): Cabeçalhos de autenticação e validação pública.
- **Title** (`700`, `0.98rem`, `line-height: 1.35`): Títulos de `.card-title`.
- **Body** (`400`, `14px`, `line-height: 1.55`): Texto padrão, tabelas e pareceres sociais.
- **Label** (`600`–`700`, `0.74rem`–`0.78rem`, `uppercase`, `letter-spacing: 0.04em`–`0.05em`): Cabeçalhos de tabela e `.detail-label`.

## Layout

Layout persistente de coluna dupla com rail fixo esquerdo (`264px`) e área principal fluida (`max-width: 1440px`, padding `26px 28px 40px`), além de topbar sticky (`64px`) exibindo órgão/unidade configuráveis e usuário autenticado. Em telas `<= 960px`, o rail recolhe para drawer off-canvas controlado por `#sidebar-toggle`.

## Elevation & Depth

Superfícies em repouso são planas com delimitação única por borda (`1px solid #DCE3EC`). Sombras são reservadas estritamente a camadas modais/portais sobrepostas (`.modal` e `.auth-shell`) e ao anel de foco acessível (`0 0 0 3px rgba(38, 112, 232, 0.25)`).

## Shapes

Raios de borda padronizados em três degraus: `6px` (`--radius-sm`) para botões, inputs e links de paginação; `10px` (`--radius-md`) para cards e barras de filtro; `14px` (`--radius-lg`) para modais e cartões de autenticação; e `99px` (pill) para badges e barras gráficas de progresso.

## Components

### Buttons
- **Shape:** Raio de `6px` (`--radius-sm`), texto `0.86rem`, peso `600`.
- **Primary:** Fundo `#1351B4`, texto branco, hover `#0C3D8E`.
- **Secondary / Outline:** Fundo `#FFFFFF`, borda `1px solid #CBD5E1`, texto `#0F1E36`.

### Cards / Containers
- **Corner Style:** `10px` (`--radius-md`), fundo `#FFFFFF`, borda `1px solid #DCE3EC`.
- **Padding:** `15px 20px` no `.card-header`, `20px` no `.card-body`.

### Inputs / Fields
- **Style:** Fundo `#FFFFFF`, borda `1px solid #CBD5E1`, raio `6px`, padding `9px 12px`.
- **Focus:** Borda `#1351B4` + anel `0 0 0 3px rgba(38, 112, 232, 0.25)`.

### Navigation
- Rail vertical `#0B2545` filtrado dinamicamente por `$can($permission)`, links de `0.88rem` com estado ativo `#1351B4` e rodapé rápido para troca de senha, 2FA e validador QR.

## Do's and Don'ts

### Do:
- **Do** usar `IconHelper::svg()` (`stroke-width: 1.75`) para toda iconografia de interface.
- **Do** aplicar `.tabular-nums` / `.mono` em quantidades de estoque, valores financeiros, datas e códigos de protocolo.

### Don't:
- **Don't** usar emojis Unicode em botões, menus, badges ou alertas.
- **Don't** adicionar tarjas coloridas laterais (`border-left: 4px`) ou combinar `box-shadow` pesado com bordas grossas em cards estáticos.

