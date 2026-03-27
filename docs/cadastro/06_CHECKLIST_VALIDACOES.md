# ✅ Checklist de Validações — Cadastro de Clientes

> **Data:** 27/03/2026  
> **Objetivo:** Definir todas as validações necessárias (client-side e server-side)

---

## 1. Validações Server-Side (PHP — Validator)

### 1.1 Campos Obrigatórios

| Campo          | Regra                  | Mensagem de Erro                          |
|----------------|------------------------|-------------------------------------------|
| `person_type`  | `required`, `in:PF,PJ` | "Selecione o tipo de pessoa"              |
| `name`         | `required`, `max:191`  | "Nome / Razão Social é obrigatório"        |
| `document`     | `required` (config.)   | "CPF / CNPJ é obrigatório"                |

### 1.2 Validações de Formato

| Campo          | Regra                                     | Mensagem de Erro                          |
|----------------|-------------------------------------------|-------------------------------------------|
| `document` (PF)| CPF válido (11 dígitos + mod 11)          | "CPF informado é inválido"                |
| `document` (PJ)| CNPJ válido (14 dígitos + mod 11)        | "CNPJ informado é inválido"               |
| `email`        | E-mail válido (RFC 5322)                  | "E-mail informado é inválido"             |
| `email_secondary`| E-mail válido (se preenchido)           | "E-mail secundário inválido"              |
| `phone`        | Telefone (10-11 dígitos numéricos)        | "Telefone inválido"                       |
| `cellphone`    | Celular (11 dígitos, inicia com 9)        | "Celular inválido"                        |
| `zipcode`      | CEP (8 dígitos numéricos)                 | "CEP inválido"                            |
| `birth_date`   | Data válida (Y-m-d), não futura           | "Data inválida"                           |
| `credit_limit` | Decimal positivo                          | "Limite de crédito deve ser positivo"     |
| `discount_default`| Decimal 0-100                          | "Desconto deve ser entre 0% e 100%"      |
| `website`      | URL válida (se preenchido)                | "URL do site inválida"                    |

### 1.3 Validações de Negócio

| Regra                                      | Ação                                              |
|--------------------------------------------|----------------------------------------------------|
| Duplicidade de CPF/CNPJ                    | Bloquear se já existe outro cliente com mesmo doc. |
| Duplicidade de e-mail (configurável)        | Avisar, mas permitir salvar                        |
| Limite de clientes do tenant               | Bloquear se atingiu o máximo permitido             |
| Código interno sequencial                  | Gerar automaticamente (CLI-XXXXX)                  |
| Status `blocked` não pode criar pedidos     | Validar no módulo de pedidos                       |

### 1.4 Sanitização

| Campo          | Tratamento                                |
|----------------|-------------------------------------------|
| `document`     | Remover pontos, traços, barras            |
| `phone`        | Remover parênteses, espaços, traços       |
| `cellphone`    | Remover parênteses, espaços, traços       |
| `zipcode`      | Remover traço                             |
| `name`         | `trim()`, remover espaços duplos          |
| `email`        | `trim()`, `strtolower()`                  |
| Todos os campos| `htmlspecialchars()` na saída (XSS)       |

---

## 2. Validações Client-Side (JavaScript)

### 2.1 Validação em Tempo Real (On Blur/Input)

```javascript
// Pseudocódigo das validações client-side

// CPF (PF)
function isValidCPF(cpf) {
    cpf = cpf.replace(/\D/g, '');
    if (cpf.length !== 11) return false;
    if (/^(\d)\1{10}$/.test(cpf)) return false; // todos iguais
    
    // Dígito verificador 1
    let sum = 0;
    for (let i = 0; i < 9; i++) sum += parseInt(cpf[i]) * (10 - i);
    let d1 = 11 - (sum % 11);
    if (d1 >= 10) d1 = 0;
    if (parseInt(cpf[9]) !== d1) return false;
    
    // Dígito verificador 2
    sum = 0;
    for (let i = 0; i < 10; i++) sum += parseInt(cpf[i]) * (11 - i);
    let d2 = 11 - (sum % 11);
    if (d2 >= 10) d2 = 0;
    return parseInt(cpf[10]) === d2;
}

// CNPJ (PJ)
function isValidCNPJ(cnpj) {
    cnpj = cnpj.replace(/\D/g, '');
    if (cnpj.length !== 14) return false;
    if (/^(\d)\1{13}$/.test(cnpj)) return false;
    
    const weights1 = [5,4,3,2,9,8,7,6,5,4,3,2];
    const weights2 = [6,5,4,3,2,9,8,7,6,5,4,3,2];
    
    let sum = 0;
    for (let i = 0; i < 12; i++) sum += parseInt(cnpj[i]) * weights1[i];
    let d1 = sum % 11 < 2 ? 0 : 11 - (sum % 11);
    if (parseInt(cnpj[12]) !== d1) return false;
    
    sum = 0;
    for (let i = 0; i < 13; i++) sum += parseInt(cnpj[i]) * weights2[i];
    let d2 = sum % 11 < 2 ? 0 : 11 - (sum % 11);
    return parseInt(cnpj[13]) === d2;
}

// E-mail
function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

// CEP
function isValidCEP(cep) {
    return /^\d{5}-?\d{3}$/.test(cep);
}

// Telefone
function isValidPhone(phone) {
    const cleaned = phone.replace(/\D/g, '');
    return cleaned.length >= 10 && cleaned.length <= 11;
}
```

