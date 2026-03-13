# Módulos Extras

---

## Sumário
- [Frontend](#frontend)
- [UI e Feedback Visual](#ui-e-feedback-visual)
- [Grades e Herança](#grades-e-herança)
- [Fiscal](#fiscal)
- [Bibliotecas e Frameworks](#bibliotecas-e-frameworks-frontend)
- [Menu Superior](#menu-superior-headerphp)
- [Padrão de Formulários](#padrão-de-formulários-createedit)

---

## Frontend
- Utilizar Bootstrap 5 para layout.
- jQuery para manipulação DOM/AJAX.

---

## UI e Feedback Visual
- SweetAlert2 obrigatório para feedbacks e confirmações.
- Não usar confirm()/alert() nativos do JS.

---

## Grades e Herança
- Produtos suportam variações em múltiplas dimensões.
- Grades de categorias sugeridas na criação/edição, podem ser inativadas.

---

## Fiscal
- Estruturas dedicadas para dados NFe (NCM, CST/CSOSN).
- Homologação por padrão.

---

## Bibliotecas e Frameworks Frontend
- Bootstrap 5
- jQuery 3.7
- Font Awesome 6
- SweetAlert2
- jQuery Mask

---

## Menu Superior (header.php)
- Nome do usuário: redireciona para perfil.
- Ícone engrenagem: gestão de usuários (apenas admin).
- Botão sair: logout.
- Menu fixo no topo.

---

## Padrão de Formulários (Create/Edit)
- Formulários de criação e edição devem ser idênticos.
- Mesma estrutura, campos, labels e layout.
- Edit: campos pré-preenchidos, action=update.
- Edit inclui `<input type="hidden" name="id">` com o ID.

---