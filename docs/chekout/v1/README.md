# Checkout Transparente — Índice de Documentação v1

> Documentação completa do módulo de Checkout Transparente do sistema Akti.

---

## Documentos

| # | Arquivo | Descrição |
|---|---------|-----------|
| 1 | [ROADMAP.md](ROADMAP.md) | Roadmap completo com visão geral, arquitetura, fases de implementação e checklists |
| 2 | [DATABASE.md](DATABASE.md) | Especificação de banco de dados (tabelas, campos, índices, queries) |
| 3 | [API.md](API.md) | Especificação de endpoints HTTP (rotas, parâmetros, respostas, diagramas de sequência) |
| 4 | [FRONTEND.md](FRONTEND.md) | Especificação de frontend (views, JavaScript, CSS, SDKs dos gateways) |
| 5 | [SECURITY.md](SECURITY.md) | Análise de ameaças, controles de segurança, PCI compliance e checklists |

---

## Resumo Executivo

### O que é?
Página pública de checkout hospedada no domínio do tenant que processa pagamentos via gateway padrão (PIX, Cartão, Boleto) sem redirecionar o cliente para checkout externo.

### Por que?
- Preserva identidade visual do tenant
- Oferece múltiplos métodos na mesma página
- Maior controle sobre UX e analytics
- Independência visual dos gateways

### Como funciona?
1. Operador gera link de checkout transparente (token seguro de 256 bits)
2. Cliente acessa URL pública: `/?page=checkout&token=xxx`
3. Escolhe método (PIX/Cartão/Boleto) e paga
4. Pagamento processado via API do gateway
5. Webhook confirma e atualiza pedido/parcela

### Arquivos novos necessários
- `app/controllers/CheckoutController.php`
- `app/services/CheckoutService.php`
- `app/models/CheckoutToken.php`
- `app/views/checkout/` (pay, confirmation, expired, partials)
- `assets/css/checkout.css`
- `assets/js/checkout.js`
- Migration SQL para tabela `checkout_tokens`

### 5 Fases de implementação
1. **Fundação** — Banco + Model + Service
2. **Controller + Rotas** — Endpoints e integração
3. **Frontend** — Página de checkout completa
4. **Integração Admin + Portal** — Botões e UI
5. **Webhooks + Testes** — Confirmação e qualidade

---

*Documentação v1 — 2026-04-08*
