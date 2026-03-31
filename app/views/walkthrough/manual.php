<div class="container-fluid py-4 main-content">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">

            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h2 class="fw-bold mb-1"><i class="fas fa-book text-primary me-2"></i>Manual do Sistema</h2>
                    <p class="text-muted mb-0">Guia completo de todas as funcionalidades do Akti — Gestão em Produção</p>
                </div>
                <button class="btn btn-outline-primary" onclick="window.aktiWalkthrough.start(0);">
                    <i class="fas fa-play me-1"></i> Iniciar Tour
                </button>
            </div>

            <!-- Navegação rápida -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="fw-bold text-muted mb-3"><i class="fas fa-list me-2"></i>Índice Rápido</h6>
                    <div class="row g-2">
                        <div class="col-6 col-md-4 col-lg-3"><a href="#sec-home" class="btn btn-light btn-sm w-100 text-start"><i class="fas fa-home me-1 text-primary"></i> Página Inicial</a></div>
                        <div class="col-6 col-md-4 col-lg-3"><a href="#sec-pedidos" class="btn btn-light btn-sm w-100 text-start"><i class="fas fa-clipboard-list me-1 text-success"></i> Pedidos</a></div>
                        <div class="col-6 col-md-4 col-lg-3"><a href="#sec-pipeline" class="btn btn-light btn-sm w-100 text-start"><i class="fas fa-columns me-1 text-info"></i> Pipeline</a></div>
                        <div class="col-6 col-md-4 col-lg-3"><a href="#sec-clientes" class="btn btn-light btn-sm w-100 text-start"><i class="fas fa-users me-1 text-warning"></i> Clientes</a></div>
                        <div class="col-6 col-md-4 col-lg-3"><a href="#sec-produtos" class="btn btn-light btn-sm w-100 text-start"><i class="fas fa-boxes-stacked me-1 text-danger"></i> Produtos</a></div>
                        <div class="col-6 col-md-4 col-lg-3"><a href="#sec-categorias" class="btn btn-light btn-sm w-100 text-start"><i class="fas fa-folder-open me-1 text-primary"></i> Categorias</a></div>
                        <div class="col-6 col-md-4 col-lg-3"><a href="#sec-estoque" class="btn btn-light btn-sm w-100 text-start"><i class="fas fa-warehouse me-1 text-secondary"></i> Estoque</a></div>
                        <div class="col-6 col-md-4 col-lg-3"><a href="#sec-setores" class="btn btn-light btn-sm w-100 text-start"><i class="fas fa-industry me-1 text-primary"></i> Setores</a></div>
                        <div class="col-6 col-md-4 col-lg-3"><a href="#sec-fiscal" class="btn btn-light btn-sm w-100 text-start"><i class="fas fa-coins me-1 text-warning"></i> Fiscal</a></div>
                        <div class="col-6 col-md-4 col-lg-3"><a href="#sec-config" class="btn btn-light btn-sm w-100 text-start"><i class="fas fa-cog me-1 text-muted"></i> Configurações</a></div>
                        <div class="col-6 col-md-4 col-lg-3"><a href="#sec-usuarios" class="btn btn-light btn-sm w-100 text-start"><i class="fas fa-user-shield me-1 text-info"></i> Usuários</a></div>
                        <div class="col-6 col-md-4 col-lg-3"><a href="#sec-perfil" class="btn btn-light btn-sm w-100 text-start"><i class="fas fa-user-circle me-1 text-success"></i> Perfil</a></div>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════ -->
            <!-- Página Inicial -->
            <!-- ══════════════════════════════════════ -->
            <div class="card border-0 shadow-sm mb-4" id="sec-home">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-home me-2"></i>Página Inicial</h5>
                </div>
                <div class="card-body">
                    <p>A Página Inicial é o <strong>painel central</strong> do sistema. Ao fazer login ou clicar na <strong>logo do Akti</strong>, você é levado diretamente a esta tela.</p>

                    <div class="alert alert-info py-2">
                        <i class="fas fa-lightbulb me-1"></i> <strong>Dica:</strong> Não existe um item "Dashboard" no menu. Para voltar à Página Inicial, basta <strong>clicar na logo</strong> no canto superior esquerdo.
                    </div>

                    <h6 class="fw-bold mt-3">O que você encontra aqui:</h6>

                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6 class="fw-bold"><i class="fas fa-bolt me-1 text-warning"></i> Atalhos Rápidos</h6>
                                <p class="small text-muted mb-0">Botões no topo para as ações mais comuns: <strong>Novo Pedido</strong>, <strong>Novo Cliente</strong>, <strong>Pipeline</strong> e <strong>Pagamentos</strong>.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6 class="fw-bold"><i class="fas fa-tasks me-1 text-primary"></i> Cards de Resumo</h6>
                                <p class="small text-muted mb-0"><strong>Pedidos Ativos</strong>, <strong>Criados Hoje</strong>, <strong>Atrasados</strong> e <strong>Concluídos no Mês</strong>. Clique para ir à área correspondente.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6 class="fw-bold"><i class="fas fa-stream me-1 text-info"></i> Pipeline Visual</h6>
                                <p class="small text-muted mb-0">Visão compacta de quantos pedidos estão em cada etapa do pipeline (Contato, Orçamento, Venda, Produção, Preparação, Envio, Financeiro).</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6 class="fw-bold"><i class="fas fa-coins me-1 text-success"></i> Resumo Financeiro</h6>
                                <p class="small text-muted mb-0"><strong>Recebido no mês</strong>, <strong>A Receber</strong>, <strong>Em Atraso</strong> e <strong>Aguardando Confirmação</strong>.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6 class="fw-bold"><i class="fas fa-exclamation-triangle me-1 text-danger"></i> Pedidos Atrasados</h6>
                                <p class="small text-muted mb-0">Lista dos pedidos que ultrapassaram a meta de tempo na etapa atual, com indicação de quantas horas de atraso.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6 class="fw-bold"><i class="fas fa-calendar-check me-1 icon-purple"></i> Próximos Contatos</h6>
                                <p class="small text-muted mb-0">Agenda de follow-ups agendados com clientes. Contatos de hoje ficam em destaque.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6 class="fw-bold"><i class="fas fa-history me-1 text-primary"></i> Atividade Recente</h6>
                                <p class="small text-muted mb-0">Últimas movimentações no pipeline — quais pedidos foram movidos e para qual etapa.</p>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning py-2 mt-3 mb-0">
                        <i class="fas fa-bell me-1"></i> Se houver pedidos atrasados, um <strong>alerta automático</strong> será exibido ao abrir a Página Inicial.
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════ -->
            <!-- Pedidos -->
            <!-- ══════════════════════════════════════ -->
            <div class="card border-0 shadow-sm mb-4" id="sec-pedidos">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Pedidos</h5>
                </div>
                <div class="card-body">
                    <p>O módulo de Pedidos é o <strong>coração do sistema</strong>. Cada pedido representa uma solicitação de um cliente e percorre todas as etapas do pipeline.</p>

                    <h6 class="fw-bold mt-3">Como criar um pedido:</h6>
                    <ol>
                        <li>Acesse <strong>Comercial → Pedidos</strong> no menu ou clique em <strong>"Novo Pedido"</strong> na Página Inicial</li>
                        <li>Clique em <strong>"Novo Pedido"</strong></li>
                        <li>Selecione o <strong>cliente</strong> (ou cadastre um novo rapidamente)</li>
                        <li>Adicione os <strong>produtos</strong> desejados</li>
                        <li>Se o produto tiver <strong>grades</strong> (tamanho, cor, material), selecione a combinação</li>
                        <li>Defina <strong>quantidade, prioridade e prazo de entrega</strong></li>
                        <li>Configure <strong>desconto, frete e forma de pagamento</strong></li>
                        <li>Se desejar, adicione <strong>custos extras</strong> (arte, montagem, etc.)</li>
                        <li>Clique em <strong>"Salvar"</strong></li>
                    </ol>

                    <h6 class="fw-bold mt-3">Status dos pedidos:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light"><tr><th>Status</th><th>Descrição</th></tr></thead>
                            <tbody>
                                <tr><td><span class="badge bg-secondary">Orçamento</span></td><td>Proposta gerada, aguardando aprovação do cliente</td></tr>
                                <tr><td><span class="badge bg-warning text-dark">Pendente</span></td><td>Aprovado pelo cliente, aguardando início da produção</td></tr>
                                <tr><td><span class="badge bg-info">Em Produção</span></td><td>Sendo fabricado/preparado nos setores de produção</td></tr>
                                <tr><td><span class="badge bg-success">Concluído</span></td><td>Finalizado, entregue e pago</td></tr>
                                <tr><td><span class="badge bg-danger">Cancelado</span></td><td>Pedido cancelado (não contabilizado)</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <h6 class="fw-bold mt-3">Etapas do Pipeline:</h6>
                    <p><code>Contato → Orçamento → Venda → Produção → Preparação → Envio/Entrega → Financeiro → Concluído</code></p>

                    <h6 class="fw-bold mt-3">Funcionalidades adicionais:</h6>
                    <ul>
                        <li><strong>Link público de orçamento</strong> — gera um link para o cliente visualizar sem precisar de login</li>
                        <li><strong>Custos extras</strong> — arte, embalagem, montagem, urgência, etc.</li>
                        <li><strong>Impressão de orçamento</strong> — layout profissional para imprimir ou enviar por email/WhatsApp</li>
                        <li><strong>Relatório de pedidos</strong> — filtros por período, status, cliente e exportação</li>
                        <li><strong>Agenda de contatos</strong> — agende follow-ups para pedidos na etapa de contato</li>
                        <li><strong>Frete</strong> — calcule e adicione frete ao pedido</li>
                        <li><strong>Prioridade</strong> — defina alta, média ou baixa para organizar a produção</li>
                    </ul>

                    <div class="alert alert-info py-2 mb-0">
                        <i class="fas fa-lightbulb me-1"></i> Ao editar um pedido, você pode adicionar/remover produtos, alterar quantidades e recalcular automaticamente o total.
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════ -->
            <!-- Pipeline -->
            <!-- ══════════════════════════════════════ -->
            <div class="card border-0 shadow-sm mb-4" id="sec-pipeline">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-columns me-2"></i>Pipeline de Produção</h5>
                </div>
                <div class="card-body">
                    <p>O Pipeline é uma visão <strong>Kanban</strong> (quadro visual) de todos os pedidos organizados por etapa do processo produtivo.</p>

                    <h6 class="fw-bold mt-3">Como funciona:</h6>
                    <ul>
                        <li>Cada <strong>coluna</strong> representa uma etapa: Contato, Orçamento, Venda, Produção, Preparação, Envio, Financeiro, Concluído</li>
                        <li>Cada <strong>card</strong> representa um pedido com informações resumidas (nº, cliente, prioridade)</li>
                        <li><strong>Arraste e solte</strong> os cards entre colunas para mover pedidos de etapa</li>
                        <li>Pedidos que ultrapassam a <strong>meta de tempo</strong> ficam destacados em vermelho</li>
                        <li>Clique em um card para ver todos os <strong>detalhes</strong> e histórico do pedido</li>
                    </ul>

                    <h6 class="fw-bold mt-3">Detalhe do Pedido (dentro do Pipeline):</h6>
                    <ul>
                        <li><strong>Histórico de movimentação</strong> — todas as mudanças de etapa com data e hora</li>
                        <li><strong>Setores de produção</strong> — acompanhe o progresso setor a setor</li>
                        <li><strong>Checklist de preparação</strong> — conferência antes do envio</li>
                        <li><strong>Anexos e fotos</strong> — registros visuais de cada etapa</li>
                        <li><strong>Ordem de produção</strong> — imprima a ficha para o chão de fábrica</li>
                    </ul>

                    <h6 class="fw-bold mt-3">Painel de Produção:</h6>
                    <p>Acessível via <strong>Produção → Painel de Produção</strong>, é uma visão simplificada e detalhada para o <strong>chão de fábrica</strong>, focando apenas nas etapas de produção e preparação.</p>

                    <h6 class="fw-bold mt-3">Configurações do Pipeline:</h6>
                    <p>Em <strong>Configurações</strong>, você pode definir as <strong>metas de tempo</strong> (em horas) para cada etapa. Quando ultrapassadas, o pedido fica destacado.</p>

                    <div class="alert alert-warning py-2 mb-0">
                        <i class="fas fa-hand-pointer me-1"></i> <strong>Basta arrastar e soltar!</strong> Mover um pedido de etapa é tão simples quanto arrastar o card para a nova coluna.
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════ -->
            <!-- Clientes -->
            <!-- ══════════════════════════════════════ -->
            <div class="card border-0 shadow-sm mb-4" id="sec-clientes">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i>Clientes</h5>
                </div>
                <div class="card-body">
                    <p>Gerencie sua base de clientes com informações completas para vendas, NF-e e boletos.</p>

                    <h6 class="fw-bold mt-3">Dados do cliente:</h6>
                    <ul>
                        <li><strong>Nome / Razão Social</strong> — nome completo ou razão social da empresa</li>
                        <li><strong>Email</strong> — para envio de orçamentos e notificações</li>
                        <li><strong>Telefone / WhatsApp</strong> — contato principal</li>
                        <li><strong>CPF ou CNPJ</strong> — documento fiscal obrigatório para NF-e</li>
                        <li><strong>Inscrição Estadual / Municipal</strong> — quando aplicável</li>
                        <li><strong>Endereço completo</strong> — usado em NF-e, boletos e entregas (com CEP, cidade, UF)</li>
                        <li><strong>Foto</strong> — imagem do cliente (opcional)</li>
                        <li><strong>Tabela de preço</strong> — vincule uma tabela de preço para preços diferenciados</li>
                        <li><strong>Observações</strong> — notas internas sobre o cliente</li>
                    </ul>

                    <h6 class="fw-bold mt-3">Funcionalidades:</h6>
                    <ul>
                        <li><strong>Busca rápida</strong> — pesquise por nome, email, telefone ou documento</li>
                        <li><strong>Histórico de pedidos</strong> — veja todos os pedidos feitos por esse cliente</li>
                        <li><strong>Tabela de preço vinculada</strong> — todos os pedidos do cliente usam automaticamente os preços especiais</li>
                    </ul>

                    <div class="alert alert-info py-2 mb-0">
                        <i class="fas fa-lightbulb me-1"></i> Ao criar um pedido, você pode cadastrar um novo cliente direto no formulário de pedido, sem precisar sair da tela.
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════ -->
            <!-- Produtos -->
            <!-- ══════════════════════════════════════ -->
            <div class="card border-0 shadow-sm mb-4" id="sec-produtos">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-boxes-stacked me-2"></i>Produtos</h5>
                </div>
                <div class="card-body">
                    <p>Cadastre seus produtos com informações completas para vendas, produção e fiscalização.</p>

                    <h6 class="fw-bold mt-3">Dados do produto:</h6>
                    <ul>
                        <li><strong>Nome, SKU e Código de Barras</strong> — identificação do produto</li>
                        <li><strong>Descrição</strong> — texto descritivo para orçamentos e notas</li>
                        <li><strong>Preço de custo e preço de venda</strong></li>
                        <li><strong>Categoria / Subcategoria</strong> — organização hierárquica do catálogo</li>
                        <li><strong>Fotos</strong> — múltiplas imagens (JPG, PNG, WebP, GIF)</li>
                        <li><strong>Peso e dimensões</strong> — para cálculo de frete</li>
                    </ul>

                    <h6 class="fw-bold mt-3">Grades (Variações):</h6>
                    <p>Grades permitem criar variações de um produto (tamanho, cor, material). Ao configurar, o sistema gera <strong>todas as combinações automaticamente</strong>.</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light"><tr><th>Exemplo</th><th>Opções</th></tr></thead>
                            <tbody>
                                <tr><td>Tamanho</td><td>P, M, G, GG, XG</td></tr>
                                <tr><td>Cor</td><td>Branco, Preto, Azul, Vermelho</td></tr>
                                <tr><td>Material</td><td>Algodão, Poliéster, Dry-fit</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="small text-muted">As grades podem ser <strong>herdadas da categoria</strong> — configure uma vez e todos os produtos da categoria usam as mesmas grades.</p>

                    <h6 class="fw-bold mt-3">Dados Fiscais (NF-e):</h6>
                    <p>Cada produto pode ter informações fiscais completas para emissão de Nota Fiscal Eletrônica:</p>
                    <ul>
                        <li><strong>NCM</strong> — Nomenclatura Comum do Mercosul (código fiscal do produto)</li>
                        <li><strong>CFOP</strong> — Código Fiscal de Operações e Prestações</li>
                        <li><strong>Origem</strong> — Nacional, Importada, etc.</li>
                        <li><strong>ICMS</strong> — CST, Base de Cálculo, Alíquota</li>
                        <li><strong>PIS</strong> — CST e Alíquota</li>
                        <li><strong>COFINS</strong> — CST e Alíquota</li>
                        <li><strong>IPI</strong> — CST e Alíquota</li>
                    </ul>

                    <h6 class="fw-bold mt-3">Setores de produção:</h6>
                    <p>Vincule setores (Costura, Corte, Estamparia) ao produto para que, ao entrar na produção, o pipeline saiba quais etapas internas o pedido deve percorrer.</p>

                    <h6 class="fw-bold mt-3">Controle de estoque:</h6>
                    <p>Ative o <strong>controle de estoque</strong> por produto. Quando ativado, o sistema controla entradas, saídas e saldos por armazém e por combinação de grade.</p>

                    <div class="alert alert-info py-2 mb-0">
                        <i class="fas fa-lightbulb me-1"></i> Produtos com estoque disponível podem <strong>pular a etapa de produção</strong> automaticamente no pipeline!
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════ -->
            <!-- Categorias -->
            <!-- ══════════════════════════════════════ -->
            <div class="card border-0 shadow-sm mb-4" id="sec-categorias">
                <div class="card-header card-header-indigo">
                    <h5 class="mb-0"><i class="fas fa-folder-open me-2"></i>Categorias e Subcategorias</h5>
                </div>
                <div class="card-body">
                    <p>Organize seu catálogo em <strong>categorias</strong> e <strong>subcategorias</strong> hierárquicas.</p>

                    <h6 class="fw-bold mt-3">Funcionalidades:</h6>
                    <ul>
                        <li><strong>Categorias</strong> — agrupamentos principais (ex: Camisetas, Canecas, Banners)</li>
                        <li><strong>Subcategorias</strong> — divisões dentro de uma categoria (ex: Camiseta Manga Longa, Camiseta Baby Look)</li>
                        <li><strong>Grades herdáveis</strong> — configure grades (tamanho, cor) na categoria e todos os produtos dentro dela herdam automaticamente</li>
                        <li><strong>Setores herdáveis</strong> — vincule setores de produção à categoria para aplicar a todos os produtos</li>
                        <li><strong>Imagem da categoria</strong> — útil para o catálogo público</li>
                    </ul>

                    <div class="alert alert-info py-2 mb-0">
                        <i class="fas fa-lightbulb me-1"></i> Use subcategorias para organização mais fina. Ex: Categoria "Vestuário" → Subcategorias "Camisetas", "Moletons", "Bonés".
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════ -->
            <!-- Estoque -->
            <!-- ══════════════════════════════════════ -->
            <div class="card border-0 shadow-sm mb-4" id="sec-estoque">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-warehouse me-2"></i>Controle de Estoque</h5>
                </div>
                <div class="card-body">
                    <p>O módulo de estoque permite o controle completo de <strong>múltiplos armazéns</strong> com entradas, saídas, transferências e alertas.</p>

                    <h6 class="fw-bold mt-3">Visão Geral (Cards de Resumo):</h6>
                    <p>A tela principal do estoque exibe cards com:</p>
                    <ul>
                        <li><strong>Armazéns</strong> — total de armazéns cadastrados</li>
                        <li><strong>Itens</strong> — total de itens em estoque</li>
                        <li><strong>Produtos</strong> — quantos produtos distintos estão no estoque</li>
                        <li><strong>Valor Total</strong> — valor estimado de todo o estoque</li>
                        <li><strong>Baixo</strong> — itens abaixo do estoque mínimo (alerta)</li>
                        <li><strong>Mov. Hoje</strong> — movimentações registradas no dia</li>
                    </ul>

                    <h6 class="fw-bold mt-3">Armazéns:</h6>
                    <ul>
                        <li>Crie quantos armazéns precisar (ex: Estoque Principal, Loja, Depósito, Pronta-entrega)</li>
                        <li>Cada armazém tem nome, descrição e status (ativo/inativo)</li>
                    </ul>

                    <h6 class="fw-bold mt-3">Entradas e Saídas:</h6>
                    <ul>
                        <li><strong>Entrada</strong> — recebimento de materiais, compras, produção finalizada</li>
                        <li><strong>Saída</strong> — venda, consumo, perda, transferência</li>
                        <li>Cada movimentação registra: produto, quantidade, armazém, observação e data</li>
                        <li>Se o produto tiver <strong>grades</strong>, selecione a combinação específica</li>
                    </ul>

                    <h6 class="fw-bold mt-3">Transferências:</h6>
                    <p>Mova itens entre armazéns com registro automático de saída no armazém de origem e entrada no destino.</p>

                    <h6 class="fw-bold mt-3">Estoque Mínimo e Alertas:</h6>
                    <ul>
                        <li>Defina o <strong>estoque mínimo</strong> por item/armazém</li>
                        <li>Quando o saldo cai abaixo do mínimo, aparece um <strong>alerta visual</strong> na tela principal</li>
                        <li>Itens com estoque baixo ficam em destaque na listagem</li>
                    </ul>

                    <h6 class="fw-bold mt-3">Localização Física:</h6>
                    <p>Opcionalmente, defina a <strong>localização física</strong> de cada item (ex: corredor A, prateleira 3 — <code>A1-P3</code>).</p>

                    <h6 class="fw-bold mt-3">Movimentações (Histórico):</h6>
                    <p>Acesse o histórico completo de todas as movimentações com filtros por armazém, produto e período.</p>

                    <div class="alert alert-success py-2 mb-0">
                        <i class="fas fa-check-circle me-1"></i> Produtos com estoque disponível podem <strong>pular a etapa de produção</strong> no pipeline, agilizando o fluxo de entrega!
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════ -->
            <!-- Setores -->
            <!-- ══════════════════════════════════════ -->
            <div class="card border-0 shadow-sm mb-4" id="sec-setores">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-industry me-2"></i>Setores de Produção</h5>
                </div>
                <div class="card-body">
                    <p>Organize sua <strong>linha produtiva</strong> por setores para acompanhar cada fase da fabricação.</p>

                    <h6 class="fw-bold mt-3">Como funciona:</h6>
                    <ul>
                        <li>Crie setores como <em>Costura, Corte, Estamparia, Serigrafia, Embalagem, Acabamento</em></li>
                        <li>Defina a <strong>ordem</strong> dos setores na linha de produção</li>
                        <li>Vincule setores a <strong>produtos</strong>, <strong>categorias</strong> ou <strong>subcategorias</strong></li>
                        <li>No pipeline (etapa Produção), acompanhe o progresso <strong>setor a setor</strong></li>
                        <li>Cada setor pode ter uma <strong>cor</strong> e <strong>ícone</strong> personalizados</li>
                    </ul>

                    <h6 class="fw-bold mt-3">Exemplo de linha de produção:</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-primary p-2">1. Corte</span>
                        <span class="text-muted">→</span>
                        <span class="badge bg-info p-2">2. Estamparia</span>
                        <span class="text-muted">→</span>
                        <span class="badge bg-warning text-dark p-2">3. Costura</span>
                        <span class="text-muted">→</span>
                        <span class="badge bg-success p-2">4. Acabamento</span>
                        <span class="text-muted">→</span>
                        <span class="badge bg-secondary p-2">5. Embalagem</span>
                    </div>

                    <div class="alert alert-info py-2 mt-3 mb-0">
                        <i class="fas fa-lightbulb me-1"></i> Setores herdados da categoria são automaticamente aplicados ao criar pedidos com produtos dessa categoria.
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════ -->
            <!-- FISCAL — Seção completa e detalhada -->
            <!-- ══════════════════════════════════════ -->
            <div class="card border-0 shadow-sm mb-4" id="sec-fiscal">
                <div class="card-header card-header-amber">
                    <h5 class="mb-0"><i class="fas fa-coins me-2"></i>Fiscal / Financeiro</h5>
                </div>
                <div class="card-body">
                    <p>O módulo <strong>Fiscal</strong> centraliza todo o controle financeiro do sistema. Ele é acessível pelo menu <strong>Fiscal</strong> na barra de navegação e contém duas áreas principais:</p>

                    <!-- Pagamentos -->
                    <div class="border rounded p-3 mb-3 bg-section-green">
                        <h6 class="fw-bold text-success"><i class="fas fa-file-invoice-dollar me-2"></i>Pagamentos (Parcelas)</h6>
                        <p class="mb-2">Gerencie <strong>todas as parcelas</strong> de todos os pedidos do sistema. Aqui você acompanha recebimentos, confirma pagamentos e cobra inadimplentes.</p>

                        <h6 class="fw-bold mt-3 small text-uppercase text-muted">Funcionalidades:</h6>
                        <ul>
                            <li><strong>Listagem de parcelas</strong> — todas as parcelas de todos os pedidos, organizadas por vencimento</li>
                            <li><strong>Filtros</strong> — filtre por <strong>status</strong> (pendente, pago, atrasado, cancelado), <strong>mês</strong> e <strong>ano</strong></li>
                            <li><strong>Registrar pagamento</strong> — marque como pago informando o valor recebido e a forma de pagamento</li>
                            <li><strong>Forma de pagamento</strong> — Dinheiro, PIX, Cartão de Crédito, Cartão de Débito, Boleto, Transferência, Cheque</li>
                            <li><strong>Confirmação de pagamento</strong> — pagamentos marcados como pagos precisam de uma <strong>confirmação</strong> manual (aprovação do financeiro)</li>
                            <li><strong>Estorno</strong> — reverta um pagamento (fica registrado para auditoria mas não afeta os cálculos de saldo)</li>
                            <li><strong>Anexar comprovante</strong> — faça upload de foto ou PDF do comprovante de pagamento</li>
                            <li><strong>Reimprimir boleto</strong> — gera boleto CNAB 400/FEBRABAN com código de barras ITF-25, linha digitável e dados bancários</li>
                        </ul>

                        <h6 class="fw-bold mt-3 small text-uppercase text-muted">Status das parcelas:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-2">
                                <thead class="table-light"><tr><th>Status</th><th>Descrição</th></tr></thead>
                                <tbody>
                                    <tr><td><span class="badge bg-warning text-dark">Pendente</span></td><td>Parcela aguardando pagamento (dentro do prazo)</td></tr>
                                    <tr><td><span class="badge bg-success">Pago</span></td><td>Pagamento registrado e confirmado</td></tr>
                                    <tr><td><span class="badge bg-info">Aguardando Confirmação</span></td><td>Pagamento registrado mas ainda não confirmado pelo financeiro</td></tr>
                                    <tr><td><span class="badge bg-danger">Atrasado</span></td><td>Parcela com vencimento ultrapassado sem pagamento</td></tr>
                                    <tr><td><span class="badge bg-secondary">Cancelado</span></td><td>Parcela cancelada (não contabilizada)</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <h6 class="fw-bold mt-3 small text-uppercase text-muted">Geração de parcelas:</h6>
                        <p class="mb-1">As parcelas são geradas automaticamente ao configurar a forma de pagamento no pedido. Exemplo:</p>
                        <ul class="mb-2">
                            <li>Pedido de R$ 1.000,00 em 3x → gera 3 parcelas de R$ 333,33 com vencimentos mensais</li>
                            <li>Pagamento à vista → gera 1 parcela com vencimento imediato</li>
                        </ul>

                        <h6 class="fw-bold mt-3 small text-uppercase text-muted">Visualização por pedido:</h6>
                        <p class="mb-0">Ao clicar em <strong>"Ver parcelas"</strong> de um pedido, você vê todas as parcelas daquele pedido específico com linha do tempo de pagamentos.</p>
                    </div>

                    <!-- Entradas e Saídas -->
                    <div class="border rounded p-3 mb-3 bg-section-blue">
                        <h6 class="fw-bold text-primary"><i class="fas fa-exchange-alt me-2"></i>Entradas e Saídas (Livro Caixa)</h6>
                        <p class="mb-2">O <strong>livro caixa</strong> registra todas as movimentações financeiras da empresa, separando entradas e saídas.</p>

                        <h6 class="fw-bold mt-3 small text-uppercase text-muted">Tipos de transação:</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="border rounded p-2 h-100 bg-white">
                                    <h6 class="fw-bold text-success small"><i class="fas fa-arrow-down me-1"></i> Entradas</h6>
                                    <ul class="small mb-0">
                                        <li>Pagamentos de pedidos (automático ao confirmar parcelas)</li>
                                        <li>Serviços avulsos</li>
                                        <li>Venda direta</li>
                                        <li>Outras receitas (investimentos, empréstimos, etc.)</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-2 h-100 bg-white">
                                    <h6 class="fw-bold text-danger small"><i class="fas fa-arrow-up me-1"></i> Saídas</h6>
                                    <ul class="small mb-0">
                                        <li>Compra de materiais</li>
                                        <li>Aluguel e infraestrutura</li>
                                        <li>Salários e encargos</li>
                                        <li>Impostos e taxas</li>
                                        <li>Fornecedores</li>
                                        <li>Outros gastos</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <h6 class="fw-bold mt-3 small text-uppercase text-muted">Funcionalidades:</h6>
                        <ul>
                            <li><strong>Nova Transação</strong> — adicione entradas e saídas manualmente com categoria, descrição e valor</li>
                            <li><strong>Filtros</strong> — por <strong>tipo</strong> (entrada/saída), <strong>mês</strong>, <strong>ano</strong> e <strong>categoria</strong></li>
                            <li><strong>Totalizadores</strong> — no topo da página são exibidos: <strong>Total de Entradas</strong>, <strong>Total de Saídas</strong> e <strong>Saldo</strong></li>
                            <li><strong>Estornos</strong> — transações estornadas ficam visíveis com marcação visual, mas <strong>não são contabilizadas</strong> nos totais</li>
                            <li><strong>Vínculo com pedidos</strong> — pagamentos confirmados geram automaticamente uma entrada no livro caixa</li>
                        </ul>

                        <h6 class="fw-bold mt-3 small text-uppercase text-muted">Categorias de transação:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light"><tr><th>Categoria</th><th>Tipo</th><th>Descrição</th></tr></thead>
                                <tbody>
                                    <tr><td>Pagamento de Pedido</td><td><span class="badge bg-success">Entrada</span></td><td>Gerado automaticamente ao confirmar parcela</td></tr>
                                    <tr><td>Serviço Avulso</td><td><span class="badge bg-success">Entrada</span></td><td>Receita por serviço não vinculado a pedido</td></tr>
                                    <tr><td>Venda Direta</td><td><span class="badge bg-success">Entrada</span></td><td>Venda direta sem pedido formal</td></tr>
                                    <tr><td>Material</td><td><span class="badge bg-danger">Saída</span></td><td>Compra de insumos e matéria-prima</td></tr>
                                    <tr><td>Aluguel</td><td><span class="badge bg-danger">Saída</span></td><td>Aluguel de imóvel/equipamento</td></tr>
                                    <tr><td>Salários</td><td><span class="badge bg-danger">Saída</span></td><td>Folha de pagamento</td></tr>
                                    <tr><td>Impostos</td><td><span class="badge bg-danger">Saída</span></td><td>Tributos e taxas</td></tr>
                                    <tr><td>Fornecedor</td><td><span class="badge bg-danger">Saída</span></td><td>Pagamento a fornecedores</td></tr>
                                    <tr><td>Outros</td><td><span class="badge bg-secondary">Ambos</span></td><td>Outros valores não categorizados</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Resumo na Página Inicial -->
                    <div class="border rounded p-3 mb-3 bg-section-yellow">
                        <h6 class="fw-bold text-amber"><i class="fas fa-home me-2"></i>Resumo Financeiro na Página Inicial</h6>
                        <p class="mb-2">A Página Inicial exibe um <strong>resumo financeiro</strong> do mês corrente com quatro indicadores:</p>
                        <ul class="mb-0">
                            <li><strong>Recebido</strong> — total de parcelas pagas e confirmadas no mês</li>
                            <li><strong>A Receber</strong> — total de parcelas pendentes ou atrasadas</li>
                            <li><strong>Em Atraso</strong> — valor total de parcelas vencidas e não pagas</li>
                            <li><strong>Aguardando Confirmação</strong> — número de parcelas pagas mas ainda não confirmadas pelo financeiro</li>
                        </ul>
                    </div>

                    <!-- Boletos -->
                    <div class="border rounded p-3 mb-3 bg-section-pink">
                        <h6 class="fw-bold text-danger"><i class="fas fa-barcode me-2"></i>Boletos Bancários</h6>
                        <p class="mb-2">O sistema gera boletos no padrão <strong>CNAB 400 / FEBRABAN</strong> com:</p>
                        <ul>
                            <li><strong>Código de barras</strong> — formato ITF-25 interleaved</li>
                            <li><strong>Linha digitável</strong> — para pagamento online ou caixa eletrônico</li>
                            <li><strong>Dados do cedente</strong> — nome, CNPJ, endereço e dados bancários da empresa (configurável)</li>
                            <li><strong>Dados do sacado</strong> — nome, CPF/CNPJ e endereço do cliente</li>
                            <li><strong>Instruções de pagamento</strong> — multa, juros, desconto por antecipação (configurável)</li>
                        </ul>
                        <p class="small text-muted mb-0">Os dados bancários são configurados em <strong>Configurações → Dados Bancários/Boleto</strong>.</p>
                    </div>

                    <!-- Dados Fiscais nos Produtos -->
                    <div class="border rounded p-3 bg-section-cyan">
                        <h6 class="fw-bold text-info"><i class="fas fa-file-alt me-2"></i>Dados Fiscais nos Produtos (NF-e)</h6>
                        <p class="mb-2">Cada produto pode conter dados fiscais completos para emissão de Nota Fiscal Eletrônica:</p>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-2">
                                <thead class="table-light"><tr><th>Campo</th><th>Descrição</th><th>Exemplo</th></tr></thead>
                                <tbody>
                                    <tr><td><strong>NCM</strong></td><td>Nomenclatura Comum do Mercosul — classifica o produto para fins de tributação internacional</td><td>6105.10.00</td></tr>
                                    <tr><td><strong>CFOP</strong></td><td>Código Fiscal de Operações — identifica o tipo de operação (venda, remessa, etc.)</td><td>5102 (Venda dentro do estado)</td></tr>
                                    <tr><td><strong>Origem</strong></td><td>Origem da mercadoria (nacional, importada, etc.)</td><td>0 — Nacional</td></tr>
                                    <tr><td><strong>ICMS CST</strong></td><td>Código de Situação Tributária do ICMS</td><td>00 — Tributada integralmente</td></tr>
                                    <tr><td><strong>ICMS Alíquota</strong></td><td>Percentual de ICMS sobre a base de cálculo</td><td>18%</td></tr>
                                    <tr><td><strong>ICMS Base</strong></td><td>Percentual da base de cálculo (redução, se houver)</td><td>100%</td></tr>
                                    <tr><td><strong>PIS CST</strong></td><td>Código de Situação Tributária do PIS</td><td>01 — Operação tributável</td></tr>
                                    <tr><td><strong>PIS Alíquota</strong></td><td>Percentual do PIS</td><td>1.65%</td></tr>
                                    <tr><td><strong>COFINS CST</strong></td><td>Código de Situação Tributária da COFINS</td><td>01 — Operação tributável</td></tr>
                                    <tr><td><strong>COFINS Alíquota</strong></td><td>Percentual da COFINS</td><td>7.60%</td></tr>
                                    <tr><td><strong>IPI CST</strong></td><td>Código de Situação Tributária do IPI</td><td>50 — Saída tributada</td></tr>
                                    <tr><td><strong>IPI Alíquota</strong></td><td>Percentual do IPI</td><td>5%</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-warning py-2 mb-0">
                            <i class="fas fa-exclamation-triangle me-1"></i> <strong>Importante:</strong> Os dados fiscais devem ser configurados por um contador ou responsável fiscal. Valores incorretos podem gerar problemas na emissão de NF-e.
                        </div>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════ -->
            <!-- Configurações -->
            <!-- ══════════════════════════════════════ -->
            <div class="card border-0 shadow-sm mb-4" id="sec-config">
                <div class="card-header card-header-slate">
                    <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Configurações</h5>
                </div>
                <div class="card-body">
                    <p>Personalize todo o sistema de acordo com sua empresa:</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6 class="fw-bold"><i class="fas fa-building me-1 text-primary"></i> Dados da Empresa</h6>
                                <p class="small text-muted mb-0">Nome fantasia, razão social, CNPJ, inscrição estadual, endereço completo, telefone e logo da empresa. Esses dados são usados em boletos, NF-e e impressões.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6 class="fw-bold"><i class="fas fa-tags me-1 text-success"></i> Tabelas de Preço</h6>
                                <p class="small text-muted mb-0">Crie tabelas de preço diferenciadas por grupo de clientes. Cada tabela define preços específicos por produto/grade. Vincule a tabela ao cadastro do cliente.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6 class="fw-bold"><i class="fas fa-columns me-1 text-info"></i> Pipeline</h6>
                                <p class="small text-muted mb-0">Defina as metas de tempo (em horas) para cada etapa do pipeline. Quando ultrapassadas, o pedido fica destacado em vermelho como atrasado.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6 class="fw-bold"><i class="fas fa-file-invoice me-1 text-warning"></i> Dados Fiscais</h6>
                                <p class="small text-muted mb-0">Configurações padrão para NF-e: certificado digital, série, ambiente (produção/homologação), natureza da operação.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6 class="fw-bold"><i class="fas fa-barcode me-1 text-danger"></i> Dados Bancários / Boleto</h6>
                                <p class="small text-muted mb-0">Banco, agência, conta, carteira, código do cedente, convênio. Essas informações são usadas na geração de boletos FEBRABAN para parcelas.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6 class="fw-bold"><i class="fas fa-clipboard-check me-1 text-secondary"></i> Preparação (Checklist)</h6>
                                <p class="small text-muted mb-0">Configure os itens de checklist da etapa de preparação: conferência de peças, embalagem, etiquetagem, controle de qualidade.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════ -->
            <!-- Usuários -->
            <!-- ══════════════════════════════════════ -->
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
            <div class="card border-0 shadow-sm mb-4" id="sec-usuarios">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-user-shield me-2"></i>Usuários e Permissões</h5>
                </div>
                <div class="card-body">
                    <p>Gerencie o acesso ao sistema com controle granular de permissões.</p>

                    <h6 class="fw-bold mt-3">Tipos de usuário:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light"><tr><th>Tipo</th><th>Descrição</th></tr></thead>
                            <tbody>
                                <tr><td><strong>Admin</strong></td><td>Acesso total a todas as funcionalidades do sistema, incluindo configurações e gestão de usuários</td></tr>
                                <tr><td><strong>Funcionário</strong></td><td>Acesso limitado às páginas permitidas pelo grupo de permissão vinculado</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <h6 class="fw-bold mt-3">Grupos de permissão:</h6>
                    <p>Crie grupos para organizar as permissões dos funcionários. Cada grupo define quais <strong>módulos</strong> podem ser acessados.</p>

                    <h6 class="fw-bold mt-3">Exemplo de grupos:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light"><tr><th>Grupo</th><th>Permissões sugeridas</th></tr></thead>
                            <tbody>
                                <tr><td>Administração</td><td>Acesso total (admin)</td></tr>
                                <tr><td>Produção</td><td>Página Inicial, Pedidos, Pipeline, Painel de Produção, Setores</td></tr>
                                <tr><td>Vendas</td><td>Página Inicial, Pedidos, Clientes, Produtos, Agenda, Tabelas de Preço</td></tr>
                                <tr><td>Estoque</td><td>Página Inicial, Estoque, Produtos, Categorias</td></tr>
                                <tr><td>Financeiro</td><td>Página Inicial, Pagamentos, Entradas/Saídas</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <h6 class="fw-bold mt-3">Como cadastrar um usuário:</h6>
                    <ol>
                        <li>Acesse <strong>Usuários</strong> (ícone de engrenagem no canto superior)</li>
                        <li>Clique em <strong>"Novo Usuário"</strong></li>
                        <li>Preencha nome, email, telefone e senha</li>
                        <li>Selecione o <strong>tipo</strong> (Admin ou Funcionário)</li>
                        <li>Se Funcionário, selecione o <strong>grupo de permissão</strong></li>
                        <li>Clique em <strong>"Salvar"</strong></li>
                    </ol>

                    <div class="alert alert-warning py-2 mb-0">
                        <i class="fas fa-exclamation-triangle me-1"></i> Apenas <strong>administradores</strong> podem criar, editar ou excluir usuários e grupos.
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- ══════════════════════════════════════ -->
            <!-- Perfil -->
            <!-- ══════════════════════════════════════ -->
            <div class="card border-0 shadow-sm mb-4" id="sec-perfil">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-user-circle me-2"></i>Perfil do Usuário</h5>
                </div>
                <div class="card-body">
                    <p>Cada usuário pode gerenciar seus próprios dados pessoais.</p>

                    <h6 class="fw-bold mt-3">Como acessar:</h6>
                    <ol>
                        <li>Clique no seu <strong>nome</strong> no canto superior direito</li>
                        <li>Selecione <strong>"Meu Perfil"</strong></li>
                    </ol>

                    <h6 class="fw-bold mt-3">O que pode ser alterado:</h6>
                    <ul>
                        <li><strong>Nome</strong> — seu nome de exibição no sistema</li>
                        <li><strong>Email</strong> — usado para login e recuperação de senha</li>
                        <li><strong>Senha</strong> — altere quando necessário</li>
                        <li><strong>Foto de perfil</strong> — imagem que aparece ao lado do seu nome</li>
                    </ul>

                    <div class="alert alert-warning py-2 mb-0">
                        <i class="fas fa-exclamation-triangle me-1"></i> Seu tipo de acesso (admin/funcionário) e grupo de permissões só podem ser alterados por um <strong>administrador</strong>.
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════ -->
            <!-- Atalhos e Dicas -->
            <!-- ══════════════════════════════════════ -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header card-header-slate-d">
                    <h5 class="mb-0"><i class="fas fa-keyboard me-2"></i>Atalhos e Dicas</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <h6 class="fw-bold">Atalhos do Tour:</h6>
                            <ul class="mb-0">
                                <li><kbd>→</kbd> ou <kbd>Enter</kbd> — Próximo passo</li>
                                <li><kbd>←</kbd> — Passo anterior</li>
                                <li><kbd>Esc</kbd> — Pular tour</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold">Navegação:</h6>
                            <ul class="mb-0">
                                <li>🏠 Clique na <strong>logo</strong> para voltar à Página Inicial</li>
                                <li>🔔 O <strong>sino</strong> mostra alertas de pedidos atrasados</li>
                                <li>👤 Seu <strong>nome</strong> abre o menu de perfil</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mt-3">Dicas Gerais:</h6>
                            <ul class="mb-0">
                                <li>🔍 Use a <strong>barra de pesquisa</strong> nas listagens para busca rápida</li>
                                <li>📱 O sistema é <strong>responsivo</strong> e funciona em celular e tablet</li>
                                <li>🖼️ Fotos aceitam <strong>WebP</strong> (menor tamanho e melhor qualidade)</li>
                                <li>📤 Arraste produtos no <strong>Pipeline</strong> para mover entre etapas</li>
                                <li>💰 Pagamentos confirmados geram <strong>automaticamente</strong> uma entrada no livro caixa</li>
                                <li>📦 Produtos com estoque podem <strong>pular a produção</strong></li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mt-3">Estrutura do Menu:</h6>
                            <ul class="mb-0">
                                <li><strong>Comercial</strong> — Clientes, Pedidos, Agenda, Tabelas de Preço</li>
                                <li><strong>Catálogo</strong> — Produtos, Categorias, Estoque</li>
                                <li><strong>Produção</strong> — Linha de Produção, Painel, Setores</li>
                                <li><strong>Fiscal</strong> — Pagamentos, Entradas/Saídas</li>
                                <li><i class="fas fa-cog text-muted me-1"></i> Configurações e Usuários ficam no canto direito</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
