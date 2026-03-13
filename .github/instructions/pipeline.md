# Módulo: Pipeline

- É o módulo do Kanban flow: contato → orçamento → venda → produção → preparação → envio → financeiro → concluído.
- `app/views/pipeline/detail.php` é o componente central para alteração minuciosa. Observe a re-validação das exibições com base na etapa corrente. Exemplo: dados de envio estão isolados até aprovação de produção.  
- Limites de SLA e alerta temporal de metas por estágio são baseados em horas visíveis na query do Model.

## Módulo: Linha de Produção (Pipeline)

### Conceito
O Pipeline controla o fluxo completo de cada pedido/ordem de produção, desde o primeiro contato com o cliente até a conclusão financeira. Cada pedido passa pelas seguintes etapas:

1. **Contato** (📞) — Primeiro contato com cliente, entendimento da necessidade
2. **Orçamento** (📄) — Elaboração e envio do orçamento ao cliente
3. **Venda** (🤝) — Orçamento aprovado, venda confirmada
4. **Produção** (🏭) — Pedido em execução na linha de produção do cliente
5. **Preparação** (📦) — Acabamento, corte, empacotamento
6. **Envio/Entrega** (🚚) — Pronto para envio ou entrega ao cliente
7. **Financeiro** (💰) — Cobrança, conferência de pagamento
8. **Concluído** (✅) — Pedido finalizado com sucesso

### Tabelas no Banco de Dados
- `orders` — Colunas adicionadas: `pipeline_stage`, `pipeline_entered_at`, `deadline`, `priority`, `notes`, `assigned_to`, `payment_status`, `payment_method`, `discount`, `shipping_type`, `shipping_address`, `tracking_code`
- `pipeline_history` — Histórico de movimentação (de qual etapa para qual, por quem, quando)
- `pipeline_stage_goals` — Metas configuráveis de tempo máximo (em horas) por etapa

### Regras de Negócio
- Ao criar um pedido, ele entra automaticamente na etapa "Contato"
- Mover entre etapas registra no histórico com timestamp e usuário
- Pedidos que ultrapassam a meta de horas de uma etapa são marcados como **atrasados**
- Alertas visuais aparecem no Kanban e no Dashboard quando há atrasos
- Cada pedido pode ter prioridade (baixa, normal, alta, urgente), responsável, prazo e notas internas
- Dados de financeiro (pagamento) e envio (endereço, rastreio) são gerenciados pelo detalhe do pipeline
- pedidos com parcelas pagas jamais podem retornar para produção ou etapas pré produção (contato, orçamento, venda, produção), ou serem cancelados.
- existindo parcelas pagas o sistema deve proibir alteração na forma de pagamentos, alterar quantidade, desconto, ou quantidade de produtos liberando apenas se as parcelas forem estornadas.

### Arquivos do Módulo
- `app/models/Pipeline.php` — Model com métodos de consulta e movimentação
- `app/controllers/PipelineController.php` — Controller com actions do pipeline
- `app/views/pipeline/index.php` — Kanban Board visual
- `app/views/pipeline/detail.php` — Detalhe completo do pedido
- `app/views/pipeline/settings.php` — Configuração de metas por etapa

## Regras de Visibilidade dos Cards no Detalhe do Pipeline (`detail.php`)

### Princípio Geral
Cada card/seção no detalhe do pipeline só deve ser exibido nas etapas em que é relevante. Isso reduz poluição visual e evita ações acidentais fora do contexto correto.

### Card de Controle de Produção (Ordem de Produção)
- **Visível apenas na etapa:** `producao` (Produção 🏭).
- **Não aparece em:** `preparacao`, `envio`, `financeiro`, `concluido`, nem nas etapas anteriores (`contato`, `orcamento`, `venda`).
- Quando visível, os campos são **somente leitura** (readonly), pois os dados de produção são preenchidos na etapa de produção e apenas consultados depois.
- A impressão da ordem de produção (`print_production_order.php`) também só é acessível na etapa de produção.

### Card de Produtos / Orçamento
- **Visível nas etapas:** `contato`, `orcamento`, `venda`, `preparacao`.
- **Não aparece em:** `producao`, `envio`, `financeiro`, `concluido`.
- Justificativa: nas etapas de produção em diante, os produtos já foram definidos e não devem ser alterados. Na etapa de envio, o foco é na logística/entrega.

