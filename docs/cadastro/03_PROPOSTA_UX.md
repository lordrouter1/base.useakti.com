# 🎨 Proposta de UX/UI — Cadastro de Clientes (Design Minimalista Avançado)

> **Data:** 27/03/2026  
> **Filosofia:** "Less is More" — Minimalismo funcional com alta usabilidade  
> **Referências visuais:** Linear App, Stripe Dashboard, Notion, Vercel

---

## 1. Princípios de Design

### 1.1 Fundamentos do Minimalismo Avançado

| Princípio              | Aplicação no Cadastro                                           |
|------------------------|-----------------------------------------------------------------|
| **Espaço negativo**    | Generoso padding/margin entre seções. Formulário respira.        |
| **Hierarquia visual**  | Títulos claros, labels discretos, campos em destaque.            |
| **Redução cognitiva**  | Mostrar apenas o necessário; campos avançados sob demanda.       |
| **Feedback imediato**  | Validação em tempo real com micro-animações sutis.               |
| **Consistência**       | Padrão uniforme de cores, espaçamentos e tipografia.             |
| **Acessibilidade**     | Contraste adequado, labels semânticos, navegação por teclado.    |

### 1.2 Paleta de Cores Sugerida (Minimalista)

```
Primária:         #3498db (Azul Akti)
Sucesso:          #27ae60 (Verde suave)
Erro:             #e74c3c (Vermelho suave)
Alerta:           #f39c12 (Âmbar)
Background:       #f8f9fb (Cinza ultra-claro)
Card Background:  #ffffff (Branco puro)
Texto Primário:   #1a1a2e (Quase preto)
Texto Secundário: #6c757d (Cinza médio)
Borda sutil:      #e9ecef (Cinza claro)
Focus ring:       rgba(52, 152, 219, 0.25)
```

---

## 2. Novo Layout do Formulário — Wizard Multi-Step

### 2.1 Conceito: Stepper Horizontal Minimalista

Em vez de um formulário longo com scroll, o cadastro será dividido em **4 etapas** com stepper visual no topo:

```
┌─────────────────────────────────────────────────────────────────────┐
│  ● Identificação  ─── ○ Contato  ─── ○ Endereço  ─── ○ Comercial  │
│                                                                     │
│  ┌───────────────────────────────────────────────────────────────┐  │
│  │                                                               │  │
│  │   Conteúdo do Step Ativo                                      │  │
│  │                                                               │  │
│  └───────────────────────────────────────────────────────────────┘  │
│                                                                     │
│                              [Anterior]  [Próximo →]                │
└─────────────────────────────────────────────────────────────────────┘
```

### 2.2 Detalhamento dos Steps

#### **Step 1 — Identificação**
- Seletor PF/PJ (toggle pill elegante)
- Foto com drag & drop (circunferência com borda pontilhada)
- Campos condicionais (mudam conforme PF/PJ selecionado)
- Auto-preenchimento por CNPJ (consulta automática)

#### **Step 2 — Contato**
- E-mail principal + secundário
- Telefone fixo + celular/WhatsApp + comercial
- Website + Instagram
- Contato principal (PJ): nome + cargo

#### **Step 3 — Endereço**
- CEP com auto-preenchimento (ViaCEP)
- Campos que preenchem automaticamente: rua, bairro, cidade, estado
- Mapa miniatura (opcional, Google Maps embed)
- Checkbox "Mesmo endereço para entrega"

#### **Step 4 — Comercial**
- Tabela de preço
- Condição de pagamento
- Limite de crédito
- Vendedor responsável
- Origem do cliente
- Tags
- Observações

### 2.3 Alternativa: Formulário Single-Page com Acordeão

Para quem prefere não usar wizard, uma alternativa minimalista:

```
┌─────────────────────────────────────────────────────────┐
│  ▼ Identificação                        [✓ completo]    │
│    ┌─────────────────────────────────────────────────┐  │
│    │  Campos do bloco                                │  │
│    └─────────────────────────────────────────────────┘  │
│                                                         │
│  ► Contato                              [○ pendente]    │
│  ► Endereço                             [○ pendente]    │
│  ► Comercial                            [○ pendente]    │
└─────────────────────────────────────────────────────────┘
```

---

## 3. Componentes de UX Específicos

### 3.1 Seletor PF/PJ — Toggle Pill

```
┌─────────────────────────────────────────┐
│  ┌──────────────┐ ┌──────────────────┐  │
│  │ 👤 Pessoa    │ │ 🏢 Pessoa        │  │
│  │   Física     │ │   Jurídica       │  │
│  │  (ativo)     │ │                  │  │
│  └──────────────┘ └──────────────────┘  │
└─────────────────────────────────────────┘
```

