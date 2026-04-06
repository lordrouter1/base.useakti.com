<!-- 
    Edição de Cliente — Wizard Multi-Step (Fase 3)
    Variáveis: $customer, $priceTables, $sellers, $contacts
-->
<link rel="stylesheet" href="assets/css/customers.css">

<?php
    // Dados do cliente para pré-preenchimento
    $c = $customer;
    $personType = $c['person_type'] ?? 'PF';
    $isPJ = ($personType === 'PJ');
?>

<div class="container py-4">
    <!-- Header com banner resumido -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-3">
            <?php if (!empty($c['photo'])): ?>
                <img src="<?= eAttr(thumb_url($c['photo'], 48, 48)) ?>" alt="Foto" class="rounded-circle" style="width:48px;height:48px;object-fit:cover;border:2px solid var(--cst-border);">
            <?php else: ?>
                <div class="icon-circle icon-circle-48 icon-circle-blue text-blue" style="font-size:1.2rem;font-weight:700;">
                    <?= strtoupper(mb_substr($c['name'], 0, 1)) ?>
                </div>
            <?php endif; ?>
            <div>
                <h1 class="h3 mb-0"><i class="fas fa-user-edit me-2 text-primary"></i>Editar Cliente</h1>
                <p class="text-muted mb-0" style="font-size:.78rem;">
                    <?= e($c['name']) ?>
                    <?php if (!empty($c['code'])): ?>
                        <span class="badge bg-light text-dark border ms-1"><?= e($c['code']) ?></span>
                    <?php endif; ?>
                    <?php
                        $statusClass = ['active' => 'success', 'inactive' => 'warning', 'blocked' => 'danger'];
                        $statusLabel = ['active' => 'Ativo', 'inactive' => 'Inativo', 'blocked' => 'Bloqueado'];
                        $st = $c['status'] ?? 'active';
                    ?>
                    <span class="badge bg-<?= $statusClass[$st] ?? 'secondary' ?> ms-1"><?= $statusLabel[$st] ?? $st ?></span>
                </p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="?page=customers&action=view&id=<?= (int)$c['id'] ?>" class="btn btn-outline-info btn-sm" title="Ver Ficha"><i class="fas fa-eye me-1"></i>Ficha</a>
            <a href="?page=customers" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
        </div>
    </div>

    <!-- Stepper Visual -->
    <div class="cst-stepper">
        <div class="cst-step active" data-step="1" onclick="if(window.CstWizard)CstWizard.goToStep(1)">
            <span class="cst-step-number"><span>1</span></span>
            <span class="cst-step-label">Identificação</span>
        </div>
        <div class="cst-step" data-step="2" onclick="if(window.CstWizard)CstWizard.goToStep(2)">
            <span class="cst-step-number"><span>2</span></span>
            <span class="cst-step-label">Contato</span>
        </div>
        <div class="cst-step" data-step="3" onclick="if(window.CstWizard)CstWizard.goToStep(3)">
            <span class="cst-step-number"><span>3</span></span>
            <span class="cst-step-label">Endereço</span>
        </div>
        <div class="cst-step" data-step="4" onclick="if(window.CstWizard)CstWizard.goToStep(4)">
            <span class="cst-step-number"><span>4</span></span>
            <span class="cst-step-label">Comercial</span>
        </div>
    </div>

    <!-- Formulário -->
    <form id="customerForm" method="post" action="?page=customers&action=update" enctype="multipart/form-data" class="cst-wizard-card">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
        <input type="hidden" id="person_type" name="person_type" value="<?= eAttr($personType) ?>">
        <input type="hidden" id="edit_customer_id" value="<?= (int)$c['id'] ?>">

        <!-- ═══════════════════════════════════════════════ -->
        <!-- STEP 1 — Identificação                         -->
        <!-- ═══════════════════════════════════════════════ -->
        <div class="cst-step-content active" id="cst-step-1">
            <div class="cst-form-section">
                <div class="cst-form-section-title">
                    <i class="fas fa-id-card icon-circle-blue text-blue"></i>
                    Dados de Identificação
                </div>

                <!-- Toggle PF/PJ -->
                <div class="text-center mb-4">
                    <div class="cst-person-toggle">
                        <button type="button" class="cst-toggle-option <?= !$isPJ ? 'active' : '' ?>" data-type="PF">
                            <i class="fas fa-user"></i> Pessoa Física
                        </button>
                        <button type="button" class="cst-toggle-option <?= $isPJ ? 'active' : '' ?>" data-type="PJ">
                            <i class="fas fa-building"></i> Pessoa Jurídica
                        </button>
                    </div>
                </div>

                <div class="row">
                    <!-- Coluna Esquerda: Foto + Status -->
                    <div class="col-md-3 text-center mb-3">
                        <div class="cst-photo-upload <?= !empty($c['photo']) ? 'has-photo' : '' ?>" title="Clique ou arraste para adicionar foto">
                            <img id="preview-photo" src="<?= !empty($c['photo']) ? eAttr(thumb_url($c['photo'], 150, 150)) : 'assets/img/default-avatar.png' ?>" alt="Foto" style="<?= !empty($c['photo']) ? '' : 'display:none;' ?>">
                            <div class="cst-photo-placeholder" style="<?= !empty($c['photo']) ? 'display:none;' : '' ?>">
                                <i class="fas fa-camera"></i>
                                <span>Arraste ou clique</span>
                            </div>
                            <div class="cst-photo-overlay">
                                <i class="fas fa-camera me-1"></i> Alterar
                            </div>
                        </div>
                        <input type="file" id="photo" name="photo" class="d-none" accept="image/jpeg,image/png,image/gif">
                        <small class="text-muted d-block mt-2" style="font-size:.7rem;">JPG, PNG ou GIF (máx 5MB)</small>

                        <div class="mt-3">
                            <label class="form-label fw-bold" style="font-size:.78rem;">Status</label>
                            <select id="status" name="status" class="form-select form-select-sm">
                                <option value="active" <?= ($c['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Ativo</option>
                                <option value="inactive" <?= ($c['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inativo</option>
                                <option value="blocked" <?= ($c['status'] ?? '') === 'blocked' ? 'selected' : '' ?>>Bloqueado</option>
                            </select>
                        </div>
                    </div>

                    <!-- Coluna Direita: Dados -->
                    <div class="col-md-9">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="name" class="form-label"><?= $isPJ ? 'Razão Social' : 'Nome Completo' ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required maxlength="191" placeholder="<?= $isPJ ? 'Razão social da empresa' : 'Nome completo do cliente' ?>" value="<?= eAttr($c['name']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="document" class="form-label"><?= $isPJ ? 'CNPJ' : 'CPF' ?> <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="document" name="document" placeholder="<?= $isPJ ? '00.000.000/0000-00' : '000.000.000-00' ?>" value="<?= eAttr($c['document'] ?? '') ?>">
                                    <button type="button" class="btn btn-outline-info btn-sm cst-cnpj-search-btn" id="btnSearchCnpj" style="<?= $isPJ ? '' : 'display:none;' ?>" title="Consultar CNPJ na Receita Federal">
                                        <i class="fas fa-search me-1"></i>Consultar
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="fantasy_name" class="form-label"><?= $isPJ ? 'Nome Fantasia' : 'Apelido' ?></label>
                                <input type="text" class="form-control" id="fantasy_name" name="fantasy_name" maxlength="191" placeholder="<?= $isPJ ? 'Nome fantasia' : 'Apelido' ?>" value="<?= eAttr($c['fantasy_name'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="rg_ie" class="form-label"><?= $isPJ ? 'Inscrição Estadual' : 'RG' ?></label>
                                <input type="text" class="form-control" id="rg_ie" name="rg_ie" maxlength="30" value="<?= eAttr($c['rg_ie'] ?? '') ?>">
                            </div>
                            <div class="col-md-3" id="group-im" style="<?= $isPJ ? '' : 'display:none;' ?>">
                                <label for="im" class="form-label">Inscrição Municipal</label>
                                <input type="text" class="form-control" id="im" name="im" maxlength="30" value="<?= eAttr($c['im'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="birth_date" class="form-label"><?= $isPJ ? 'Data de Fundação' : 'Data de Nascimento' ?></label>
                                <input type="text" class="form-control" id="birth_date" name="birth_date" placeholder="DD/MM/AAAA" value="<?= !empty($c['birth_date']) ? date('d/m/Y', strtotime($c['birth_date'])) : '' ?>">
                            </div>
                            <div class="col-md-3" id="group-gender" style="<?= $isPJ ? 'display:none;' : '' ?>">
                                <label for="gender" class="form-label">Gênero</label>
                                <select id="gender" name="gender" class="form-select">
                                    <option value="">Selecione...</option>
                                    <option value="M" <?= ($c['gender'] ?? '') === 'M' ? 'selected' : '' ?>>Masculino</option>
                                    <option value="F" <?= ($c['gender'] ?? '') === 'F' ? 'selected' : '' ?>>Feminino</option>
                                    <option value="O" <?= ($c['gender'] ?? '') === 'O' ? 'selected' : '' ?>>Outro</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════ -->
        <!-- STEP 2 — Contato                               -->
        <!-- ═══════════════════════════════════════════════ -->
        <div class="cst-step-content" id="cst-step-2">
            <div class="cst-form-section">
                <div class="cst-form-section-title">
                    <i class="fas fa-address-book icon-circle-green text-green"></i>
                    Informações de Contato
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="email" class="form-label">E-mail Principal</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" maxlength="191" placeholder="email@exemplo.com" value="<?= eAttr($c['email'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="email_secondary" class="form-label">E-mail Secundário</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email_secondary" name="email_secondary" maxlength="191" placeholder="email2@exemplo.com" value="<?= eAttr($c['email_secondary'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="phone" class="form-label">Telefone Fixo</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                            <input type="text" class="form-control" id="phone" name="phone" placeholder="(00) 0000-0000" value="<?= eAttr($c['phone'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="cellphone" class="form-label">Celular / WhatsApp</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fab fa-whatsapp text-success"></i></span>
                            <input type="text" class="form-control" id="cellphone" name="cellphone" placeholder="(00) 00000-0000" value="<?= eAttr($c['cellphone'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="phone_commercial" class="form-label">Telefone Comercial</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-building"></i></span>
                            <input type="text" class="form-control" id="phone_commercial" name="phone_commercial" placeholder="(00) 0000-0000" value="<?= eAttr($c['phone_commercial'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="website" class="form-label">Website</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-globe"></i></span>
                            <input type="url" class="form-control" id="website" name="website" maxlength="200" placeholder="https://www.exemplo.com.br" value="<?= eAttr($c['website'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="instagram" class="form-label">Instagram</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fab fa-instagram text-danger"></i></span>
                            <span class="input-group-text">@</span>
                            <input type="text" class="form-control" id="instagram" name="instagram" maxlength="100" placeholder="perfil" value="<?= eAttr($c['instagram'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <!-- Contato PJ -->
                <div id="group-contact-pj" style="<?= $isPJ ? '' : 'display:none;' ?>" class="mt-3">
                    <hr>
                    <h6 class="fw-bold mb-3" style="font-size:.82rem;"><i class="fas fa-user-tie me-1 text-info"></i>Contato Principal (PJ)</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="contact_name" class="form-label">Nome do Contato</label>
                            <input type="text" class="form-control" id="contact_name" name="contact_name" maxlength="100" placeholder="Nome da pessoa responsável" value="<?= eAttr($c['contact_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="contact_role" class="form-label">Cargo / Função</label>
                            <input type="text" class="form-control" id="contact_role" name="contact_role" maxlength="80" placeholder="Ex: Gerente de Compras" value="<?= eAttr($c['contact_role'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════ -->
        <!-- STEP 3 — Endereço                              -->
        <!-- ═══════════════════════════════════════════════ -->
        <div class="cst-step-content" id="cst-step-3">
            <div class="cst-form-section">
                <div class="cst-form-section-title">
                    <i class="fas fa-map-marker-alt icon-circle-orange text-orange"></i>
                    Endereço
                </div>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="zipcode" class="form-label">CEP</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="zipcode" name="zipcode" placeholder="00000-000" value="<?= eAttr($c['zipcode'] ?? '') ?>" aria-describedby="zipcode_help">
                            <span class="input-group-text" id="cep-spinner" style="display:none;"><i class="fas fa-spinner fa-spin"></i></span>
                        </div>
                        <small class="text-muted" id="zipcode_help" style="font-size:.68rem;">Digite o CEP para preencher automaticamente</small>
                    </div>
                    <div class="col-md-7">
                        <label for="address_street" class="form-label">Logradouro</label>
                        <input type="text" class="form-control" id="address_street" name="address_street" maxlength="200" placeholder="Rua, Avenida, Travessa..." value="<?= eAttr($c['address_street'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="address_number" class="form-label">Número</label>
                        <input type="text" class="form-control" id="address_number" name="address_number" maxlength="20" placeholder="Nº" value="<?= eAttr($c['address_number'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="address_complement" class="form-label">Complemento</label>
                        <input type="text" class="form-control" id="address_complement" name="address_complement" maxlength="100" placeholder="Apto, Sala, Bloco..." value="<?= eAttr($c['address_complement'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="address_neighborhood" class="form-label">Bairro</label>
                        <input type="text" class="form-control" id="address_neighborhood" name="address_neighborhood" maxlength="100" value="<?= eAttr($c['address_neighborhood'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="address_city" class="form-label">Cidade</label>
                        <input type="text" class="form-control" id="address_city" name="address_city" maxlength="100" value="<?= eAttr($c['address_city'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="address_state" class="form-label">Estado (UF)</label>
                        <select id="address_state" name="address_state" class="form-select">
                            <option value="">Selecione...</option>
                            <?php
                            $ufs = ['AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO'];
                            foreach ($ufs as $uf): ?>
                            <option value="<?= $uf ?>" <?= ($c['address_state'] ?? '') === $uf ? 'selected' : '' ?>><?= $uf ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="address_country" class="form-label">País</label>
                        <input type="text" class="form-control" id="address_country" name="address_country" value="<?= eAttr($c['address_country'] ?? 'Brasil') ?>" maxlength="50">
                    </div>
                    <div class="col-md-4">
                        <label for="address_ibge" class="form-label">Código IBGE</label>
                        <input type="text" class="form-control bg-light" id="address_ibge" name="address_ibge" maxlength="10" readonly placeholder="Preenchido automaticamente" value="<?= eAttr($c['address_ibge'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════ -->
        <!-- STEP 4 — Comercial + Finalização               -->
        <!-- ═══════════════════════════════════════════════ -->
        <div class="cst-step-content" id="cst-step-4">
            <div class="cst-form-section">
                <div class="cst-form-section-title">
                    <i class="fas fa-briefcase icon-circle-purple text-purple"></i>
                    Dados Comerciais
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="price_table_id" class="form-label">Tabela de Preço</label>
                        <select id="price_table_id" name="price_table_id" class="form-select">
                            <option value="">Usar tabela padrão</option>
                            <?php if (!empty($priceTables)): ?>
                            <?php foreach ($priceTables as $pt): ?>
                            <option value="<?= (int)$pt['id'] ?>" <?= ($c['price_table_id'] ?? '') == $pt['id'] ? 'selected' : '' ?>><?= e($pt['name']) ?> <?= $pt['is_default'] ? '(Padrão)' : '' ?></option>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="payment_term" class="form-label">Condição de Pagamento</label>
                        <?php $pt_val = $c['payment_term'] ?? ''; ?>
                        <select id="payment_term" name="payment_term" class="form-select">
                            <option value="">Selecione...</option>
                            <option value="a_vista" <?= $pt_val === 'a_vista' ? 'selected' : '' ?>>À Vista</option>
                            <option value="7_dias" <?= $pt_val === '7_dias' ? 'selected' : '' ?>>7 dias</option>
                            <option value="14_dias" <?= $pt_val === '14_dias' ? 'selected' : '' ?>>14 dias</option>
                            <option value="21_dias" <?= $pt_val === '21_dias' ? 'selected' : '' ?>>21 dias</option>
                            <option value="30_dias" <?= $pt_val === '30_dias' ? 'selected' : '' ?>>30 dias</option>
                            <option value="30_60" <?= $pt_val === '30_60' ? 'selected' : '' ?>>30/60 dias</option>
                            <option value="30_60_90" <?= $pt_val === '30_60_90' ? 'selected' : '' ?>>30/60/90 dias</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="credit_limit" class="form-label">Limite de Crédito</label>
                        <input type="text" class="form-control" id="credit_limit" name="credit_limit" placeholder="R$ 0,00" value="<?= !empty($c['credit_limit']) ? number_format((float)$c['credit_limit'], 2, ',', '.') : '' ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="discount_default" class="form-label">Desconto Padrão (%)</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="discount_default" name="discount_default" placeholder="0,00" value="<?= !empty($c['discount_default']) ? str_replace('.', ',', $c['discount_default']) : '' ?>">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="seller_id" class="form-label">Vendedor Responsável</label>
                        <select id="seller_id" name="seller_id" class="form-select">
                            <option value="">Nenhum</option>
                            <?php if (!empty($sellers)): ?>
                            <?php foreach ($sellers as $s): ?>
                            <option value="<?= (int)$s['id'] ?>" <?= ($c['seller_id'] ?? '') == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="origin" class="form-label">Origem do Cliente</label>
                        <?php $orig = $c['origin'] ?? ''; ?>
                        <select id="origin" name="origin" class="form-select">
                            <option value="">Selecione...</option>
                            <option value="indicacao" <?= $orig === 'indicacao' ? 'selected' : '' ?>>Indicação</option>
                            <option value="google" <?= $orig === 'google' ? 'selected' : '' ?>>Google / Busca</option>
                            <option value="redes_sociais" <?= $orig === 'redes_sociais' ? 'selected' : '' ?>>Redes Sociais</option>
                            <option value="site" <?= $orig === 'site' ? 'selected' : '' ?>>Site</option>
                            <option value="visita" <?= $orig === 'visita' ? 'selected' : '' ?>>Visita Presencial</option>
                            <option value="telefone" <?= $orig === 'telefone' ? 'selected' : '' ?>>Telefone</option>
                            <option value="feira_evento" <?= $orig === 'feira_evento' ? 'selected' : '' ?>>Feira / Evento</option>
                            <option value="outro" <?= $orig === 'outro' ? 'selected' : '' ?>>Outro</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="tags" class="form-label">Tags</label>
                        <div class="position-relative">
                            <div class="cst-tag-input-wrapper" id="tags-wrapper">
                                <input type="text" class="cst-tag-input" id="tagInput" placeholder="Digite e pressione Enter..." autocomplete="off">
                            </div>
                            <input type="hidden" id="tags" name="tags" value="<?= eAttr($c['tags'] ?? '') ?>" aria-describedby="tags_help">
                            <div class="cst-tag-suggestions" id="tagSuggestions"></div>
                        </div>
                        <small class="text-muted" id="tags_help" style="font-size:.68rem;">Pressione Enter para adicionar. Ex: VIP, Atacado, Indústria</small>
                    </div>
                    <div class="col-12">
                        <label for="observations" class="form-label">Observações</label>
                        <textarea class="form-control" id="observations" name="observations" rows="3" placeholder="Informações adicionais sobre o cliente..." style="resize:vertical;"><?= e($c['observations'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Indicador de Completude -->
            <div class="cst-completeness mt-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold" style="font-size:.82rem;" id="completeness-text">Completude: 0%</span>
                </div>
                <div class="cst-completeness-bar">
                    <div class="cst-completeness-fill low" id="completeness-fill" style="width:0%"></div>
                </div>
                <div class="cst-completeness-checks" id="completeness-checks"></div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════ -->
        <!-- WIZARD FOOTER — Botões de navegação            -->
        <!-- ═══════════════════════════════════════════════ -->
        <div class="cst-wizard-footer">
            <a href="?page=customers" class="btn btn-light">Cancelar</a>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary" id="btnWizardPrev" style="display:none;">
                    <i class="fas fa-arrow-left me-1"></i>Anterior
                </button>
                <button type="button" class="btn btn-primary" id="btnWizardNext">
                    Próximo <i class="fas fa-arrow-right ms-1"></i>
                </button>
                <button type="submit" class="btn btn-success" id="btnWizardSubmit" style="display:none;">
                    <i class="fas fa-save me-1"></i>Salvar Alterações
                </button>
            </div>
        </div>
    </form>
</div>

<!-- IMask.js via CDN -->
<script src="https://unpkg.com/imask@7.1.3/dist/imask.min.js"></script>
<!-- Módulos JS do cliente (Fase 4 — completo) -->
<script src="assets/js/customer-masks.js"></script>
<script src="assets/js/customer-validation.js"></script>
<script src="assets/js/customer-completeness.js"></script>
<script src="assets/js/customer-tags.js"></script>
<script src="assets/js/customer-autosave.js"></script>
<script src="assets/js/customer-shortcuts.js"></script>
<script src="assets/js/customer-wizard.js"></script>
