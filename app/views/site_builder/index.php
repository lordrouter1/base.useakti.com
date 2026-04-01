<?php
/**
 * Site Builder — Editor Visual (View principal)
 *
 * Interface split-view: editor à esquerda, preview à direita.
 * Usa SortableJS para drag & drop de seções e componentes.
 *
 * Variáveis disponíveis (vindas do controller):
 *   $pages          — lista de páginas da loja
 *   $currentPage    — página selecionada (com seções e componentes)
 *   $themeSettings  — configurações globais do tema
 *   $settingsSchema — schema de campos de configuração
 */

$currentPageId = $currentPage['id'] ?? 0;
$currentPageTitle = $currentPage['title'] ?? '';

// Tipos de seções disponíveis
$sectionTypes = [
    'hero-banner'        => ['label' => 'Banner Principal',        'icon' => 'fas fa-image'],
    'featured-products'  => ['label' => 'Produtos em Destaque',    'icon' => 'fas fa-star'],
    'image-with-text'    => ['label' => 'Imagem + Texto',          'icon' => 'fas fa-columns'],
    'newsletter'         => ['label' => 'Newsletter',              'icon' => 'fas fa-envelope'],
    'testimonials'       => ['label' => 'Depoimentos',             'icon' => 'fas fa-quote-right'],
    'gallery'            => ['label' => 'Galeria',                 'icon' => 'fas fa-images'],
    'custom-html'        => ['label' => 'HTML Customizado',        'icon' => 'fas fa-code'],
];

// Tipos de componentes disponíveis
$componentTypes = [
    'rich-text'       => ['label' => 'Texto Rico',       'icon' => 'fas fa-align-left'],
    'image'           => ['label' => 'Imagem',           'icon' => 'fas fa-image'],
    'button'          => ['label' => 'Botão',            'icon' => 'fas fa-hand-pointer'],
    'spacer'          => ['label' => 'Espaçador',        'icon' => 'fas fa-arrows-alt-v'],
    'divider'         => ['label' => 'Divisor',          'icon' => 'fas fa-minus'],
    'custom-html'     => ['label' => 'HTML Customizado', 'icon' => 'fas fa-code'],
    'product-grid'    => ['label' => 'Grid de Produtos', 'icon' => 'fas fa-th'],
    'product-carousel'=> ['label' => 'Carrossel',        'icon' => 'fas fa-film'],
];
?>

<style>
    /* ── Site Builder Layout ── */
    .sb-container {
        display: flex;
        height: calc(100vh - 120px);
        overflow: hidden;
    }

    .sb-editor {
        width: 380px;
        min-width: 380px;
        border-right: 1px solid #dee2e6;
        overflow-y: auto;
        background: #f8f9fa;
    }

    .sb-preview-wrapper {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: #e9ecef;
    }

    .sb-preview-toolbar {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 8px 16px;
        background: #fff;
        border-bottom: 1px solid #dee2e6;
    }

    .sb-preview-frame {
        flex: 1;
        display: flex;
        justify-content: center;
        padding: 16px;
        overflow: auto;
    }

    .sb-preview-frame iframe {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        transition: width 0.3s ease;
    }

    /* ── Editor Panels ── */
    .sb-panel {
        border-bottom: 1px solid #dee2e6;
    }

    .sb-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 16px;
        background: #fff;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .sb-panel-header:hover {
        background: #f0f0f0;
    }

    .sb-panel-body {
        padding: 12px 16px;
        background: #fff;
    }

    /* ── Section List ── */
    .sb-section-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        margin-bottom: 6px;
        cursor: grab;
        transition: box-shadow 0.2s ease;
    }

    .sb-section-item:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .sb-section-item.sortable-ghost {
        opacity: 0.4;
        background: #e2e8f0;
    }

    .sb-section-item .sb-section-label {
        flex: 1;
        font-size: 0.85rem;
    }

    .sb-section-item .sb-section-actions {
        display: flex;
        gap: 4px;
    }

    .sb-section-item .sb-section-actions button {
        border: none;
        background: none;
        color: #94a3b8;
        cursor: pointer;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.8rem;
    }

    .sb-section-item .sb-section-actions button:hover {
        background: #f1f5f9;
        color: #475569;
    }

    /* ── Component Palette ── */
    .sb-component-palette {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }

    .sb-component-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        padding: 12px 8px;
        border: 1px dashed #cbd5e1;
        border-radius: 8px;
        background: #fff;
        cursor: grab;
        font-size: 0.75rem;
        color: #475569;
        transition: all 0.2s ease;
    }

    .sb-component-btn:hover {
        border-color: var(--bs-primary);
        color: var(--bs-primary);
        background: #eff6ff;
    }

    .sb-component-btn i {
        font-size: 1.3rem;
    }

    /* ── Grid Editor ── */
    .sb-grid-editor {
        min-height: 80px;
        padding: 8px;
        border: 2px dashed #cbd5e1;
        border-radius: 8px;
        background: #fafafa;
    }

    .sb-grid-item {
        position: relative;
        padding: 12px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        margin-bottom: 8px;
        cursor: grab;
    }

    .sb-grid-item:hover {
        border-color: var(--bs-primary);
    }

    .sb-grid-item .sb-grid-item-actions {
        position: absolute;
        top: 4px;
        right: 4px;
        display: none;
        gap: 2px;
    }

    .sb-grid-item:hover .sb-grid-item-actions {
        display: flex;
    }

    /* ── Viewport Buttons ── */
    .sb-viewport-btn {
        padding: 4px 10px;
        border: 1px solid #dee2e6;
        background: #fff;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.9rem;
        color: #64748b;
    }

    .sb-viewport-btn.active {
        background: var(--bs-primary);
        color: #fff;
        border-color: var(--bs-primary);
    }

    /* ── Add Section ── */
    .sb-add-section {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6px;
    }

    .sb-add-section-btn {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 10px;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        background: #fff;
        cursor: pointer;
        font-size: 0.78rem;
        color: #475569;
        transition: all 0.15s ease;
    }

    .sb-add-section-btn:hover {
        border-color: var(--bs-primary);
        background: #eff6ff;
    }
