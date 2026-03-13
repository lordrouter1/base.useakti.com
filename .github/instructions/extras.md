# Módulos Extras

- Frontend: Utilizar Bootstrap 5 para layout e jQuery para manipulação DOM/AJAX.
- UI: SweetAlert2 é obrigatório para feedbacks e confirmações. Não usar confirm() / alert() nativos do JS.
- Grades: Produtos suportam variações em múltiplas dimensões, combinadas de forma cartesiana.
- Herança: Grades de categorias são sugeridas na criação/edição e podem ser inativadas em diferentes níveis.
- Fiscal: Estruturas dedicadas para armazenamento de dados NFe (NCM, CST/CSOSN). Homologação por padrão.

## Bibliotecas e Frameworks Frontend
- **Bootstrap 5** — Layout e componentes UI
- **jQuery 3.7** — Manipulação DOM e AJAX
- **Font Awesome 6** — Ícones
- **SweetAlert2** — Alertas e confirmações visuais (substituir `confirm()` e `alert()`)
- **jQuery Mask** — Máscaras de input (CPF, telefone, CEP)

### Padrão para Feedback Visual (SweetAlert2)
- Após ações de CRUD, redirecionar com `?status=success` na URL.
- Na view de listagem, verificar `$_GET['status']` e disparar `Swal.fire()`.
- Para exclusões, usar `Swal.fire()` com confirmação antes de prosseguir.
- Nunca usar `confirm()` ou `alert()` nativo do JavaScript.

### Menu Superior (header.php)
- O **nome do usuário** sempre redireciona para o **Perfil** (`?page=profile`).
- O ícone de **engrenagem (⚙️)** redireciona para a **Gestão de Usuários** (`?page=users`) e **só aparece para admin**.
- O botão de **Sair** faz logout (`?page=login&action=logout`).
- O menu é fixo no topo (`sticky-top`) e não muda de tamanho ao selecionar itens.

### Padrão de Formulários (Create/Edit)
- Os formulários de **criação** e **edição** de cada módulo devem ser **visualmente idênticos**.
- Ambos devem usar a mesma estrutura de fieldsets, mesmos campos, mesmos labels e mesmo layout.
- A diferença é que no edit os campos vêm pré-preenchidos com `value="<?= $model['campo'] ?>"` e o form action aponta para `action=update` em vez de `action=store`.
- Formulários de edição incluem um `<input type="hidden" name="id">` com o ID do registr