### Card de Envio / Entrega (Shipping)
- **Visível nas etapas:** `envio` (e potencialmente `preparacao` para pré-preenchimento).
- **Comportamento dinâmico por Modalidade de Envio:**
  - O select "Modalidade de Envio" (`shipping_type`) controla dinamicamente (via JS) quais seções são exibidas:
    - **Retirada na Loja** (`retirada`): Oculta o card de endereço e o botão de impressão. Mostra apenas mensagem de retirada.
    - **Entrega Própria** (`entrega`): Exibe card de endereço em destaque + botão "Imprimir Guia de Endereçamento".
    - **Correios / Transportadora** (`correios`): Exibe card de endereço em destaque + botão "Imprimir Guia de Endereçamento" + campo de rastreio.
  - Ao trocar a modalidade, as seções atualizam instantaneamente sem recarregar a página.
- **Estrutura obrigatória do card:**
  1. **Endereço de entrega** em destaque visual (card com borda colorida, ícone de mapa, texto grande e legível). Visível apenas para `entrega` e `correios`.
  2. Botão "Usar endereço do cliente" que copia automaticamente o endereço cadastrado do cliente para o campo de envio.
  3. **Tipo de envio** (Correios, Motoboy, Retirada, etc.) em campo separado e visível.
  4. **Código de rastreamento** com campo dedicado.
  5. **Área de integração futura** com APIs de transportadoras (placeholder visual para Correios, Jadlog, etc.), preparada para receber dados de frete, rastreamento automático e status de entrega.
  6. **Botão "Imprimir Guia de Endereçamento"** — abre uma nova janela com etiqueta formatada (tamanho A5 landscape) contendo: remetente (dados da empresa), destinatário (nome, telefone, endereço completo), modalidade de envio, código de rastreio e data. O layout é otimizado para ser recortado e colado na embalagem.
- O card deve usar `fieldset` com `legend` estilizado, e o endereço deve ser o elemento mais proeminente da seção.
- O badge no `legend` do fieldset e a cor da borda atualizam dinamicamente conforme a modalidade selecionada.

### Card Financeiro (Pagamento, Parcelamento, Boleto, NF-e)
- **Visível nas etapas:** `venda`, `financeiro`, `concluido`.
- **Na etapa `financeiro`:** o card é o foco principal. O card de Produtos/Orçamento é **ocultado** para evitar poluição visual, e no lugar é exibido um resumo compacto dos produtos dentro do card financeiro.
- **Funcionalidades do card financeiro:**
  1. **Valor Total** (somente leitura, vindo do pedido).
  2. **Status de Pagamento** (`pendente`, `parcial`, `pago`).
  3. **Forma de Pagamento** (dinheiro, pix, cartão crédito/débito, boleto, transferência).
  4. **Parcelamento** — aparece para `cartao_credito` e `boleto`:
     - Número de parcelas (2x a 12x).
     - Entrada / sinal (`down_payment`) — campo numérico, 0 se não houver.
     - Valor por parcela (calculado automaticamente: `(total - desconto - entrada) / nParcelas`).
  5. **Tabela de boletos** — aparece apenas para forma de pagamento `boleto`:
     - Cada parcela tem data de vencimento editável, valor e status.
     - Botão **"Imprimir Boletos"** — abre nova janela com layout A4 formatado para impressão.
  6. **Links de Pagamento** — placeholder para integração futura com PagSeguro, Mercado Pago, PIX dinâmico, Stripe.
  7. **Fiscal / Nota Fiscal** — seção para NF-e:
     - Campos: número, série, status (`emitida`, `enviada`, `cancelada`), chave de acesso (44 dígitos), observações.
     - Botão **"Emitir NF"** (placeholder para integração futura com NFe.io, Bling, Tiny ERP, eNotas).
- **Campos no banco de dados:** `payment_status`, `payment_method`, `installments`, `installment_value`, `down_payment`, `discount`, `nf_number`, `nf_series`, `nf_status`, `nf_access_key`, `nf_notes`.
- **Migração SQL:** `sql/financial_upgrade.sql`.
- Quando o card financeiro está **oculto** (etapas que não o exibem), os valores são preservados via `<input type="hidden">` para não serem perdidos ao salvar o formulário.

### Regra Geral de Extensão
- Ao adicionar novos cards ou seções ao detalhe do pipeline, sempre definir explicitamente em quais etapas (`pipeline_stage`) o card será visível, usando condições PHP no `detail.php`.
- Documentar a visibilidade nesta seção do `.github/instructions/pipeline.md`.