### 2.2 Feedback Visual

| Estado       | Classe CSS                     | Ícone    | Cor da Borda |
|--------------|-------------------------------|----------|--------------|
| Vazio        | (default)                     | —        | #dee2e6      |
| Digitando    | (default)                     | —        | #86b7fe      |
| Válido       | `.is-valid`                   | ✓        | #27ae60      |
| Inválido     | `.is-invalid`                 | ✕        | #e74c3c      |
| Preenchido API| `.api-filled`                | 🔄→✓    | #86efac      |

### 2.3 Feedback de Duplicidade (AJAX)

```
Evento: on blur do campo "document"
  1. Limpa dígitos não-numéricos
  2. Valida formato (CPF ou CNPJ conforme person_type)
  3. Se válido → fetch('?page=customers&action=checkDuplicate&document=XXX')
  4. Se já existe:
     - Mostra warning: "⚠ Já existe um cliente com este CPF/CNPJ"
     - Link: "[Ver cadastro existente]"
     - NÃO bloqueia o envio (apenas avisa)
  5. Se não existe:
     - Mostra sucesso: "✓ Documento disponível"
```

---

## 3. Validações por Step do Wizard

### Step 1 — Identificação

| Campo          | Obrigatório | Validação                          | Bloqueia Próximo Step? |
|----------------|:-----------:|------------------------------------|-----------------------:|
| `person_type`  | ✅          | Selecionado (PF ou PJ)            | Sim                    |
| `name`         | ✅          | Mínimo 3 chars, máximo 191        | Sim                    |
| `document`     | ✅          | CPF/CNPJ válido                   | Sim                    |
| `fantasy_name` | ❌          | Máximo 191 chars                   | Não                    |
| `rg_ie`        | ❌          | Máximo 30 chars                    | Não                    |
| `im`           | ❌          | Máximo 30 chars                    | Não                    |
| `birth_date`   | ❌          | Data válida, não futura            | Não                    |
| `gender`       | ❌          | M, F ou O (se PF)                 | Não                    |
| `photo`        | ❌          | Imagem (JPEG/PNG/GIF), max 5MB    | Não                    |

### Step 2 — Contato

| Campo             | Obrigatório | Validação                       | Bloqueia Próximo Step? |
|-------------------|:-----------:|---------------------------------|-----------------------:|
| `email`           | ❌ (recom.) | E-mail válido                   | Não                    |
| `email_secondary` | ❌          | E-mail válido                   | Não                    |
| `phone`           | ❌          | Formato telefone                | Não                    |
| `cellphone`       | ❌ (recom.) | Formato celular                 | Não                    |
| `phone_commercial`| ❌          | Formato telefone                | Não                    |
| `website`         | ❌          | URL válida                      | Não                    |
| `instagram`       | ❌          | Sem @, alfanumérico             | Não                    |
| `contact_name`    | ❌          | Máximo 100 chars                | Não                    |
| `contact_role`    | ❌          | Máximo 80 chars                 | Não                    |

### Step 3 — Endereço

