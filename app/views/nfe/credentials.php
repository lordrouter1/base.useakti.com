<?php
/**
 * View: Credenciais SEFAZ (NF-e)
 * Formulário de configuração do emitente + certificado digital.
 * @var array $credentials Credenciais atuais
 * @var array $validation  Resultado da validação de credenciais
 * @var bool  $certExpired Certificado expirado
 * @var bool  $certExpiringSoon Certificado expirando em breve
 */
$pageTitle = 'Credenciais SEFAZ — NF-e';
?>

<div class="container py-4">

    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center pt-2 pb-2 mb-3 border-bottom">
        <div>
            <h1 class="h2 mb-0"><i class="fas fa-certificate me-2 text-success"></i> Credenciais SEFAZ</h1>
            <small class="text-muted">Configure os dados do emitente e o certificado digital para emissão de NF-e</small>
        </div>
        <div class="d-flex gap-2">
            <a href="?page=nfe_documents" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-file-invoice me-1"></i> Painel de Notas
            </a>
            <button type="button" class="btn btn-outline-success btn-sm" id="btnTestConnection">
                <i class="fas fa-plug me-1"></i> Testar Conexão SEFAZ
            </button>
        </div>
    </div>

    <!-- Alertas de status -->
    <?php if (!$validation['valid']): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Credenciais incompletas:</strong> <?= e(implode(', ', $validation['missing'])) ?>
    </div>
    <?php else: ?>
    <div class="alert alert-success border-0">
        <i class="fas fa-check-circle me-2"></i> Credenciais completas — pronto para emitir NF-e.
    </div>
    <?php endif; ?>

    <?php if ($certExpired): ?>
    <div class="alert alert-danger">
        <i class="fas fa-times-circle me-2"></i>
        <strong>Certificado digital expirado!</strong> Validade: <?= e($credentials['certificate_expiry'] ?? '') ?>. 
        Faça upload de um novo certificado.
    </div>
    <?php elseif ($certExpiringSoon): ?>
    <div class="alert alert-warning">
        <i class="fas fa-clock me-2"></i>
        <strong>Certificado expirando em breve!</strong> Validade: <?= e($credentials['certificate_expiry'] ?? '') ?>. 
        Renove o certificado.
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check me-2"></i> <?= e($_SESSION['flash_success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_success']); endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-times me-2"></i> <?= e($_SESSION['flash_error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_error']); endif; ?>

    <!-- Formulário -->
    <form method="POST" action="?page=nfe_credentials&action=store" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <!-- ═══ Dados do Emitente ═══ -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header py-2 bg-primary ">
                <h6 class="mb-0 text-primary"><i class="fas fa-building me-2"></i> Dados do Emitente</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">CNPJ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="cnpj" 
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

        <!-- ═══ Endereço ═══ -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header py-2 bg-primary ">
                <h6 class="mb-0 text-primary"><i class="fas fa-map-marker-alt me-2"></i> Endereço do Emitente</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">UF</label>
                        <select class="form-select" name="uf">
                            <?php 
                            $ufs = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
                            foreach ($ufs as $uf): ?>
                            <option value="<?= $uf ?>" <?= ($credentials['uf'] ?? 'RS') === $uf ? 'selected' : '' ?>><?= $uf ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Código IBGE Município</label>
                        <input type="text" class="form-control" name="cod_municipio" 
                               value="<?= eAttr($credentials['cod_municipio'] ?? '') ?>"
                               placeholder="Ex: 4314902" maxlength="10">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Município</label>
                        <input type="text" class="form-control" name="municipio" 
                               value="<?= eAttr($credentials['municipio'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">CEP</label>
                        <input type="text" class="form-control" name="cep" 
                               value="<?= eAttr($credentials['cep'] ?? '') ?>"
                               placeholder="00000-000" maxlength="10">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small fw-bold">Logradouro</label>
                        <input type="text" class="form-control" name="logradouro" 
                               value="<?= eAttr($credentials['logradouro'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Número</label>
                        <input type="text" class="form-control" name="numero" 
                               value="<?= eAttr($credentials['numero'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Bairro</label>
                        <input type="text" class="form-control" name="bairro" 
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

        <!-- ═══ Certificado Digital ═══ -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header py-2 bg-warning ">
                <h6 class="mb-0 text-warning"><i class="fas fa-key me-2"></i> Certificado Digital A1 (.pfx)</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Arquivo do Certificado (.pfx / .p12)</label>
                        <input type="file" class="form-control" name="certificate" accept=".pfx,.p12">
                        <?php if (!empty($credentials['certificate_path'])): ?>
                        <div class="form-text text-success">
                            <i class="fas fa-check-circle me-1"></i> Certificado configurado
                            <?php if (!empty($credentials['certificate_expiry'])): ?>
                            — Validade: <?= e($credentials['certificate_expiry']) ?>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="form-text text-muted">Faça upload do certificado A1 no formato .pfx</div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Senha do Certificado</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="certificate_password" id="certPassword"
                                   placeholder="<?= !empty($credentials['certificate_password']) ? '••••••••' : 'Senha do .pfx' ?>"
                                   autocomplete="new-password">
                            <button class="btn btn-outline-secondary" type="button" id="toggleCertPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text text-muted">A senha é criptografada antes de ser armazenada.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ Configuração NF-e ═══ -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header py-2 bg-success ">
                <h6 class="mb-0 text-success"><i class="fas fa-cog me-2"></i> Configuração de Emissão</h6>
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

        <!-- Botão salvar -->
        <div class="d-flex justify-content-end gap-2 mb-4">
            <button type="submit" class="btn btn-primary px-4">
                <i class="fas fa-save me-1"></i> Salvar Credenciais
            </button>
        </div>
    </form>

    <!-- Resultado teste de conexão -->
    <div id="testConnectionResult" class="mb-4" style="display:none;"></div>
</div>

<script>
$(function(){
    // Toggle senha certificado
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

    // Alerta ao mudar para produção
    $('#selEnvironment').on('change', function(){
        var isProd = $(this).val() === 'producao';
        $('#prodWarning').toggle(isProd);
        if (isProd) {
            Swal.fire({
                icon: 'warning',
                title: 'Mudar para Produção?',
                html: '<p>NF-e emitidas em ambiente de <strong>produção</strong> têm validade fiscal e são registradas na SEFAZ.</p><p class="text-danger">Certifique-se de que todos os dados estão corretos antes de emitir.</p>',
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
    // Inicializar estado do warning
    if ($('#selEnvironment').val() === 'producao') $('#prodWarning').show();

    // Teste de Conexão SEFAZ
    $('#btnTestConnection').on('click', function(){
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Testando...');
        var resultDiv = $('#testConnectionResult');
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
                '<div class="alert ' + alertClass + '">' +
                '<i class="fas ' + icon + ' me-2"></i>' +
                '<strong>Resultado:</strong> ' + (resp.message || 'Sem resposta') +
                '</div>'
            ).show();
        }).fail(function(){
            resultDiv.html(
                '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Erro ao testar conexão.</div>'
            ).show();
        }).always(function(){
            btn.prop('disabled', false).html('<i class="fas fa-plug me-1"></i> Testar Conexão SEFAZ');
        });
    });
});
</script>
