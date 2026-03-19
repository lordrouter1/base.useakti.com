# Módulo: Pipeline

---

## Sumário
- [Visão Geral](#visão-geral)
- [Etapas do Pipeline](#etapas-do-pipeline)
- [Tabelas do Banco](#tabelas-do-banco)
- [Regras de Negócio](#regras-de-negócio)
- [Arquivos do Módulo](#arquivos-do-módulo)
- [Visibilidade dos Cards](#regras-de-visibilidade-dos-cards-no-detalhe-do-pipeline)

---

## Visão Geral
Módulo Kanban: fluxo de pedidos desde contato até conclusão financeira.

---

## Etapas do Pipeline
1. Contato
2. Orçamento
3. Venda
4. Produção
5. Preparação
6. Envio/Entrega
7. Financeiro
8. Concluído

---

## Tabelas do Banco
- `orders`: colunas para estágio, prazos, notas, responsável, pagamento, envio.
- `pipeline_history`: histórico de movimentação.
- `pipeline_stage_goals`: metas de tempo por etapa.

---

## Regras de Negócio
- Pedido entra na etapa "Contato" ao criar.
- Movimentação entre etapas registra histórico.
- Pedidos atrasados recebem alerta visual.
- Prioridade, responsável, prazo e notas internas.
- Financeiro e envio gerenciados no detalhe.
- Parcelas pagas bloqueiam retorno a etapas anteriores e alterações.

---

## Arquivos do Módulo
- `app/models/Pipeline.php`
- `app/controllers/PipelineController.php`
- `app/views/pipeline/index.php`
- `app/views/pipeline/detail.php`
- `app/views/pipeline/settings.php`

---

## Regras de Visibilidade dos Cards no Detalhe do Pipeline
- Cada card só aparece nas etapas relevantes.
- Controle de produção: visível apenas em "produção".
- Produtos/orçamento: visível em etapas iniciais.
- Envio: visível em "envio".
- Financeiro: visível em "venda", "financeiro", "concluído".
- NF-e e Cupom Não Fiscal: visível nas etapas "venda", "financeiro", "concluído".
- Mini Manual Contextual: sempre visível no topo da coluna direita, conteúdo muda conforme a etapa.

---

## Layout do Detalhe do Pipeline

### Estrutura Principal
- **Coluna esquerda (col-lg-8):** Formulário principal com dados do cliente, produtos, produção, financeiro, envio.
- **Coluna direita (col-lg-4):** Mini manual contextual, histórico de movimentação, registro de logs.

### Mini Manual Contextual (Sidebar)
- Card fixo no topo da coluna direita com dicas contextuais baseadas na etapa atual.
- Mostra orientações específicas para guiar o usuário no fluxo do pedido.
- Inclui link para o Manual Completo do sistema.

### Padrão CTA em Cards de Documentos
- Cards de NF-e, Cupom Não Fiscal e Nota de Pedido seguem o padrão CTA:
  - Fundo com gradiente suave e borda dashed.
  - Ícone grande centralizado.
  - Descrição curta da ação.
  - Botão proeminente com cor sólida.
  - Info contextual (valor, cliente) abaixo do botão.

### Referências de UI/UX
- Ver `.github/instructions/ui-ux.md` para diretrizes completas.
- Ver `.github/instructions/modal-style.md` para padrões de SweetAlert2.

---