## Módulo: Grades de Categorias e Subcategorias (Herança)

### Conceito
Categorias e subcategorias podem ter grades padrão definidas. Ao criar ou editar um produto, se a subcategoria selecionada possui grades, estas são oferecidas para importação automática. Caso a subcategoria não tenha grades, o sistema verifica a categoria. A subcategoria sempre tem prioridade sobre a categoria.

### Tabelas no Banco de Dados
- `category_grades` — Grades vinculadas a uma categoria (mesma estrutura de product_grades)
- `category_grade_values` — Valores de cada grade de categoria
- `category_grade_combinations` — Combinações de grades de categoria (com controle de ativação)
- `subcategory_grades` — Grades vinculadas a uma subcategoria
- `subcategory_grade_values` — Valores de cada grade de subcategoria
- `subcategory_grade_combinations` — Combinações de grades de subcategoria (com controle de ativação)

### Regras de Negócio — Herança
1. Ao selecionar uma **subcategoria** no formulário de produto:
   - Se a subcategoria tem grades → oferece importação das grades da subcategoria
   - Se a subcategoria NÃO tem grades → verifica a categoria
2. Ao selecionar uma **categoria** sem subcategoria:
   - Se a categoria tem grades → oferece importação das grades da categoria
3. O botão "Importar Grades" aparece automaticamente quando grades herdáveis são detectadas
4. O usuário pode optar por importar ou configurar manualmente
5. Após importação, o produto é dono das suas grades (pode editar independentemente)

### Regras de Negócio — Inativação de Combinações
- Combinações podem ser **ativadas/inativadas** em 3 níveis:
  - **Categoria:** define quais combinações padrão são válidas
  - **Subcategoria:** pode refinar quais combinações são válidas (sobrepõe categoria)
  - **Produto:** controle final de quais combinações estão disponíveis para venda
- Combinações inativas em qualquer nível superior são informadas durante a herança
- Toggle visual (switch) em cada combinação permite ativar/desativar
- Combinações inativas aparecem riscadas e em vermelho na interface
- No pedido, apenas combinações ativas são listadas para seleção

### Arquivos do Módulo
- `sql/category_grades.sql` — Script de migração para bancos existentes
- `sql/database.sql` — Tabelas incluídas no script principal (seção grades de categorias)
- `app/models/CategoryGrade.php` — Model com CRUD para grades de categorias/subcategorias + lógica de herança
- `app/views/categories/_grades_partial.php` — Partial view reutilizada nos forms de categoria e subcategoria
- `app/controllers/CategoryController.php` — Actions de grade: store/update incluem grades, AJAX para herança

### Actions AJAX (`?page=categories`)
- `getInheritedGrades` — GET: retorna grades herdáveis (subcategory_id e/ou category_id)
- `toggleCategoryCombination` — POST: ativa/desativa combinação de categoria
- `toggleSubcategoryCombination` — POST: ativa/desativa combinação de subcategoria