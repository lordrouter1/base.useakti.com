# Auditoria — Implementação Meta Cloud WhatsApp API — Akti v1

> **Data da Auditoria:** 04/04/2026  
> **Escopo:** Avaliação do estado atual, viabilidade de integração, valor de negócio e roadmap  
> **Auditor:** Auditoria Automatizada via Análise Estática de Código  

---

## Resumo Executivo

O sistema Akti **não possui integração com a Meta Cloud WhatsApp API**. O WhatsApp é utilizado exclusivamente via **links manuais `wa.me/`** (8 pontos na interface) que abrem o WhatsApp Web para o operador digitar/enviar mensagens manualmente.

A implementação da API oficial da Meta transformaria esses 8 pontos manuais em **comunicação automatizada server-side**, e habilitaria **12+ novos casos de uso** que hoje simplesmente não existem no sistema.

### Impacto Projetado

| Métrica | Antes | Após Implementação |
|---------|-------|--------------------|
| Pontos de comunicação WhatsApp | 8 (manuais) | 20+ (automatizados) |
| Mensagens requerendo ação manual | 100% | ~15% (apenas conversas) |
| Eventos do sistema que notificam cliente | 0 | 12+ |
| Tempo médio de resposta ao cliente | Depende do operador | < 5 segundos (automático) |
| Taxa de abertura de comunicados | ~20% (email) | ~90%+ (WhatsApp) |

---

## Índice de Documentos

| # | Arquivo | Conteúdo |
|---|---------|----------|
| 00 | [README.md](README.md) | Este documento — resumo executivo |
| 01 | [01_ESTADO_ATUAL.md](01_ESTADO_ATUAL.md) | Diagnóstico completo do estado atual |
| 02 | [02_VALOR_DE_NEGOCIO.md](02_VALOR_DE_NEGOCIO.md) | Quanto agrega, como o cliente usa, vantagens |
| 03 | [03_ARQUITETURA_TECNICA.md](03_ARQUITETURA_TECNICA.md) | Arquitetura proposta e design técnico |
| 04 | [04_ROADMAP_IMPLEMENTACAO.md](04_ROADMAP_IMPLEMENTACAO.md) | Plano de implementação em fases |

---

## Quick Facts

| Item | Detalhe |
|------|---------|
| API Alvo | Meta Cloud API (graph.facebook.com) v21.0+ |
| Tipo de Conta | WhatsApp Business Account (WABA) |
| Custo da API | Grátis para mensagens de resposta (24h); Cobrado por template message |
| Dependência PHP | Nenhuma lib obrigatória (HTTP nativo com cURL) |
| Dependência Node.js | Webhook receiver no Express (já existente) |
| Módulos do Akti impactados | Pipeline, Pedidos, Financeiro, Portal, Email Marketing, Catálogos, NF-e |
| Multi-tenant | Cada tenant configura seu WABA/token independente |
