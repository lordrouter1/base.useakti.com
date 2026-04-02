<?php
/**
 * E-mail Marketing — Formulário de Template
 * FEAT-013
 * Variáveis: $template (null = novo, array = editar)
 */
$isEdit = !empty($template);
$t = $template ?? [];
$vars = [];
if ($isEdit && !empty($t['variables'])) {
    $decoded = is_string($t['variables']) ? json_decode($t['variables'], true) : $t['variables'];
    $vars = is_array($decoded) ? $decoded : [];
}
?>

<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs5.min.css" rel="stylesheet">

<div class="container-fluid py-3">

    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-file-alt me-2 text-primary"></i><?= $isEdit ? 'Editar Template' : 'Novo Template' ?></h1>
        </div>
        <a href="?page=email_marketing&action=templates" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
    </div>

    <form method="post" action="?page=email_marketing&action=<?= $isEdit ? 'updateTemplate' : 'storeTemplate' ?>">
        <?= csrf_field() ?>
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
        <?php endif; ?>

        <div class="row g-4">
            <!-- Coluna Principal -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nome do Template <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="<?= eAttr($t['name'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Assunto <span class="text-danger">*</span></label>
                            <input type="text" name="subject" class="form-control" value="<?= eAttr($t['subject'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Conteúdo HTML</label>
                            <textarea name="body_html" id="bodyHtml" class="form-control"><?= e($t['body_html'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Coluna Lateral -->
            <div class="col-lg-4">
                <!-- Variáveis -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white fw-bold">
                        <i class="fas fa-code me-1 text-primary"></i>Variáveis Disponíveis
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
                            <strong>Exemplo:</strong> "Olá <code>{{nome}}</code>, temos uma novidade para você!"<br>
                            Resultado: "Olá <strong>João Silva</strong>, temos uma novidade para você!"
                        </small>
                    </div>
                </div>

                <!-- Variáveis customizadas do template -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white fw-bold">
                        <i class="fas fa-tags me-1 text-info"></i>Variáveis do Template
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-2">Separe por vírgula. Ex: <code>desconto, produto, link</code></p>
                        <input type="text" name="variables" class="form-control form-control-sm" placeholder="desconto, produto, link" value="<?= eAttr(implode(', ', $vars)) ?>">
                    </div>
                </div>

                <!-- Ações -->
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Salvar Template</button>
                    <a href="?page=email_marketing&action=templates" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function loadScript(src, callback) {
        const s = document.createElement('script');
        s.src = src;
        s.onload = callback;
        document.body.appendChild(s);
    }

    loadScript('https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs5.min.js', function() {
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
                placeholder: 'Escreva o conteúdo do template aqui...'
            });

            // Variáveis clicáveis — inserir no Summernote
            document.querySelectorAll('.var-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const tag = this.dataset.var;
                    $('#bodyHtml').summernote('editor.insertText', tag);
                });
            });
        });
    });
});
</script>
