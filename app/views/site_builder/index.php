<?php
/**
 * Site Builder — Editor Minimalista de Configurações da Loja
 *
 * Formulário full-width sem preview iframe.
 * Upload de imagens via drag-and-drop.
 * Paletas de cores predefinidas.
 *
 * Variáveis (vindas do controller):
 *   $settings       — configurações salvas (key => value)
 *   $settingsSchema — schema JSON dos campos
 */

$themeFontOptions = [
    'Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Poppins',
    'Raleway', 'Nunito', 'Ubuntu', 'Playfair Display', 'Merriweather',
    'Source Sans Pro', 'PT Sans', 'Oswald', 'Quicksand',
];

$csrfToken = $_SESSION['csrf_token'] ?? '';
?>

<style>
/* ─── Layout ───────────────────────────────────────────────── */
.sb-wrap { max-width: 720px; margin: 0 auto; padding: 1.5rem 1rem 3rem; }
.sb-top-bar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; }
.sb-top-bar h4 { margin: 0; font-weight: 700; }
.sb-top-actions { display: flex; gap: 0.5rem; align-items: center; }

/* ─── Groups ───────────────────────────────────────────────── */
.sb-group { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; margin-bottom: 1rem; overflow: hidden; }
.sb-group-head { padding: 0.85rem 1.1rem; display: flex; align-items: center; gap: 0.6rem; cursor: pointer; user-select: none; font-weight: 600; font-size: 0.92rem; transition: background 0.15s; }
.sb-group-head:hover { background: #f9fafb; }
.sb-group-head .gi { width: 20px; text-align: center; color: #9ca3af; font-size: 0.85rem; }
.sb-group-head .arrow { margin-left: auto; color: #9ca3af; transition: transform 0.2s; font-size: 0.7rem; }
.sb-group-head.closed .arrow { transform: rotate(-90deg); }
.sb-group-content { padding: 0 1.1rem 1rem; }
.sb-group-content.hide { display: none; }

/* ─── Fields ───────────────────────────────────────────────── */
.sf { margin-bottom: 0.85rem; }
.sf:last-child { margin-bottom: 0; }
.sf-label { display: block; font-size: 0.78rem; font-weight: 500; color: #6b7280; margin-bottom: 0.3rem; letter-spacing: 0.01em; }
.sf input[type="text"], .sf textarea, .sf select {
    width: 100%; padding: 0.45rem 0.65rem; border: 1px solid #d1d5db; border-radius: 6px;
    font-size: 0.85rem; background: #fff; transition: border-color 0.15s, box-shadow 0.15s;
}
.sf input[type="text"]:focus, .sf textarea:focus, .sf select:focus {
    border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); outline: none;
}
.sf textarea { resize: vertical; min-height: 56px; }

/* ─── Color ────────────────────────────────────────────────── */
.sf-color { display: flex; align-items: center; gap: 0.5rem; }
.sf-color input[type="color"] { width: 36px; height: 36px; padding: 2px; border: 1px solid #d1d5db; border-radius: 6px; cursor: pointer; }
.sf-color input[type="text"] { flex: 1; font-family: monospace; }

/* ─── Toggle ───────────────────────────────────────────────── */
.sf-toggle { display: flex; align-items: center; gap: 0.6rem; padding-top: 0.15rem; }
.sf-toggle input { display: none; }
.sf-toggle .track { width: 38px; height: 22px; background: #d1d5db; border-radius: 11px; position: relative; cursor: pointer; transition: background 0.2s; flex-shrink: 0; }
.sf-toggle .track::after { content: ''; width: 16px; height: 16px; background: #fff; border-radius: 50%; position: absolute; top: 3px; left: 3px; transition: transform 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.15); }
.sf-toggle input:checked + .track { background: #3b82f6; }
.sf-toggle input:checked + .track::after { transform: translateX(16px); }
.sf-toggle .tl { font-size: 0.82rem; color: #374151; }

/* ─── Image upload ─────────────────────────────────────────── */
.sf-img-wrap { position: relative; }
.sf-img-drop { border: 2px dashed #d1d5db; border-radius: 8px; padding: 1.2rem; text-align: center; cursor: pointer; transition: all 0.2s; background: #fafafa; }
.sf-img-drop:hover, .sf-img-drop.dragover { border-color: #3b82f6; background: #eff6ff; }
.sf-img-drop .placeholder { color: #9ca3af; font-size: 0.82rem; }
.sf-img-drop .placeholder i { font-size: 1.4rem; display: block; margin-bottom: 0.4rem; }
.sf-img-preview { display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 8px; background: #fff; }
.sf-img-preview img { width: 56px; height: 56px; object-fit: contain; border-radius: 6px; background: #f3f4f6; }
.sf-img-preview .meta { flex: 1; min-width: 0; }
.sf-img-preview .meta .fn { font-size: 0.8rem; color: #374151; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sf-img-preview .meta .fs { font-size: 0.72rem; color: #9ca3af; }
.sf-img-preview .rm { border: none; background: #fee2e2; color: #ef4444; cursor: pointer; font-size: 1rem; padding: 0.35rem 0.5rem; border-radius: 6px; line-height: 1; display: inline-flex; align-items: center; justify-content: center; transition: all 0.15s; flex-shrink: 0; }
.sf-img-preview .rm:hover { color: #fff; background: #ef4444; }
.sf-img-preview .rm i { display: inline-block; width: 1em; height: 1em; line-height: 1; }
.sf-img-uploading { text-align: center; padding: 1rem; color: #6b7280; font-size: 0.82rem; }
.sf-img-uploading i { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* ─── Palette ──────────────────────────────────────────────── */
.sb-palettes { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 0.5rem; margin-bottom: 1rem; }
.sb-pal { display: flex; flex-direction: column; align-items: center; gap: 0.35rem; padding: 0.6rem 0.4rem; border: 2px solid #e5e7eb; border-radius: 8px; cursor: pointer; transition: all 0.15s; background: #fff; }
.sb-pal:hover { border-color: #93c5fd; box-shadow: 0 2px 8px rgba(59,130,246,0.12); }
.sb-pal.active { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }
.sb-pal-colors { display: flex; gap: 2px; width: 100%; }
.sb-pal-colors span { flex: 1; height: 24px; border-radius: 4px; }
.sb-pal-name { font-size: 0.72rem; color: #6b7280; font-weight: 500; }

/* ─── Status ───────────────────────────────────────────────── */
.sb-toast { position: fixed; bottom: 1.5rem; right: 1.5rem; padding: 0.6rem 1.1rem; border-radius: 8px; font-size: 0.85rem; font-weight: 500; z-index: 9999; opacity: 0; transform: translateY(10px); transition: all 0.3s; pointer-events: none; }
.sb-toast.show { opacity: 1; transform: translateY(0); }
.sb-toast.ok { background: #d1fae5; color: #065f46; }
.sb-toast.err { background: #fee2e2; color: #991b1b; }
</style>

<div class="sb-wrap">
    <!-- Top bar -->
    <div class="sb-top-bar">
        <h4><i class="fas fa-palette text-primary me-2"></i>Site Builder</h4>
        <div class="sb-top-actions">
            <a href="/loja/" target="_blank" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-external-link-alt me-1"></i>Abrir Loja
            </a>
            <button type="button" class="btn btn-primary btn-sm" onclick="sbSave()">
                <i class="fas fa-save me-1"></i>Salvar
            </button>
        </div>
    </div>

    <!-- Groups from schema -->
    <?php foreach ($settingsSchema as $gIdx => $groupSchema):
        $gName     = $groupSchema['name'] ?? 'Grupo';
        $gKey      = $groupSchema['group'] ?? 'general';
        $gIcon     = $groupSchema['icon'] ?? 'fas fa-cog';
        $gSettings = $groupSchema['settings'] ?? [];
        $isColors  = $gKey === 'colors';
    ?>
    <div class="sb-group">
        <div class="sb-group-head <?= $gIdx > 0 ? 'closed' : '' ?>" onclick="sbToggle(this)">
            <i class="<?= e($gIcon) ?> gi"></i>
            <?= e($gName) ?>
            <i class="fas fa-chevron-down arrow"></i>
        </div>
        <div class="sb-group-content <?= $gIdx > 0 ? 'hide' : '' ?>" data-group="<?= eAttr($gKey) ?>">

            <?php if ($isColors): ?>
            <!-- Paletas predefinidas -->
            <div class="sf">
                <span class="sf-label">Paleta Rápida</span>
                <div class="sb-palettes" id="sbPalettes"></div>
            </div>
            <?php endif; ?>

            <?php foreach ($gSettings as $field):
                $fk  = $field['key'] ?? '';
                $fl  = $field['label'] ?? $fk;
                $ft  = $field['type'] ?? 'text';
                $fd  = $field['default'] ?? '';
                $fv  = $settings[$fk] ?? $fd;
                $fp  = $field['placeholder'] ?? '';
                $fo  = $field['options'] ?? [];
            ?>
            <div class="sf">

                <?php if ($ft === 'color'): ?>
                    <span class="sf-label"><?= e($fl) ?></span>
                    <div class="sf-color">
                        <input type="color" value="<?= eAttr($fv) ?>" data-key="<?= eAttr($fk) ?>"
                               onchange="this.nextElementSibling.value=this.value">
                        <input type="text" value="<?= eAttr($fv) ?>" data-key="<?= eAttr($fk) ?>"
                               class="sb-s" maxlength="7"
                               onchange="let c=this.previousElementSibling; if(/^#[0-9a-fA-F]{6}$/.test(this.value)) c.value=this.value;">
                    </div>

                <?php elseif ($ft === 'checkbox'): ?>
                    <label class="sf-toggle">
                        <input type="checkbox" class="sb-s" data-key="<?= eAttr($fk) ?>"
                               <?= ($fv === 'true' || $fv === '1' || $fv === true) ? 'checked' : '' ?>>
                        <span class="track"></span>
                        <span class="tl"><?= e($fl) ?></span>
                    </label>

                <?php elseif ($ft === 'select'): ?>
                    <span class="sf-label"><?= e($fl) ?></span>
                    <select class="sb-s" data-key="<?= eAttr($fk) ?>">
                        <?php foreach ($fo as $opt): ?>
                            <option value="<?= eAttr($opt['value']) ?>" <?= (string)$fv === (string)$opt['value'] ? 'selected' : '' ?>>
                                <?= e($opt['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                <?php elseif ($ft === 'font_picker'): ?>
                    <span class="sf-label"><?= e($fl) ?></span>
                    <select class="sb-s" data-key="<?= eAttr($fk) ?>">
                        <?php foreach ($themeFontOptions as $font): ?>
                            <option value="<?= eAttr($font) ?>" <?= $fv === $font ? 'selected' : '' ?>
                                    style="font-family:'<?= e($font) ?>'">
                                <?= e($font) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                <?php elseif ($ft === 'textarea'): ?>
                    <span class="sf-label"><?= e($fl) ?></span>
                    <textarea class="sb-s" data-key="<?= eAttr($fk) ?>"
                              placeholder="<?= eAttr($fp) ?>" rows="3"><?= e($fv) ?></textarea>

                <?php elseif ($ft === 'image'): ?>
                    <span class="sf-label"><?= e($fl) ?></span>
                    <div class="sf-img-wrap" id="imgWrap_<?= eAttr($fk) ?>" data-key="<?= eAttr($fk) ?>">
                        <?php if ($fv): ?>
                            <div class="sf-img-preview">
                                <img src="<?= eAttr($fv) ?>" alt="">
                                <div class="meta">
                                    <div class="fn"><?= e(basename($fv)) ?></div>
                                    <div class="fs">Imagem atual</div>
                                </div>
                                <button type="button" class="rm" onclick="sbImgRemove('<?= eAttr($fk) ?>')" title="Remover">
                                    <i class="fas fa-xmark"></i>
                                </button>
                            </div>
                            <input type="hidden" class="sb-s" data-key="<?= eAttr($fk) ?>" value="<?= eAttr($fv) ?>">
                        <?php else: ?>
                            <div class="sf-img-drop"
                                 onclick="document.getElementById('fileIn_<?= eAttr($fk) ?>').click()"
                                 ondragover="event.preventDefault();this.classList.add('dragover')"
                                 ondragleave="this.classList.remove('dragover')"
                                 ondrop="event.preventDefault();this.classList.remove('dragover');sbImgDrop(event,'<?= eAttr($fk) ?>')">
                                <div class="placeholder">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    Clique ou arraste uma imagem
                                </div>
                            </div>
                            <input type="hidden" class="sb-s" data-key="<?= eAttr($fk) ?>" value="">
                        <?php endif; ?>
                        <input type="file" id="fileIn_<?= eAttr($fk) ?>" accept="image/*" style="display:none"
                               onchange="sbImgUpload(this.files[0],'<?= eAttr($fk) ?>')">
                    </div>

                <?php else: ?>
                    <span class="sf-label"><?= e($fl) ?></span>
                    <input type="text" class="sb-s" data-key="<?= eAttr($fk) ?>"
                           value="<?= eAttr($fv) ?>" placeholder="<?= eAttr($fp) ?>">
                <?php endif; ?>

            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Bottom save -->
    <div class="d-flex justify-content-end gap-2 mt-2">
        <a href="/loja/" target="_blank" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-external-link-alt me-1"></i>Abrir Loja
        </a>
        <button type="button" class="btn btn-primary btn-sm px-4" onclick="sbSave()">
            <i class="fas fa-save me-1"></i>Salvar Configurações
        </button>
    </div>
</div>

<!-- Toast -->
<div id="sbToast" class="sb-toast"></div>

<script>
let csrfToken = <?= json_encode($csrfToken) ?>;

// ┌──────────────────────────────────────────────────────────┐
// │ Paletas de cores predefinidas                            │
// └──────────────────────────────────────────────────────────┘
const palettes = [
    { name: 'Azul Padrão',   primary: '#3b82f6', secondary: '#64748b', accent: '#f59e0b', bg: '#ffffff', text: '#333333' },
    { name: 'Índigo',        primary: '#6366f1', secondary: '#8b5cf6', accent: '#ec4899', bg: '#ffffff', text: '#1e1b4b' },
    { name: 'Esmeralda',     primary: '#059669', secondary: '#0d9488', accent: '#f59e0b', bg: '#ffffff', text: '#1f2937' },
    { name: 'Sunset',        primary: '#f97316', secondary: '#ef4444', accent: '#eab308', bg: '#fffbeb', text: '#431407' },
    { name: 'Rosa',          primary: '#ec4899', secondary: '#a855f7', accent: '#f43f5e', bg: '#fff1f2', text: '#4a044e' },
    { name: 'Oceano',        primary: '#0ea5e9', secondary: '#06b6d4', accent: '#14b8a6', bg: '#f0f9ff', text: '#0c4a6e' },
    { name: 'Noturno',       primary: '#8b5cf6', secondary: '#6366f1', accent: '#a78bfa', bg: '#0f172a', text: '#e2e8f0' },
    { name: 'Terracota',     primary: '#c2410c', secondary: '#b45309', accent: '#d97706', bg: '#fffbeb', text: '#422006' },
    { name: 'Neon',          primary: '#22d3ee', secondary: '#a3e635', accent: '#f472b6', bg: '#0f172a', text: '#f8fafc' },
    { name: 'Elegante',      primary: '#1e293b', secondary: '#475569', accent: '#c8a951', bg: '#ffffff', text: '#0f172a' },
    { name: 'Pastel',        primary: '#93c5fd', secondary: '#a5b4fc', accent: '#fca5a5', bg: '#f8fafc', text: '#334155' },
    { name: 'Floresta',      primary: '#166534', secondary: '#15803d', accent: '#ca8a04', bg: '#f0fdf4', text: '#14532d' },
];

function renderPalettes() {
    const container = document.getElementById('sbPalettes');
    if (!container) return;

    // Detect current for active state
    const cur = {
        primary: getFieldVal('primary_color'),
        secondary: getFieldVal('secondary_color'),
        accent: getFieldVal('accent_color'),
        bg: getFieldVal('bg_color'),
        text: getFieldVal('text_color'),
    };

    container.innerHTML = palettes.map((p, i) => {
        const isActive = p.primary === cur.primary && p.secondary === cur.secondary && p.accent === cur.accent;
        return `<div class="sb-pal ${isActive ? 'active' : ''}" onclick="sbApplyPalette(${i})">
            <div class="sb-pal-colors">
                <span style="background:${p.primary}"></span>
                <span style="background:${p.secondary}"></span>
                <span style="background:${p.accent}"></span>
                <span style="background:${p.bg};border:1px solid #e5e7eb"></span>
                <span style="background:${p.text}"></span>
            </div>
            <span class="sb-pal-name">${p.name}</span>
        </div>`;
    }).join('');
}

function sbApplyPalette(idx) {
    const p = palettes[idx];
    setFieldVal('primary_color', p.primary);
    setFieldVal('secondary_color', p.secondary);
    setFieldVal('accent_color', p.accent);
    setFieldVal('bg_color', p.bg);
    setFieldVal('text_color', p.text);
    renderPalettes();
}

function getFieldVal(key) {
    const el = document.querySelector(`.sb-s[data-key="${key}"]`);
    return el ? el.value : '';
}

function setFieldVal(key, val) {
    // Update text input
    const texts = document.querySelectorAll(`input[type="text"][data-key="${key}"]`);
    texts.forEach(el => el.value = val);
    // Update color input
    const colors = document.querySelectorAll(`input[type="color"][data-key="${key}"]`);
    colors.forEach(el => el.value = val);
    // Update hidden
    const hiddens = document.querySelectorAll(`input[type="hidden"][data-key="${key}"]`);
    hiddens.forEach(el => el.value = val);
}

// ┌──────────────────────────────────────────────────────────┐
// │ Accordion                                                │
// └──────────────────────────────────────────────────────────┘
function sbToggle(head) {
    head.classList.toggle('closed');
    const body = head.nextElementSibling;
    if (body) body.classList.toggle('hide');
}

// ┌──────────────────────────────────────────────────────────┐
// │ Image upload                                             │
// └──────────────────────────────────────────────────────────┘
function sbImgDrop(e, key) {
    const file = e.dataTransfer?.files?.[0];
    if (file && file.type.startsWith('image/')) {
        sbImgUpload(file, key);
    }
}

function sbImgUpload(file, key) {
    if (!file || !file.type.startsWith('image/')) {
        sbToast('Selecione um arquivo de imagem válido', 'err');
        return;
    }
    if (file.size > 5 * 1024 * 1024) {
        sbToast('Imagem muito grande (máx 5MB)', 'err');
        return;
    }

    const wrap = document.getElementById('imgWrap_' + key);
    wrap.innerHTML = `<div class="sf-img-uploading"><i class="fas fa-spinner"></i> Enviando...</div>
                      <input type="hidden" class="sb-s" data-key="${key}" value="">`;

    const fd = new FormData();
    fd.append('image', file);
    fd.append('key', key);

    fetch('?page=site_builder&action=uploadImage', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        body: fd,
    })
    .then(r => r.json())
    .then(data => {
        if (data.csrf_error && data.new_token) {
            csrfToken = data.new_token;
            sbImgUpload(file, key); // retry with fresh token
            return;
        }
        if (data.success && data.url) {
            const fname = data.filename || file.name;
            const fsize = file.size < 1024 ? file.size + ' B' : (file.size / 1024).toFixed(1) + ' KB';
            wrap.innerHTML = `
                <div class="sf-img-preview">
                    <img src="${data.url}" alt="">
                    <div class="meta">
                        <div class="fn">${fname}</div>
                        <div class="fs">${fsize}</div>
                    </div>
                    <button type="button" class="rm" onclick="sbImgRemove('${key}')" title="Remover">
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>
                <input type="hidden" class="sb-s" data-key="${key}" value="${data.url}">`;
            sbToast('Imagem enviada', 'ok');
        } else {
            sbImgReset(key);
            sbToast(data.message || 'Erro ao enviar imagem', 'err');
        }
    })
    .catch(() => {
        sbImgReset(key);
        sbToast('Erro de conexão ao enviar imagem', 'err');
    });
}

function sbImgRemove(key) {
    sbImgReset(key);
}

function sbImgReset(key) {
    const wrap = document.getElementById('imgWrap_' + key);
    wrap.innerHTML = `
        <div class="sf-img-drop"
             onclick="document.getElementById('fileIn_${key}').click()"
             ondragover="event.preventDefault();this.classList.add('dragover')"
             ondragleave="this.classList.remove('dragover')"
             ondrop="event.preventDefault();this.classList.remove('dragover');sbImgDrop(event,'${key}')">
            <div class="placeholder">
                <i class="fas fa-cloud-upload-alt"></i>
                Clique ou arraste uma imagem
            </div>
        </div>
        <input type="hidden" class="sb-s" data-key="${key}" value="">`;
    // Re-create file input
    let fileIn = document.getElementById('fileIn_' + key);
    if (!fileIn) {
        fileIn = document.createElement('input');
        fileIn.type = 'file';
        fileIn.id = 'fileIn_' + key;
        fileIn.accept = 'image/*';
        fileIn.style.display = 'none';
        fileIn.onchange = function() { sbImgUpload(this.files[0], key); };
        wrap.appendChild(fileIn);
    }
}

// ┌──────────────────────────────────────────────────────────┐
// │ Save                                                     │
// └──────────────────────────────────────────────────────────┘
function sbSave() {
    const groups = {};

    document.querySelectorAll('.sb-group-content').forEach(gc => {
        const gKey = gc.dataset.group;
        const gs = {};

        gc.querySelectorAll('.sb-s').forEach(el => {
            const k = el.dataset.key;
            if (!k) return;
            gs[k] = el.type === 'checkbox' ? (el.checked ? '1' : '0') : el.value;
        });

        if (Object.keys(gs).length) groups[gKey] = gs;
    });

    const entries = Object.entries(groups);
    let ok = 0, fail = 0, idx = 0;
    let csrfRetried = false;

    function sendGroup(gKey, gs) {
        const fd = new FormData();
        fd.append('group', gKey);
        fd.append('settings', JSON.stringify(gs));

        return fetch('?page=site_builder&action=saveSettings', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            body: fd,
        }).then(r => r.json());
    }

    function next() {
        if (idx >= entries.length) {
            if (fail === 0) sbToast('Configurações salvas!', 'ok');
            else sbToast(`Erro em ${fail} grupo(s)`, 'err');
            return;
        }
        const [gKey, gs] = entries[idx++];

        sendGroup(gKey, gs)
        .then(d => {
            if (d.csrf_error && d.new_token && !csrfRetried) {
                csrfRetried = true;
                csrfToken = d.new_token;
                idx--; // retry this group
                next();
                return;
            }
            d.success ? ok++ : fail++;
            next();
        })
        .catch(() => { fail++; next(); });
    }
    next();
}

// ┌──────────────────────────────────────────────────────────┐
// │ Toast                                                    │
// └──────────────────────────────────────────────────────────┘
function sbToast(msg, type) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: type === 'ok' ? 'success' : 'error',
            title: type === 'ok' ? 'Sucesso' : 'Erro',
            text: msg,
            timer: 3000,
            timerProgressBar: true,
            showConfirmButton: false,
            toast: false,
            position: 'center',
            customClass: { popup: 'sb-swal-popup' },
        });
    } else {
        const el = document.getElementById('sbToast');
        el.textContent = msg;
        el.className = 'sb-toast ' + type + ' show';
        clearTimeout(el._t);
        el._t = setTimeout(() => el.classList.remove('show'), 2500);
    }
}

// Init
renderPalettes();
</script>