</style>

<div class="container-fluid px-0">
    <!-- ── Toolbar ── -->
    <div class="d-flex align-items-center justify-content-between px-3 py-2 bg-white border-bottom">
        <div class="d-flex align-items-center gap-3">
            <h5 class="mb-0">
                <i class="fas fa-paint-brush me-2 text-primary"></i>Site Builder
            </h5>
            <span class="text-muted">—</span>
            <select id="sbPageSelect" class="form-select form-select-sm" style="width: 200px;">
                <?php foreach ($pages as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" <?= (int) $p['id'] === $currentPageId ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['title']) ?>
                    </option>
                <?php endforeach; ?>
                <?php if (empty($pages)): ?>
                    <option value="0">Nenhuma página</option>
                <?php endif; ?>
            </select>
            <button class="btn btn-sm btn-outline-primary" id="sbAddPageBtn" title="Nova Página">
                <i class="fas fa-plus me-1"></i>Página
            </button>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm btn-success" id="sbSaveBtn">
                <i class="fas fa-save me-1"></i>Salvar
            </button>
            <a href="?page=site_builder&action=preview&page_id=<?= $currentPageId ?>"
               class="btn btn-sm btn-outline-secondary" target="_blank" title="Ver loja">
                <i class="fas fa-external-link-alt me-1"></i>Ver Loja
            </a>
        </div>
    </div>

    <!-- ── Split View: Editor + Preview ── -->
    <div class="sb-container">

        <!-- ═══ EDITOR (esquerda) ═══ -->
        <div class="sb-editor">

            <!-- Painel: Configurações de Tema -->
            <div class="sb-panel">
                <div class="sb-panel-header" data-bs-toggle="collapse" data-bs-target="#sbThemePanel">
                    <span><i class="fas fa-palette me-2"></i>Configurações do Tema</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="collapse" id="sbThemePanel">
                    <div class="sb-panel-body">
                        <!-- Header -->
                        <h6 class="small fw-bold text-muted mb-2">Cabeçalho</h6>
                        <div class="mb-2">
                            <label class="form-label small">Estilo</label>
                            <select class="form-select form-select-sm sb-theme-field" data-key="header_style" data-group="header">
                                <option value="default" <?= ($themeSettings['header_style'] ?? '') === 'default' ? 'selected' : '' ?>>Padrão</option>
                                <option value="centered" <?= ($themeSettings['header_style'] ?? '') === 'centered' ? 'selected' : '' ?>>Centralizado</option>
                                <option value="minimal" <?= ($themeSettings['header_style'] ?? '') === 'minimal' ? 'selected' : '' ?>>Minimalista</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Cor de Fundo</label>
                            <input type="color" class="form-control form-control-sm form-control-color sb-theme-field"
                                   data-key="header_bg_color" data-group="header"
                                   value="<?= htmlspecialchars($themeSettings['header_bg_color'] ?? '#ffffff') ?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Cor do Texto</label>
                            <input type="color" class="form-control form-control-sm form-control-color sb-theme-field"
                                   data-key="header_text_color" data-group="header"
                                   value="<?= htmlspecialchars($themeSettings['header_text_color'] ?? '#333333') ?>">
                        </div>
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="checkbox" class="form-check-input sb-theme-field"
                                       data-key="header_sticky" data-group="header" id="sbHeaderSticky"
                                       <?= ($themeSettings['header_sticky'] ?? '1') === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="sbHeaderSticky">Fixar no topo</label>
                            </div>
                        </div>

                        <hr class="my-2">

                        <!-- Footer -->
                        <h6 class="small fw-bold text-muted mb-2">Rodapé</h6>
                        <div class="mb-2">
                            <label class="form-label small">Colunas</label>
                            <select class="form-select form-select-sm sb-theme-field" data-key="footer_columns" data-group="footer">
                                <option value="1" <?= ($themeSettings['footer_columns'] ?? '') === '1' ? 'selected' : '' ?>>1</option>
                                <option value="2" <?= ($themeSettings['footer_columns'] ?? '') === '2' ? 'selected' : '' ?>>2</option>
                                <option value="3" <?= ($themeSettings['footer_columns'] ?? '3') === '3' ? 'selected' : '' ?>>3</option>
                                <option value="4" <?= ($themeSettings['footer_columns'] ?? '') === '4' ? 'selected' : '' ?>>4</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Cor de Fundo</label>
                            <input type="color" class="form-control form-control-sm form-control-color sb-theme-field"
                                   data-key="footer_bg_color" data-group="footer"
                                   value="<?= htmlspecialchars($themeSettings['footer_bg_color'] ?? '#2c3e50') ?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Cor do Texto</label>
                            <input type="color" class="form-control form-control-sm form-control-color sb-theme-field"
                                   data-key="footer_text_color" data-group="footer"
                                   value="<?= htmlspecialchars($themeSettings['footer_text_color'] ?? '#ffffff') ?>">
                        </div>

                        <hr class="my-2">

                        <!-- Cores Gerais -->
                        <h6 class="small fw-bold text-muted mb-2">Cores</h6>
                        <div class="mb-2">
                            <label class="form-label small">Cor Primária</label>
                            <input type="color" class="form-control form-control-sm form-control-color sb-theme-field"
                                   data-key="primary_color" data-group="colors"
                                   value="<?= htmlspecialchars($themeSettings['primary_color'] ?? '#3b82f6') ?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Cor Secundária</label>
                            <input type="color" class="form-control form-control-sm form-control-color sb-theme-field"
                                   data-key="secondary_color" data-group="colors"
                                   value="<?= htmlspecialchars($themeSettings['secondary_color'] ?? '#64748b') ?>">
                        </div>

                        <button class="btn btn-sm btn-primary w-100 mt-2" id="sbSaveThemeBtn">
                            <i class="fas fa-save me-1"></i>Salvar Tema
                        </button>
                    </div>
                </div>
            </div>

            <!-- Painel: Seções da Página -->
            <div class="sb-panel">
                <div class="sb-panel-header" data-bs-toggle="collapse" data-bs-target="#sbSectionsPanel">
                    <span><i class="fas fa-layer-group me-2"></i>Seções da Página</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="collapse show" id="sbSectionsPanel">
                    <div class="sb-panel-body">
                        <div id="sbSectionList">
                            <?php if (!empty($currentPage['sections'])): ?>
                                <?php foreach ($currentPage['sections'] as $section): ?>
                                    <div class="sb-section-item" data-id="<?= (int) $section['id'] ?>" data-type="<?= htmlspecialchars($section['type']) ?>">
                                        <i class="fas fa-grip-vertical text-muted"></i>
                                        <i class="<?= $sectionTypes[$section['type']]['icon'] ?? 'fas fa-puzzle-piece' ?> text-primary"></i>
                                        <span class="sb-section-label"><?= htmlspecialchars($sectionTypes[$section['type']]['label'] ?? $section['type']) ?></span>
                                        <div class="sb-section-actions">
                                            <button class="sb-edit-section" title="Editar"><i class="fas fa-pen"></i></button>
                                            <button class="sb-delete-section" title="Excluir"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted text-center small py-3">Nenhuma seção. Adicione abaixo.</p>
                            <?php endif; ?>
                        </div>

                        <hr class="my-2">
                        <p class="small fw-bold text-muted mb-2">Adicionar Seção:</p>
                        <div class="sb-add-section">
                            <?php foreach ($sectionTypes as $sType => $sInfo): ?>
                                <button class="sb-add-section-btn" data-type="<?= $sType ?>">
                                    <i class="<?= $sInfo['icon'] ?>"></i>
                                    <?= $sInfo['label'] ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Painel: Componentes (paleta de drag) -->
            <div class="sb-panel">
                <div class="sb-panel-header" data-bs-toggle="collapse" data-bs-target="#sbComponentsPanel">
                    <span><i class="fas fa-puzzle-piece me-2"></i>Componentes</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="collapse show" id="sbComponentsPanel">
                    <div class="sb-panel-body">
                        <div class="sb-component-palette" id="sbComponentPalette">
                            <?php foreach ($componentTypes as $cType => $cInfo): ?>
                                <div class="sb-component-btn" draggable="true" data-type="<?= $cType ?>">
                                    <i class="<?= $cInfo['icon'] ?>"></i>
                                    <span><?= $cInfo['label'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /.sb-editor -->

        <!-- ═══ PREVIEW (direita) ═══ -->
        <div class="sb-preview-wrapper">
            <div class="sb-preview-toolbar">
                <button class="sb-viewport-btn" data-width="375" title="Mobile">
                    <i class="fas fa-mobile-alt"></i>
                </button>
                <button class="sb-viewport-btn" data-width="768" title="Tablet">
                    <i class="fas fa-tablet-alt"></i>
                </button>
                <button class="sb-viewport-btn active" data-width="100%" title="Desktop">
                    <i class="fas fa-desktop"></i>
                </button>
                <span class="text-muted small ms-2" id="sbViewportLabel">Desktop</span>
            </div>

            <div class="sb-preview-frame">
                <iframe id="sbPreviewFrame"
                        src="?page=site_builder&action=preview&page_id=<?= $currentPageId ?>"
                        style="width: 100%; height: 100%; border: none;"
                        title="Preview da Loja"></iframe>
            </div>
        </div><!-- /.sb-preview-wrapper -->

    </div><!-- /.sb-container -->
</div>

<!-- ── Modal: Nova Página ── -->
<div class="modal fade" id="sbNewPageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Nova Página</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Título</label>
                    <input type="text" class="form-control" id="sbNewPageTitle" placeholder="Ex: Página Inicial">
                </div>
                <div class="mb-3">
                    <label class="form-label">Slug (URL)</label>
                    <input type="text" class="form-control" id="sbNewPageSlug" placeholder="Ex: home">
                </div>
                <div class="mb-3">
                    <label class="form-label">Tipo</label>
                    <select class="form-select" id="sbNewPageType">
                        <option value="home">Página Inicial</option>
                        <option value="collection">Coleção de Produtos</option>
                        <option value="product">Produto</option>
                        <option value="cart">Carrinho</option>
                        <option value="contact">Contato</option>
                        <option value="custom">Personalizada</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="sbCreatePageBtn">
                    <i class="fas fa-plus me-1"></i>Criar Página
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    var csrf = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrf ? csrf.getAttribute('content') : '';
    var currentPageId = <?= $currentPageId ?>;
    var previewFrame = document.getElementById('sbPreviewFrame');

    // ── Helpers ──
    function postJson(action, data, callback) {
        var formData = new FormData();
        formData.append('csrf_token', csrfToken);
        for (var key in data) {
            if (data.hasOwnProperty(key)) {
                formData.append(key, typeof data[key] === 'object' ? JSON.stringify(data[key]) : data[key]);
            }
        }
        fetch('?page=site_builder&action=' + action, {
            method: 'POST',
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (callback) callback(res);
        })
        .catch(function(err) { console.error(err); });
    }

    function refreshPreview() {
        if (previewFrame && currentPageId > 0) {
            previewFrame.src = '?page=site_builder&action=preview&page_id=' + currentPageId + '&_t=' + Date.now();
        }
    }

    var previewTimeout;
    function debouncedRefresh() {
        clearTimeout(previewTimeout);
        previewTimeout = setTimeout(refreshPreview, 300);
    }

    // ── Page Select ──
    var pageSelect = document.getElementById('sbPageSelect');
    if (pageSelect) {
        pageSelect.addEventListener('change', function() {
            window.location.href = '?page=site_builder&page_id=' + this.value;
        });
    }

    // ── New Page Modal ──
    var addPageBtn = document.getElementById('sbAddPageBtn');
    if (addPageBtn) {
        addPageBtn.addEventListener('click', function() {
            var modal = new bootstrap.Modal(document.getElementById('sbNewPageModal'));
            modal.show();
        });
    }

    var createPageBtn = document.getElementById('sbCreatePageBtn');
    if (createPageBtn) {
        createPageBtn.addEventListener('click', function() {
            var title = document.getElementById('sbNewPageTitle').value.trim();
            var slug = document.getElementById('sbNewPageSlug').value.trim();
            var type = document.getElementById('sbNewPageType').value;
            if (!title || !slug) {
                Swal.fire('Atenção', 'Título e slug são obrigatórios.', 'warning');
                return;
            }
            postJson('createPage', { title: title, slug: slug, type: type }, function(res) {
                if (res.success) {
                    window.location.href = '?page=site_builder&page_id=' + res.id;
                } else {
                    Swal.fire('Erro', res.message || 'Falha ao criar página.', 'error');
                }
            });
        });
    }

    // ── Slug auto-generate from title ──
    var newPageTitle = document.getElementById('sbNewPageTitle');
    var newPageSlug = document.getElementById('sbNewPageSlug');
    if (newPageTitle && newPageSlug) {
        newPageTitle.addEventListener('input', function() {
            newPageSlug.value = this.value.toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-|-$/g, '');
        });
    }

    // ── Viewport Buttons ──
    document.querySelectorAll('.sb-viewport-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.sb-viewport-btn').forEach(function(b) { b.classList.remove('active'); });
            this.classList.add('active');
            var width = this.dataset.width;
            var label = document.getElementById('sbViewportLabel');
            if (width === '100%') {
                previewFrame.style.width = '100%';
                if (label) label.textContent = 'Desktop';
            } else {
                previewFrame.style.width = width + 'px';
                if (label) label.textContent = width + 'px';
            }
        });
    });

    // ── Add Section ──
    document.querySelectorAll('.sb-add-section-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var type = this.dataset.type;
            postJson('saveSections', {
                page_id: currentPageId,
                sections: JSON.stringify([{ type: type, settings: {}, is_visible: 1 }])
            }, function(res) {
                if (res.success) {
                    window.location.href = '?page=site_builder&page_id=' + currentPageId;
                }
            });
        });
    });

    // ── Delete Section ──
    document.querySelectorAll('.sb-delete-section').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            var item = this.closest('.sb-section-item');
            var sectionId = item.dataset.id;
            Swal.fire({
                title: 'Excluir seção?',
                text: 'Esta ação não pode ser desfeita.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Excluir',
                cancelButtonText: 'Cancelar'
            }).then(function(result) {
                if (result.isConfirmed) {
                    var formData = new FormData();
                    formData.append('csrf_token', csrfToken);
                    formData.append('id', sectionId);
                    fetch('?page=site_builder&action=deleteSection', {
                        method: 'POST',
                        body: formData
                    })
                    .then(function() {
                        item.remove();
                        debouncedRefresh();
                    });
                }
            });
        });
    });

    // ── Save Theme Settings ──
    var saveThemeBtn = document.getElementById('sbSaveThemeBtn');
    if (saveThemeBtn) {
        saveThemeBtn.addEventListener('click', function() {
            var fields = document.querySelectorAll('.sb-theme-field');
            var groups = {};
            fields.forEach(function(field) {
                var group = field.dataset.group || 'general';
                var key = field.dataset.key;
                var value;
                if (field.type === 'checkbox') {
                    value = field.checked ? '1' : '0';
                } else {
                    value = field.value;
                }
                if (!groups[group]) groups[group] = {};
                groups[group][key] = value;
            });

            var saves = Object.keys(groups).map(function(group) {
                return new Promise(function(resolve) {
                    postJson('saveThemeSettings', { settings: groups[group], group: group }, resolve);
                });
            });

            Promise.all(saves).then(function() {
                Swal.fire({ icon: 'success', title: 'Tema salvo!', timer: 1500, showConfirmButton: false });
                debouncedRefresh();
            });
        });
    }

    // ── SortableJS for sections (if available) ──
    if (typeof Sortable !== 'undefined') {
        var sectionList = document.getElementById('sbSectionList');
        if (sectionList) {
            Sortable.create(sectionList, {
                animation: 150,
                handle: '.fa-grip-vertical',
                ghostClass: 'sortable-ghost',
                onEnd: function() {
                    var order = [];
                    sectionList.querySelectorAll('.sb-section-item').forEach(function(item) {
                        order.push(item.dataset.id);
                    });
                    postJson('reorderSections', {
                        page_id: currentPageId,
                        order: order
                    }, function() {
                        debouncedRefresh();
                    });
                }
            });
        }
    }
});
</script>
