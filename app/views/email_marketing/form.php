<?php
/**
 * E-mail Marketing — Formulário de campanha
 * FEAT-013
 * Variáveis: $campaign (null = nova), $templates, $stats (edit mode)
 */
$isEdit = !empty($campaign);
$c = $campaign ?? [];
$segmentFilters = [];
if ($isEdit && !empty($c['segment_filters'])) {
    $decoded = is_string($c['segment_filters']) ? json_decode($c['segment_filters'], true) : $c['segment_filters'];
    $segmentFilters = is_array($decoded) ? $decoded : [];
}
$recipientType = !empty($segmentFilters['customer_ids']) ? 'selected' : 'all';
?>

<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('assets/css/summernote-fix.css') ?>">

<div class="container-fluid py-3">

    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-envelope me-2 text-primary"></i><?= $isEdit ? 'Editar Campanha' : 'Nova Campanha' ?></h1>
        </div>
        <a href="?page=email_marketing" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
    </div>

    <?php if ($isEdit && !empty($stats)): ?>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-primary border-4">
                <div class="card-body p-3 text-center">
                    <div class="text-muted small">Enviados</div>
                    <div class="fw-bold fs-4"><?= (int) ($stats['sent'] ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-success border-4">
                <div class="card-body p-3 text-center">
                    <div class="text-muted small">Abertos</div>
                    <div class="fw-bold fs-4"><?= (int) ($stats['opened'] ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-info border-4">
                <div class="card-body p-3 text-center">
                    <div class="text-muted small">Clicados</div>
                    <div class="fw-bold fs-4"><?= (int) ($stats['clicked'] ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-danger border-4">
                <div class="card-body p-3 text-center">
                    <div class="text-muted small">Bounced</div>
                    <div class="fw-bold fs-4"><?= (int) ($stats['bounced'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <form method="post" action="?page=email_marketing&action=<?= $isEdit ? 'update' : 'store' ?>">
        <?= csrf_field() ?>
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
        <?php endif; ?>

        <div class="row g-4">
            <!-- Coluna Principal -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nome da Campanha <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="<?= eAttr($c['name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Template</label>
                                <select name="template_id" id="templateSelect" class="form-select">
                                    <option value="">Sem template</option>
                                    <?php foreach ($templates ?? [] as $t): ?>
                                    <option value="<?= (int) $t['id'] ?>" <?= ($c['template_id'] ?? 0) == $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Agendamento</label>
                                <input type="datetime-local" name="scheduled_at" class="form-control" value="<?= eAttr($c['scheduled_at'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Assunto <span class="text-danger">*</span></label>
                                <input type="text" name="subject" id="subjectField" class="form-control" value="<?= eAttr($c['subject'] ?? '') ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Conteúdo HTML</label>
                                <textarea name="body_html" id="bodyHtml" class="form-control"><?= e($c['body_html'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Coluna Lateral -->
            <div class="col-lg-4">
                <!-- Variáveis -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white fw-bold">
                        <i class="fas fa-code me-1 text-primary"></i>Variáveis
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-2">Clique para inserir no editor. No envio, cada variável será substituída pelos dados reais do cliente destinatário.</p>
                        <div class="d-flex flex-wrap gap-1" id="variableButtons">
                            <button type="button" class="btn btn-sm btn-outline-secondary var-btn" data-var="{{nome}}" title="Nome completo ou razão social do cliente"><i class="fas fa-user me-1"></i>{{nome}}</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary var-btn" data-var="{{email}}" title="E-mail principal de contato do cliente"><i class="fas fa-envelope me-1"></i>{{email}}</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary var-btn" data-var="{{telefone}}" title="Telefone principal com DDD"><i class="fas fa-phone me-1"></i>{{telefone}}</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary var-btn" data-var="{{documento}}" title="CPF ou CNPJ do cliente"><i class="fas fa-id-card me-1"></i>{{documento}}</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary var-btn" data-var="{{cidade}}" title="Cidade do endereço principal"><i class="fas fa-map-marker-alt me-1"></i>{{cidade}}</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary var-btn" data-var="{{estado}}" title="Estado (UF) do endereço"><i class="fas fa-map me-1"></i>{{estado}}</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary var-btn" data-var="{{empresa}}" title="Nome da empresa (tenant)"><i class="fas fa-building me-1"></i>{{empresa}}</button>
                        </div>
                        <hr class="my-2">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>Exemplo:</strong> "Olá <code>{{nome}}</code>, confira!" → "Olá <strong>João Silva</strong>, confira!"
                        </small>
                    </div>
                </div>

                <!-- Destinatários -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white fw-bold">
                        <i class="fas fa-users me-1 text-success"></i>Destinatários
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="recipient_type" id="recipientAll" value="all" <?= $recipientType === 'all' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="recipientAll">
                                <i class="fas fa-globe me-1"></i>Todos os clientes ativos
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="recipient_type" id="recipientSelected" value="selected" <?= $recipientType === 'selected' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="recipientSelected">
                                <i class="fas fa-user-check me-1"></i>Selecionar clientes
                            </label>
                        </div>
                        <div id="customerSelectGroup" class="mt-2 <?= $recipientType === 'selected' ? '' : 'd-none' ?>">
                            <select name="customer_ids[]" id="customerSelect" class="form-select form-select-sm" multiple style="width:100%;">
                                <?php if (!empty($segmentFilters['customer_ids'])): ?>
                                    <?php foreach ($segmentFilters['customer_ids'] as $cid): ?>
                                    <option value="<?= (int) $cid['id'] ?>" selected><?= e($cid['text'] ?? "Cliente #{$cid['id']}") ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Ações -->
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Salvar Campanha</button>
                    <?php if ($isEdit): ?>
                    <button type="button" class="btn btn-outline-info" id="btnPreviewCampaign"><i class="fas fa-eye me-1"></i>Visualizar Preview</button>
                    <?php if (($c['status'] ?? 'draft') !== 'sent'): ?>
                    <hr class="my-1">
                    <button type="button" class="btn btn-outline-warning" id="btnSendTest"><i class="fas fa-paper-plane me-1"></i>Enviar Teste</button>
                    <button type="button" class="btn btn-success" id="btnSendCampaign"><i class="fas fa-rocket me-1"></i>Enviar Campanha</button>
                    <?php endif; ?>
                    <?php endif; ?>
                    <a href="?page=email_marketing" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </div>
        </div>

        <input type="hidden" name="segment_filters" id="segmentFiltersInput" value="">
    </form>
</div>

<?php if ($isEdit): ?>
<!-- Modal Preview -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-eye me-1"></i>Preview da Campanha</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="previewFrame" style="width:100%;height:500px;border:none;"></iframe>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function loadScript(src, callback) {
        const s = document.createElement('script');
        s.src = src;
        s.onload = callback;
        document.body.appendChild(s);
    }

    // Carregar Summernote após jQuery (footer)
    loadScript('https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js', function() {
        loadScript('https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/lang/summernote-pt-BR.min.js', function() {
            $('#bodyHtml').summernote({
                lang: 'pt-BR',
                height: 400,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
                    ['fontname', ['fontname']],
                    ['fontsize', ['fontsize']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture', 'hr']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ],
                placeholder: 'Escreva o conteúdo do e-mail aqui...'
            });

            // Variáveis clicáveis
            document.querySelectorAll('.var-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const tag = this.dataset.var;
                    $('#bodyHtml').summernote('editor.insertText', tag);
                });
            });

            // Template auto-fill
            const templateSelect = document.getElementById('templateSelect');
            templateSelect.addEventListener('change', function() {
                const tplId = this.value;
                if (!tplId) return;

                Swal.fire({
                    title: 'Carregar template?',
                    text: 'O assunto e conteúdo serão substituídos pelo modelo do template selecionado.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sim, carregar',
                    cancelButtonText: 'Não'
                }).then(result => {
                    if (!result.isConfirmed) return;

                    fetch('?page=email_marketing&action=getTemplateJson&id=' + tplId)
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                document.getElementById('subjectField').value = data.subject || '';
                                $('#bodyHtml').summernote('code', data.body_html || '');
                                if (typeof AktiToast !== 'undefined') {
                                    AktiToast.success('Template carregado!');
                                }
                            }
                        });
                });
            });

            // Preview button (edit mode)
            const btnPreview = document.getElementById('btnPreviewCampaign');
            if (btnPreview) {
                btnPreview.addEventListener('click', function() {
                    const frame = document.getElementById('previewFrame');
                    frame.src = '?page=email_marketing&action=previewCampaign&id=<?= (int) ($c['id'] ?? 0) ?>';
                    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
                    modal.show();
                });
            }
        });
    });

    // Destinatários — toggle Select2
    const customerGroup = document.getElementById('customerSelectGroup');

    function initCustomerSelect2() {
        const $cs = $('#customerSelect');
        if ($cs.hasClass('select2-hidden-accessible')) return;
        $cs.select2({
            placeholder: 'Pesquisar clientes...',
            allowClear: true,
            minimumInputLength: 0,
            width: '100%',
            ajax: {
                url: '?page=email_marketing&action=searchCustomers',
                dataType: 'json',
                delay: 300,
                data: function(params) {
                    return { term: params.term || '' };
                },
                processResults: function(data) {
                    return { results: data.results || [] };
                },
                cache: true
            }
        });
    }

    document.querySelectorAll('input[name="recipient_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'selected') {
                customerGroup.classList.remove('d-none');
                initCustomerSelect2();
            } else {
                customerGroup.classList.add('d-none');
            }
        });
    });

    // Init Select2 if pre-selected
    if (document.getElementById('recipientSelected').checked) {
        initCustomerSelect2();
    }

    // Before submit — build segment_filters JSON
    document.querySelector('form').addEventListener('submit', function() {
        const recipientType = document.querySelector('input[name="recipient_type"]:checked')?.value || 'all';
        const filters = { type: recipientType };

        if (recipientType === 'selected') {
            const $cs = $('#customerSelect');
            if ($cs.hasClass('select2-hidden-accessible')) {
                const selectedData = $cs.select2('data') || [];
                filters.customer_ids = selectedData.map(item => ({ id: parseInt(item.id), text: item.text }));
            }
        }

        document.getElementById('segmentFiltersInput').value = JSON.stringify(filters);
    });

    // Send Test Email
    const btnSendTest = document.getElementById('btnSendTest');
    if (btnSendTest) {
        btnSendTest.addEventListener('click', function() {
            Swal.fire({
                title: 'Enviar e-mail de teste',
                input: 'email',
                inputLabel: 'Digite o e-mail para receber o teste:',
                inputPlaceholder: 'email@exemplo.com',
                showCancelButton: true,
                confirmButtonText: 'Enviar teste',
                cancelButtonText: 'Cancelar',
                showLoaderOnConfirm: true,
                preConfirm: (email) => {
                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 15000);
                    return fetch('?page=email_marketing&action=sendTest&id=<?= (int) ($c['id'] ?? 0) ?>&email=' + encodeURIComponent(email), { signal: controller.signal })
                        .then(r => r.json())
                        .then(data => {
                            clearTimeout(timeoutId);
                            if (!data.success) throw new Error(data.error || 'Erro ao enviar.');
                            return data;
                        })
                        .catch(err => {
                            clearTimeout(timeoutId);
                            if (err.name === 'AbortError') {
                                Swal.showValidationMessage('Tempo esgotado. Verifique as configurações SMTP em .env (MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD).');
                            } else {
                                Swal.showValidationMessage(err.message);
                            }
                        });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then(result => {
                if (result.isConfirmed) {
                    Swal.fire('Enviado!', 'E-mail de teste enviado com sucesso.', 'success');
                }
            });
        });
    }

    // Send Campaign
    const btnSendCampaign = document.getElementById('btnSendCampaign');
    if (btnSendCampaign) {
        btnSendCampaign.addEventListener('click', function() {
            Swal.fire({
                title: 'Enviar campanha?',
                html: 'Os e-mails serão enviados para todos os destinatários selecionados.<br><strong>Esta ação não pode ser desfeita.</strong>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                confirmButtonText: '<i class="fas fa-rocket me-1"></i>Sim, enviar agora',
                cancelButtonText: 'Cancelar',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 60000);
                    return fetch('?page=email_marketing&action=sendCampaign&id=<?= (int) ($c['id'] ?? 0) ?>', { signal: controller.signal })
                        .then(r => r.json())
                        .then(data => {
                            clearTimeout(timeoutId);
                            if (!data.success) throw new Error(data.error || 'Erro ao enviar campanha.');
                            return data;
                        })
                        .catch(err => {
                            clearTimeout(timeoutId);
                            if (err.name === 'AbortError') {
                                Swal.showValidationMessage('Tempo esgotado. Verifique as configurações SMTP.');
                            } else {
                                Swal.showValidationMessage(err.message);
                            }
                        });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then(result => {
                if (result.isConfirmed) {
                    const d = result.value;
                    Swal.fire({
                        title: 'Campanha enviada!',
                        html: `<p>Total: <strong>${d.total}</strong> destinatários</p>
                               <p class="text-success">Enviados: <strong>${d.sent}</strong></p>
                               ${d.failed > 0 ? `<p class="text-danger">Falharam: <strong>${d.failed}</strong></p>` : ''}`,
                        icon: 'success'
                    }).then(() => location.reload());
                }
            });
        });
    }
});
</script>
