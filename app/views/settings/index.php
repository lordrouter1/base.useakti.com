<?php
$activeTab = \Akti\Core\ModuleBootloader::sanitizeSettingsTab($_GET['tab'] ?? 'company');
$canUseBoletoModule = \Akti\Core\ModuleBootloader::isModuleEnabled('boleto');
$canUseFiscalModule = \Akti\Core\ModuleBootloader::isModuleEnabled('fiscal');
?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-building me-2"></i>Configurações do Sistema</h1>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'company' ? 'active' : '' ?>" href="?page=settings&tab=company">
                <i class="fas fa-building me-1"></i> Dados da Empresa
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'prices' ? 'active' : '' ?>" href="?page=settings&tab=prices">
                <i class="fas fa-tags me-1"></i> Tabelas de Preço
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'preparation' ? 'active' : '' ?>" href="?page=settings&tab=preparation">
                <i class="fas fa-boxes-packing me-1"></i> Etapas de Preparo
            </a>
        </li>
        <?php if ($canUseBoletoModule): ?>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'boleto' ? 'active' : '' ?>" href="?page=settings&tab=boleto">
                <i class="fas fa-barcode me-1"></i> Boleto / Bancário
            </a>
        </li>
        <?php endif; ?>
        <?php if ($canUseFiscalModule): ?>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'fiscal' ? 'active' : '' ?>" href="?page=settings&tab=fiscal">
                <i class="fas fa-file-invoice me-1"></i> Fiscal / NF-e
            </a>
        </li>
        <?php endif; ?>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'security' ? 'active' : '' ?>" href="?page=settings&tab=security">
                <i class="fas fa-shield-alt me-1"></i> Segurança
            </a>
        </li>
    </ul>

    <?php if ($activeTab === 'company'): ?>
    <!-- ══════════ ABA: DADOS DA EMPRESA ══════════ -->
    <form method="POST" action="?page=settings&action=saveCompany" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="row">
            <div class="col-lg-8">
                <!-- Dados Básicos -->
                <fieldset class="border p-4 mb-4 rounded bg-white shadow-sm">
                    <legend class="float-none w-auto px-2 fs-5 text-primary fw-bold">
                        <i class="fas fa-id-card me-2"></i>Dados Básicos
                    </legend>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Nome da Empresa</label>
                            <input type="text" class="form-control" name="company_name" value="<?= eAttr($settings['company_name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">CNPJ / CPF</label>
                            <input type="text" class="form-control" name="company_document" value="<?= eAttr($settings['company_document'] ?? '') ?>" placeholder="00.000.000/0000-00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Telefone</label>
                            <input type="text" class="form-control" name="company_phone" value="<?= eAttr($settings['company_phone'] ?? '') ?>" placeholder="(00) 00000-0000">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">E-mail</label>
                            <input type="email" class="form-control" name="company_email" value="<?= eAttr($settings['company_email'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Website</label>
                            <input type="text" class="form-control" name="company_website" value="<?= eAttr($settings['company_website'] ?? '') ?>" placeholder="https://">
                        </div>
                    </div>
                </fieldset>

                <!-- Endereço -->
                <fieldset class="border p-4 mb-4 rounded bg-white shadow-sm">
                    <legend class="float-none w-auto px-2 fs-5 text-primary fw-bold">
                        <i class="fas fa-map-marker-alt me-2"></i>Endereço
                    </legend>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted">CEP</label>
                            <input type="text" class="form-control" name="company_zipcode" value="<?= eAttr($settings['company_zipcode'] ?? '') ?>" placeholder="00000-000">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted">Tipo Logradouro</label>
                            <select class="form-select" name="company_address_type">
                                <?php 
                                $tipos = ['Rua', 'Avenida', 'Travessa', 'Praça', 'Alameda', 'Rodovia', 'Outro'];
                                foreach ($tipos as $t): ?>
                                <option value="<?= $t ?>" <?= ($settings['company_address_type'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Nome do Logradouro</label>
                            <input type="text" class="form-control" name="company_address_name" value="<?= eAttr($settings['company_address_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold small text-muted">Número</label>
                            <input type="text" class="form-control" name="company_address_number" value="<?= eAttr($settings['company_address_number'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Bairro</label>
                            <input type="text" class="form-control" name="company_neighborhood" value="<?= eAttr($settings['company_neighborhood'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted">Complemento</label>
                            <input type="text" class="form-control" name="company_complement" value="<?= eAttr($settings['company_complement'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Cidade</label>
                            <input type="text" class="form-control" name="company_city" value="<?= eAttr($settings['company_city'] ?? '') ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold small text-muted">Estado (UF)</label>
                            <input type="text" class="form-control" name="company_state" value="<?= eAttr($settings['company_state'] ?? '') ?>" maxlength="2" placeholder="SP">
                        </div>
                    </div>
                </fieldset>

                <!-- Configurações de Orçamento -->
                <fieldset class="border p-4 mb-4 rounded bg-white shadow-sm">
                    <legend class="float-none w-auto px-2 fs-5 text-primary fw-bold">
                        <i class="fas fa-file-invoice me-2"></i>Configurações de Orçamento
                    </legend>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted">Validade (dias)</label>
                            <input type="number" class="form-control" name="quote_validity_days" value="<?= eAttr($settings['quote_validity_days'] ?? '15') ?>" min="1">
                        </div>
                        <div class="col-md-9">
                            <label class="form-label fw-bold small text-muted">Nota de Rodapé do Orçamento</label>
                            <textarea class="form-control" name="quote_footer_note" rows="2"><?= eAttr($settings['quote_footer_note'] ?? '') ?></textarea>
                        </div>
                    </div>
                </fieldset>

                <div class="text-end mb-4">
                    <button type="submit" class="btn btn-primary px-4 fw-bold"><i class="fas fa-save me-2"></i>Salvar Configurações</button>
                </div>
            </div>

            <!-- Coluna Direita: Logo -->
            <div class="col-lg-4">
                <fieldset class="border p-4 mb-4 rounded bg-white shadow-sm">
                    <legend class="float-none w-auto px-2 fs-5 text-primary fw-bold">
                        <i class="fas fa-image me-2"></i>Logo da Empresa
                    </legend>
                    <div class="text-center mb-3">
                        <?php if (!empty($settings['company_logo']) && file_exists($settings['company_logo'])): ?>
                            <img src="<?= $settings['company_logo'] ?>" alt="Logo" class="img-fluid rounded shadow-sm" style="max-height: 200px;">
                            <div class="mt-2">
                                <label class="form-check-label small text-danger">
                                    <input type="checkbox" name="remove_logo" value="1" class="form-check-input"> Remover logo atual
                                </label>
                            </div>
                        <?php else: ?>
                            <div class="border rounded p-4 text-muted bg-light">
                                <i class="fas fa-image d-block mb-2" style="font-size:3rem; opacity:0.3;"></i>
                                <small>Nenhuma logo cadastrada</small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="form-label fw-bold small text-muted"><?= !empty($settings['company_logo']) ? 'Substituir' : 'Enviar' ?> Logo</label>
                        <input type="file" class="form-control form-control-sm" name="company_logo" accept="image/*">
                        <small class="text-muted">Formatos: JPG, PNG, SVG. Recomendado: 300x100px</small>
                    </div>
                </fieldset>

                <!-- Preview do Endereço -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light py-2">
                        <h6 class="mb-0 text-primary fw-bold"><i class="fas fa-eye me-2"></i>Prévia do Cabeçalho</h6>
                    </div>
                    <div class="card-body text-center">
                        <?php if (!empty($settings['company_logo']) && file_exists($settings['company_logo'])): ?>
                            <img src="<?= $settings['company_logo'] ?>" alt="Logo" style="max-height: 60px;" class="mb-2">
                        <?php else: ?>
                            <i class="fas fa-print me-2 text-primary" style="font-size:1.5rem;"></i>
                        <?php endif; ?>
                        <div class="fw-bold"><?= eAttr($settings['company_name'] ?? 'Minha Gráfica') ?></div>
                        <?php if (!empty($settings['company_document'])): ?>
                        <small class="text-muted d-block"><?= $settings['company_document'] ?></small>
                        <?php endif; ?>
                        <?php if (!empty($settings['company_phone'])): ?>
                        <small class="text-muted d-block"><i class="fas fa-phone me-1"></i><?= $settings['company_phone'] ?></small>
                        <?php endif; ?>
                        <?php if (!empty($settings['company_email'])): ?>
                        <small class="text-muted d-block"><i class="fas fa-envelope me-1"></i><?= $settings['company_email'] ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <?php elseif ($activeTab === 'prices'): ?>
    <!-- ══════════ ABA: TABELAS DE PREÇO ══════════ -->
    <?php if (!empty($priceTableLimitReached)): ?>
    <div class="alert alert-warning border-warning d-flex align-items-center mb-3" role="alert">
        <i class="fas fa-exclamation-triangle fs-5 me-3 text-warning"></i>
        <div>
            <strong>Limite do plano atingido!</strong> Você possui <strong><?= $priceTableLimitInfo['current'] ?></strong> de <strong><?= $priceTableLimitInfo['max'] ?></strong> tabelas de preço permitidas.
            <span class="text-muted">Para cadastrar mais tabelas, entre em contato com o suporte para fazer um upgrade do seu plano.</span>
        </div>
    </div>
    <?php endif; ?>
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 text-primary"><i class="fas fa-tags me-2"></i>Tabelas de Preço</h5>
                    <?php if (!empty($priceTableLimitReached)): ?>
                        <button class="btn btn-primary btn-sm disabled" disabled title="Limite do plano atingido">
                            <i class="fas fa-plus me-1"></i> Nova Tabela
                        </button>
                    <?php else: ?>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNewTable">
                            <i class="fas fa-plus me-1"></i> Nova Tabela
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nome</th>
                                    <th>Descrição</th>
                                    <th class="text-center">Produtos</th>
                                    <th class="text-center">Tipo</th>
                                    <th class="text-center" style="width:120px;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($priceTables)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">Nenhuma tabela de preço cadastrada.</td></tr>
                                <?php else: ?>
                                <?php foreach ($priceTables as $pt): ?>
                                <tr>
                                    <td class="fw-bold"><?= eAttr($pt['name']) ?></td>
                                    <td class="text-muted small"><?= eAttr($pt['description'] ?? '') ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary rounded-pill"><?= $pt['item_count'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($pt['is_default']): ?>
                                            <span class="badge bg-success"><i class="fas fa-star me-1"></i>Padrão</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Personalizada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="?page=settings&action=editPriceTable&id=<?= $pt['id'] ?>" class="btn btn-outline-primary" title="Editar Preços">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if (!$pt['is_default']): ?>
                                            <a href="?page=settings&action=deletePriceTable&id=<?= $pt['id'] ?>" class="btn btn-outline-danger btn-delete-table" title="Excluir">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info lateral -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0 text-primary fw-bold"><i class="fas fa-info-circle me-2"></i>Como Funciona</h6>
                </div>
                <div class="card-body small">
                    <p><strong>Tabelas de preço</strong> permitem definir preços diferenciados para cada produto.</p>
                    <ul class="mb-2">
                        <li>A <strong>Tabela Padrão</strong> é usada quando o cliente não tem tabela atribuída.</li>
                        <li>Associe uma tabela a um cliente no <strong>cadastro do cliente</strong>.</li>
                        <li>Ao criar um orçamento, o preço será carregado automaticamente da tabela do cliente.</li>
                        <li>O preço pode ser <strong>alterado manualmente</strong> durante o orçamento.</li>
                    </ul>
                    <p class="text-muted mb-0"><strong>Prioridade:</strong> Tabela do Cliente → Tabela Padrão → Preço do Produto.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nova Tabela -->
    <div class="modal fade" id="modalNewTable" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="?page=settings&action=createPriceTable">
                <?= csrf_field() ?>
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Nova Tabela de Preço</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nome da Tabela</label>
                            <input type="text" class="form-control" name="name" required placeholder="Ex: Tabela Atacado">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Descrição</label>
                            <textarea class="form-control" name="description" rows="2" placeholder="Descrição opcional..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Criar Tabela</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($activeTab === 'preparation'): ?>
    <!-- ══════════ ABA: ETAPAS DE PREPARO ══════════ -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0" style="color: #1abc9c;"><i class="fas fa-boxes-packing me-2"></i>Etapas de Preparo dos Pedidos</h5>
                    <button class="btn btn-sm text-white" style="background:#1abc9c;" data-bs-toggle="modal" data-bs-target="#modalNewStep">
                        <i class="fas fa-plus me-1"></i> Nova Etapa
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:50px;" class="text-center">Ordem</th>
                                    <th style="width:50px;" class="text-center">Ícone</th>
                                    <th>Nome da Etapa</th>
                                    <th>Descrição</th>
                                    <th class="text-center" style="width:90px;">Status</th>
                                    <th class="text-center" style="width:130px;">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="prepStepsTableBody">
                                <?php if (empty($preparationSteps)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">Nenhuma etapa de preparo cadastrada.</td></tr>
                                <?php else: ?>
                                <?php foreach ($preparationSteps as $step): ?>
                                <tr class="<?= !$step['is_active'] ? 'table-secondary opacity-75' : '' ?>">
                                    <td class="text-center fw-bold text-muted"><?= $step['sort_order'] ?></td>
                                    <td class="text-center">
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle" 
                                              style="width:32px;height:32px;background:<?= $step['is_active'] ? '#e0f7f1' : '#f0f0f0' ?>;">
                                            <i class="<?= eAttr($step['icon']) ?>" style="color:<?= $step['is_active'] ? '#1abc9c' : '#999' ?>;font-size:0.85rem;"></i>
                                        </span>
                                    </td>
                                    <td class="fw-bold <?= !$step['is_active'] ? 'text-muted text-decoration-line-through' : '' ?>">
                                        <?= eAttr($step['label']) ?>
                                        <div class="small text-muted" style="font-size:0.7rem;">Chave: <code><?= eAttr($step['step_key']) ?></code></div>
                                    </td>
                                    <td class="small text-muted"><?= eAttr($step['description']) ?></td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-toggle-step <?= $step['is_active'] ? 'btn-success' : 'btn-outline-secondary' ?>" 
                                                data-step-id="<?= $step['id'] ?>" title="<?= $step['is_active'] ? 'Ativa' : 'Inativa' ?>">
                                            <i class="fas fa-<?= $step['is_active'] ? 'check-circle' : 'times-circle' ?> me-1"></i>
                                            <?= $step['is_active'] ? 'Ativa' : 'Inativa' ?>
                                        </button>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary btn-edit-step" 
                                                    data-id="<?= $step['id'] ?>"
                                                    data-label="<?= eAttr($step['label']) ?>"
                                                    data-description="<?= eAttr($step['description']) ?>"
                                                    data-icon="<?= eAttr($step['icon']) ?>"
                                                    data-sort="<?= $step['sort_order'] ?>"
                                                    data-active="<?= $step['is_active'] ?>"
                                                    title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?page=settings&action=deletePreparationStep&id=<?= $step['id'] ?>" 
                                               class="btn btn-outline-danger btn-delete-step" title="Excluir">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info lateral -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0 fw-bold" style="color:#1abc9c;"><i class="fas fa-info-circle me-2"></i>Como Funciona</h6>
                </div>
                <div class="card-body small">
                    <p><strong>Etapas de preparo</strong> são o checklist que aparece quando um pedido entra na fase de <span class="badge" style="background:#1abc9c;">Preparação</span>.</p>
                    <ul class="mb-2">
                        <li>As etapas <strong>ativas</strong> serão exibidas para todos os pedidos em preparação.</li>
                        <li>Desative etapas que não são necessárias no momento sem excluí-las.</li>
                        <li>A <strong>ordem</strong> define a sequência de exibição no checklist.</li>
                        <li>Cada etapa terá registro de quem confirmou e quando.</li>
                    </ul>
                    <p class="text-muted mb-0"><strong>Dica:</strong> Use ícones do <a href="https://fontawesome.com/search?o=r&m=free&s=solid" target="_blank">Font Awesome</a> (ex: <code>fas fa-box</code>).</p>
                </div>
            </div>

            <!-- Preview das etapas ativas -->
            <div class="card border-0 shadow-sm">
                <div class="card-header py-2" style="background: #e0f7f1;">
                    <h6 class="mb-0 fw-bold" style="color:#1abc9c;"><i class="fas fa-eye me-2"></i>Prévia do Checklist</h6>
                </div>
                <div class="card-body p-2">
                    <?php 
                    $activeSteps = array_filter($preparationSteps ?? [], function($s) { return $s['is_active']; });
                    if (empty($activeSteps)): ?>
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-clipboard d-block mb-2" style="font-size:1.5rem;opacity:0.3;"></i>
                        <small>Nenhuma etapa ativa</small>
                    </div>
                    <?php else: ?>
                    <?php foreach ($activeSteps as $step): ?>
                    <div class="d-flex align-items-center gap-2 p-2 border-bottom">
                        <span class="d-flex align-items-center justify-content-center rounded-circle border border-2" 
                              style="width:24px;height:24px;min-width:24px;border-color:#ccc !important;">
                            <i class="<?= eAttr($step['icon']) ?> text-muted" style="font-size:0.6rem;"></i>
                        </span>
                        <div>
                            <div class="fw-bold" style="font-size:0.8rem;"><?= eAttr($step['label']) ?></div>
                            <?php if (!empty($step['description'])): ?>
                            <div class="text-muted" style="font-size:0.65rem;"><?= eAttr($step['description']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div class="text-center text-muted mt-2" style="font-size:0.7rem;">
                        <i class="fas fa-info-circle me-1"></i><?= count($activeSteps) ?> etapa(s) ativa(s)
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nova Etapa -->
    <div class="modal fade" id="modalNewStep" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="?page=settings&action=addPreparationStep">
                <?= csrf_field() ?>
                <div class="modal-content">
                    <div class="modal-header" style="background:#e0f7f1;">
                        <h5 class="modal-title" style="color:#1abc9c;"><i class="fas fa-plus-circle me-2"></i>Nova Etapa de Preparo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nome da Etapa <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="label" required placeholder="Ex: Conferência de Cores">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Descrição</label>
                            <textarea class="form-control" name="description" rows="2" placeholder="Descrição breve da etapa..."></textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label fw-bold">Ícone <small class="text-muted">(classe Font Awesome)</small></label>
                                <div class="input-group">
                                    <span class="input-group-text" id="newStepIconPreview"><i class="fas fa-check"></i></span>
                                    <input type="text" class="form-control" name="icon" value="fas fa-check" placeholder="fas fa-check" id="newStepIconInput">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Ordem</label>
                                <input type="number" class="form-control" name="sort_order" value="0" min="0">
                            </div>
                        </div>
                        <div class="mt-3">
                            <small class="text-muted"><i class="fas fa-lightbulb me-1"></i>Ícones populares: 
                                <code class="cursor-pointer icon-pick" data-icon="fas fa-check">fa-check</code>,
                                <code class="cursor-pointer icon-pick" data-icon="fas fa-box">fa-box</code>,
                                <code class="cursor-pointer icon-pick" data-icon="fas fa-cut">fa-cut</code>,
                                <code class="cursor-pointer icon-pick" data-icon="fas fa-search">fa-search</code>,
                                <code class="cursor-pointer icon-pick" data-icon="fas fa-file-check">fa-file-check</code>,
                                <code class="cursor-pointer icon-pick" data-icon="fas fa-truck-loading">fa-truck-loading</code>,
                                <code class="cursor-pointer icon-pick" data-icon="fas fa-list-check">fa-list-check</code>,
                                <code class="cursor-pointer icon-pick" data-icon="fas fa-paint-roller">fa-paint-roller</code>,
                                <code class="cursor-pointer icon-pick" data-icon="fas fa-print">fa-print</code>,
                                <code class="cursor-pointer icon-pick" data-icon="fas fa-scissors">fa-scissors</code>
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn text-white" style="background:#1abc9c;"><i class="fas fa-save me-1"></i> Criar Etapa</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Editar Etapa -->
    <div class="modal fade" id="modalEditStep" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="?page=settings&action=updatePreparationStep">
                <?= csrf_field() ?>
                <div class="modal-content">
                    <div class="modal-header" style="background:#e0f7f1;">
                        <h5 class="modal-title" style="color:#1abc9c;"><i class="fas fa-edit me-2"></i>Editar Etapa de Preparo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="editStepId">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nome da Etapa <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="label" id="editStepLabel" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Descrição</label>
                            <textarea class="form-control" name="description" id="editStepDesc" rows="2"></textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label fw-bold">Ícone</label>
                                <div class="input-group">
                                    <span class="input-group-text" id="editStepIconPreview"><i class="fas fa-check"></i></span>
                                    <input type="text" class="form-control" name="icon" id="editStepIcon" value="fas fa-check">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Ordem</label>
                                <input type="number" class="form-control" name="sort_order" id="editStepSort" value="0" min="0">
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="editStepActive" name="is_active" value="1" checked>
                                <label class="form-check-label fw-bold" for="editStepActive">Etapa Ativa</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn text-white" style="background:#1abc9c;"><i class="fas fa-save me-1"></i> Salvar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($activeTab === 'boleto' && $canUseBoletoModule): ?>
    <!-- ══════════ ABA: BOLETO / BANCÁRIO ══════════ -->
    <form method="POST" action="?page=settings&action=saveBankSettings">
        <?= csrf_field() ?>
        <div class="row">
            <div class="col-lg-8">
                <!-- Dados Bancários do Cedente -->
                <fieldset class="border p-4 mb-4 rounded bg-white shadow-sm">
                    <legend class="float-none w-auto px-2 fs-5 fw-bold" style="color: #f39c12;">
                        <i class="fas fa-university me-2"></i>Dados Bancários do Cedente
                    </legend>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Banco</label>
                            <select class="form-select" name="boleto_banco" id="boletoBanco">
                                <option value="">Selecione o banco...</option>
                                <?php
                                $bancos = [
                                    '001' => '001 — Banco do Brasil',
                                    '033' => '033 — Santander',
                                    '104' => '104 — Caixa Econômica Federal',
                                    '237' => '237 — Bradesco',
                                    '341' => '341 — Itaú Unibanco',
                                    '356' => '356 — Banco Real (ABN Amro)',
                                    '389' => '389 — Banco Mercantil do Brasil',
                                    '399' => '399 — HSBC',
                                    '422' => '422 — Banco Safra',
                                    '453' => '453 — Banco Rural',
                                    '633' => '633 — Banco Rendimento',
                                    '652' => '652 — Itaú Unibanco Holding',
                                    '707' => '707 — Banco Daycoval',
                                    '745' => '745 — Citibank',
                                    '748' => '748 — Sicredi',
                                    '756' => '756 — Sicoob',
                                    '084' => '084 — Uniprime',
                                    '136' => '136 — Unicred',
                                    '077' => '077 — Banco Inter',
                                    '260' => '260 — Nu Pagamentos (Nubank)',
                                    '336' => '336 — Banco C6',
                                    '290' => '290 — PagSeguro',
                                    '380' => '380 — PicPay',
                                    '403' => '403 — Cora SCD',
                                    '323' => '323 — Mercado Pago',
                                ];
                                foreach ($bancos as $cod => $nome): ?>
                                <option value="<?= $cod ?>" <?= ($settings['boleto_banco'] ?? '') === $cod ? 'selected' : '' ?>><?= $nome ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted">Agência</label>
                            <input type="text" class="form-control" name="boleto_agencia" value="<?= eAttr($settings['boleto_agencia'] ?? '') ?>" placeholder="0000" maxlength="10">
                            <small class="text-muted" style="font-size:0.65rem;">Sem dígito verificador</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted">Dígito Agência</label>
                            <input type="text" class="form-control" name="boleto_agencia_dv" value="<?= eAttr($settings['boleto_agencia_dv'] ?? '') ?>" placeholder="0" maxlength="1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Conta Corrente</label>
                            <input type="text" class="form-control" name="boleto_conta" value="<?= eAttr($settings['boleto_conta'] ?? '') ?>" placeholder="00000000" maxlength="15">
                            <small class="text-muted" style="font-size:0.65rem;">Sem dígito verificador</small>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold small text-muted">Dígito Conta</label>
                            <input type="text" class="form-control" name="boleto_conta_dv" value="<?= eAttr($settings['boleto_conta_dv'] ?? '') ?>" placeholder="0" maxlength="1">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted">Carteira</label>
                            <input type="text" class="form-control" name="boleto_carteira" value="<?= eAttr($settings['boleto_carteira'] ?? '109') ?>" placeholder="Ex: 109, 17, RG" maxlength="5">
                            <small class="text-muted" style="font-size:0.65rem;">Consulte o banco</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted">Espécie Moeda</label>
                            <select class="form-select" name="boleto_especie">
                                <option value="R$" <?= ($settings['boleto_especie'] ?? 'R$') === 'R$' ? 'selected' : '' ?>>R$ (Real)</option>
                                <option value="US$" <?= ($settings['boleto_especie'] ?? '') === 'US$' ? 'selected' : '' ?>>US$ (Dólar)</option>
                            </select>
                        </div>
                    </div>
                </fieldset>

                <!-- Cedente / Beneficiário -->
                <fieldset class="border p-4 mb-4 rounded bg-white shadow-sm">
                    <legend class="float-none w-auto px-2 fs-5 fw-bold" style="color: #f39c12;">
                        <i class="fas fa-user-tie me-2"></i>Cedente / Beneficiário
                    </legend>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Nome/Razão Social do Cedente</label>
                            <input type="text" class="form-control" name="boleto_cedente" value="<?= eAttr($settings['boleto_cedente'] ?? $settings['company_name'] ?? '') ?>" placeholder="Nome conforme registrado no banco">
                            <small class="text-muted" style="font-size:0.65rem;">Conforme registrado junto ao banco</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">CNPJ/CPF do Cedente</label>
                            <input type="text" class="form-control" name="boleto_cedente_documento" value="<?= eAttr($settings['boleto_cedente_documento'] ?? $settings['company_document'] ?? '') ?>" placeholder="00.000.000/0000-00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Código do Cedente / Convênio</label>
                            <input type="text" class="form-control" name="boleto_convenio" value="<?= eAttr($settings['boleto_convenio'] ?? '') ?>" placeholder="Ex: 1234567" maxlength="20">
                            <small class="text-muted" style="font-size:0.65rem;">Fornecido pelo banco ao contratar o serviço de cobrança</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Nosso Número (Próximo)</label>
                            <input type="number" class="form-control" name="boleto_nosso_numero" value="<?= eAttr($settings['boleto_nosso_numero'] ?? '1') ?>" placeholder="1" min="1">
                            <small class="text-muted" style="font-size:0.65rem;">Será incrementado automaticamente a cada boleto gerado</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Nº de Dígitos do Nosso Número</label>
                            <select class="form-select" name="boleto_nosso_numero_digitos">
                                <?php for ($d = 6; $d <= 12; $d++): ?>
                                <option value="<?= $d ?>" <?= ($settings['boleto_nosso_numero_digitos'] ?? '7') == $d ? 'selected' : '' ?>><?= $d ?> dígitos</option>
                                <?php endfor; ?>
                            </select>
                            <small class="text-muted" style="font-size:0.65rem;">Depende do banco/convênio</small>
                        </div>
                    </div>
                </fieldset>

                <!-- Instruções do Boleto -->
                <fieldset class="border p-4 mb-4 rounded bg-white shadow-sm">
                    <legend class="float-none w-auto px-2 fs-5 fw-bold" style="color: #f39c12;">
                        <i class="fas fa-file-alt me-2"></i>Instruções e Textos do Boleto
                    </legend>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold small text-muted">Instruções de Pagamento (aparece no boleto)</label>
                            <textarea class="form-control" name="boleto_instrucoes" rows="3" placeholder="Linha 1: Não receber após o vencimento.&#10;Linha 2: Multa de 2% após vencimento.&#10;Linha 3: Juros de 1% ao mês."><?= eAttr($settings['boleto_instrucoes'] ?? "Não receber após o vencimento.\nMulta de 2% após o vencimento.\nJuros de 1% ao mês.") ?></textarea>
                            <small class="text-muted" style="font-size:0.65rem;">Cada linha será exibida separadamente. Máximo 3 linhas recomendado.</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted">Multa (%)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="boleto_multa" step="0.01" min="0" max="100" value="<?= eAttr($settings['boleto_multa'] ?? '2.00') ?>">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted">Juros ao Mês (%)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="boleto_juros" step="0.01" min="0" max="100" value="<?= eAttr($settings['boleto_juros'] ?? '1.00') ?>">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted">Aceite</label>
                            <select class="form-select" name="boleto_aceite">
                                <option value="N" <?= ($settings['boleto_aceite'] ?? 'N') === 'N' ? 'selected' : '' ?>>N — Não</option>
                                <option value="S" <?= ($settings['boleto_aceite'] ?? '') === 'S' ? 'selected' : '' ?>>S — Sim</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted">Espécie Doc.</label>
                            <select class="form-select" name="boleto_especie_doc">
                                <?php 
                                $especies = ['DM' => 'DM — Duplicata Mercantil', 'DS' => 'DS — Duplicata de Serviço', 'NP' => 'NP — Nota Promissória', 'RC' => 'RC — Recibo', 'ME' => 'ME — Mensalidade Escolar', 'OU' => 'OU — Outros'];
                                foreach ($especies as $cod => $nome): ?>
                                <option value="<?= $cod ?>" <?= ($settings['boleto_especie_doc'] ?? 'DM') === $cod ? 'selected' : '' ?>><?= $nome ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small text-muted">Demonstrativo (opcional)</label>
                            <textarea class="form-control" name="boleto_demonstrativo" rows="2" placeholder="Texto descritivo do serviço/produto (opcional)"><?= eAttr($settings['boleto_demonstrativo'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Local de Pagamento</label>
                            <input type="text" class="form-control" name="boleto_local_pagamento" value="<?= eAttr($settings['boleto_local_pagamento'] ?? 'Pagável em qualquer banco até o vencimento') ?>" placeholder="Pagável em qualquer banco até o vencimento">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Endereço do Cedente no Boleto</label>
                            <input type="text" class="form-control" name="boleto_cedente_endereco" value="<?= eAttr($settings['boleto_cedente_endereco'] ?? '') ?>" placeholder="Deixe em branco para usar o endereço da empresa">
                            <small class="text-muted" style="font-size:0.65rem;">Se vazio, será usado o endereço cadastrado em Dados da Empresa</small>
                        </div>

                        <div class="col-12 mt-2">
                            <hr>
                            <h6 class="fw-bold mb-2" style="color:#0d6efd;"><i class="fas fa-link me-2"></i>Gateway Mercado Pago</h6>
                            <small class="text-muted d-block mb-2">Essas credenciais habilitam geração de link de pagamento no pedido em etapa financeira.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Access Token (Secret)</label>
                            <input type="password" class="form-control" name="mercadopago_access_token" value="<?= eAttr($settings['mercadopago_access_token'] ?? '') ?>" placeholder="APP_USR-...">
                            <small class="text-muted" style="font-size:0.65rem;">Necessário para criar preferência de pagamento na API.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Public Key (Opcional)</label>
                            <input type="text" class="form-control" name="mercadopago_public_key" value="<?= eAttr($settings['mercadopago_public_key'] ?? '') ?>" placeholder="APP_USR-...">
                            <small class="text-muted" style="font-size:0.65rem;">Opcional para futuras integrações checkout no front.</small>
                        </div>
                    </div>
                </fieldset>

                <div class="text-end mb-4">
                    <button type="submit" class="btn px-4 fw-bold text-white" style="background:#f39c12;"><i class="fas fa-save me-2"></i>Salvar Configurações Bancárias</button>
                </div>
            </div>

            <!-- Coluna Direita: Info e Preview -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header py-2" style="background: #fef5e7;">
                        <h6 class="mb-0 fw-bold" style="color:#f39c12;"><i class="fas fa-info-circle me-2"></i>Sobre o Boleto</h6>
                    </div>
                    <div class="card-body small">
                        <p><strong>Padrão FEBRABAN:</strong> Os boletos gerados seguem o layout padrão da Federação Brasileira de Bancos.</p>
                        <ul class="mb-2">
                            <li>O <strong>Nosso Número</strong> é incrementado automaticamente.</li>
                            <li>A <strong>linha digitável</strong> e o <strong>código de barras</strong> são gerados conforme regras do banco selecionado.</li>
                            <li>Configure a <strong>carteira</strong> conforme o contrato com o banco.</li>
                            <li>As <strong>instruções</strong> aparecem no canhoto/recibo do sacado.</li>
                        </ul>
                        <div class="alert alert-warning py-2 px-3 mb-2" style="font-size:0.75rem;">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            <strong>Importante:</strong> Estes boletos são gerados localmente para impressão. Para registro bancário (compensação), é necessário integração com a API de cobrança do banco.
                        </div>
                        <p class="text-muted mb-0"><strong>CNAB:</strong> Compatível com os padrões CNAB 240 e CNAB 400 para futura geração de arquivos de remessa.</p>
                    </div>
                </div>

                <!-- Preview do Boleto -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header py-2" style="background: #fef5e7;">
                        <h6 class="mb-0 fw-bold" style="color:#f39c12;"><i class="fas fa-eye me-2"></i>Prévia dos Dados</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="border rounded p-3 bg-light" style="font-size:0.75rem;">
                            <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                                <strong><?= eAttr($settings['boleto_cedente'] ?? $settings['company_name'] ?? '(Cedente)') ?></strong>
                                <span class="badge" style="background:#f39c12;color:#fff;"><?= eAttr($settings['boleto_banco'] ?? '---') ?></span>
                            </div>
                            <div class="mb-1"><strong>Ag:</strong> <?= eAttr(($settings['boleto_agencia'] ?? '----') . '-' . ($settings['boleto_agencia_dv'] ?? '0')) ?></div>
                            <div class="mb-1"><strong>CC:</strong> <?= eAttr(($settings['boleto_conta'] ?? '------') . '-' . ($settings['boleto_conta_dv'] ?? '0')) ?></div>
                            <div class="mb-1"><strong>Carteira:</strong> <?= eAttr($settings['boleto_carteira'] ?? '---') ?></div>
                            <div class="mb-1"><strong>Convênio:</strong> <?= eAttr($settings['boleto_convenio'] ?? '---') ?></div>
                            <div class="mb-1"><strong>Nosso Nº:</strong> <?= eAttr($settings['boleto_nosso_numero'] ?? '1') ?></div>
                            <hr class="my-2">
                            <div class="text-muted" style="font-size:0.65rem;">
                                <i class="fas fa-barcode me-1"></i>
                                Código de barras será gerado na impressão do boleto
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <?php endif; ?>

    <?php if ($activeTab === 'fiscal' && $canUseFiscalModule): ?>
    <!-- ══════════ ABA: FISCAL / NF-e ══════════ -->
    <form method="POST" action="?page=settings&action=saveFiscalSettings">
        <?= csrf_field() ?>
        <div class="row">
            <div class="col-lg-8">
                <!-- Identificação Fiscal da Empresa -->
                <fieldset class="border p-4 mb-4 rounded bg-white shadow-sm">
                    <legend class="float-none w-auto px-2 fs-5 fw-bold" style="color: #8e44ad;">
                        <i class="fas fa-id-card-alt me-2"></i>Identificação Fiscal
                    </legend>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Razão Social</label>
                            <input type="text" class="form-control" name="fiscal_razao_social" value="<?= eAttr($settings['fiscal_razao_social'] ?? '') ?>" placeholder="Razão social conforme CNPJ">
                            <small class="text-muted" style="font-size:0.65rem;">Conforme registrado na Receita Federal</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Nome Fantasia</label>
                            <input type="text" class="form-control" name="fiscal_nome_fantasia" value="<?= eAttr($settings['fiscal_nome_fantasia'] ?? '') ?>" placeholder="Nome fantasia">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">CNPJ</label>
                            <input type="text" class="form-control" name="fiscal_cnpj" value="<?= eAttr($settings['fiscal_cnpj'] ?? '') ?>" placeholder="00.000.000/0000-00" maxlength="18">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Inscrição Estadual (IE)</label>
                            <input type="text" class="form-control" name="fiscal_ie" value="<?= eAttr($settings['fiscal_ie'] ?? '') ?>" placeholder="Inscrição Estadual" maxlength="20">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Inscrição Municipal (IM)</label>
                            <input type="text" class="form-control" name="fiscal_im" value="<?= eAttr($settings['fiscal_im'] ?? '') ?>" placeholder="Inscrição Municipal" maxlength="20">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">CNAE Principal</label>
                            <input type="text" class="form-control" name="fiscal_cnae" value="<?= eAttr($settings['fiscal_cnae'] ?? '') ?>" placeholder="0000-0/00" maxlength="12">
                            <small class="text-muted" style="font-size:0.65rem;">Classificação Nacional de Atividades Econômicas</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Regime Tributário (CRT)</label>
                            <select class="form-select" name="fiscal_crt">
                                <option value="1" <?= ($settings['fiscal_crt'] ?? '1') == '1' ? 'selected' : '' ?>>1 — Simples Nacional</option>
                                <option value="2" <?= ($settings['fiscal_crt'] ?? '') == '2' ? 'selected' : '' ?>>2 — Simples Nacional (excesso sublimite)</option>
                                <option value="3" <?= ($settings['fiscal_crt'] ?? '') == '3' ? 'selected' : '' ?>>3 — Regime Normal (Lucro Presumido/Real)</option>
                                <option value="4" <?= ($settings['fiscal_crt'] ?? '') == '4' ? 'selected' : '' ?>>4 — MEI — Simples Nacional</option>
                            </select>
                        </div>
                    </div>
                </fieldset>

                <!-- Endereço Fiscal -->
                <fieldset class="border p-4 mb-4 rounded bg-white shadow-sm">
                    <legend class="float-none w-auto px-2 fs-5 fw-bold" style="color: #8e44ad;">
                        <i class="fas fa-map-pin me-2"></i>Endereço Fiscal
                    </legend>
                    <div class="alert alert-info py-2 px-3 small mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Preencha somente se o endereço fiscal for diferente do endereço comercial cadastrado em <strong>Dados da Empresa</strong>.
                        Se deixado em branco, será usado o endereço comercial.
                    </div>
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label fw-bold small text-muted">Logradouro</label>
                            <input type="text" class="form-control" name="fiscal_endereco_logradouro" value="<?= eAttr($settings['fiscal_endereco_logradouro'] ?? '') ?>" placeholder="Rua, Av, Travessa...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold small text-muted">Número</label>
                            <input type="text" class="form-control" name="fiscal_endereco_numero" value="<?= eAttr($settings['fiscal_endereco_numero'] ?? '') ?>" placeholder="Nº">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold small text-muted">Complemento</label>
                            <input type="text" class="form-control" name="fiscal_endereco_complemento" value="<?= eAttr($settings['fiscal_endereco_complemento'] ?? '') ?>" placeholder="Sala, Andar, Bloco...">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Bairro</label>
                            <input type="text" class="form-control" name="fiscal_endereco_bairro" value="<?= eAttr($settings['fiscal_endereco_bairro'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Cidade</label>
                            <input type="text" class="form-control" name="fiscal_endereco_cidade" value="<?= eAttr($settings['fiscal_endereco_cidade'] ?? '') ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold small text-muted">UF</label>
                            <input type="text" class="form-control" name="fiscal_endereco_uf" value="<?= eAttr($settings['fiscal_endereco_uf'] ?? '') ?>" maxlength="2" placeholder="SP">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold small text-muted">CEP</label>
                            <input type="text" class="form-control" name="fiscal_endereco_cep" value="<?= eAttr($settings['fiscal_endereco_cep'] ?? '') ?>" placeholder="00000-000" maxlength="9">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Código do Município (IBGE)</label>
                            <input type="text" class="form-control" name="fiscal_endereco_cod_municipio" value="<?= eAttr($settings['fiscal_endereco_cod_municipio'] ?? '') ?>" placeholder="0000000" maxlength="7">
                            <small class="text-muted" style="font-size:0.65rem;">7 dígitos IBGE. Ex: 3550308 (São Paulo)</small>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold small text-muted">Cód. País</label>
                            <input type="text" class="form-control" name="fiscal_endereco_cod_pais" value="<?= eAttr($settings['fiscal_endereco_cod_pais'] ?? '1058') ?>" placeholder="1058" maxlength="4">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted">País</label>
                            <input type="text" class="form-control" name="fiscal_endereco_pais" value="<?= eAttr($settings['fiscal_endereco_pais'] ?? 'Brasil') ?>" placeholder="Brasil">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted">Telefone</label>
                            <input type="text" class="form-control" name="fiscal_endereco_fone" value="<?= eAttr($settings['fiscal_endereco_fone'] ?? '') ?>" placeholder="(00) 00000-0000">
                        </div>
                    </div>
                </fieldset>

                <!-- Certificado Digital -->
                <fieldset class="border p-4 mb-4 rounded bg-white shadow-sm">
                    <legend class="float-none w-auto px-2 fs-5 fw-bold" style="color: #8e44ad;">
                        <i class="fas fa-key me-2"></i>Certificado Digital
                    </legend>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Tipo do Certificado</label>
                            <select class="form-select" name="fiscal_certificado_tipo">
                                <option value="A1" <?= ($settings['fiscal_certificado_tipo'] ?? 'A1') === 'A1' ? 'selected' : '' ?>>A1 — Arquivo digital (.pfx)</option>
                                <option value="A3" <?= ($settings['fiscal_certificado_tipo'] ?? '') === 'A3' ? 'selected' : '' ?>>A3 — Token / Smartcard</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Senha do Certificado</label>
                            <input type="password" class="form-control" name="fiscal_certificado_senha" value="<?= eAttr($settings['fiscal_certificado_senha'] ?? '') ?>" placeholder="••••••••" autocomplete="new-password">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Validade do Certificado</label>
                            <input type="date" class="form-control" name="fiscal_certificado_validade" value="<?= eAttr($settings['fiscal_certificado_validade'] ?? '') ?>">
                            <?php 
                            $validade = $settings['fiscal_certificado_validade'] ?? '';
                            if ($validade && strtotime($validade) < strtotime('+30 days')): ?>
                                <small class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>Certificado próximo do vencimento ou vencido!</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </fieldset>

                <!-- Configurações NF-e -->
                <fieldset class="border p-4 mb-4 rounded bg-white shadow-sm">
                    <legend class="float-none w-auto px-2 fs-5 fw-bold" style="color: #8e44ad;">
                        <i class="fas fa-cog me-2"></i>Configurações NF-e
                    </legend>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted">Ambiente</label>
                            <select class="form-select" name="fiscal_ambiente">
                                <option value="2" <?= ($settings['fiscal_ambiente'] ?? '2') == '2' ? 'selected' : '' ?>>2 — Homologação (Testes)</option>
                                <option value="1" <?= ($settings['fiscal_ambiente'] ?? '') == '1' ? 'selected' : '' ?>>1 — Produção</option>
                            </select>
                            <small class="text-muted" style="font-size:0.65rem;">Use homologação para testes</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted">Modelo</label>
                            <select class="form-select" name="fiscal_modelo_nfe">
                                <option value="55" <?= ($settings['fiscal_modelo_nfe'] ?? '55') == '55' ? 'selected' : '' ?>>55 — NF-e</option>
                                <option value="65" <?= ($settings['fiscal_modelo_nfe'] ?? '') == '65' ? 'selected' : '' ?>>65 — NFC-e</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted">Série</label>
                            <input type="number" class="form-control" name="fiscal_serie_nfe" value="<?= eAttr($settings['fiscal_serie_nfe'] ?? '1') ?>" min="1" max="999">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted">Próximo Nº NF-e</label>
                            <input type="number" class="form-control" name="fiscal_proximo_numero_nfe" value="<?= eAttr($settings['fiscal_proximo_numero_nfe'] ?? '1') ?>" min="1">
                            <small class="text-muted" style="font-size:0.65rem;">Incrementado automaticamente</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted">Tipo de Emissão</label>
                            <select class="form-select" name="fiscal_tipo_emissao">
                                <option value="1" <?= ($settings['fiscal_tipo_emissao'] ?? '1') == '1' ? 'selected' : '' ?>>1 — Normal</option>
                                <option value="2" <?= ($settings['fiscal_tipo_emissao'] ?? '') == '2' ? 'selected' : '' ?>>2 — Contingência FS-IA</option>
                                <option value="5" <?= ($settings['fiscal_tipo_emissao'] ?? '') == '5' ? 'selected' : '' ?>>5 — Contingência FS-DA</option>
                                <option value="6" <?= ($settings['fiscal_tipo_emissao'] ?? '') == '6' ? 'selected' : '' ?>>6 — SVC-AN</option>
                                <option value="7" <?= ($settings['fiscal_tipo_emissao'] ?? '') == '7' ? 'selected' : '' ?>>7 — SVC-RS</option>
                                <option value="9" <?= ($settings['fiscal_tipo_emissao'] ?? '') == '9' ? 'selected' : '' ?>>9 — Contingência offline NFC-e</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted">Finalidade</label>
                            <select class="form-select" name="fiscal_finalidade">
                                <option value="1" <?= ($settings['fiscal_finalidade'] ?? '1') == '1' ? 'selected' : '' ?>>1 — Normal</option>
                                <option value="2" <?= ($settings['fiscal_finalidade'] ?? '') == '2' ? 'selected' : '' ?>>2 — Complementar</option>
                                <option value="3" <?= ($settings['fiscal_finalidade'] ?? '') == '3' ? 'selected' : '' ?>>3 — Ajuste</option>
                                <option value="4" <?= ($settings['fiscal_finalidade'] ?? '') == '4' ? 'selected' : '' ?>>4 — Devolução</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Natureza da Operação</label>
                            <input type="text" class="form-control" name="fiscal_nat_operacao" value="<?= eAttr($settings['fiscal_nat_operacao'] ?? 'Venda de mercadoria') ?>" placeholder="Venda de mercadoria">
                        </div>
                    </div>
                </fieldset>

                <!-- Alíquotas Padrão -->
                <fieldset class="border p-4 mb-4 rounded bg-white shadow-sm">
                    <legend class="float-none w-auto px-2 fs-5 fw-bold" style="color: #8e44ad;">
                        <i class="fas fa-percentage me-2"></i>Alíquotas Padrão da Empresa
                    </legend>
                    <div class="alert alert-info py-2 px-3 small mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Estas alíquotas serão usadas como padrão quando o produto não tiver alíquotas próprias definidas.
                    </div>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted">Alíq. ICMS Padrão (%)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" class="form-control" name="fiscal_aliq_icms_padrao" value="<?= eAttr($settings['fiscal_aliq_icms_padrao'] ?? '') ?>" placeholder="0.00" min="0" max="100">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted">Alíq. PIS Padrão (%)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" class="form-control" name="fiscal_aliq_pis_padrao" value="<?= eAttr($settings['fiscal_aliq_pis_padrao'] ?? '0.65') ?>" placeholder="0.65" min="0" max="100">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted">Alíq. COFINS Padrão (%)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" class="form-control" name="fiscal_aliq_cofins_padrao" value="<?= eAttr($settings['fiscal_aliq_cofins_padrao'] ?? '3.00') ?>" placeholder="3.00" min="0" max="100">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted">Alíq. ISS Padrão (%)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" class="form-control" name="fiscal_aliq_iss_padrao" value="<?= eAttr($settings['fiscal_aliq_iss_padrao'] ?? '') ?>" placeholder="0.00" min="0" max="100">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted" style="font-size:0.65rem;">Para prestação de serviços</small>
                        </div>
                    </div>
                </fieldset>

                <!-- Informações Complementares -->
                <fieldset class="border p-4 mb-4 rounded bg-white shadow-sm">
                    <legend class="float-none w-auto px-2 fs-5 fw-bold" style="color: #8e44ad;">
                        <i class="fas fa-sticky-note me-2"></i>Informações Complementares
                    </legend>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold small text-muted">Informações Complementares Padrão (NF-e)</label>
                            <textarea class="form-control" name="fiscal_info_complementar" rows="3" placeholder="Texto padrão que aparecerá no campo de Informações Complementares de todas as NF-e emitidas."><?= eAttr($settings['fiscal_info_complementar'] ?? '') ?></textarea>
                            <small class="text-muted" style="font-size:0.65rem;">Ex: "Documento emitido por ME ou EPP optante pelo Simples Nacional..."</small>
                        </div>
                    </div>
                </fieldset>

                <div class="text-end mb-4">
                    <button type="submit" class="btn px-4 fw-bold text-white" style="background:#8e44ad;"><i class="fas fa-save me-2"></i>Salvar Configurações Fiscais</button>
                </div>
            </div>

            <!-- Coluna Direita: Info e Resumo -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header py-2" style="background: #f0e6f6;">
                        <h6 class="mb-0 fw-bold" style="color:#8e44ad;"><i class="fas fa-info-circle me-2"></i>Sobre Dados Fiscais</h6>
                    </div>
                    <div class="card-body small">
                        <p><strong>Configurações fiscais</strong> são necessárias para emissão de Nota Fiscal Eletrônica (NF-e/NFC-e).</p>
                        <ul class="mb-2">
                            <li>A <strong>Razão Social</strong> e o <strong>CNPJ</strong> devem ser idênticos ao cadastro na Receita Federal.</li>
                            <li>A <strong>Inscrição Estadual</strong> é obrigatória para contribuintes de ICMS.</li>
                            <li>O <strong>CRT</strong> (Regime Tributário) define como o ICMS será calculado na NF-e.</li>
                            <li>O <strong>Código do Município</strong> (IBGE) é necessário para o XML da NF-e.</li>
                            <li>O <strong>Certificado Digital A1</strong> (.pfx) é necessário para assinar a NF-e.</li>
                        </ul>
                        <div class="alert alert-warning py-2 px-3 mb-2" style="font-size:0.75rem;">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            <strong>Importante:</strong> Utilize o ambiente de <strong>Homologação</strong> para testes antes de mudar para Produção.
                        </div>
                    </div>
                </div>

                <!-- Resumo Fiscal -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header py-2" style="background: #f0e6f6;">
                        <h6 class="mb-0 fw-bold" style="color:#8e44ad;"><i class="fas fa-eye me-2"></i>Resumo Fiscal</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="border rounded p-3 bg-light" style="font-size:0.75rem;">
                            <div class="fw-bold mb-2"><?= eAttr($settings['fiscal_razao_social'] ?? '(Razão Social)') ?></div>
                            <?php if (!empty($settings['fiscal_nome_fantasia'])): ?>
                            <div class="text-muted mb-1"><i class="fas fa-store me-1"></i><?= eAttr($settings['fiscal_nome_fantasia']) ?></div>
                            <?php endif; ?>
                            <div class="mb-1"><strong>CNPJ:</strong> <?= eAttr($settings['fiscal_cnpj'] ?? '—') ?></div>
                            <div class="mb-1"><strong>IE:</strong> <?= eAttr($settings['fiscal_ie'] ?? '—') ?></div>
                            <?php if (!empty($settings['fiscal_im'])): ?>
                            <div class="mb-1"><strong>IM:</strong> <?= eAttr($settings['fiscal_im']) ?></div>
                            <?php endif; ?>
                            <div class="mb-2"><strong>CRT:</strong> 
                                <?php 
                                $crtMap = ['1' => 'Simples Nacional', '2' => 'SN (excesso)', '3' => 'Regime Normal', '4' => 'MEI'];
                                echo $crtMap[$settings['fiscal_crt'] ?? '1'] ?? '—';
                                ?>
                            </div>
                            <hr class="my-2">
                            <div class="mb-1">
                                <strong>Ambiente:</strong> 
                                <span class="badge <?= ($settings['fiscal_ambiente'] ?? '2') == '1' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                    <?= ($settings['fiscal_ambiente'] ?? '2') == '1' ? 'Produção' : 'Homologação' ?>
                                </span>
                            </div>
                            <div class="mb-1"><strong>Modelo:</strong> <?= ($settings['fiscal_modelo_nfe'] ?? '55') == '55' ? 'NF-e (55)' : 'NFC-e (65)' ?></div>
                            <div class="mb-1"><strong>Série:</strong> <?= eAttr($settings['fiscal_serie_nfe'] ?? '1') ?></div>
                            <div class="mb-1"><strong>Próx. Nº:</strong> <?= eAttr($settings['fiscal_proximo_numero_nfe'] ?? '1') ?></div>
                            <?php if (!empty($settings['fiscal_certificado_validade'])): 
                                $certDate = strtotime($settings['fiscal_certificado_validade']);
                                $isExpired = $certDate < time();
                                $isExpiring = $certDate < strtotime('+30 days');
                            ?>
                            <hr class="my-2">
                            <div class="mb-0">
                                <strong>Certificado:</strong> 
                                <span class="badge <?= $isExpired ? 'bg-danger' : ($isExpiring ? 'bg-warning text-dark' : 'bg-success') ?>">
                                    <?= $isExpired ? 'Vencido' : ($isExpiring ? 'Expirando' : 'Válido') ?>
                                </span>
                                <small class="text-muted d-block"><?= date('d/m/Y', $certDate) ?></small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Links Úteis -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header py-2" style="background: #f0e6f6;">
                        <h6 class="mb-0 fw-bold" style="color:#8e44ad;"><i class="fas fa-external-link-alt me-2"></i>Links Úteis</h6>
                    </div>
                    <div class="card-body small">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><a href="https://www.ibge.gov.br/explica/codigos-dos-municipios.php" target="_blank"><i class="fas fa-external-link-alt me-1"></i>Códigos de Municípios IBGE</a></li>
                            <li class="mb-2"><a href="https://portalunico.siscomex.gov.br/classif/#/nomenclatura/tabela" target="_blank"><i class="fas fa-external-link-alt me-1"></i>Tabela NCM</a></li>
                            <li class="mb-2"><a href="https://www.confaz.fazenda.gov.br/legislacao/convenios/2015/cv085_15" target="_blank"><i class="fas fa-external-link-alt me-1"></i>Tabela CEST</a></li>
                            <li class="mb-0"><a href="https://www.nfe.fazenda.gov.br/portal/principal.aspx" target="_blank"><i class="fas fa-external-link-alt me-1"></i>Portal NF-e (SEFAZ)</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <?php endif; ?>

    <?php if ($activeTab === 'security'): ?>
    <!-- ══════════ ABA: SEGURANÇA ══════════ -->
    <form method="POST" action="?page=settings&action=saveSecuritySettings">
        <?= csrf_field() ?>
        <div class="row">
            <div class="col-lg-8">
                <!-- Timeout de Sessão -->
                <fieldset class="border p-4 mb-4 rounded bg-white shadow-sm">
                    <legend class="float-none w-auto px-2 fs-5 fw-bold" style="color: #e74c3c;">
                        <i class="fas fa-clock me-2"></i>Tempo Limite de Sessão
                    </legend>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Timeout por Inatividade (minutos)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-hourglass-half"></i></span>
                                <input type="number" class="form-control" name="session_timeout_minutes"
                                       value="<?= eAttr($settings['session_timeout_minutes'] ?? '60') ?>"
                                       min="5" max="1440" step="1">
                                <span class="input-group-text">min</span>
                            </div>
                            <small class="text-muted" style="font-size:0.7rem;">
                                Mínimo: 5 min · Máximo: 1440 min (24h) · Padrão: 60 min
                            </small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Valor atual em uso</label>
                            <?php
                            $currentTimeout = (int)($settings['session_timeout_minutes'] ?? 60);
                            if ($currentTimeout < 5) $currentTimeout = 60;
                            $hours = floor($currentTimeout / 60);
                            $mins = $currentTimeout % 60;
                            $humanTime = '';
                            if ($hours > 0) $humanTime .= $hours . 'h ';
                            if ($mins > 0 || $hours === 0) $humanTime .= $mins . 'min';
                            ?>
                            <div class="form-control-plaintext">
                                <span class="badge bg-primary fs-6 px-3 py-2">
                                    <i class="fas fa-stopwatch me-1"></i> <?= trim($humanTime) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info py-2 px-3 small mt-3 mb-0">
                        <i class="fas fa-info-circle me-1"></i>
                        Após este período sem nenhuma interação, o usuário será desconectado automaticamente e
                        redirecionado para o login com a mensagem <em>"Sessão expirada por inatividade"</em>.
                        Um <strong>aviso visual</strong> (modal com contagem regressiva) aparece nos <strong>últimos 5 minutos</strong> antes da expiração,
                        permitindo ao usuário renovar a sessão clicando em "Continuar".
                    </div>
                </fieldset>

                <!-- Proteções Ativas -->
                <fieldset class="border p-4 mb-4 rounded bg-white shadow-sm">
                    <legend class="float-none w-auto px-2 fs-5 fw-bold" style="color: #e74c3c;">
                        <i class="fas fa-shield-alt me-2"></i>Proteções Ativas
                    </legend>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <tbody>
                                <tr>
                                    <td style="width:40px;"><span class="badge bg-success"><i class="fas fa-check"></i></span></td>
                                    <td class="fw-bold">Cookie HttpOnly</td>
                                    <td class="text-muted small">Cookie de sessão inacessível via JavaScript, prevenindo roubo por XSS.</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-success"><i class="fas fa-check"></i></span></td>
                                    <td class="fw-bold">Cookie Secure (HTTPS)</td>
                                    <td class="text-muted small">Cookie transmitido apenas em conexões HTTPS.</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-success"><i class="fas fa-check"></i></span></td>
                                    <td class="fw-bold">SameSite Strict</td>
                                    <td class="text-muted small">Cookie não enviado em requisições cross-site (previne CSRF).</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-success"><i class="fas fa-check"></i></span></td>
                                    <td class="fw-bold">Session Fixation Prevention</td>
                                    <td class="text-muted small">ID da sessão regenerado a cada login bem-sucedido.</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-success"><i class="fas fa-check"></i></span></td>
                                    <td class="fw-bold">Strict Session Mode</td>
                                    <td class="text-muted small">IDs de sessão não gerados pelo servidor são rejeitados.</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-success"><i class="fas fa-check"></i></span></td>
                                    <td class="fw-bold">Detecção de Troca de Tenant</td>
                                    <td class="text-muted small">Sessão destruída se o subdomínio mudar durante uso ativo.</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-success"><i class="fas fa-check"></i></span></td>
                                    <td class="fw-bold">Bloqueio por Tentativas de Login</td>
                                    <td class="text-muted small">Conta bloqueada temporariamente após múltiplas tentativas com credenciais inválidas.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </fieldset>

                <div class="text-end mb-4">
                    <button type="submit" class="btn px-4 fw-bold text-white" style="background:#e74c3c;">
                        <i class="fas fa-save me-2"></i>Salvar Configurações de Segurança
                    </button>
                </div>
            </div>

            <!-- Coluna Direita: Info -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header py-2" style="background: #fdecea;">
                        <h6 class="mb-0 fw-bold" style="color:#e74c3c;"><i class="fas fa-info-circle me-2"></i>Sobre Sessão Segura</h6>
                    </div>
                    <div class="card-body small">
                        <p>O sistema gerencia sessões de forma segura com múltiplas camadas de proteção:</p>
                        <ul class="mb-2">
                            <li><strong>Timeout:</strong> O tempo de inatividade é configurável. Após expirar, o usuário precisa fazer login novamente.</li>
                            <li><strong>Aviso prévio:</strong> Um modal aparece 5 minutos antes da expiração com contagem regressiva e botão para renovar.</li>
                            <li><strong>Cookies seguros:</strong> HttpOnly, Secure e SameSite protegem contra XSS, interceptação e CSRF.</li>
                            <li><strong>Anti-fixation:</strong> O ID da sessão é regenerado após cada login.</li>
                        </ul>
                        <div class="alert alert-warning py-2 px-3 mb-0" style="font-size:0.75rem;">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            Valores muito baixos de timeout (abaixo de 15 min) podem causar inconveniência para usuários
                            que consultam informações enquanto trabalham.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if(isset($_GET['status']) && $_GET['status'] === 'saved'): ?>
    Swal.fire({ icon: 'success', title: 'Salvo!', text: 'Configurações atualizadas.', timer: 2000, showConfirmButton: false });
    <?php endif; ?>
    <?php if(isset($_GET['status']) && in_array($_GET['status'], ['table_created','table_updated','table_deleted'])): ?>
    Swal.fire({ icon: 'success', title: 'Sucesso!', timer: 1500, showConfirmButton: false });
    <?php endif; ?>
    <?php if(isset($_GET['status']) && $_GET['status'] === 'table_default_error'): ?>
    Swal.fire({ icon: 'error', title: 'Erro', text: 'Não é possível excluir a tabela padrão.', confirmButtonColor: '#3498db' });
    <?php endif; ?>
    <?php if(isset($_GET['status']) && in_array($_GET['status'], ['step_added','step_updated','step_deleted'])): ?>
    Swal.fire({ icon: 'success', title: 'Sucesso!', text: 'Etapa de preparo atualizada.', timer: 1500, showConfirmButton: false });
    <?php endif; ?>

    // Confirmar exclusão de tabela
    document.querySelectorAll('.btn-delete-table').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.href;
            Swal.fire({
                title: 'Excluir tabela de preço?',
                text: 'Clientes associados a esta tabela ficarão sem tabela de preço.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-trash me-1"></i> Excluir',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#e74c3c'
            }).then(r => { if (r.isConfirmed) window.location.href = href; });
        });
    });

    // ═══ Etapas de Preparo — Ações ═══

    // Toggle ativo/inativo via AJAX
    document.querySelectorAll('.btn-toggle-step').forEach(btn => {
        btn.addEventListener('click', function() {
            const stepId = this.dataset.stepId;
            fetch('?page=settings&action=togglePreparationStep', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + stepId
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        });
    });

    // Abrir modal de edição com dados da etapa
    document.querySelectorAll('.btn-edit-step').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('editStepId').value = this.dataset.id;
            document.getElementById('editStepLabel').value = this.dataset.label;
            document.getElementById('editStepDesc').value = this.dataset.description;
            document.getElementById('editStepIcon').value = this.dataset.icon;
            document.getElementById('editStepSort').value = this.dataset.sort;
            document.getElementById('editStepActive').checked = this.dataset.active === '1';
            // Atualizar preview do ícone
            document.getElementById('editStepIconPreview').innerHTML = '<i class="' + this.dataset.icon + '"></i>';
            new bootstrap.Modal(document.getElementById('modalEditStep')).show();
        });
    });

    // Confirmar exclusão de etapa
    document.querySelectorAll('.btn-delete-step').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.href;
            Swal.fire({
                title: 'Excluir etapa de preparo?',
                text: 'Esta ação não pode ser desfeita. Os registros já feitos com esta etapa serão mantidos no histórico.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-trash me-1"></i> Excluir',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#e74c3c'
            }).then(r => { if (r.isConfirmed) window.location.href = href; });
        });
    });

    // Preview de ícone ao digitar
    const newIconInput = document.getElementById('newStepIconInput');
    if (newIconInput) {
        newIconInput.addEventListener('input', function() {
            document.getElementById('newStepIconPreview').innerHTML = '<i class="' + this.value + '"></i>';
        });
    }
    const editIconInput = document.getElementById('editStepIcon');
    if (editIconInput) {
        editIconInput.addEventListener('input', function() {
            document.getElementById('editStepIconPreview').innerHTML = '<i class="' + this.value + '"></i>';
        });
    }

    // Quick pick de ícones
    document.querySelectorAll('.icon-pick').forEach(el => {
        el.style.cursor = 'pointer';
        el.addEventListener('click', function() {
            const icon = this.dataset.icon;
            const modal = this.closest('.modal');
            if (modal) {
                const input = modal.querySelector('input[name="icon"]');
                const preview = modal.querySelector('[id$="IconPreview"]');
                if (input) input.value = icon;
                if (preview) preview.innerHTML = '<i class="' + icon + '"></i>';
            }
        });
    });
});
</script>