**Comportamento:**
- Ao clicar em PJ, os labels mudam dinamicamente:
  - "Nome completo" → "Razão Social"
  - "CPF" → "CNPJ"
  - "RG" → "Inscrição Estadual"
  - Aparecem campos: Nome Fantasia, Inscrição Municipal, Contato Principal
  - Máscara do documento muda: `000.000.000-00` → `00.000.000/0000-00`
- Transição suave com `CSS transition` (fade + slide)

### 3.2 Upload de Foto — Drag & Drop Minimalista

```
    ┌─────────────────┐
    │                 │
    │    ┌───────┐    │
    │    │  📷   │    │
    │    │       │    │
    │    └───────┘    │
    │                 │
    │  Arraste ou     │
    │  clique aqui    │
    │                 │
    └─────────────────┘
```

**Interações:**
- Borda pontilhada sutil (`border: 2px dashed #dee2e6`)
- Ao hover, borda fica azul com fundo levemente colorido
- Ao dropar imagem, preview instantâneo com crop circular
- Botão discreto "Remover foto" aparece sobre a imagem

### 3.3 Campo de CEP com Auto-preenchimento (ViaCEP)

```
┌──────────────────────────────────────────────────────┐
│  CEP                                                  │
│  ┌───────────────┐  ⟳ (loading)                      │
│  │ 01001-000     │  ✓ Encontrado!                     │
│  └───────────────┘                                    │
│                                                        │
│  Logradouro            Número      Complemento        │
│  ┌──────────────────┐  ┌──────┐   ┌──────────────┐   │
│  │ Praça da Sé      │  │      │   │              │   │
│  └──────────────────┘  └──────┘   └──────────────┘   │
│  (preenchido auto)     (foco aqui)                     │
│                                                        │
│  Bairro                Cidade          UF             │
│  ┌──────────────────┐  ┌────────────┐  ┌────┐        │
│  │ Sé               │  │ São Paulo  │  │ SP │        │
│  └──────────────────┘  └────────────┘  └────┘        │
│  (preenchido auto)     (preenchido)    (preenchido)   │
└──────────────────────────────────────────────────────┘
```

