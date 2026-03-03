<div class="container-fluid py-4 main-content">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">

            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h2 class="fw-bold mb-1"><i class="fas fa-book text-primary me-2"></i>Manual do Sistema</h2>
                    <p class="text-muted mb-0">Guia completo de todas as funcionalidades do Akti</p>
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
                        <div class="col-6 col-md-4 col-lg-3"><a href="#sec-dashboard" class="btn btn-light btn-sm w-100 text-start"><i class="fas fa-chart-line me-1 text-primary"></i> Dashboard</a></div>
                        <div class="col-6 col-md-4 col-lg-3"><a href="#sec-pedidos" class="btn btn-light btn-sm w-100 text-start"><i class="fas fa-clipboard-list me-1 text-success"></i> Pedidos</a></div>
                        <div class="col-6 col-md-4 col-lg-3"><a href="#sec-pipeline" class="btn btn-light btn-sm w-100 text-start"><i class="fas fa-columns me-1 text-info"></i> Pipeline</a></div>
                        <div class="col-6 col-md-4 col-lg-3"><a href="#sec-clientes" class="btn btn-light btn-sm w-100 text-start"><i class="fas fa-users me-1 text-warning"></i> Clientes</a></div>
                        <div class="col-6 col-md-4 col-lg-3"><a href="#sec-produtos" class="btn btn-light btn-sm w-100 text-start"><i class="fas fa-boxes-stacked me-1 text-danger"></i> Produtos</a></div>
                        <div class="col-6 col-md-4 col-lg-3"><a href="#sec-estoque" class="btn btn-light btn-sm w-100 text-start"><i class="fas fa-warehouse me-1 text-secondary"></i> Estoque</a></div>
                        <div class="col-6 col-md-4 col-lg-3"><a href="#sec-setores" class="btn btn-light btn-sm w-100 text-start"><i class="fas fa-industry me-1 text-primary"></i> Setores</a></div>
                        <div class="col-6 col-md-4 col-lg-3"><a href="#sec-config" class="btn btn-light btn-sm w-100 text-start"><i class="fas fa-cog me-1 text-muted"></i> Configurações</a></div>
                        <div class="col-6 col-md-4 col-lg-3"><a href="#sec-usuarios" class="btn btn-light btn-sm w-100 text-start"><i class="fas fa-user-shield me-1 text-info"></i> Usuários</a></div>
                        <div class="col-6 col-md-4 col-lg-3"><a href="#sec-perfil" class="btn btn-light btn-sm w-100 text-start"><i class="fas fa-user-circle me-1 text-success"></i> Perfil</a></div>
                    </div>
                </div>
            </div>

            <!-- Dashboard -->
            <div class="card border-0 shadow-sm mb-4" id="sec-dashboard">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Dashboard</h5>
                </div>
                <div class="card-body">
                    <p>O Dashboard é a <strong>tela inicial</strong> do sistema e fornece uma visão geral do negócio.</p>
                    <h6 class="fw-bold mt-3">O que você encontra aqui:</h6>
                    <ul>
                        <li><strong>Resumo de Pedidos</strong> — Total por status (pendente, em produção, concluído)</li>
                        <li><strong>Faturamento</strong> — Valores totais e por período</li>
                        <li><strong>Pipeline</strong> — Distribuição visual dos pedidos</li>
                        <li><strong>Alertas</strong> — Pedidos com prazo próximo ou atrasados</li>
                    </ul>
                    <div class="alert alert-info py-2 mb-0">
                        <i class="fas fa-lightbulb me-1"></i> Clique nos cards do dashboard para ir direto à área correspondente.
                    </div>
                </div>
            </div>

            <!-- Pedidos -->
            <div class="card border-0 shadow-sm mb-4" id="sec-pedidos">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Pedidos</h5>
                </div>
                <div class="card-body">
                    <p>O módulo de Pedidos é o <strong>coração do sistema</strong>. Cada pedido representa uma solicitação de um cliente.</p>

                    <h6 class="fw-bold mt-3">Como criar um pedido:</h6>
                    <ol>
                        <li>Acesse <strong>Pedidos</strong> no menu</li>
                        <li>Clique em <strong>"Novo Pedido"</strong></li>
                        <li>Selecione o <strong>cliente</strong></li>
                        <li>Adicione os <strong>produtos</strong> (com grades, se houver)</li>
                        <li>Configure prioridade, prazo, frete e pagamento</li>
                        <li>Clique em <strong>"Salvar"</strong></li>
                    </ol>

                    <h6 class="fw-bold mt-3">Status dos pedidos:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light"><tr><th>Status</th><th>Descrição</th></tr></thead>
                            <tbody>
                                <tr><td><span class="badge bg-secondary">Orçamento</span></td><td>Aguardando aprovação do cliente</td></tr>
                                <tr><td><span class="badge bg-warning text-dark">Pendente</span></td><td>Aprovado, aguardando produção</td></tr>
                                <tr><td><span class="badge bg-info">Em Produção</span></td><td>Sendo fabricado/preparado</td></tr>
                                <tr><td><span class="badge bg-success">Concluído</span></td><td>Finalizado e entregue</td></tr>
                                <tr><td><span class="badge bg-danger">Cancelado</span></td><td>Cancelado</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <h6 class="fw-bold mt-3">Etapas do Pipeline:</h6>
                    <p><code>Contato → Orçamento → Venda → Produção → Preparação → Envio → Financeiro → Concluído</code></p>

                    <div class="alert alert-info py-2 mb-0">
                        <i class="fas fa-lightbulb me-1"></i> Você pode adicionar <strong>custos extras</strong> e gerar <strong>links públicos</strong> para o cliente visualizar o orçamento.
                    </div>
                </div>
            </div>

            <!-- Pipeline -->
            <div class="card border-0 shadow-sm mb-4" id="sec-pipeline">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-columns me-2"></i>Pipeline de Produção</h5>
                </div>
                <div class="card-body">
                    <p>O Pipeline é uma visão <strong>Kanban</strong> (quadro visual) de todos os pedidos organizados por etapa.</p>
                    <ul>
                        <li>Cada <strong>coluna</strong> = uma etapa do processo</li>
                        <li>Cada <strong>card</strong> = um pedido</li>
                        <li><strong>Arraste</strong> os cards entre colunas para mover pedidos</li>
                        <li>Pedidos que ultrapassam a meta de tempo ficam <strong>destacados</strong></li>
                    </ul>
                    <div class="alert alert-warning py-2 mb-0">
                        <i class="fas fa-hand-pointer me-1"></i> Basta arrastar e soltar para mover um pedido de etapa!
                    </div>
                </div>
            </div>

            <!-- Clientes -->
            <div class="card border-0 shadow-sm mb-4" id="sec-clientes">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i>Clientes</h5>
                </div>
                <div class="card-body">
                    <p>Gerencie sua base de clientes com informações completas.</p>
                    <ul>
                        <li>Cadastre com <strong>nome, email, telefone, CPF/CNPJ</strong></li>
                        <li>Vincule uma <strong>tabela de preço</strong> específica</li>
                        <li>Acesse o <strong>histórico de pedidos</strong> do cliente</li>
                        <li>Adicione <strong>foto</strong> e <strong>endereço</strong></li>
                    </ul>
                    <div class="alert alert-info py-2 mb-0">
                        <i class="fas fa-lightbulb me-1"></i> Ao vincular uma tabela de preço, todos os pedidos desse cliente usarão os preços especiais automaticamente.
                    </div>
                </div>
            </div>

            <!-- Produtos -->
            <div class="card border-0 shadow-sm mb-4" id="sec-produtos">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-boxes-stacked me-2"></i>Produtos</h5>
                </div>
                <div class="card-body">
                    <p>Cadastre seus produtos com informações completas.</p>

                    <h6 class="fw-bold mt-3">Funcionalidades:</h6>
                    <ul>
                        <li><strong>Fotos</strong> — Múltiplas fotos (JPG, PNG, WebP, GIF)</li>
                        <li><strong>Categorias / Subcategorias</strong> — Organize seu catálogo</li>
                        <li><strong>Grades</strong> — Variações (tamanho, cor, material) com combinações automáticas</li>
                        <li><strong>Dados Fiscais</strong> — NCM, CFOP, CSTs, alíquotas para NF-e</li>
                        <li><strong>Setores</strong> — Vincule setores de produção ao produto</li>
                        <li><strong>Estoque</strong> — Ative o controle de estoque por produto</li>
                    </ul>

                    <div class="alert alert-info py-2 mb-0">
                        <i class="fas fa-lightbulb me-1"></i> Grades podem ser herdadas da <strong>categoria</strong> — configure uma vez e aplique a todos os produtos.
                    </div>
                </div>
            </div>

            <!-- Estoque -->
            <div class="card border-0 shadow-sm mb-4" id="sec-estoque">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-warehouse me-2"></i>Estoque</h5>
                </div>
                <div class="card-body">
                    <p>Controle o estoque em <strong>múltiplos armazéns</strong>.</p>
                    <ul>
                        <li>Crie <strong>armazéns</strong> (Estoque Principal, Loja, Depósito)</li>
                        <li>Registre <strong>entradas e saídas</strong> com observações</li>
                        <li>Faça <strong>transferências</strong> entre armazéns</li>
                        <li>Consulte saldos e histórico de movimentações</li>
                    </ul>
                    <div class="alert alert-success py-2 mb-0">
                        <i class="fas fa-check-circle me-1"></i> Produtos com estoque disponível podem <strong>pular a etapa de produção</strong>!
                    </div>
                </div>
            </div>

            <!-- Setores -->
            <div class="card border-0 shadow-sm mb-4" id="sec-setores">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-industry me-2"></i>Setores de Produção</h5>
                </div>
                <div class="card-body">
                    <p>Organize sua <strong>linha produtiva</strong> por setores.</p>
                    <ul>
                        <li>Crie setores como <em>Costura, Corte, Estamparia, Embalagem</em></li>
                        <li>Vincule setores a <strong>produtos</strong>, <strong>categorias</strong> ou <strong>subcategorias</strong></li>
                        <li>No pipeline, acompanhe o progresso <strong>setor a setor</strong></li>
                    </ul>
                </div>
            </div>

            <!-- Configurações -->
            <div class="card border-0 shadow-sm mb-4" id="sec-config">
                <div class="card-header" style="background: #475569; color: white;">
                    <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Configurações</h5>
                </div>
                <div class="card-body">
                    <p>Personalize todo o sistema:</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6 class="fw-bold"><i class="fas fa-building me-1 text-primary"></i> Dados da Empresa</h6>
                                <p class="small text-muted mb-0">Nome, CNPJ, endereço, telefone, logo</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6 class="fw-bold"><i class="fas fa-tags me-1 text-success"></i> Tabelas de Preço</h6>
                                <p class="small text-muted mb-0">Crie preços diferenciados por grupo/cliente</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6 class="fw-bold"><i class="fas fa-columns me-1 text-info"></i> Pipeline</h6>
                                <p class="small text-muted mb-0">Metas de tempo, cores e ícones das etapas</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6 class="fw-bold"><i class="fas fa-file-invoice me-1 text-warning"></i> Dados Fiscais</h6>
                                <p class="small text-muted mb-0">Configurações para NF-e e boletos</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Usuários -->
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
            <div class="card border-0 shadow-sm mb-4" id="sec-usuarios">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-user-shield me-2"></i>Usuários e Permissões</h5>
                </div>
                <div class="card-body">
                    <p>Gerencie o acesso ao sistema:</p>
                    <ul>
                        <li>Cadastre <strong>usuários</strong> (Admin ou Funcionário)</li>
                        <li>Crie <strong>grupos de permissão</strong></li>
                        <li>Cada grupo define quais <strong>páginas</strong> o membro acessa</li>
                    </ul>

                    <h6 class="fw-bold mt-3">Exemplo de grupos:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light"><tr><th>Grupo</th><th>Permissões</th></tr></thead>
                            <tbody>
                                <tr><td>Administração</td><td>Acesso total</td></tr>
                                <tr><td>Produção</td><td>Dashboard, Pedidos, Pipeline, Setores</td></tr>
                                <tr><td>Vendas</td><td>Dashboard, Pedidos, Clientes, Produtos</td></tr>
                                <tr><td>Estoque</td><td>Dashboard, Estoque, Produtos</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Perfil -->
            <div class="card border-0 shadow-sm mb-4" id="sec-perfil">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-user-circle me-2"></i>Perfil do Usuário</h5>
                </div>
                <div class="card-body">
                    <p>Cada usuário pode gerenciar seus próprios dados:</p>
                    <ol>
                        <li>Clique no seu <strong>nome</strong> (canto superior direito)</li>
                        <li>Clique em <strong>"Meu Perfil"</strong></li>
                        <li>Altere nome, email ou senha</li>
                        <li>Clique em <strong>"Atualizar"</strong></li>
                    </ol>
                    <div class="alert alert-warning py-2 mb-0">
                        <i class="fas fa-exclamation-triangle me-1"></i> Seu perfil (admin/funcionário) e grupo de permissões só podem ser alterados por um administrador.
                    </div>
                </div>
            </div>

            <!-- Atalhos -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header" style="background: #1e293b; color: white;">
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
                            <h6 class="fw-bold">Dicas Gerais:</h6>
                            <ul class="mb-0">
                                <li>🔍 Use a barra de pesquisa nas listagens</li>
                                <li>📱 O sistema funciona em celular e tablet</li>
                                <li>🖼️ Fotos aceitam WebP (menor tamanho)</li>
                                <li>🔔 O sino mostra alertas de pedidos</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
