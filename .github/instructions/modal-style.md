# Padrão de Modais (SweetAlert2) — Akti

---

## Sumário
- [Regra Geral](#regra-geral)
- [1. Toast (Notificação Rápida)](#1-toast-notificação-rápida)
- [2. Confirmação Simples](#2-confirmação-simples)
- [3. Confirmação Destrutiva](#3-confirmação-destrutiva)
- [4. Confirmação com Formulário](#4-confirmação-com-formulário)
- [5. Loading com Requisição Assíncrona](#5-loading-com-requisição-assíncrona)
- [6. Resultado de Operação](#6-resultado-de-operação)
- [7. Bloqueio/Aviso de Permissão](#7-bloqueioaviso-de-permissão)
- [8. Informativo/Tutorial](#8-informativotutorial)
- [Cores dos Botões](#cores-dos-botões)
- [Anti-Padrões](#anti-padrões)

---

## Regra Geral

- **NUNCA** usar `alert()`, `confirm()` ou `prompt()` nativos.
- **SEMPRE** usar SweetAlert2 (já incluído via CDN no footer.php).
- Ícones nos botões de confirmação (`<i class="fas ..."></i>`).
- `showCancelButton: true` quando houver opção de cancelar.
- Texto do `cancelButtonText` sempre "Cancelar" ou "Fechar".
- Timer com `timerProgressBar: true` quando auto-dismiss.

---

## 1. Toast (Notificação Rápida)

Use para feedback de operações rápidas que não requerem ação do usuário.

```js
Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 1500,
    timerProgressBar: true,
    didOpen: function(toast) {
        toast.addEventListener('mouseenter', Swal.stopTimer);
        toast.addEventListener('mouseleave', Swal.resumeTimer);
    }
}).fire({
    icon: 'success', // 'success' | 'error' | 'warning' | 'info'
    title: 'Operação realizada!'
});
```

**Quando usar:**
- Após salvar com sucesso.
- Após copiar link/texto.
- Feedback de toggle (marcar/desmarcar checklist).

---

## 2. Confirmação Simples

Use para ações reversíveis que mudam estado (mover etapa, avançar).

```js
Swal.fire({
    title: 'Avançar pedido?',
    html: 'Avançar para <strong>Produção</strong>?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: '<i class="fas fa-check me-1"></i> Confirmar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#27ae60'
}).then(function(result) {
    if (result.isConfirmed) {
        // Executar ação
    }
});
```

---

## 3. Confirmação Destrutiva

Use para exclusões, cancelamentos e ações irreversíveis.

```js
Swal.fire({
    title: 'Remover item?',
    text: 'O item será removido permanentemente.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: '<i class="fas fa-trash me-1"></i> Remover',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#e74c3c'
}).then(function(result) {
    if (result.isConfirmed) {
        // Executar exclusão
    }
});
```

**Regras:**
- `confirmButtonColor: '#e74c3c'` (vermelho) obrigatório.
- Ícone de lixeira ou ban no botão de confirmação.
- Nunca auto-dismiss — exigir clique explícito.

---

## 4. Confirmação com Formulário

Use quando a confirmação requer input do usuário (motivo, justificativa).

```js
Swal.fire({
    icon: 'warning',
    title: 'Cancelar NF-e?',
    html: '<p>Esta ação é <strong>irreversível</strong>.</p>'
        + '<div class="mb-3">'
        + '<label class="form-label small fw-bold">Justificativa (mín. 15 chars)</label>'
        + '<textarea id="swalMotivo" class="form-control" rows="3" placeholder="Descreva..."></textarea>'
        + '</div>',
    showCancelButton: true,
    confirmButtonText: '<i class="fas fa-ban me-1"></i> Cancelar NF-e',
    cancelButtonText: 'Voltar',
    confirmButtonColor: '#dc3545',
    showLoaderOnConfirm: true,
    preConfirm: function() {
        var motivo = document.getElementById('swalMotivo').value.trim();
        if (motivo.length < 15) {
            Swal.showValidationMessage('Justificativa deve ter no mínimo 15 caracteres.');
            return false;
        }
        return fetch(url, { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .catch(function() { Swal.showValidationMessage('Erro de comunicação.'); });
    },
    allowOutsideClick: function() { return !Swal.isLoading(); }
}).then(function(result) {
    if (result.isConfirmed && result.value) {
        // Processar resposta
    }
});
```

**Regras:**
- Inputs dentro do HTML do Swal devem usar classes Bootstrap.
- Validação via `Swal.showValidationMessage()`.
- `showLoaderOnConfirm: true` quando há fetch.
- `allowOutsideClick: () => !Swal.isLoading()` quando há fetch.

---

## 5. Loading com Requisição Assíncrona

Use para operações demoradas com feedback de progresso.

```js
Swal.fire({
    title: '<i class="fas fa-spinner fa-spin me-2"></i>Processando...',
    html: 'Aguarde...',
    showConfirmButton: false,
    allowOutsideClick: false,
    didOpen: function() {
        // Fetch ou operação assíncrona aqui
        fetch(url)
            .then(r => r.json())
            .then(data => {
                // Atualizar ou fechar o Swal
                Swal.fire({ icon: 'success', title: 'Concluído!', timer: 1500 });
            });
    }
});
```

---

## 6. Resultado de Operação

Use para mostrar resultado de uma operação com ação subsequente.

```js
Swal.fire({
    icon: 'success',
    title: 'NF-e Autorizada!',
    html: 'Chave: <code>xxxxx</code>'
        + '<hr><p class="small mb-0"><i class="fas fa-print me-1"></i>Deseja imprimir o DANFE?</p>',
    showCancelButton: true,
    confirmButtonText: '<i class="fas fa-file-pdf me-1"></i> Imprimir DANFE',
    cancelButtonText: 'Fechar',
    confirmButtonColor: '#dc3545',
    cancelButtonColor: '#6c757d'
}).then(function(result) {
    if (result.isConfirmed) {
        window.open(danfeUrl, '_blank');
    }
    location.reload();
});
```

---

## 7. Bloqueio/Aviso de Permissão

Use quando uma ação está bloqueada por regras de negócio.

```js
Swal.fire({
    icon: 'error',
    title: '<i class="fas fa-lock me-2"></i>Ação bloqueada',
    html: '<p>Não é possível realizar esta ação porque...</p>'
        + '<p class="small text-muted mt-2">Orientação de como resolver.</p>',
    confirmButtonText: '<i class="fas fa-external-link-alt me-1"></i> Ir para...',
    showCancelButton: true,
    cancelButtonText: 'Fechar',
    confirmButtonColor: '#e74c3c'
}).then(function(result) {
    if (result.isConfirmed) {
        window.open(redirectUrl, '_blank');
    }
});
```

---

## 8. Informativo/Tutorial

Use para informações contextuais ou módulos desabilitados.

```js
Swal.fire({
    icon: 'info',
    title: 'Módulo não disponível',
    html: '<p>Este módulo está desabilitado.</p>'
        + '<p class="small text-muted">Entre em contato com o administrador.</p>',
    confirmButtonText: 'Entendi',
    confirmButtonColor: '#3498db'
});
```

---

## Cores dos Botões

| Contexto | Cor | Uso |
|----------|-----|-----|
| Sucesso/Confirmar | `#27ae60` | Avançar, salvar, concluir |
| Perigo/Destruir | `#e74c3c` / `#dc3545` | Excluir, cancelar NF-e |
| Alerta/Atenção | `#f39c12` / `#e67e22` | Retroceder, bloqueio financeiro |
| Informação | `#3498db` / `#17a2b8` | Carta correção, info, tutorial |
| Neutro | `#6c757d` | Botão "Fechar", "Cancelar" secundário |

---

## Anti-Padrões

❌ **NÃO FAZER:**
- `alert('mensagem')` → Use Toast.
- `confirm('tem certeza?')` → Use Confirmação Simples.
- `prompt('digite o valor')` → Use Confirmação com Formulário.
- Modal sem ícone no botão de confirmação.
- Swal de sucesso sem timer (bloqueia fluxo desnecessariamente).
- Dois Swal.fire consecutivos sem encadear via `.then()`.
- `Swal.fire()` dentro de `setTimeout` sem razão clara.

✅ **SEMPRE FAZER:**
- Ícone nos botões de confirmação (`<i class="fas ..."></i>`).
- `timerProgressBar: true` quando usar timer.
- `showCancelButton: true` quando houver alternativa.
- Usar `.then()` para ações pós-confirmação.
- Encadear Swal quando houver resultado + ação subsequente.

---