| Campo                  | Obrigatório | Validação                  | Bloqueia Próximo Step? |
|------------------------|:-----------:|----------------------------|-----------------------:|
| `zipcode`              | ❌          | CEP válido (8 dígitos)     | Não                    |
| `address_street`       | ❌          | Máximo 200 chars           | Não                    |
| `address_number`       | ❌          | Máximo 20 chars            | Não                    |
| `address_complement`   | ❌          | Máximo 100 chars           | Não                    |
| `address_neighborhood` | ❌          | Máximo 100 chars           | Não                    |
| `address_city`         | ❌          | Máximo 100 chars           | Não                    |
| `address_state`        | ❌          | 2 chars, UF válida         | Não                    |

### Step 4 — Comercial

| Campo              | Obrigatório | Validação                    | Bloqueia Salvamento? |
|--------------------|:-----------:|------------------------------|-----------------------:|
| `price_table_id`   | ❌          | FK válida                    | Não                    |
| `payment_term`     | ❌          | Máximo 50 chars              | Não                    |
| `credit_limit`     | ❌          | Decimal ≥ 0                  | Não                    |
| `discount_default` | ❌          | Decimal 0-100                | Não                    |
| `seller_id`        | ❌          | FK válida                    | Não                    |
| `origin`           | ❌          | Máximo 50 chars              | Não                    |
| `tags`             | ❌          | Máximo 500 chars             | Não                    |
| `observations`     | ❌          | TEXT (sem limite prático)    | Não                    |
| `status`           | ✅          | active/inactive/blocked      | Sim                    |

---

## 4. Regras de Campos Condicionais (PF vs PJ)

### Ao selecionar PF (Pessoa Física):
- Label "Nome" → "Nome Completo"
- Label "Documento" → "CPF"
- Máscara: `000.000.000-00`
- Campo `fantasy_name` → label "Apelido" (opcional)
- Campo `rg_ie` → label "RG"
- Campo `im` → **OCULTO**
- Campo `gender` → **VISÍVEL**
- Campo `birth_date` → label "Data de Nascimento"
- Campos `contact_name` e `contact_role` → **OCULTOS**

### Ao selecionar PJ (Pessoa Jurídica):
- Label "Nome" → "Razão Social"
- Label "Documento" → "CNPJ"
- Máscara: `00.000.000/0000-00`
- Campo `fantasy_name` → label "Nome Fantasia" (recomendado)
- Campo `rg_ie` → label "Inscrição Estadual"
- Campo `im` → label "Inscrição Municipal" (**VISÍVEL**)
- Campo `gender` → **OCULTO**
- Campo `birth_date` → label "Data de Fundação"
- Campos `contact_name` e `contact_role` → **VISÍVEIS**
- Botão "🔍 Consultar CNPJ" → **VISÍVEL** ao lado do campo CNPJ

---

## 5. APIs de Integração

### 5.1 ViaCEP — Auto-preenchimento de Endereço

```
GET https://viacep.com.br/ws/{cep}/json/

Resposta:
{
    "cep": "01001-000",
    "logradouro": "Praça da Sé",
    "complemento": "lado ímpar",
    "bairro": "Sé",
    "localidade": "São Paulo",
    "uf": "SP",
    "ibge": "3550308",
    "gia": "1004",
    "ddd": "11",
    "siafi": "7107"
}

Mapeamento:
  logradouro → address_street
  bairro     → address_neighborhood
  localidade → address_city
  uf         → address_state
  ibge       → address_ibge
```

### 5.2 BrasilAPI — Consulta de CNPJ

```
GET https://brasilapi.com.br/api/cnpj/v1/{cnpj}

Resposta (parcial):
{
    "razao_social": "EMPRESA EXEMPLO LTDA",
    "nome_fantasia": "EXEMPLO",
    "cnpj": "12345678000199",
    "logradouro": "RUA EXEMPLO",
    "numero": "100",
    "complemento": "SALA 5",
    "bairro": "CENTRO",
    "municipio": "SAO PAULO",
    "uf": "SP",
    "cep": "01001000",
    "ddd_telefone_1": "1133334444",
    "email": "contato@exemplo.com.br"
}

Mapeamento:
  razao_social   → name
  nome_fantasia  → fantasy_name
  logradouro     → address_street
  numero         → address_number
  complemento    → address_complement
  bairro         → address_neighborhood
  municipio      → address_city
  uf             → address_state
  cep            → zipcode
  ddd_telefone_1 → phone
  email          → email
```

---

> **Nota:** Todas as chamadas a APIs externas devem ser feitas pelo frontend (JavaScript), sem proxy do backend, para evitar bloqueios de rate-limit no servidor. Caso necessário, implementar um proxy com cache no backend.
