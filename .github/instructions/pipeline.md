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

---