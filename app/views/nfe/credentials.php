<?php
/**
 * View: Credenciais SEFAZ (NF-e) — Fase 6 UX Modernizada
 * Wizard em 3 etapas: Empresa → Certificado → Configuração
 * @var array $credentials    Credenciais atuais
 * @var array $validation     Resultado da validação de credenciais
 * @var bool  $certExpired    Certificado expirado
 * @var bool  $certExpiringSoon Certificado expirando em breve
 */
$pageTitle = 'Credenciais SEFAZ — NF-e';

// Calcular completude das etapas
$step1Complete = !empty($credentials['cnpj']) && !empty($credentials['ie']) && !empty($credentials['razao_social']);
$step2Complete = !empty($credentials['certificate_path']) && !$certExpired;
$step3Complete = !empty($credentials['environment']) && !empty($credentials['serie_nfe']);
$allComplete = $step1Complete && $step2Complete && $step3Complete;
$completePct = 0;
if ($step1Complete) $completePct += 34;
if ($step2Complete) $completePct += 33;
if ($step3Complete) $completePct += 33;
?>

<style>
    .wizard-progress { display: flex; justify-content: center; gap: 0; margin-bottom: 2rem; }
    .wizard-step { display: flex; align-items: center; gap: 0; }
    .wizard-step-circle {
        width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 1.1rem; border: 3px solid #dee2e6; background: #fff; color: #6c757d;
        transition: all 0.3s; cursor: pointer; position: relative; z-index: 2;
    }
    .wizard-step-circle.active { border-color: #0d6efd; color: #0d6efd; box-shadow: 0 0 0 4px rgba(13,110,253,0.15); }
    .wizard-step-circle.completed { border-color: #198754; background: #198754; color: #fff; }
    .wizard-step-circle.error { border-color: #dc3545; background: #dc3545; color: #fff; }
    .wizard-step-label { font-size: 0.78rem; text-align: center; margin-top: 0.5rem; color: #6c757d; font-weight: 500; }
    .wizard-step-label.active { color: #0d6efd; font-weight: 700; }
    .wizard-step-label.completed { color: #198754; }
    .wizard-connector { width: 80px; height: 3px; background: #dee2e6; margin-top: -1.5rem; }
    .wizard-connector.completed { background: #198754; }
    .wizard-panel { display: none; animation: fadeInPanel 0.3s ease; }
    .wizard-panel.active { display: block; }
    @keyframes fadeInPanel { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .credential-status-bar { border-radius: 1rem; overflow: hidden; }
    .cert-status-card { border-radius: 0.75rem; transition: all 0.2s; }
    .cert-status-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    .info-label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.5px; color: #6c757d; margin-bottom: 2px; }
    @media (max-width: 768px) {
        .wizard-connector { width: 30px; }
        .wizard-step-circle { width: 40px; height: 40px; font-size: 0.95rem; }
    }
</style>

<?php $isAjax = $isAjax ?? false; ?>

<?php if (!$isAjax): ?>
<div class="container py-4">

    <!-- ═══ Cabeçalho ═══ -->
    <div class="d-flex flex-wrap justify-content-between align-items-center pt-2 pb-2 mb-3 border-bottom gap-2">
        <div>
            <h1 class="h3 mb-0 fw-bold"><i class="fas fa-certificate me-2 text-success"></i> Credenciais SEFAZ</h1>
            <small class="text-muted">Configure os dados do emitente e o certificado digital para emissão de NF-e</small>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="?page=nfe_documents" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-file-invoice me-1"></i> Painel de Notas
            </a>
            <button type="button" class="btn btn-outline-success btn-sm" id="btnTestConnection">
                <i class="fas fa-plug me-1"></i> Testar Conexão
            </button>
        </div>
    </div>
<?php else: ?>
    <!-- Em modo AJAX, apenas botão de teste inline -->
    <div class="d-flex justify-content-end mb-3">
        <button type="button" class="btn btn-outline-success btn-sm" id="btnTestConnection">
            <i class="fas fa-plug me-1"></i> Testar Conexão SEFAZ
        </button>
    </div>
<?php endif; ?>

    <!-- ═══ Barra de Completude ═══ -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-bold small">
                    <?php if ($allComplete): ?>
                    <i class="fas fa-check-circle text-success me-1"></i> Configuração completa
                    <?php else: ?>
                    <i class="fas fa-exclamation-circle text-warning me-1"></i> Configuração pendente
                    <?php endif; ?>
                </span>
                <span class="small text-muted"><?= $completePct ?>% completo</span>
            </div>
            <div class="progress credential-status-bar" style="height: 8px;">
                <div class="progress-bar <?= $allComplete ? 'bg-success' : 'bg-primary' ?>" style="width: <?= $completePct ?>%;" role="progressbar"></div>
            </div>
        </div>
    </div>

    <!-- ═══ Alertas ═══ -->
    <?php if ($certExpired): ?>
    <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center gap-2 mb-3">
        <i class="fas fa-times-circle fs-4"></i>
        <div>
            <strong>Certificado digital expirado!</strong>
            <span class="d-block small">Validade: <?= e($credentials['certificate_expiry'] ?? '') ?>. Faça upload de um novo certificado.</span>
        </div>
    </div>
    <?php elseif ($certExpiringSoon): ?>
    <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center gap-2 mb-3">
        <i class="fas fa-clock fs-4"></i>
        <div>
            <strong>Certificado expirando em breve!</strong>
            <span class="d-block small">Validade: <?= e($credentials['certificate_expiry'] ?? '') ?>. Renove o certificado.</span>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm">
        <i class="fas fa-check-circle me-2"></i> <?= e($_SESSION['flash_success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_success']); endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm">
        <i class="fas fa-times-circle me-2"></i> <?= e($_SESSION['flash_error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_error']); endif; ?>

    <!-- ═══ Wizard Navigation ═══ -->
    <div class="wizard-progress">
        <div class="text-center">
            <div class="wizard-step-circle <?= $step1Complete ? 'completed' : 'active' ?>" data-step="1" onclick="goToStep(1)">
                <?= $step1Complete ? '<i class="fas fa-check"></i>' : '1' ?>
            </div>
            <div class="wizard-step-label <?= $step1Complete ? 'completed' : 'active' ?>">Empresa</div>
        </div>
        <div class="wizard-connector <?= $step1Complete ? 'completed' : '' ?>"></div>
        <div class="text-center">
            <div class="wizard-step-circle <?= $step2Complete ? 'completed' : ($certExpired ? 'error' : '') ?>" data-step="2" onclick="goToStep(2)">
                <?php if ($step2Complete): ?><i class="fas fa-check"></i>
                <?php elseif ($certExpired): ?><i class="fas fa-times"></i>
                <?php else: ?>2<?php endif; ?>
            </div>
            <div class="wizard-step-label <?= $step2Complete ? 'completed' : '' ?>">Certificado</div>
        </div>
        <div class="wizard-connector <?= $step2Complete ? 'completed' : '' ?>"></div>
        <div class="text-center">
            <div class="wizard-step-circle <?= $step3Complete ? 'completed' : '' ?>" data-step="3" onclick="goToStep(3)">
                <?= $step3Complete ? '<i class="fas fa-check"></i>' : '3' ?>
            </div>
            <div class="wizard-step-label <?= $step3Complete ? 'completed' : '' ?>">Configuração</div>
        </div>
    </div>

    <!-- ═══ Formulário com Wizard Panels ═══ -->
    <form method="POST" action="?page=nfe_credentials&action=store" enctype="multipart/form-data" id="formCredentials">
        <?= csrf_field() ?>

        <!-- ═══ STEP 1: Dados da Empresa ═══ -->
        <div class="wizard-panel active" id="wizardStep1">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-building me-2 text-primary"></i> Dados do Emitente</h5>
                    <small class="text-muted">Informações da empresa que emitirá as notas fiscais</small>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">CNPJ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="cnpj" id="inputCnpj"
                                   value="<?= eAttr($credentials['cnpj'] ?? '') ?>"
                                   placeholder="00.000.000/0000-00" maxlength="18" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Inscrição Estadual <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="ie" 
                                   value="<?= eAttr($credentials['ie'] ?? '') ?>"
                                   placeholder="Número da IE" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">CRT (Regime Tributário)</label>
                            <select class="form-select" name="crt">
                                <option value="1" <?= ($credentials['crt'] ?? 1) == 1 ? 'selected' : '' ?>>1 — Simples Nacional</option>
                                <option value="2" <?= ($credentials['crt'] ?? 1) == 2 ? 'selected' : '' ?>>2 — Simples Nacional (Excesso)</option>
                                <option value="3" <?= ($credentials['crt'] ?? 1) == 3 ? 'selected' : '' ?>>3 — Regime Normal</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Razão Social <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="razao_social" 
                                   value="<?= eAttr($credentials['razao_social'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nome Fantasia</label>
                            <input type="text" class="form-control" name="nome_fantasia" 
                                   value="<?= eAttr($credentials['nome_fantasia'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Endereço -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-map-marker-alt me-2 text-danger"></i> Endereço do Emitente</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">CEP</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="cep" id="inputCep"
                                       value="<?= eAttr($credentials['cep'] ?? '') ?>"
                                       placeholder="00000-000" maxlength="10">
                                <button class="btn btn-outline-secondary" type="button" id="btnBuscaCep" title="Buscar CEP">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">UF</label>
                            <select class="form-select" name="uf" id="inputUf">
                                <?php 
                                $ufs = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
                                foreach ($ufs as $uf): ?>
                                <option value="<?= $uf ?>" <?= ($credentials['uf'] ?? 'RS') === $uf ? 'selected' : '' ?>><?= $uf ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Município</label>
                            <input type="text" class="form-control" name="municipio" id="inputMunicipio"
                                   value="<?= eAttr($credentials['municipio'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Cód. IBGE Município</label>
                            <input type="text" class="form-control" name="cod_municipio" id="inputCodMunicipio"
                                   value="<?= eAttr($credentials['cod_municipio'] ?? '') ?>"
                                   placeholder="Ex: 4314902" maxlength="10">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-bold">Logradouro</label>
                            <input type="text" class="form-control" name="logradouro" id="inputLogradouro"
                                   value="<?= eAttr($credentials['logradouro'] ?? '') ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Número</label>
                            <input type="text" class="form-control" name="numero" 
                                   value="<?= eAttr($credentials['numero'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Bairro</label>
                            <input type="text" class="form-control" name="bairro" id="inputBairro"
                                   value="<?= eAttr($credentials['bairro'] ?? '') ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Complemento</label>
                            <input type="text" class="form-control" name="complemento" 
                                   value="<?= eAttr($credentials['complemento'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Telefone</label>
                            <input type="text" class="form-control" name="telefone" 
                                   value="<?= eAttr($credentials['telefone'] ?? '') ?>"
                                   placeholder="(00) 00000-0000">
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-primary px-4" onclick="goToStep(2)">
                    Próximo <i class="fas fa-arrow-right ms-1"></i>
                </button>
            </div>
        </div>

        <!-- ═══ STEP 2: Certificado Digital ═══ -->
        <div class="wizard-panel" id="wizardStep2">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-key me-2 text-warning"></i> Certificado Digital A1</h5>
                    <small class="text-muted">Upload e configuração do certificado .pfx para assinatura das NF-e</small>
                </div>
                <div class="card-body">

                    <!-- Status do certificado atual -->
                    <?php if (!empty($credentials['certificate_path'])): ?>
                    <div class="cert-status-card border rounded-3 p-3 mb-4 <?= $certExpired ? 'border-danger bg-danger bg-opacity-10' : ($certExpiringSoon ? 'border-warning bg-warning bg-opacity-10' : 'border-success bg-success bg-opacity-10') ?>">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle p-2 <?= $certExpired ? 'bg-danger' : ($certExpiringSoon ? 'bg-warning' : 'bg-success') ?> bg-opacity-25">
                                <i class="fas <?= $certExpired ? 'fa-times-circle text-danger' : ($certExpiringSoon ? 'fa-clock text-warning' : 'fa-shield-alt text-success') ?> fs-4"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold">
                                    <?= $certExpired ? 'Certificado Expirado' : ($certExpiringSoon ? 'Certificado Expirando' : 'Certificado Válido') ?>
                                </div>
                                <small class="text-muted">
                                    Validade: <?= e($credentials['certificate_expiry'] ?? 'N/A') ?>
                                    <?php if (!empty($credentials['certificate_cnpj'] ?? '')): ?>
                                    — CNPJ: <?= e($credentials['certificate_cnpj']) ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                            <?php if (!$certExpired): ?>
                            <span class="badge bg-success">Ativo</span>
                            <?php else: ?>
                            <span class="badge bg-danger">Expirado</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="cert-status-card border rounded-3 p-3 mb-4 border-secondary bg-light">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle p-2 bg-secondary bg-opacity-25">
                                <i class="fas fa-upload text-secondary fs-4"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-secondary">Nenhum certificado configurado</div>
                                <small class="text-muted">Faça upload do certificado A1 (.pfx / .p12) abaixo</small>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">
                                <i class="fas fa-file-upload me-1"></i> Arquivo do Certificado (.pfx / .p12)
                            </label>
                            <input type="file" class="form-control" name="certificate" id="inputCertificate" accept=".pfx,.p12">
                            <div class="form-text text-muted">
                                <?php if (!empty($credentials['certificate_path'])): ?>
                                <i class="fas fa-info-circle me-1"></i> Envie um novo arquivo para substituir o certificado atual.
                                <?php else: ?>
                                <i class="fas fa-info-circle me-1"></i> Formato aceito: .pfx ou .p12 (certificado tipo A1)
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold"><i class="fas fa-lock me-1"></i> Senha do Certificado</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="certificate_password" id="certPassword"
                                       placeholder="<?= !empty($credentials['certificate_password']) ? '••••••••' : 'Senha do .pfx' ?>"
                                       autocomplete="new-password">
                                <button class="btn btn-outline-secondary" type="button" id="toggleCertPassword" title="Mostrar/ocultar">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text text-muted">
                                <i class="fas fa-shield-alt me-1"></i> A senha é criptografada antes de ser armazenada.
                            </div>
                        </div>
                    </div>

                    <!-- Validação visual do certificado -->
                    <div id="certValidationResult" class="mt-3" style="display:none;"></div>
                </div>
            </div>

            <div class="d-flex justify-content-between gap-2">
                <button type="button" class="btn btn-outline-secondary px-4" onclick="goToStep(1)">
                    <i class="fas fa-arrow-left me-1"></i> Anterior
                </button>
                <button type="button" class="btn btn-primary px-4" onclick="goToStep(3)">
                    Próximo <i class="fas fa-arrow-right ms-1"></i>
                </button>
            </div>
        </div>

        <!-- ═══ STEP 3: Configuração de Emissão ═══ -->
        <div class="wizard-panel" id="wizardStep3">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-cog me-2 text-success"></i> Configuração de Emissão</h5>
                    <small class="text-muted">Parâmetros de emissão e ambiente SEFAZ</small>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Ambiente SEFAZ</label>
                            <select class="form-select" name="environment" id="selEnvironment">
                                <option value="homologacao" <?= ($credentials['environment'] ?? 'homologacao') === 'homologacao' ? 'selected' : '' ?>>
                                    🧪 Homologação (Testes)
                                </option>
                                <option value="producao" <?= ($credentials['environment'] ?? 'homologacao') === 'producao' ? 'selected' : '' ?>>
                                    🔴 Produção (NF-e válida)
                                </option>
                            </select>
                            <div class="form-text text-danger" id="prodWarning" style="display:none;">
                                <i class="fas fa-exclamation-triangle me-1"></i> 
                                Atenção: NF-e emitidas em produção têm validade fiscal!
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Série NF-e</label>
                            <input type="number" class="form-control" name="serie_nfe" 
                                   value="<?= eAttr($credentials['serie_nfe'] ?? 1) ?>" min="1" max="999">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Próximo Número NF-e</label>
                            <input type="number" class="form-control" name="proximo_numero" 
                                   value="<?= eAttr($credentials['proximo_numero'] ?? 1) ?>" min="1">
                            <div class="form-text text-muted">Incrementado automaticamente a cada emissão.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">CSC ID (NFC-e)</label>
                            <input type="text" class="form-control" name="csc_id" 
                                   value="<?= eAttr($credentials['csc_id'] ?? '') ?>"
                                   placeholder="Opcional">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">CSC Token (NFC-e)</label>
                            <input type="text" class="form-control" name="csc_token" 
                                   value="<?= eAttr($credentials['csc_token'] ?? '') ?>"
                                   placeholder="Opcional — apenas se emitir NFC-e">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Teste de Conexão -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="fw-bold mb-1"><i class="fas fa-plug me-2 text-success"></i> Testar Conexão SEFAZ</h6>
                            <small class="text-muted">Verifica se as credenciais estão corretas e a SEFAZ está acessível.</small>
                        </div>
                        <button type="button" class="btn btn-outline-success" id="btnTestConnectionWizard">
                            <i class="fas fa-plug me-1"></i> Testar
                        </button>
                    </div>
                    <div id="testConnectionResultWizard" class="mt-3" style="display:none;"></div>
                </div>
            </div>

            <div class="d-flex justify-content-between gap-2">
                <button type="button" class="btn btn-outline-secondary px-4" onclick="goToStep(2)">
                    <i class="fas fa-arrow-left me-1"></i> Anterior
                </button>
                <button type="submit" class="btn btn-success px-5">
                    <i class="fas fa-save me-1"></i> Salvar Credenciais
                </button>
            </div>
        </div>
    </form>

    <!-- ═══ IBPTax — Fora do Wizard ═══ -->
    <div class="card border-0 shadow-sm mb-4 mt-5">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0 fw-bold"><i class="fas fa-table me-2 text-info"></i> Tabela IBPTax (Lei 12.741)</h5>
                    <small class="text-muted">Importação das alíquotas aproximadas de tributos por NCM</small>
                </div>
                <button type="button" class="btn btn-sm btn-outline-info" id="btnRefreshIbptaxStats">
                    <i class="fas fa-sync me-1"></i> Atualizar
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="alert alert-info border-0 py-2 mb-3 d-flex align-items-center gap-2">
                <i class="fas fa-info-circle"></i>
                <small>
                    A tabela IBPTax contém as alíquotas aproximadas de tributos conforme a Lei 12.741/2012.
                    Baixe a tabela atualizada em <a href="https://ibpt.com.br" target="_blank" rel="noopener">ibpt.com.br</a>.
                </small>
            </div>

            <!-- Stats cards -->
            <div class="row g-3 mb-4" id="ibptaxStatsRow">
                <div class="col-6 col-md-3">
                    <div class="border rounded-3 p-3 text-center">
                        <div class="info-label">Registros</div>
                        <div class="fw-bold fs-5" id="ibptaxStatTotal">—</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded-3 p-3 text-center">
                        <div class="info-label">NCMs distintos</div>
                        <div class="fw-bold fs-5" id="ibptaxStatNcms">—</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded-3 p-3 text-center">
                        <div class="info-label">Versão</div>
                        <div class="fw-bold fs-5" id="ibptaxStatVersion">—</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded-3 p-3 text-center">
                        <div class="info-label">Vigência até</div>
                        <div class="fw-bold fs-5" id="ibptaxStatExpiry">—</div>
                    </div>
                </div>
            </div>

            <!-- Import form -->
            <form id="formImportIbptax" enctype="multipart/form-data">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Arquivo CSV da tabela IBPTax</label>
                        <input type="file" class="form-control" name="ibptax_csv" id="ibptaxCsvFile" accept=".csv,.txt" required>
                        <div class="form-text text-muted" style="font-size:0.7rem;">
                            Formato: NCM;Ex;Tipo;Descrição;AliqNac;AliqImp;AliqEst;AliqMun;VigInicio;VigFim;Versão;Fonte
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="ibptaxTruncate" name="truncate_before" value="1">
                            <label class="form-check-label small" for="ibptaxTruncate">
                                Limpar tabela antes de importar
                            </label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-info w-100" id="btnImportIbptax">
                            <i class="fas fa-upload me-1"></i> Importar
                        </button>
                    </div>
                </div>
            </form>
            <div id="ibptaxImportResult" class="mt-3" style="display:none;"></div>
        </div>
    </div>

    <!-- Resultado teste de conexão (global) -->
    <div id="testConnectionResult" class="mb-4" style="display:none;"></div>
<?php if (!$isAjax): ?>
</div>
<?php endif; ?>

<script>
(function(__run){if(typeof jQuery!=='undefined'){jQuery(__run);}else{document.addEventListener('DOMContentLoaded',__run);}})(function(){

    // ═══════════════════════════════════════
    // Wizard Navigation
    // ═══════════════════════════════════════
    var currentStep = 1;

    window.goToStep = function(step) {
        currentStep = step;
        $('.wizard-panel').removeClass('active');
        $('#wizardStep' + step).addClass('active');

        // Update circles
        $('.wizard-step-circle').each(function(){
            var s = parseInt($(this).data('step'));
            if (s === step && !$(this).hasClass('completed') && !$(this).hasClass('error')) {
                $(this).addClass('active');
            } else {
                $(this).removeClass('active');
            }
        });

        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    // ═══════════════════════════════════════
    // Toggle senha certificado
    // ═══════════════════════════════════════
    $('#toggleCertPassword').on('click', function(){
        var input = $('#certPassword');
        var icon = $(this).find('i');
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // ═══════════════════════════════════════
    // Busca CEP via ViaCEP
    // ═══════════════════════════════════════
    $('#btnBuscaCep').on('click', function(){
        var cep = $('#inputCep').val().replace(/\D/g, '');
        if (cep.length !== 8) {
            Swal.fire('CEP inválido', 'Informe um CEP com 8 dígitos.', 'warning');
            return;
        }
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.getJSON('https://viacep.com.br/ws/' + cep + '/json/', function(data){
            if (data.erro) {
                Swal.fire('CEP não encontrado', 'Verifique o CEP informado.', 'warning');
            } else {
                $('#inputLogradouro').val(data.logradouro || '');
                $('#inputBairro').val(data.bairro || '');
                $('#inputMunicipio').val(data.localidade || '');
                $('#inputUf').val(data.uf || '');
                if (data.ibge) $('#inputCodMunicipio').val(data.ibge);
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Endereço preenchido!', showConfirmButton: false, timer: 2000 });
            }
        }).fail(function(){
            Swal.fire('Erro', 'Não foi possível consultar o CEP.', 'error');
        }).always(function(){
            btn.prop('disabled', false).html('<i class="fas fa-search"></i>');
        });
    });

    // ═══════════════════════════════════════
    // Alerta ao mudar para produção
    // ═══════════════════════════════════════
    $('#selEnvironment').on('change', function(){
        var isProd = $(this).val() === 'producao';
        $('#prodWarning').toggle(isProd);
        if (isProd) {
            Swal.fire({
                icon: 'warning',
                title: 'Mudar para Produção?',
                html: '<p>NF-e emitidas em ambiente de <strong>produção</strong> têm validade fiscal.</p><p class="text-danger">Certifique-se de que todos os dados estão corretos.</p>',
                showCancelButton: true,
                confirmButtonText: 'Sim, usar produção',
                cancelButtonText: 'Manter homologação',
                confirmButtonColor: '#dc3545',
            }).then(function(result){
                if (!result.isConfirmed) {
                    $('#selEnvironment').val('homologacao');
                    $('#prodWarning').hide();
                }
            });
        }
    });
    if ($('#selEnvironment').val() === 'producao') $('#prodWarning').show();

    // ═══════════════════════════════════════
    // Teste de Conexão SEFAZ
    // ═══════════════════════════════════════
    function testConnection(resultSelector) {
        var resultDiv = $(resultSelector);
        resultDiv.hide();

        $.ajax({
            url: '?page=nfe_credentials&action=testConnection',
            method: 'POST',
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function(resp){
            var alertClass = resp.success ? 'alert-success' : 'alert-danger';
            var icon = resp.success ? 'fa-check-circle' : 'fa-times-circle';
            resultDiv.html(
                '<div class="alert ' + alertClass + ' border-0 d-flex align-items-center gap-2">' +
                '<i class="fas ' + icon + ' fs-5"></i>' +
                '<div><strong>Resultado:</strong> ' + (resp.message || 'Sem resposta') + '</div></div>'
            ).show();
        }).fail(function(){
            resultDiv.html(
                '<div class="alert alert-danger border-0"><i class="fas fa-times-circle me-2"></i>Erro ao testar conexão.</div>'
            ).show();
        });
    }

    $('#btnTestConnection').on('click', function(){
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Testando...');
        testConnection('#testConnectionResult');
        setTimeout(function(){ btn.prop('disabled', false).html('<i class="fas fa-plug me-1"></i> Testar Conexão'); }, 3000);
    });

    $('#btnTestConnectionWizard').on('click', function(){
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Testando...');
        testConnection('#testConnectionResultWizard');
        setTimeout(function(){ btn.prop('disabled', false).html('<i class="fas fa-plug me-1"></i> Testar'); }, 3000);
    });

    // ═══════════════════════════════════════
    // IBPTax
    // ═══════════════════════════════════════
    function loadIbptaxStats() {
        $.ajax({
            url: '?page=nfe_credentials&action=ibptaxStats',
            method: 'GET',
            dataType: 'json'
        }).done(function(resp) {
            if (resp.success && resp.stats) {
                var s = resp.stats;
                $('#ibptaxStatTotal').text(parseInt(s.total_registros || 0).toLocaleString('pt-BR'));
                $('#ibptaxStatNcms').text(parseInt(s.ncms_distintos || 0).toLocaleString('pt-BR'));
                $('#ibptaxStatVersion').text(s.versao_mais_recente || '—');
                $('#ibptaxStatExpiry').text(
                    s.vigencia_fim_max 
                        ? new Date(s.vigencia_fim_max).toLocaleDateString('pt-BR') 
                        : '—'
                );
            }
        });
    }
    loadIbptaxStats();

    $('#btnRefreshIbptaxStats').on('click', function() {
        loadIbptaxStats();
        $(this).html('<i class="fas fa-sync fa-spin me-1"></i> Atualizando...');
        setTimeout(function() {
            $('#btnRefreshIbptaxStats').html('<i class="fas fa-sync me-1"></i> Atualizar');
        }, 1000);
    });

    $('#formImportIbptax').on('submit', function(e) {
        e.preventDefault();
        var btn = $('#btnImportIbptax');
        var resultDiv = $('#ibptaxImportResult');
        var fileInput = document.getElementById('ibptaxCsvFile');

        if (!fileInput.files.length) {
            Swal.fire('Atenção', 'Selecione um arquivo CSV.', 'warning');
            return;
        }

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Importando...');
        resultDiv.hide();

        var formData = new FormData();
        formData.append('ibptax_csv', fileInput.files[0]);
        formData.append('truncate_before', $('#ibptaxTruncate').is(':checked') ? '1' : '0');

        $.ajax({
            url: '?page=nfe_credentials&action=importIbptax',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function(resp) {
            var alertClass = resp.success ? 'alert-success' : 'alert-danger';
            var icon = resp.success ? 'fa-check-circle' : 'fa-times-circle';
            resultDiv.html(
                '<div class="alert ' + alertClass + ' border-0 d-flex align-items-center gap-2">' +
                '<i class="fas ' + icon + ' fs-5"></i>' +
                '<div>' + (resp.message || 'Sem resposta') + '</div></div>'
            ).show();
            if (resp.success) {
                loadIbptaxStats();
                fileInput.value = '';
            }
        }).fail(function() {
            resultDiv.html(
                '<div class="alert alert-danger border-0"><i class="fas fa-times-circle me-2"></i>Erro na importação.</div>'
            ).show();
        }).always(function() {
            btn.prop('disabled', false).html('<i class="fas fa-upload me-1"></i> Importar');
        });
    });
});
</script>