**Comportamento:**
- Ao digitar 8 dígitos no CEP, consulta automática à API ViaCEP
- Spinner discreto durante a consulta
- Campos preenchidos ficam com fundo levemente verde (#f0fdf4)
- Focus automático vai para o campo "Número" após preenchimento
- Se CEP não encontrado, borda vermelha sutil + mensagem discreta

### 3.4 Campo de CNPJ com Consulta à Receita

```
┌──────────────────────────────────────────────────────┐
│  CNPJ                                                 │
│  ┌────────────────────────┐  🔍 Consultar             │
│  │ 12.345.678/0001-99     │                           │
│  └────────────────────────┘                           │
│                                                        │
│  ✅ Empresa encontrada! Dados preenchidos              │
│  automaticamente.                                      │
└──────────────────────────────────────────────────────┘
```

**Comportamento:**
- Botão "Consultar" aparece ao lado do campo quando CNPJ é válido
- Consulta API pública (ReceitaWS, BrasilAPI, etc.)
- Preenche automaticamente: Razão Social, Nome Fantasia, Endereço, Telefone, E-mail
- Usuário pode editar os dados preenchidos

### 3.5 Validação em Tempo Real — Inline Feedback

```
  E-mail
  ┌────────────────────────────┐
  │ usuario@email              │  ← Digitando...
  └────────────────────────────┘

  E-mail                                   ✓
  ┌────────────────────────────┐
  │ usuario@email.com          │  ← Válido!
  └────────────────────────────┘
  (borda verde sutil)

  CPF                                      ✕
  ┌────────────────────────────┐
  │ 123.456.789-00             │  ← CPF inválido
  └────────────────────────────┘
  (borda vermelha + msg abaixo)
  "O CPF informado não é válido"
```

**Regras de validação client-side:**
- **CPF:** Validação de dígitos verificadores (algoritmo mod 11)
- **CNPJ:** Validação de dígitos verificadores
- **E-mail:** Regex padrão
- **CEP:** 8 dígitos numéricos
- **Telefone:** Formato (XX) XXXXX-XXXX ou (XX) XXXX-XXXX

### 3.6 Máscaras de Input (dinâmicas)

| Campo      | Máscara PF              | Máscara PJ                |
|------------|-------------------------|---------------------------|
| Documento  | `000.000.000-00`        | `00.000.000/0000-00`      |
| Telefone   | `(00) 00000-0000`       | `(00) 00000-0000`         |
| CEP        | `00000-000`             | `00000-000`               |
| RG/IE      | Livre (15 chars)        | Livre (20 chars)          |

**Biblioteca sugerida:** [IMask.js](https://imask.js.org/) — leve, sem dependências, configurável.

---

## 4. Layout Responsivo

### 4.1 Desktop (≥992px) — Layout de 2 Colunas

```
┌──────────────────────────────────────────────────────────────┐
│  ┌─────────────┐  ┌────────────────────────────────────────┐ │
│  │             │  │                                        │ │
│  │    FOTO     │  │  ┌─ Tipo: ○ PF  ● PJ ──────────────┐ │ │
│  │   Avatar    │  │  │                                   │ │ │
│  │             │  │  │  Razão Social *                    │ │ │
│  │  [alterar]  │  │  │  ┌──────────────────────────────┐ │ │ │
│  │             │  │  │  │                              │ │ │ │
│  │             │  │  │  └──────────────────────────────┘ │ │ │
│  │  ─────────  │  │  │                                   │ │ │
│  │  Status:    │  │  │  CNPJ              Nome Fantasia  │ │ │
│  │  ● Ativo    │  │  │  ┌─────────────┐  ┌───────────┐  │ │ │
│  │  ○ Inativo  │  │  │  │             │  │           │  │ │ │
│  │  ○ Bloqueado│  │  │  └─────────────┘  └───────────┘  │ │ │
│  │             │  │  │                                   │ │ │
│  └─────────────┘  │  └───────────────────────────────────┘ │ │
│                    │                                        │ │
│                    └────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────┘
```

### 4.2 Mobile (< 768px) — Layout Empilhado

```
┌──────────────────────────┐
│         FOTO             │
│        Avatar            │
│       [alterar]          │
│                          │
│  ○ PF   ● PJ            │
│                          │
│  Razão Social *          │
│  ┌────────────────────┐  │
│  │                    │  │
│  └────────────────────┘  │
│                          │
│  CNPJ                    │
│  ┌────────────────────┐  │
│  │                    │  │
│  └────────────────────┘  │
│                          │
│  Nome Fantasia           │
│  ┌────────────────────┐  │
│  │                    │  │
│  └────────────────────┘  │
│                          │
│  [← Anterior] [Próximo→] │
└──────────────────────────┘
```

---

## 5. Micro-interações e Animações

### 5.1 Transições CSS

```css
/* Transição suave entre steps */
.step-content {
    transition: opacity 0.3s ease, transform 0.3s ease;
}
.step-content.entering {
    opacity: 0;
    transform: translateX(20px);
}
.step-content.active {
    opacity: 1;
    transform: translateX(0);
}

/* Feedback de validação */
.form-control.is-valid {
    border-color: #27ae60;
    box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.1);
    transition: all 0.2s ease;
}
.form-control.is-invalid {
    border-color: #e74c3c;
    box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
    transition: all 0.2s ease;
}

/* Campos preenchidos por API (ViaCEP) */
.form-control.api-filled {
    background-color: #f0fdf4;
    border-color: #86efac;
    transition: background-color 0.5s ease;
}
```

### 5.2 Feedback de Salvamento

```
┌──────────────────────────────────┐
│  ✅ Cliente salvo com sucesso!   │
│                                  │
│  CLI-00042 — Empresa ABC Ltda    │
│                                  │
│  [Ver cadastro]  [Novo cliente]  │
└──────────────────────────────────┘
```

- Toast notification sutil no canto superior direito
- Redirecionamento automático após 2s (com opção de ficar)

---

## 6. Funcionalidades Adicionais de UX

### 6.1 Indicador de Completude do Cadastro

```
┌──────────────────────────────────────┐
│  Completude do cadastro: 75%         │
│  ████████████████░░░░░░              │
│                                      │
│  ✅ Dados básicos                    │
│  ✅ Contato                          │
│  ✅ Endereço                         │
│  ❌ Dados comerciais (opcional)      │
└──────────────────────────────────────┘
```

- Barra de progresso visual que incentiva o preenchimento completo
- Não impede o salvamento — apenas sugere completar

### 6.2 Duplicidade em Tempo Real

Ao digitar CPF/CNPJ, consulta AJAX verifica se já existe:

```
  ⚠ Já existe um cliente com este CNPJ:
  "Empresa ABC Ltda" (CLI-00015)
  [Ver cadastro existente]
```

### 6.3 Atalhos de Teclado

| Atalho       | Ação                           |
|-------------|--------------------------------|
| `Ctrl+S`    | Salvar formulário               |
| `Tab`       | Próximo campo                   |
| `Ctrl+→`   | Próximo step                    |
| `Ctrl+←`   | Step anterior                   |
| `Esc`       | Cancelar / Voltar à listagem    |

### 6.4 Auto-save (Rascunho)

- A cada 30 segundos, salva os dados preenchidos no `localStorage`
- Ao retornar ao formulário, pergunta: "Deseja continuar de onde parou?"
- Ao salvar com sucesso, limpa o rascunho

### 6.5 Campo de Tags com Autocomplete

```
  Tags
  ┌──────────────────────────────────────┐
  │ [VIP ×]  [Atacado ×]  [___________] │
  └──────────────────────────────────────┘
  Sugestões: Varejo | Indústria | Governo | Revenda
```

- Input com chips/pills
- Autocomplete com tags já existentes no banco
- Criação de novas tags digitando e pressionando Enter

---

## 7. Página de Listagem — Melhorias UX

### 7.1 Filtros Avançados (Drawer Lateral)

```
┌────────────────────────────────────────────┐
│ 🔍 Filtros Avançados              [fechar] │
│                                             │
│  Status                                     │
│  ☑ Ativo  ☑ Inativo  ☐ Bloqueado          │
│                                             │
│  Tipo de Pessoa                             │
│  ☑ Pessoa Física  ☑ Pessoa Jurídica       │
│                                             │
│  Estado (UF)                                │
│  ┌───────────────────────────────┐          │
│  │ Todos ▼                       │          │
│  └───────────────────────────────┘          │
│                                             │
│  Cidade                                     │
│  ┌───────────────────────────────┐          │
│  │ Todas ▼                       │          │
│  └───────────────────────────────┘          │
│                                             │
│  Vendedor                                   │
│  ┌───────────────────────────────┐          │
│  │ Todos ▼                       │          │
│  └───────────────────────────────┘          │
│                                             │
│  Cadastrado entre                           │
│  ┌────────────┐  ┌────────────┐             │
│  │ 01/01/2026 │  │ 27/03/2026 │             │
│  └────────────┘  └────────────┘             │
│                                             │
│  [Limpar Filtros]     [Aplicar Filtros]     │
└────────────────────────────────────────────┘
```

### 7.2 Alternância Tabela / Cards

```
  Visualização:  [≡ Lista]  [⊞ Cards]

  ── Modo Cards ──
  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐
  │  [Avatar]    │  │  [Avatar]    │  │  [Avatar]    │
  │  Maria Silva │  │  Empresa ABC │  │  João Santos │
  │  PF · Ativo  │  │  PJ · Ativo  │  │  PF · Inativo│
  │  SP · Capital│  │  RJ · Capital│  │  MG · BH     │
  │  ✉ ☎ 🌐    │  │  ✉ ☎ 🌐    │  │  ✉ ☎       │
  │  [Editar]    │  │  [Editar]    │  │  [Editar]    │
  └─────────────┘  └─────────────┘  └─────────────┘
```

### 7.3 Ações em Lote

```
  ☑ 3 selecionados     [📤 Exportar]  [🗑 Excluir]  [🔄 Alterar Status]
```

### 7.4 Exportação

```
  [📤 Exportar ▼]
    ├── CSV
    ├── Excel (XLSX)
    └── PDF (relatório)
```

---

## 8. Tela de Detalhe do Cliente (View-only)

Antes de editar, o usuário pode visualizar o cliente em um layout de ficha:

```
┌──────────────────────────────────────────────────────────────┐
│  ← Voltar     FICHA DO CLIENTE           [✏ Editar] [🗑]   │
│                                                               │
│  ┌─────────┐  Maria da Silva                                 │
│  │ Avatar  │  CPF: 123.456.789-00                             │
│  │         │  Status: ● Ativo                                 │
│  └─────────┘  Cadastrado em: 15/01/2026                       │
│               Última atualização: 20/03/2026                  │
│                                                               │
│  ┌──────────────────┬──────────────────┬──────────────────┐  │
│  │ 📦 12 Pedidos    │ 💰 R$ 15.420,00 │ 📅 Último: 20/03 │  │
│  └──────────────────┴──────────────────┴──────────────────┘  │
│                                                               │
│  ── Contato ──────────────────────────────────────────────── │
│  ✉ maria@email.com                                           │
│  📱 (11) 99999-0000                                          │
│  🌐 www.mariasilva.com.br                                    │
│                                                               │
│  ── Endereço ─────────────────────────────────────────────── │
│  Rua das Flores, 123 - Sala 5                                │
│  Centro — São Paulo / SP — 01001-000                         │
│                                                               │
│  ── Histórico de Pedidos (últimos 5) ────────────────────── │
│  #1042  15/03/2026  R$ 1.200,00  ● Entregue                 │
│  #1038  01/03/2026  R$ 2.300,00  ● Produção                 │
│  [Ver todos os pedidos →]                                     │
│                                                               │
│  ── Observações ──────────────────────────────────────────── │
│  Cliente preferencial. Sempre pede entrega expressa.         │
└──────────────────────────────────────────────────────────────┘
```

---

> **Próximo passo:** Veja o arquivo `04_PLANO_IMPLEMENTACAO.md` para o plano de implementação técnica.
