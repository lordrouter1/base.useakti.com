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

$currentPageId = (int) ($currentPage['id'] ?? 0);
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

$themeFontOptions = [
    'Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Poppins',
    'Raleway', 'Nunito', 'Ubuntu', 'Playfair Display', 'Merriweather',
    'Source Sans Pro', 'PT Sans', 'Oswald', 'Quicksand',
];

$renderedThemeSettingIds = [
    'header_style',
    'header_bg_color',
    'header_text_color',
    'header_sticky',
    'footer_columns',
    'footer_bg_color',
    'footer_text_color',
    'primary_color',
    'secondary_color',
];

$dynamicThemeGroups = [];
foreach ($settingsSchema as $schemaGroup) {
    $groupName = $schemaGroup['name'] ?? 'Geral';
    $groupKey = strtolower((string) preg_replace('/[^a-z0-9]+/i', '_', $groupName));

    foreach (($schemaGroup['settings'] ?? []) as $setting) {
        $settingId = $setting['id'] ?? '';
        $settingType = $setting['type'] ?? 'text';
        if ($settingId === '' || in_array($settingId, $renderedThemeSettingIds, true)) {
            continue;
        }

        if (!in_array($settingType, ['color', 'select', 'checkbox', 'font_picker'], true)) {
            continue;
        }

        if (!isset($dynamicThemeGroups[$groupName])) {
            $dynamicThemeGroups[$groupName] = [
                'group_key' => $groupKey,
                'settings' => [],
            ];
        }

        $dynamicThemeGroups[$groupName]['settings'][] = $setting;
    }
}
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

    /* ── Selected Section ── */
    .sb-section-item.sb-selected {
        border-color: var(--bs-primary);
        background: #eff6ff;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
    }

    .sb-page-dropdown { width: 340px; padding: 0; border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,.12); overflow: hidden; }
    .sb-page-dropdown .sb-dd-search { padding: 10px 12px; border-bottom: 1px solid #e2e8f0; }
    .sb-page-dropdown .sb-dd-search input { font-size: .85rem; }
    .sb-page-dropdown .sb-dd-list { max-height: 260px; overflow-y: auto; padding: 6px 0; }
    .sb-page-dropdown .sb-dd-item { display: flex; align-items: center; gap: 8px; padding: 7px 12px; font-size: .85rem; color: #334155; text-decoration: none; white-space: nowrap; }
    .sb-page-dropdown .sb-dd-item:hover { background: #f1f5f9; }
    .sb-page-dropdown .sb-dd-item.active { background: #eff6ff; font-weight: 600; color: var(--bs-primary); }
    .sb-page-dropdown .sb-dd-item .sb-dd-label { flex: 1; overflow: hidden; text-overflow: ellipsis; }
    .sb-page-dropdown .sb-dd-item .sb-dd-slug { color: #94a3b8; font-size: .75rem; margin-left: 4px; }
    .sb-page-dropdown .sb-dd-item .sb-dd-del { color: #94a3b8; padding: 2px 5px; border-radius: 4px; border: none; background: none; font-size: .78rem; line-height: 1; }
    .sb-page-dropdown .sb-dd-item .sb-dd-del:hover { color: #dc3545; background: #fee2e2; }
    .sb-page-dropdown .sb-dd-footer { border-top: 1px solid #e2e8f0; padding: 8px 12px; }
    .sb-page-dropdown .sb-dd-empty { padding: 20px 12px; text-align: center; color: #94a3b8; font-size: .85rem; }
</style>

<div class="container-fluid px-0">
    <!-- ── Toolbar ── -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 px-3 py-2 bg-white border-bottom">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <h5 class="mb-0">
                <i class="fas fa-paint-brush me-2 text-primary"></i>Site Builder
            </h5>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="sbPageDropdownBtn" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                    <i class="fas fa-file-alt me-1"></i><?= htmlspecialchars($currentPageTitle !== '' ? $currentPageTitle : 'Selecionar página') ?>
                </button>
                <div class="dropdown-menu sb-page-dropdown" aria-labelledby="sbPageDropdownBtn">
                    <div class="sb-dd-search">
                        <input type="search" class="form-control form-control-sm" id="sbPageSearchInput" placeholder="Buscar página..." autocomplete="off">
                    </div>
                    <div class="sb-dd-list" id="sbPageDdList">
                        <?php if (empty($pages)): ?>
                            <div class="sb-dd-empty">Nenhuma página criada.</div>
                        <?php else: ?>
                            <?php foreach ($pages as $p): ?>
                                <?php $pid = (int) $p['id']; ?>
                                <a href="?page=site_builder&action=index&page_id=<?= $pid ?>"
                                   class="sb-dd-item<?= $pid === $currentPageId ? ' active' : '' ?>"
                                   data-page-id="<?= $pid ?>"
                                   data-search="<?= htmlspecialchars(strtolower(($p['title'] ?? '') . ' ' . ($p['slug'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">
                                    <span class="sb-dd-label"><?= htmlspecialchars($p['title'] ?? 'Sem título') ?></span>
                                    <span class="sb-dd-slug">/<?= htmlspecialchars($p['slug'] ?? '') ?></span>
                                    <button type="button" class="sb-dd-del" title="Excluir"
                                            data-page-id="<?= $pid ?>"
                                            data-page-title="<?= htmlspecialchars($p['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            data-is-current="<?= $pid === $currentPageId ? '1' : '0' ?>">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="sb-dd-footer">
                        <button type="button" class="btn btn-sm btn-primary w-100" id="sbAddPageBtn">
                            <i class="fas fa-plus me-1"></i>Nova Página
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
            <span id="sbSaveStatus" class="small text-muted" style="display:none;"></span>
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

                        <?php if (!empty($dynamicThemeGroups)): ?>
                            <hr class="my-2">
                            <h6 class="small fw-bold text-muted mb-2">Configurações Avançadas</h6>
                            <?php foreach ($dynamicThemeGroups as $groupLabel => $dynamicGroup): ?>
                                <p class="small fw-bold mb-2 mt-3"><?= htmlspecialchars($groupLabel) ?></p>
                                <?php foreach ($dynamicGroup['settings'] as $setting): ?>
                                    <?php
                                    $settingId = $setting['id'];
                                    $settingType = $setting['type'];
                                    $groupKey = $dynamicGroup['group_key'];
                                    $settingValue = $themeSettings[$settingId] ?? $setting['default'] ?? '';
                                    ?>
                                    <?php if ($settingType === 'color'): ?>
                                        <div class="mb-2">
                                            <label class="form-label small"><?= htmlspecialchars($setting['label'] ?? $settingId) ?></label>
                                            <input type="color"
                                                   class="form-control form-control-sm form-control-color sb-theme-field"
                                                   data-key="<?= htmlspecialchars($settingId) ?>"
                                                   data-group="<?= htmlspecialchars($groupKey) ?>"
                                                   value="<?= htmlspecialchars((string) $settingValue ?: '#000000') ?>">
                                        </div>
                                    <?php elseif ($settingType === 'checkbox'): ?>
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input type="checkbox"
                                                       class="form-check-input sb-theme-field"
                                                       data-key="<?= htmlspecialchars($settingId) ?>"
                                                       data-group="<?= htmlspecialchars($groupKey) ?>"
                                                       id="theme_<?= htmlspecialchars($settingId) ?>"
                                                       <?= in_array((string) $settingValue, ['1', 'true', 'on'], true) || $settingValue === true ? 'checked' : '' ?>>
                                                <label class="form-check-label small" for="theme_<?= htmlspecialchars($settingId) ?>">
                                                    <?= htmlspecialchars($setting['label'] ?? $settingId) ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php elseif ($settingType === 'select'): ?>
                                        <div class="mb-2">
                                            <label class="form-label small"><?= htmlspecialchars($setting['label'] ?? $settingId) ?></label>
                                            <select class="form-select form-select-sm sb-theme-field"
                                                    data-key="<?= htmlspecialchars($settingId) ?>"
                                                    data-group="<?= htmlspecialchars($groupKey) ?>">
                                                <?php foreach (($setting['options'] ?? []) as $option): ?>
                                                    <option value="<?= htmlspecialchars((string) ($option['value'] ?? '')) ?>"
                                                        <?= (string) ($settingValue ?? '') === (string) ($option['value'] ?? '') ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars((string) ($option['label'] ?? $option['value'] ?? '')) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php elseif ($settingType === 'font_picker'): ?>
                                        <div class="mb-2">
                                            <label class="form-label small"><?= htmlspecialchars($setting['label'] ?? $settingId) ?></label>
                                            <select class="form-select form-select-sm sb-theme-field"
                                                    data-key="<?= htmlspecialchars($settingId) ?>"
                                                    data-group="<?= htmlspecialchars($groupKey) ?>">
                                                <?php foreach ($themeFontOptions as $fontOption): ?>
                                                    <option value="<?= htmlspecialchars($fontOption) ?>"
                                                        <?= (string) ($settingValue ?? '') === (string) $fontOption ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($fontOption) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>

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
                                    <div class="sb-section-item<?= empty($section['is_visible']) ? ' opacity-50' : '' ?>"
                                         data-id="<?= (int) $section['id'] ?>"
                                         data-type="<?= htmlspecialchars($section['type']) ?>"
                                         data-visible="<?= (int) ($section['is_visible'] ?? 1) ?>">
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
                        sandbox="allow-same-origin"
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

<!-- ── Modal: Editar Seção + Componentes ── -->
<div class="modal fade" id="sbEditSectionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-layer-group me-2"></i>Editar Seção</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="sbEditSectionId">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Tipo da Seção</label>
                    <input type="text" class="form-control form-control-sm" id="sbEditSectionType" disabled>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold mb-0"><i class="fas fa-sliders-h me-2"></i>Configurações da Seção</h6>
                    <div class="form-check form-switch m-0">
                        <input type="checkbox" class="form-check-input" id="sbEditSectionVisible">
                        <label class="form-check-label small" for="sbEditSectionVisible">Visível no preview</label>
                    </div>
                </div>
                <div id="sbSectionSettingsFields" class="row g-2 mb-3">
                    <div class="col-12">
                        <p class="text-muted small mb-0">Selecione uma seção para carregar as configurações.</p>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-success mb-3" id="sbSaveSectionSettingsBtn">
                    <i class="fas fa-save me-1"></i>Salvar Configurações da Seção
                </button>

                <hr>
                <h6 class="fw-bold"><i class="fas fa-puzzle-piece me-2"></i>Componentes da Seção</h6>
                <div id="sbSectionComponentsList" class="mb-3" style="min-height:60px;">
                    <p class="text-muted text-center small py-3">Carregando...</p>
                </div>

                <!-- Adicionar componente -->
                <div class="card border-dashed" style="border-style:dashed;">
                    <div class="card-body p-2">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Tipo</label>
                                <select class="form-select form-select-sm" id="sbAddCompType">
                                    <?php foreach ($componentTypes as $cType => $cInfo): ?>
                                    <option value="<?= $cType ?>"><?= $cInfo['label'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Colunas (1-12)</label>
                                <input type="number" class="form-control form-control-sm" id="sbAddCompCol" value="12" min="1" max="12">
                            </div>
                            <div class="col-md-5">
                                <button type="button" class="btn btn-sm btn-primary w-100" id="sbAddCompBtn">
                                    <i class="fas fa-plus me-1"></i>Adicionar Componente
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    var csrf = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrf ? csrf.getAttribute('content') : '';
    var currentPageId = <?= (int) $currentPageId ?>;
    if (!currentPageId) {
        var firstPageLink = document.querySelector('.sb-dd-item[data-page-id]');
        if (firstPageLink) {
            currentPageId = parseInt(firstPageLink.dataset.pageId, 10) || 0;
        }
    }
    var previewFrame = document.getElementById('sbPreviewFrame');
    var hasUnsavedChanges = false;
    var saveStatusEl = document.getElementById('sbSaveStatus');
    var selectedSectionId = null;
    var sectionFieldMap = {
        'hero-banner': [
            { key: 'title', label: 'Título', type: 'text', placeholder: 'Bem-vindo' },
            { key: 'subtitle', label: 'Subtítulo', type: 'textarea', placeholder: 'Descreva a seção' },
            { key: 'cta_text', label: 'Texto do botão', type: 'text', placeholder: 'Comprar agora' },
            { key: 'cta_url', label: 'URL do botão', type: 'text', placeholder: '#' },
            { key: 'min_height', label: 'Altura mínima', type: 'text', placeholder: '400px' }
        ],
        'featured-products': [
            { key: 'title', label: 'Título', type: 'text', placeholder: 'Produtos em Destaque' },
            { key: 'columns', label: 'Colunas', type: 'number', min: 1, max: 4, placeholder: '3' }
        ],
        'image-with-text': [
            { key: 'title', label: 'Título', type: 'text', placeholder: 'Sobre nós' },
            { key: 'text', label: 'Texto', type: 'textarea', placeholder: 'Conteúdo da seção' }
        ],
        'newsletter': [
            { key: 'title', label: 'Título', type: 'text', placeholder: 'Fique por dentro' },
            { key: 'description', label: 'Descrição', type: 'textarea', placeholder: 'Receba novidades e promoções.' },
            { key: 'button_text', label: 'Texto do botão', type: 'text', placeholder: 'Inscrever' }
        ],
        'testimonials': [
            { key: 'title', label: 'Título', type: 'text', placeholder: 'Depoimentos' }
        ],
        'gallery': [
            { key: 'title', label: 'Título', type: 'text', placeholder: 'Galeria' },
            { key: 'columns', label: 'Colunas', type: 'number', min: 2, max: 4, placeholder: '3' }
        ],
        'custom-html': [
            { key: 'content', label: 'HTML', type: 'textarea', rows: 8, placeholder: '<p>Conteúdo HTML customizado</p>' }
        ]
    };

    function markDirty() {
        hasUnsavedChanges = true;
        if (saveStatusEl) {
            saveStatusEl.style.display = '';
            saveStatusEl.className = 'small text-warning fw-bold';
            saveStatusEl.innerHTML = '<i class="fas fa-circle me-1" style="font-size:0.5rem;"></i>Altera\u00e7\u00f5es n\u00e3o salvas';
        }
    }

    function markClean() {
        hasUnsavedChanges = false;
        if (saveStatusEl) {
            saveStatusEl.style.display = '';
            saveStatusEl.className = 'small text-success fw-bold';
            saveStatusEl.innerHTML = '<i class="fas fa-check-circle me-1"></i>Salvo';
            setTimeout(function() {
                if (!hasUnsavedChanges && saveStatusEl) {
                    saveStatusEl.style.display = 'none';
                }
            }, 3000);
        }
    }

    window.addEventListener('beforeunload', function(e) {
        if (hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

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
            headers: { 'Accept': 'application/json' },
            body: formData
        })
        .then(function(r) {
            return r.json().then(function(json) {
                return json;
            }, function() {
                throw new Error('HTTP ' + r.status + ' — resposta inválida do servidor');
            });
        })
        .then(function(res) {
            if (callback) callback(res);
        })
        .catch(function(err) {
            console.error('Site Builder AJAX error:', err);
            if (typeof Swal !== 'undefined') {
                Swal.fire('Erro', err.message || 'Falha na comunicação com o servidor.', 'error');
            }
        });
    }

    function getJson(action, params, callback) {
        var query = '?page=site_builder&action=' + action;
        for (var key in params) {
            if (params.hasOwnProperty(key)) {
                query += '&' + encodeURIComponent(key) + '=' + encodeURIComponent(params[key]);
            }
        }
        fetch(query, { headers: { 'Accept': 'application/json' } })
            .then(function(r) {
                return r.json().then(function(json) { return json; }, function() {
                    throw new Error('HTTP ' + r.status);
                });
            })
            .then(function(res) { if (callback) callback(res); })
            .catch(function(err) {
                console.error('Site Builder GET error:', err);
                if (callback) callback({ success: false, message: err.message });
            });
    }

    function refreshPreview() {
        if (previewFrame && currentPageId > 0) {
            previewFrame.src = '?page=site_builder&action=preview&page_id=' + currentPageId + '&_t=' + Date.now();
        }
    }

    function escapeHtml(value) {
        return String(value === undefined || value === null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function renderSectionSettingsFields(section) {
        var container = document.getElementById('sbSectionSettingsFields');
        var visibilityField = document.getElementById('sbEditSectionVisible');
        if (!container || !visibilityField) {
            return;
        }

        var settings = section && section.settings ? section.settings : {};
        var fields = sectionFieldMap[section.type] || [];
        visibilityField.checked = String(section.is_visible || '1') !== '0';

        if (!fields.length) {
            container.innerHTML = '<div class="col-12"><p class="text-muted small mb-0">Esta seção ainda não possui campos configuráveis no editor.</p></div>';
            return;
        }

        var html = '';
        fields.forEach(function(field) {
            var value = settings[field.key];
            if (value === undefined || value === null || value === '') {
                value = field.placeholder || '';
            }

            var columnClass = field.type === 'textarea' ? 'col-12' : 'col-md-6';
            html += '<div class="' + columnClass + '">';
            html += '<label class="form-label small fw-bold">' + escapeHtml(field.label) + '</label>';
            if (field.type === 'textarea') {
                html += '<textarea class="form-control form-control-sm sb-section-setting" data-key="' + escapeHtml(field.key) + '" rows="' + escapeHtml(field.rows || 4) + '" placeholder="' + escapeHtml(field.placeholder || '') + '">' + escapeHtml(value) + '</textarea>';
            } else {
                html += '<input type="' + escapeHtml(field.type) + '" class="form-control form-control-sm sb-section-setting" data-key="' + escapeHtml(field.key) + '" value="' + escapeHtml(value) + '" placeholder="' + escapeHtml(field.placeholder || '') + '"';
                if (field.min !== undefined) {
                    html += ' min="' + escapeHtml(field.min) + '"';
                }
                if (field.max !== undefined) {
                    html += ' max="' + escapeHtml(field.max) + '"';
                }
                html += '>';
            }
            html += '</div>';
        });

        container.innerHTML = html;
    }

    function collectSectionSettings() {
        var settings = {};
        document.querySelectorAll('.sb-section-setting').forEach(function(field) {
            settings[field.dataset.key] = field.value;
        });
        return settings;
    }

    var previewTimeout;
    function debouncedRefresh() {
        clearTimeout(previewTimeout);
        previewTimeout = setTimeout(refreshPreview, 300);
    }

    // ── Page Dropdown: search ──
    var ddSearch = document.getElementById('sbPageSearchInput');
    var ddList = document.getElementById('sbPageDdList');
    if (ddSearch && ddList) {
        ddSearch.addEventListener('input', function() {
            var term = this.value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            var items = ddList.querySelectorAll('.sb-dd-item');
            var found = false;
            items.forEach(function(el) {
                var match = term === '' || (el.dataset.search || '').indexOf(term) !== -1;
                el.style.display = match ? '' : 'none';
                if (match) found = true;
            });
            var empty = document.getElementById('sbDdEmptyMsg');
            if (!found) {
                if (!empty) {
                    var d = document.createElement('div');
                    d.className = 'sb-dd-empty';
                    d.id = 'sbDdEmptyMsg';
                    d.textContent = 'Nenhuma página encontrada.';
                    ddList.appendChild(d);
                }
            } else if (empty) {
                empty.remove();
            }
        });
    }

    // ── Page Dropdown: focus search on open ──
    var ddBtn = document.getElementById('sbPageDropdownBtn');
    if (ddBtn) {
        ddBtn.addEventListener('shown.bs.dropdown', function() {
            if (ddSearch) { ddSearch.value = ''; ddSearch.dispatchEvent(new Event('input')); ddSearch.focus(); }
        });
    }

    // ── Page Dropdown: delete buttons ──
    document.querySelectorAll('.sb-dd-del').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var pageId = this.dataset.pageId;
            var pageTitle = this.dataset.pageTitle || 'Página';
            var isCurrent = this.dataset.isCurrent === '1';
            var itemEl = this.closest('.sb-dd-item');

            Swal.fire({
                title: 'Excluir página?',
                html: 'A página <strong>' + escapeHtml(pageTitle) + '</strong> e todo seu conteúdo serão removidos.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Sim, excluir',
                cancelButtonText: 'Cancelar'
            }).then(function(result) {
                if (!result.isConfirmed) return;
                postJson('deletePage', { id: pageId }, function(res) {
                    if (!(res && res.success)) {
                        Swal.fire('Erro', (res && res.message) || 'Falha ao excluir.', 'error');
                        return;
                    }
                    if (itemEl) itemEl.remove();
                    if (isCurrent) {
                        var next = ddList ? ddList.querySelector('.sb-dd-item') : null;
                        var url = next ? next.getAttribute('href') : '?page=site_builder&action=index';
                        Swal.fire({ icon: 'success', title: 'Excluída!', timer: 1000, showConfirmButton: false });
                        setTimeout(function() { window.location.href = url; }, 1100);
                    } else {
                        Swal.fire({ icon: 'success', title: 'Excluída!', timer: 1000, showConfirmButton: false });
                    }
                });
            });
        });
    });

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
            var btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Criando...';
            postJson('createPage', { title: title, slug: slug, type: type }, function(res) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-plus me-1"></i>Criar Página';
                if (res && res.success) {
                    window.location.href = '?page=site_builder&action=index&page_id=' + res.id;
                } else {
                    Swal.fire('Erro', (res && res.message) || 'Falha ao criar página.', 'error');
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
            if (!currentPageId) {
                Swal.fire('Atenção', 'Crie uma página antes de adicionar seções.', 'warning');
                return;
            }
            var type = this.dataset.type;
            postJson('addSection', {
                page_id: currentPageId,
                type: type,
                settings: JSON.stringify({})
            }, function(res) {
                if (res && res.success) {
                    hasUnsavedChanges = false;
                    window.location.href = '?page=site_builder&action=index&page_id=' + currentPageId;
                } else {
                    Swal.fire('Erro', (res && res.message) || 'Falha ao adicionar seção.', 'error');
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
                    postJson('deleteSection', { id: sectionId }, function(res) {
                        if (!res || !res.success) {
                            Swal.fire('Erro', (res && res.message) ? res.message : 'Falha ao excluir seção.', 'error');
                            return;
                        }

                        item.remove();
                        markClean();
                        debouncedRefresh();
                    });
                }
            });
        });
    });

    // ── Save Button (persist section order) ──
    var saveBtn = document.getElementById('sbSaveBtn');
    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            if (!currentPageId) {
                Swal.fire('Atenção', 'Crie uma página primeiro para poder salvar.', 'info');
                return;
            }

            var sectionList = document.getElementById('sbSectionList');
            var order = [];
            if (sectionList) {
                sectionList.querySelectorAll('.sb-section-item').forEach(function(item) {
                    order.push(parseInt(item.dataset.id));
                });
            }

            if (order.length === 0) {
                markClean();
                Swal.fire({ icon: 'info', title: 'Nenhuma seção para salvar.', timer: 1500, showConfirmButton: false });
                return;
            }

            var btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Salvando...';

            postJson('reorderSections', { page_id: currentPageId, order: order }, function(res) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save me-1"></i>Salvar';
                if (res && res.success) {
                    markClean();
                    Swal.fire({ icon: 'success', title: 'Alterações salvas!', timer: 1200, showConfirmButton: false });
                    debouncedRefresh();
                } else {
                    Swal.fire('Erro', (res && res.message) || 'Falha ao salvar.', 'error');
                }
            });
        });
    }

    // ── Track theme field changes + live preview ──
    function applyThemePreview() {
        try {
            var doc = previewFrame.contentDocument || previewFrame.contentWindow.document;
            if (!doc) return;
            document.querySelectorAll('.sb-theme-field').forEach(function(f) {
                var key = f.dataset.key;
                var value = f.type === 'checkbox' ? (f.checked ? '1' : '0') : f.value;
                if (key === 'primary_color') {
                    doc.documentElement.style.setProperty('--primary-color', value);
                } else if (key === 'secondary_color') {
                    doc.documentElement.style.setProperty('--secondary-color', value);
                } else if (key === 'accent_color') {
                    doc.documentElement.style.setProperty('--accent-color', value);
                } else if (key === 'header_bg_color') {
                    var hdr = doc.querySelector('.store-header');
                    if (hdr) hdr.style.backgroundColor = value;
                } else if (key === 'header_text_color') {
                    var hdr2 = doc.querySelector('.store-header');
                    if (hdr2) { hdr2.style.color = value; hdr2.querySelectorAll('a,.navbar-brand').forEach(function(el) { el.style.color = value; }); }
                } else if (key === 'footer_bg_color') {
                    var ftr = doc.querySelector('footer');
                    if (ftr) ftr.style.backgroundColor = value;
                } else if (key === 'footer_text_color') {
                    var ftr2 = doc.querySelector('footer');
                    if (ftr2) { ftr2.style.color = value; ftr2.querySelectorAll('a,h5').forEach(function(el) { el.style.color = value; }); }
                } else if (key === 'body_font') {
                    doc.documentElement.style.setProperty('--body-font', '"' + value + '", sans-serif');
                } else if (key === 'heading_font') {
                    doc.documentElement.style.setProperty('--heading-font', '"' + value + '", sans-serif');
                }
            });
        } catch (e) { /* iframe not ready or cross-origin */ }
    }

    document.querySelectorAll('.sb-theme-field').forEach(function(field) {
        field.addEventListener('change', function() { markDirty(); applyThemePreview(); });
        field.addEventListener('input', function() { markDirty(); applyThemePreview(); });
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

            var btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Salvando...';

            var groupKeys = Object.keys(groups);
            var completed = 0;
            var hasError = false;

            groupKeys.forEach(function(group) {
                postJson('saveThemeSettings', { settings: groups[group], group: group }, function(res) {
                    completed++;
                    if (!res || !res.success) hasError = true;
                    if (completed === groupKeys.length) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-save me-1"></i>Salvar Tema';
                        if (hasError) {
                            Swal.fire('Erro', 'Algumas configurações não foram salvas.', 'error');
                        } else {
                            markClean();
                            Swal.fire({ icon: 'success', title: 'Tema salvo!', timer: 1500, showConfirmButton: false });
                            refreshPreview();
                        }
                    }
                });
            });
        });
    }

    // ── Section Selection ──
    function selectSection(id) {
        selectedSectionId = id;
        document.querySelectorAll('.sb-section-item').forEach(function(item) {
            item.classList.toggle('sb-selected', String(item.dataset.id) === String(id));
        });
    }

    document.querySelectorAll('.sb-section-item').forEach(function(item) {
        item.addEventListener('click', function(e) {
            if (e.target.closest('.sb-section-actions')) return;
            selectSection(this.dataset.id);
        });
    });

    // Auto-select first section
    var firstSection = document.querySelector('.sb-section-item');
    if (firstSection) selectSection(firstSection.dataset.id);

    // ── Component Palette (click to add to selected section) ──
    document.querySelectorAll('.sb-component-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!selectedSectionId) {
                Swal.fire('Atenção', 'Selecione uma seção no painel de seções primeiro.', 'info');
                return;
            }
            if (!currentPageId) {
                Swal.fire('Atenção', 'Crie uma página primeiro.', 'info');
                return;
            }
            var type = this.dataset.type;
            postJson('addComponent', {
                section_id: selectedSectionId,
                type: type,
                content: JSON.stringify({}),
                grid_col: 12
            }, function(res) {
                if (res && res.success) {
                    Swal.fire({ icon: 'success', title: 'Componente adicionado!', timer: 1000, showConfirmButton: false });
                    debouncedRefresh();
                } else {
                    Swal.fire('Erro', (res && res.message) || 'Falha ao adicionar.', 'error');
                }
            });
        });
    });

    // ── SortableJS for sections ──
    if (typeof Sortable !== 'undefined') {
        var sectionList = document.getElementById('sbSectionList');
        if (sectionList) {
            Sortable.create(sectionList, {
                animation: 150,
                handle: '.fa-grip-vertical',
                ghostClass: 'sortable-ghost',
                onEnd: function() {
                    markDirty();
                }
            });
        }
    }

    // ── Edit Section (open modal with components) ──
    var editSectionModal = null;
    document.querySelectorAll('.sb-edit-section').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            var item = this.closest('.sb-section-item');
            var sectionId = item.dataset.id;
            var sectionType = item.dataset.type;

            document.getElementById('sbEditSectionId').value = sectionId;
            document.getElementById('sbEditSectionType').value = sectionType;

            loadSectionComponents(sectionId);

            if (!editSectionModal) {
                editSectionModal = new bootstrap.Modal(document.getElementById('sbEditSectionModal'));
            }
            editSectionModal.show();
        });
    });

    function loadSectionComponents(sectionId) {
        var container = document.getElementById('sbSectionComponentsList');
        container.innerHTML = '<p class="text-muted text-center small py-2"><i class="fas fa-spinner fa-spin me-1"></i>Carregando...</p>';

        getJson('getPageData', { page_id: currentPageId }, function(data) {
            if (!data || !data.success || !data.page || !data.page.sections) {
                container.innerHTML = '<p class="text-muted text-center small py-3">Erro ao carregar dados.</p>';
                renderSectionSettingsFields({ type: document.getElementById('sbEditSectionType').value || '', settings: {}, is_visible: '1' });
                return;
            }

            var section = null;
            for (var i = 0; i < data.page.sections.length; i++) {
                if (String(data.page.sections[i].id) === String(sectionId)) {
                    section = data.page.sections[i];
                    break;
                }
            }

            if (!section || !section.components || section.components.length === 0) {
                renderSectionSettingsFields(section || { type: document.getElementById('sbEditSectionType').value || '', settings: {}, is_visible: '1' });
                container.innerHTML = '<p class="text-muted text-center small py-3"><i class="fas fa-inbox me-1"></i>Nenhum componente. Adicione abaixo.</p>';
                return;
            }

            renderSectionSettingsFields(section);

            var html = '<div class="list-group list-group-flush">';
            section.components.forEach(function(comp) {
                html += '<div class="list-group-item d-flex align-items-center justify-content-between py-2" data-comp-id="' + comp.id + '">';
                html += '<div><i class="fas fa-puzzle-piece text-primary me-2"></i>';
                html += '<span class="small fw-bold">' + (comp.type || 'unknown') + '</span>';
                html += '<span class="badge bg-light text-muted ms-2" style="font-size:0.65rem;">col-' + (comp.grid_col || 12) + '</span>';
                html += '</div>';
                html += '<button type="button" class="btn btn-sm btn-outline-danger sb-remove-comp-btn" data-id="' + comp.id + '" title="Remover">';
                html += '<i class="fas fa-trash-alt"></i></button>';
                html += '</div>';
            });
            html += '</div>';
            container.innerHTML = html;

            // Bind remove buttons
            container.querySelectorAll('.sb-remove-comp-btn').forEach(function(rbtn) {
                rbtn.addEventListener('click', function() {
                    var compId = this.dataset.id;
                    Swal.fire({
                        title: 'Remover componente?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Remover',
                        cancelButtonText: 'Cancelar'
                    }).then(function(r) {
                        if (r.isConfirmed) {
                            postJson('removeComponent', { id: compId }, function(res) {
                                if (res && res.success) {
                                    loadSectionComponents(sectionId);
                                    debouncedRefresh();
                                } else {
                                    Swal.fire('Erro', (res && res.message) || 'Falha ao remover.', 'error');
                                }
                            });
                        }
                    });
                });
            });
        });
    }

    // ── Save Section Settings ──
    var saveSectionSettingsBtn = document.getElementById('sbSaveSectionSettingsBtn');
    if (saveSectionSettingsBtn) {
        saveSectionSettingsBtn.addEventListener('click', function() {
            var sectionId = document.getElementById('sbEditSectionId').value;
            var sectionType = document.getElementById('sbEditSectionType').value;
            var isVisible = document.getElementById('sbEditSectionVisible').checked ? 1 : 0;

            if (!sectionId || !sectionType) {
                Swal.fire('Erro', 'Seção inválida.', 'error');
                return;
            }

            var btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Salvando...';

            postJson('updateSection', {
                id: sectionId,
                type: sectionType,
                is_visible: isVisible,
                settings: collectSectionSettings()
            }, function(res) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save me-1"></i>Salvar Configurações da Seção';

                if (!res || !res.success) {
                    Swal.fire('Erro', (res && res.message) || 'Falha ao salvar configurações da seção.', 'error');
                    return;
                }

                var sectionItem = document.querySelector('.sb-section-item[data-id="' + sectionId + '"]');
                if (sectionItem) {
                    sectionItem.classList.toggle('opacity-50', !isVisible);
                    sectionItem.dataset.visible = String(isVisible);
                }

                markClean();
                refreshPreview();
                loadSectionComponents(sectionId);
                Swal.fire({ icon: 'success', title: 'Seção atualizada!', timer: 1000, showConfirmButton: false });
            });
        });
    }

    // ── Add Component Button ──
    var addCompBtn = document.getElementById('sbAddCompBtn');
    if (addCompBtn) {
        addCompBtn.addEventListener('click', function() {
            var sectionId = document.getElementById('sbEditSectionId').value;
            var compType = document.getElementById('sbAddCompType').value;
            var gridCol = parseInt(document.getElementById('sbAddCompCol').value) || 12;

            if (!sectionId) return;

            var btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Adicionando...';

            postJson('addComponent', {
                section_id: sectionId,
                type: compType,
                content: JSON.stringify({}),
                grid_col: gridCol
            }, function(res) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-plus me-1"></i>Adicionar Componente';
                if (res && res.success) {
                    loadSectionComponents(sectionId);
                    debouncedRefresh();
                } else {
                    Swal.fire('Erro', (res && res.message) || 'Falha ao adicionar componente.', 'error');
                }
            });
        });
    }
});
</script>
