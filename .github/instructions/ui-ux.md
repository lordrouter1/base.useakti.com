# Diretrizes de UI/UX — Akti

---

## Sumário
- [Filosofia Geral](#filosofia-geral)
- [Princípios Fundamentais](#princípios-fundamentais)
- [Cards e Containers](#cards-e-containers)
- [Hierarquia Visual](#hierarquia-visual)
- [Botões e Ações](#botões-e-ações)
- [Cores e Semântica](#cores-e-semântica)
- [Responsividade](#responsividade)
- [Feedback e Estados](#feedback-e-estados)
- [Tipografia e Espaçamento](#tipografia-e-espaçamento)
- [Padrão CTA (Call-to-Action)](#padrão-cta-call-to-action)
- [Ícones](#ícones)
- [Mini Manual Contextual](#mini-manual-contextual)

---

## Filosofia Geral
O Akti deve ser **intuitivo, minimalista e funcional**. Cada elemento na tela precisa justificar sua presença. O sistema é usado por pessoas com **diferentes níveis de familiaridade digital** — a interface deve guiar o usuário naturalmente sem exigir treinamento.

> **Regra de ouro:** Se o usuário precisar pensar sobre como usar algo, redesenhe.

---

## Princípios Fundamentais

### 1. Minimalismo com Propósito
- Mostrar apenas o que é relevante para a etapa/contexto atual.
- Usar **progressive disclosure** (revelar detalhes sob demanda).
- Campos manuais/avançados **ocultos por padrão**, revelados via botão.

### 2. Contexto é Rei
- Cards e seções devem **aparecer apenas nas etapas relevantes** do pipeline.
- Ações devem ser **contextualmente visíveis** — ex: botão "Emitir NF-e" só aparece quando o pedido está em etapa que permite emissão.

### 3. Ação Principal Sempre Clara
- Cada card/seção deve ter **uma ação principal visualmente destacada**.
- O botão principal deve ser **grande, centralizado e com cor distinta**.
- Ações secundárias em botões menores, outline ou com ícones discretos.

### 4. Feedback Instantâneo
- Toda ação deve dar feedback visual imediato (toast, badge, animação).
- **SweetAlert2** obrigatório para confirmações e feedbacks.
- Nunca usar `alert()`, `confirm()` ou `prompt()` nativos.

---

## Cards e Containers

### Padrão de Card
```html
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header py-2" style="background: linear-gradient(135deg, ...);">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0" style="font-size:0.85rem;">
                <i class="fas fa-icon me-2"></i>Título
            </h6>
            <!-- Badges de status / botões secundários -->
        </div>
    </div>
    <div class="card-body p-3">
        <!-- Conteúdo -->
    </div>
</div>
```

### Regras de Cards
- **Sem bordas visíveis** (`border-0`) + sombra suave (`shadow-sm`).
- Headers com **gradiente sutil** (não sólido) usando `linear-gradient(135deg, cor1 0%, cor2 100%)`.
- Padding consistente: `p-3` no body, `py-2` no header.
- Título do card: `h6`, `mb-0`, `font-size: 0.85rem`.
- Ícone antes do título com `me-2`.

### Fieldsets (seções dentro do form)
- Usar `<fieldset>` com `<legend>` para seções de formulário.
- Bordas coloridas conforme semântica da seção.
- `border-radius: 8px`, `padding: 1rem 1.5rem`.

---

## Hierarquia Visual

### Níveis
1. **Cabeçalho da Página** — Nome do pedido, status global, botões de navegação.
2. **Barra de Progresso** — Etapas do pipeline (stepper visual).
3. **Ações Rápidas** — Movimentação entre etapas.
4. **Conteúdo Principal** (coluna esquerda 8/12) — Formulários, tabelas, cards contextuais.
5. **Painel Lateral** (coluna direita 4/12) — Timeline, logs, mini manual.

### Grid Layout
- Desktop: `col-lg-8` (principal) + `col-lg-4` (lateral).
- Mobile: stacked automaticamente pelo Bootstrap grid.
- Gap entre colunas: `g-4`.

---

## Botões e Ações

### Tamanhos e Uso
| Tipo | Classe | Uso |
|------|--------|-----|
| **Ação Principal** | `btn btn-{cor} btn-lg px-5` | CTA dentro de cards (Emitir NF-e, Imprimir) |
| **Ação Secundária** | `btn btn-outline-{cor} btn-sm` | Headers, ações complementares |
| **Ação Destrutiva** | `btn btn-danger` ou `btn-outline-danger` | Excluir, cancelar |
| **Micro-ação** | `btn btn-sm` + `font-size:0.7rem` | Downloads, consultas |
| **Rápida** | `btn btn-outline-secondary` | Impressão rápida, toggle |

### Padrão de Botão CTA em Cards
```html
<div class="text-center py-3" style="background: linear-gradient(135deg, #corClara 0%, #corMaisClara 100%); border-radius: 10px; border: 2px dashed #corPrincipal40;">
    <i class="fas fa-icone d-block mb-2" style="font-size: 2.2rem; color: #corPrincipal; opacity: 0.6;"></i>
    <p class="mb-1 small text-muted">Descrição curta da ação.</p>
    <button class="btn px-4 shadow-sm" style="background:#corPrincipal; color:#fff; border-radius: 10px;">
        <i class="fas fa-icone me-2"></i> Texto da Ação
    </button>
</div>
```

### Regras de Botões
- Sempre ter ícone (`<i class="fas ...">`) antes do texto.
- Ações destrutivas sempre pedem confirmação via SweetAlert2.
- Botões com estado de loading: `disabled` + spinner durante requisição.
- `border-radius: 10px` para CTAs proeminentes.

---

## Cores e Semântica

| Função | Cor | Hex | Uso |
|--------|-----|-----|-----|
| Sucesso/Positivo | Verde | `#27ae60` / `#198754` | Salvar, avançar, concluir, NF-e |
| Alerta/Atenção | Amarelo | `#f39c12` / `#e67e22` | Financeiro, pendências, warning |
| Informação | Azul | `#3498db` / `#0d6efd` | Rastreamento, links, info |
| Perigo/Destrutivo | Vermelho | `#e74c3c` / `#dc3545` | Excluir, cancelar, DANFE |
| Secundário/Neutro | Cinza | `#6c757d` | Ações secundárias, voltar |
| Fiscal/NF-e | Verde escuro | `#28a745` | Cards fiscais |
| Cupom/Recibo | Roxo | `#8e44ad` | Cupom não fiscal, gateway |
| Produção | Verde | `#27ae60` | Setores de produção |
| Preparação | Teal | `#1abc9c` | Checklist de preparo |

---

## Responsividade

- **Mobile-first**: tudo funcional em 320px.
- Botões `w-100` em mobile, `btn-sm` em desktop quando no header.
- Tabelas: sempre dentro de `table-responsive`.
- Cards de método de pagamento: `col-6` mobile, `col-sm-4` tablet, `col-lg` desktop.
- Progress bars e steppers: `overflow-x: auto` com `scrollbar-width: thin`.

---

## Feedback e Estados

### Toast (notificação sutil)
```js
Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 1500,
    timerProgressBar: true
}).fire({ icon: 'success', title: 'Mensagem curta!' });
```

### Confirmação (ação importante)
```js
Swal.fire({
    title: 'Título',
    html: 'Mensagem com <strong>HTML</strong>',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: '<i class="fas fa-check me-1"></i> Confirmar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#27ae60'
}).then(r => { if (r.isConfirmed) { /* ... */ } });
```

### Estados de Loading
- Botão: `btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Processando...';`
- Badge de sincronização: mini-badge ao lado do título do card.

---

## Tipografia e Espaçamento

- **Font:** Inter (Google Fonts), fallback sans-serif.
- **Tamanhos:**
  - Título de página: `h2` (`fs-2`)
  - Título de card: `h6`, `0.85rem`
  - Labels de formulário: `small fw-bold text-muted`
  - Informações auxiliares: `0.7rem` – `0.75rem`
  - Micro-texto (datas, IDs): `0.6rem` – `0.65rem`
- **Espaçamento:**
  - Entre seções: `mb-4`
  - Entre elementos dentro de card: `mb-2` – `mb-3`
  - Padding de card body: `p-3`

---

## Padrão CTA (Call-to-Action)

Ao criar cards com ação principal (Emitir NF-e, Imprimir Cupom, Gerar Link, etc.):

1. **Fundo suave** com gradiente dashed border.
2. **Ícone grande** (2rem+) centralizado com opacidade 0.6.
3. **Texto descritivo** curto explicando o que acontece.
4. **Botão único e proeminente** com cor sólida e sombra.
5. **Informação contextual** abaixo do botão (valor, cliente, etc.) em fonte pequena.

---

## Ícones

- **Biblioteca:** Font Awesome 6 (prefixo `fas` para sólidos, `fab` para brands).
- Sempre usar ícones nos botões, títulos de cards e labels de status.
- Ícones de status:
  - ✅ `fa-check-circle` — Sucesso
  - ⏳ `fa-clock` — Pendente
  - ❌ `fa-times-circle` — Erro/Rejeitado
  - 🔒 `fa-lock` — Bloqueado
  - ⚠️ `fa-exclamation-triangle` — Alerta
  - 🔄 `fa-spinner fa-spin` — Carregando

---

## Mini Manual Contextual

O painel lateral (coluna direita) pode conter um card de **mini manual** que muda conforme a etapa:

```html
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom p-3">
        <h6 class="mb-0 text-info fw-bold"><i class="fas fa-lightbulb me-2"></i>Dica</h6>
    </div>
    <div class="card-body p-3">
        <p class="small text-muted mb-0">Texto de ajuda contextual...</p>
    </div>
</div>
```

- Conteúdo baseado no `$currentStage`.
- Tom amigável, direto e útil.
- Pode incluir links para documentação ou ações sugeridas.

---
