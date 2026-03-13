# Módulo Grade, Categoria e Subcategoria

---

## Sumário
- [Visão Geral](#visão-geral)
- [Grades de Produto](#grades-de-produto)
- [Herança de Categorias](#herança-de-categorias)
- [Regras de Negócio](#regras-de-negócio)
- [Inativação de Combinações](#inativação-de-combinações)
- [Arquivos do Módulo](#arquivos-do-módulo)
- [Actions AJAX](#actions-ajax)

---

## Visão Geral
O módulo permite definir grades (variações) de produtos, categorias e subcategorias, com herança e sugestões automáticas.

---

## Grades de Produto
- Produtos podem ter múltiplas variações (ex: tamanho, cor, material).
- Grades são combinadas de forma cartesiana.
- Grades podem ser sugeridas por categoria ou subcategoria.

---

## Herança de Categorias
- Grades de categorias são sugeridas na criação/edição.
- Subcategoria tem prioridade sobre categoria.
- Podem ser inativadas em diferentes níveis.

---

## Regras de Negócio
1. Ao selecionar uma subcategoria:
   - Se tem grades → oferece importação das grades da subcategoria
   - Se não tem grades → verifica a categoria
2. Ao selecionar uma categoria sem subcategoria:
   - Se tem grades → oferece importação das grades da categoria
3. Botão "Importar Grades" aparece automaticamente quando grades herdáveis são detectadas
4. Usuário pode importar ou configurar manualmente
5. Após importação, o produto é dono das suas grades (pode editar independentemente)

---

## Inativação de Combinações
- Combinações podem ser ativadas/inativadas em 3 níveis:
  - Categoria
  - Subcategoria
  - Produto
- Combinações inativas aparecem riscadas e em vermelho
- No pedido, apenas combinações ativas são listadas

---

## Arquivos do Módulo
- `sql/category_grades.sql`
- `sql/database.sql`
- `app/models/CategoryGrade.php`
- `app/views/categories/_grades_partial.php`
- `app/controllers/CategoryController.php`

---

## Actions AJAX
- `getInheritedGrades`: retorna grades herdáveis
- `toggleCategoryCombination`: ativa/desativa combinação de categoria
- `toggleSubcategoryCombination`: ativa/desativa combinação de subcategoria

---