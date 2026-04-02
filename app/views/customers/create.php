<!-- 
    Cadastro de Cliente — Wizard Multi-Step (Fase 3)
    Variáveis: $priceTables, $sellers
-->
<link rel="stylesheet" href="assets/css/customers.css">

<div class="container py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1"><i class="fas fa-user-plus me-2 text-success"></i>Novo Cliente</h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">Preencha as informações do cliente. Campos com <span class="text-danger">*</span> são obrigatórios.</p>
        </div>
        <a href="?page=customers" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
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
    <form id="customerForm" method="post" action="?page=customers&action=store" enctype="multipart/form-data" class="cst-wizard-card">
        <?= csrf_field() ?>
        <input type="hidden" id="person_type" name="person_type" value="PF">

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
                        <button type="button" class="cst-toggle-option active" data-type="PF">
                            <i class="fas fa-user"></i> Pessoa Física
                        </button>
                        <button type="button" class="cst-toggle-option" data-type="PJ">
                            <i class="fas fa-building"></i> Pessoa Jurídica
                        </button>
                    </div>
                </div>

                <div class="row">
                    <!-- Coluna Esquerda: Foto + Status -->
                    <div class="col-md-3 text-center mb-3">
                        <div class="cst-photo-upload" title="Clique ou arraste para adicionar foto">
                            <img id="preview-photo" src="assets/img/default-avatar.png" alt="Foto" style="display:none;">
                            <div class="cst-photo-placeholder">
                                <i class="fas fa-camera"></i>
                                <span>Arraste ou clique</span>
                            </div>
                            <div class="cst-photo-overlay">
                                <i class="fas fa-camera me-1"></i> Alterar
                            </div>
                        </div>
                        <input type="file" id="photo" name="photo" class="d-none" accept="image/jpeg,image/png,image/gif" aria-describedby="photo_help">
                        <small class="text-muted d-block mt-2" id="photo_help" style="font-size:.7rem;">JPG, PNG ou GIF (máx 5MB)</small>

                        <div class="mt-3">
                            <label class="form-label fw-bold" style="font-size:.78rem;">Status</label>
                            <select id="status" name="status" class="form-select form-select-sm">
                                <option value="active" selected>Ativo</option>
                                <option value="inactive">Inativo</option>
                                <option value="blocked">Bloqueado</option>
                            </select>
                        </div>
                    </div>

                    <!-- Coluna Direita: Dados -->
                    <div class="col-md-9">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="name" class="form-label">Nome Completo <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required maxlength="191" placeholder="Nome completo do cliente">
                            </div>
                            <div class="col-md-4">
                                <label for="document" class="form-label">CPF <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="document" name="document" placeholder="000.000.000-00">
                                    <button type="button" class="btn btn-outline-info btn-sm cst-cnpj-search-btn" id="btnSearchCnpj" style="display:none;" title="Consultar CNPJ na Receita Federal">
                                        <i class="fas fa-search me-1"></i>Consultar
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="fantasy_name" class="form-label">Apelido</label>
                                <input type="text" class="form-control" id="fantasy_name" name="fantasy_name" maxlength="191" placeholder="Nome fantasia ou apelido">
                            </div>
                            <div class="col-md-3">
                                <label for="rg_ie" class="form-label">RG</label>
                                <input type="text" class="form-control" id="rg_ie" name="rg_ie" maxlength="30">
                            </div>
                            <div class="col-md-3" id="group-im" style="display:none;">
                                <label for="im" class="form-label">Inscrição Municipal</label>
                                <input type="text" class="form-control" id="im" name="im" maxlength="30">
                            </div>
                            <div class="col-md-3">
                                <label for="birth_date" class="form-label">Data de Nascimento</label>
                                <input type="text" class="form-control" id="birth_date" name="birth_date" placeholder="DD/MM/AAAA">
                            </div>
                            <div class="col-md-3" id="group-gender">
                                <label for="gender" class="form-label">Gênero</label>
                                <select id="gender" name="gender" class="form-select">
                                    <option value="">Selecione...</option>
                                    <option value="M">Masculino</option>
                                    <option value="F">Feminino</option>
                                    <option value="O">Outro</option>
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
                            <input type="email" class="form-control" id="email" name="email" maxlength="191" placeholder="email@exemplo.com">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="email_secondary" class="form-label">E-mail Secundário</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email_secondary" name="email_secondary" maxlength="191" placeholder="email2@exemplo.com">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="phone" class="form-label">Telefone Fixo</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                            <input type="text" class="form-control" id="phone" name="phone" placeholder="(00) 0000-0000">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="cellphone" class="form-label">Celular / WhatsApp</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fab fa-whatsapp text-success"></i></span>
                            <input type="text" class="form-control" id="cellphone" name="cellphone" placeholder="(00) 00000-0000">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="phone_commercial" class="form-label">Telefone Comercial</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-building"></i></span>
                            <input type="text" class="form-control" id="phone_commercial" name="phone_commercial" placeholder="(00) 0000-0000">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="website" class="form-label">Website</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-globe"></i></span>
                            <input type="url" class="form-control" id="website" name="website" maxlength="200" placeholder="https://www.exemplo.com.br">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="instagram" class="form-label">Instagram</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fab fa-instagram text-danger"></i></span>
                            <span class="input-group-text">@</span>
                            <input type="text" class="form-control" id="instagram" name="instagram" maxlength="100" placeholder="perfil">
                        </div>
                    </div>
                </div>

                <!-- Contato PJ -->
                <div id="group-contact-pj" style="display:none;" class="mt-3">
                    <hr>
                    <h6 class="fw-bold mb-3" style="font-size:.82rem;"><i class="fas fa-user-tie me-1 text-info"></i>Contato Principal (PJ)</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="contact_name" class="form-label">Nome do Contato</label>
                            <input type="text" class="form-control" id="contact_name" name="contact_name" maxlength="100" placeholder="Nome da pessoa responsável">
                        </div>
                        <div class="col-md-6">
                            <label for="contact_role" class="form-label">Cargo / Função</label>
                            <input type="text" class="form-control" id="contact_role" name="contact_role" maxlength="80" placeholder="Ex: Gerente de Compras">
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
                            <input type="text" class="form-control" id="zipcode" name="zipcode" placeholder="00000-000" aria-describedby="zipcode_help">
                            <span class="input-group-text" id="cep-spinner" style="display:none;"><i class="fas fa-spinner fa-spin"></i></span>
                        </div>
                        <small class="text-muted" id="zipcode_help" style="font-size:.68rem;">Digite o CEP para preencher automaticamente</small>
                    </div>
                    <div class="col-md-7">
                        <label for="address_street" class="form-label">Logradouro</label>
                        <input type="text" class="form-control" id="address_street" name="address_street" maxlength="200" placeholder="Rua, Avenida, Travessa...">
                    </div>
                    <div class="col-md-2">
                        <label for="address_number" class="form-label">Número</label>
                        <input type="text" class="form-control" id="address_number" name="address_number" maxlength="20" placeholder="Nº">
                    </div>
                    <div class="col-md-4">
                        <label for="address_complement" class="form-label">Complemento</label>
                        <input type="text" class="form-control" id="address_complement" name="address_complement" maxlength="100" placeholder="Apto, Sala, Bloco...">
                    </div>
                    <div class="col-md-4">
                        <label for="address_neighborhood" class="form-label">Bairro</label>
                        <input type="text" class="form-control" id="address_neighborhood" name="address_neighborhood" maxlength="100">
                    </div>
                    <div class="col-md-4">
                        <label for="address_city" class="form-label">Cidade</label>
                        <input type="text" class="form-control" id="address_city" name="address_city" maxlength="100">
                    </div>
                    <div class="col-md-3">
                        <label for="address_state" class="form-label">Estado (UF)</label>
                        <select id="address_state" name="address_state" class="form-select">
                            <option value="">Selecione...</option>
                            <?php
                            $ufs = ['AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO'];
                            foreach ($ufs as $uf): ?>
                            <option value="<?= $uf ?>"><?= $uf ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="address_country" class="form-label">País</label>
                        <input type="text" class="form-control" id="address_country" name="address_country" value="Brasil" maxlength="50">
                    </div>
                    <div class="col-md-4">
                        <label for="address_ibge" class="form-label">Código IBGE</label>
                        <input type="text" class="form-control bg-light" id="address_ibge" name="address_ibge" maxlength="10" readonly placeholder="Preenchido automaticamente">
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
                            <option value="<?= (int)$pt['id'] ?>"><?= e($pt['name']) ?> <?= $pt['is_default'] ? '(Padrão)' : '' ?></option>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="payment_term" class="form-label">Condição de Pagamento</label>
                        <select id="payment_term" name="payment_term" class="form-select">
                            <option value="">Selecione...</option>
                            <option value="a_vista">À Vista</option>
                            <option value="7_dias">7 dias</option>
                            <option value="14_dias">14 dias</option>
                            <option value="21_dias">21 dias</option>
                            <option value="30_dias">30 dias</option>
                            <option value="30_60">30/60 dias</option>
                            <option value="30_60_90">30/60/90 dias</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="credit_limit" class="form-label">Limite de Crédito</label>
                        <input type="text" class="form-control" id="credit_limit" name="credit_limit" placeholder="R$ 0,00">
                    </div>
                    <div class="col-md-4">
                        <label for="discount_default" class="form-label">Desconto Padrão (%)</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="discount_default" name="discount_default" placeholder="0,00">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="seller_id" class="form-label">Vendedor Responsável</label>
                        <select id="seller_id" name="seller_id" class="form-select">
                            <option value="">Nenhum</option>
                            <?php if (!empty($sellers)): ?>
                            <?php foreach ($sellers as $s): ?>
                            <option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="origin" class="form-label">Origem do Cliente</label>
                        <select id="origin" name="origin" class="form-select">
                            <option value="">Selecione...</option>
                            <option value="indicacao">Indicação</option>
                            <option value="google">Google / Busca</option>
                            <option value="redes_sociais">Redes Sociais</option>
                            <option value="site">Site</option>
                            <option value="visita">Visita Presencial</option>
                            <option value="telefone">Telefone</option>
                            <option value="feira_evento">Feira / Evento</option>
                            <option value="outro">Outro</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="tags" class="form-label">Tags</label>
                        <div class="position-relative">
                            <div class="cst-tag-input-wrapper" id="tags-wrapper">
                            </div>
                            <input type="hidden" id="tags" name="tags" value="" aria-describedby="tags_help">
                        </div>
                        <small class="text-muted" id="tags_help" style="font-size:.68rem;">Digite e pressione Enter para adicionar. Ex: VIP, Atacado, Indústria</small>
                    </div>
                    <div class="col-12">
                        <label for="observations" class="form-label">Observações</label>
                        <textarea class="form-control" id="observations" name="observations" rows="3" placeholder="Informações adicionais sobre o cliente..." style="resize:vertical;"></textarea>
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
                    <i class="fas fa-save me-1"></i>Salvar Cliente
                </button>
            </div>
        </div>
    </form>
</div>

<!-- IMask.js via CDN -->
<script src="https://unpkg.com/imask@7.1.3/dist/imask.min.js"></script>
<!-- Módulos JS do cliente -->
<script src="assets/js/customer-masks.js"></script>
<script src="assets/js/customer-validation.js"></script>
<script src="assets/js/customer-completeness.js"></script>
<script src="assets/js/customer-tags.js"></script>
<script src="assets/js/customer-autosave.js"></script>
<script src="assets/js/customer-shortcuts.js"></script>
<script src="assets/js/customer-wizard.js"></script>
