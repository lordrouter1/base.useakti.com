/**
 * Akti Walkthrough Engine v3.1
 * Tour guiado com navegação entre páginas, cutout SVG, anel de destaque,
 * e abertura automática de submenus Bootstrap.
 */
class AktiWalkthrough {

    /**
     * Escapa HTML para prevenir XSS em conteúdo dinâmico.
     */
    static _escHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = String(str);
        return div.innerHTML;
    }

    constructor() {
        this.steps = [];
        this.currentIndex = 0;
        this.isActive = false;
        this.overlay = null;
        this.popover = null;
        this.modalOverlay = null;
        this.highlightedEl = null;
        this.highlightRing = null;
        this._openedDropdown = null; // Referência ao dropdown toggle que foi aberto pelo tour
        this._elevatedNavbar = null; // Navbar com z-index elevado durante submenu
        this._preventHideHandler = null; // Handler do evento hide.bs.dropdown
        this._preventHideTarget = null; // Target do handler

        this._onResize = this._repositionPopover.bind(this);
        this._onKeyDown = this._handleKeyDown.bind(this);
    }

    // ═══════════════════════════════════════════
    // API pública
    // ═══════════════════════════════════════════

    /**
     * Verifica automaticamente se precisa iniciar o tour.
     */
    async autoStart() {
        const resumeData = sessionStorage.getItem('akti_wt_resume');
        if (resumeData) {
            const data = JSON.parse(resumeData);
            sessionStorage.removeItem('akti_wt_resume');
            setTimeout(() => this.start(data.step || 0), 700);
            return;
        }

        try {
            const resp = await fetch('?page=walkthrough&action=checkStatus', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await resp.json();
            if (data.needs_walkthrough) {
                setTimeout(() => this.start(data.current_step || 0), 900);
            }
        } catch (e) {
            console.warn('Walkthrough: Não foi possível verificar status', e);
        }
    }

    /**
     * Inicia o tour a partir de um passo.
     */
    async start(fromStep = 0) {
        if (this.isActive) {
            this._cleanup(true);
            await new Promise(r => setTimeout(r, 350));
        }

        try {
            const resp = await fetch('?page=walkthrough&action=getSteps', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await resp.json();
            this.steps = data.steps || [];
        } catch (e) {
            console.warn('Walkthrough: Erro ao buscar passos', e);
            return;
        }

        if (this.steps.length === 0) return;

        this.isActive = true;
        this.currentIndex = Math.min(fromStep, this.steps.length - 1);

        fetch('?page=walkthrough&action=start', { method: 'POST' });

        this._createElements();
        this._bindEvents();
        this._showStep(this.currentIndex);
    }

    next() {
        if (this.currentIndex < this.steps.length - 1) {
            this.currentIndex++;
            this._saveStep(this.currentIndex);
            const step = this.steps[this.currentIndex];

            if (step.page && !this._isOnPage(step.page)) {
                sessionStorage.setItem('akti_wt_resume', JSON.stringify({ step: this.currentIndex }));
                window.location.href = '?page=' + step.page;
                return;
            }

            this._showStep(this.currentIndex);
        } else {
            this.complete();
        }
    }

    prev() {
        if (this.currentIndex > 0) {
            this.currentIndex--;
            this._saveStep(this.currentIndex);
            const step = this.steps[this.currentIndex];

            if (step.page && !this._isOnPage(step.page)) {
                sessionStorage.setItem('akti_wt_resume', JSON.stringify({ step: this.currentIndex }));
                window.location.href = '?page=' + step.page;
                return;
            }

            this._showStep(this.currentIndex);
        }
    }

    skip() {
        fetch('?page=walkthrough&action=skip', { method: 'POST' });
        sessionStorage.removeItem('akti_wt_resume');
        this._cleanup();
    }

    complete() {
        fetch('?page=walkthrough&action=complete', { method: 'POST' });
        sessionStorage.removeItem('akti_wt_resume');
        this._cleanup();

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Tour concluído!',
                html: 'Você pode refazer o tour a qualquer momento pelo botão <strong>"Tutorial"</strong> no rodapé do sistema.',
                confirmButtonColor: '#3b82f6',
                timer: 6000,
                timerProgressBar: true
            });
        }
    }

    // ═══════════════════════════════════════════
    // Verificação de página atual
    // ═══════════════════════════════════════════

    _isOnPage(page) {
        const params = new URLSearchParams(window.location.search);
        const currentPage = params.get('page') || 'home';
        if (page === 'dashboard') {
            return currentPage === 'dashboard' || currentPage === 'home' || !params.get('page');
        }
        if (page === 'home') {
            return currentPage === 'home' || !params.get('page');
        }
        return currentPage === page;
    }

    // ═══════════════════════════════════════════
    // Criação dos elementos do DOM
    // ═══════════════════════════════════════════

    _createElements() {
        // Overlay com buraco (SVG)
        this.overlay = document.createElement('div');
        this.overlay.className = 'wt-overlay';
        this.overlay.id = 'wtOverlay';
        const overlaySvg = `<svg class="wt-overlay-svg" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <mask id="wtMask">
                    <rect x="0" y="0" width="100%" height="100%" fill="white"/>
                    <rect id="wtCutout" x="0" y="0" width="0" height="0" rx="12" fill="black"/>
                </mask>
            </defs>
            <rect x="0" y="0" width="100%" height="100%" fill="rgba(15,23,42,0.72)" mask="url(#wtMask)"/>
        </svg>`;
        this.overlay.innerHTML = typeof DOMPurify !== 'undefined' ? DOMPurify.sanitize(overlaySvg, { USE_PROFILES: { svg: true } }) : overlaySvg;
        document.body.appendChild(this.overlay);

        this.overlay.addEventListener('click', (e) => e.stopPropagation());

        requestAnimationFrame(() => this.overlay.classList.add('active'));

        // Anel de destaque
        this.highlightRing = document.createElement('div');
        this.highlightRing.className = 'wt-highlight-ring';
        document.body.appendChild(this.highlightRing);

        // Popover
        this.popover = document.createElement('div');
        this.popover.className = 'wt-popover';
        document.body.appendChild(this.popover);

        // Modal overlay
        this.modalOverlay = document.createElement('div');
        this.modalOverlay.className = 'wt-modal-overlay';
        document.body.appendChild(this.modalOverlay);
    }

    _bindEvents() {
        window.addEventListener('resize', this._onResize);
        document.addEventListener('keydown', this._onKeyDown);
    }

    _unbindEvents() {
        window.removeEventListener('resize', this._onResize);
        document.removeEventListener('keydown', this._onKeyDown);
    }

    _handleKeyDown(e) {
        if (!this.isActive) return;
        if (e.key === 'ArrowRight' || e.key === 'Enter') { e.preventDefault(); this.next(); }
        if (e.key === 'ArrowLeft') { e.preventDefault(); this.prev(); }
        if (e.key === 'Escape') { e.preventDefault(); this.skip(); }
    }

    // ═══════════════════════════════════════════
    // Submenus Bootstrap — abrir/fechar dropdowns
    // ═══════════════════════════════════════════

    /**
     * Se o passo tem a propriedade 'submenu', abre o dropdown correspondente
     * antes de tentar encontrar o elemento. Retorna uma Promise que resolve
     * quando o dropdown está aberto e o DOM visível.
     */
    _openSubmenuIfNeeded(step) {
        // Fechar dropdown anterior se houver
        this._closeOpenedDropdown();

        if (!step.submenu) return Promise.resolve();

        const toggleEl = document.querySelector(step.submenu);
        if (!toggleEl) {
            console.warn('Walkthrough: Toggle do submenu não encontrado:', step.submenu);
            return Promise.resolve();
        }

        // Elevar o z-index da navbar inteira para ficar acima do overlay
        // (necessário porque a navbar sticky cria um stacking context)
        const navbar = document.querySelector('.navbar-akti');
        if (navbar) {
            navbar.style.zIndex = '10002';
            this._elevatedNavbar = navbar;
        }

        // Prevenir que o Bootstrap feche automaticamente o dropdown enquanto o tour está ativo
        const preventHide = (e) => {
            if (this.isActive && this._openedDropdown === toggleEl) {
                e.preventDefault();
            }
        };
        toggleEl.addEventListener('hide.bs.dropdown', preventHide);
        this._preventHideHandler = preventHide;
        this._preventHideTarget = toggleEl;

        // Se já está aberto, não precisa fazer nada
        if (toggleEl.getAttribute('aria-expanded') === 'true') {
            this._openedDropdown = toggleEl;
            return Promise.resolve();
        }

        return new Promise((resolve) => {
            // Usar Bootstrap Dropdown API
            let bsDropdown;
            try {
                bsDropdown = bootstrap.Dropdown.getOrCreateInstance(toggleEl);
            } catch(e) {
                // Fallback: clicar
                toggleEl.click();
                this._openedDropdown = toggleEl;
                setTimeout(resolve, 350);
                return;
            }
            bsDropdown.show();
            this._openedDropdown = toggleEl;
            // Esperar transição CSS do dropdown
            setTimeout(resolve, 350);
        });
    }

    /**
     * Fecha o dropdown que o tour abriu, se houver.
     */
    _closeOpenedDropdown() {
        // Remover prevenção de hide do Bootstrap
        if (this._preventHideHandler && this._preventHideTarget) {
            this._preventHideTarget.removeEventListener('hide.bs.dropdown', this._preventHideHandler);
            this._preventHideHandler = null;
            this._preventHideTarget = null;
        }
        // Restaurar z-index da navbar
        if (this._elevatedNavbar) {
            this._elevatedNavbar.style.zIndex = '';
            this._elevatedNavbar = null;
        }
        if (!this._openedDropdown) return;
        try {
            const bsDropdown = bootstrap.Dropdown.getOrCreateInstance(this._openedDropdown);
            bsDropdown.hide();
        } catch(e) {
            // Fallback
            this._openedDropdown.setAttribute('aria-expanded', 'false');
            const menu = this._openedDropdown.nextElementSibling;
            if (menu && menu.classList.contains('dropdown-menu')) {
                menu.classList.remove('show');
            }
            const parent = this._openedDropdown.closest('.dropdown');
            if (parent) parent.classList.remove('show');
        }
        this._openedDropdown = null;
    }

    // ═══════════════════════════════════════════
    // Renderização dos passos
    // ═══════════════════════════════════════════

    _showStep(index) {
        const step = this.steps[index];
        if (!step) return;

        this._clearHighlight();
        this.popover.classList.remove('active');
        this.modalOverlay.classList.remove('active');

        if (step.type === 'modal') {
            this._closeOpenedDropdown();
            this._hideCutout();
            this._hideHighlightRing();
            this._showModal(step, index);
        } else {
            this.modalOverlay.classList.remove('active');
            this.modalOverlay.innerHTML = '';
            // Abrir submenu se necessário, depois mostrar highlight
            this._openSubmenuIfNeeded(step).then(() => {
                this._showHighlight(step, index);
            });
        }
    }

    _showModal(step, index) {
        const isFirst = index === 0;
        const isLast = index === this.steps.length - 1;

        this.overlay.classList.add('active');

        const modalHtml = `
            <div class="wt-modal">
                <div class="wt-modal-header">
                    <img src="assets/logos/akti-square-light.svg" alt="Akti" class="wt-modal-logo"
                         onerror="this.style.display='none'">
                    <span class="wt-modal-icon"><i class="${AktiWalkthrough._escHtml(step.icon) || 'fas fa-star'}"></i></span>
                    <h3>${AktiWalkthrough._escHtml(step.title)}</h3>
                </div>
                <div class="wt-modal-body">
                    <p>${AktiWalkthrough._escHtml(step.description)}</p>
                </div>
                <div class="wt-modal-footer">
                    ${!isFirst && !isLast ? '<button class="wt-btn wt-btn-secondary" id="wtModalPrev"><i class="fas fa-arrow-left me-1"></i> Voltar</button>' : ''}
                    <button class="wt-btn wt-btn-primary" id="wtModalNext">
                        ${isFirst ? '<i class="fas fa-play me-1"></i> Começar Tour' : (isLast ? '<i class="fas fa-check me-1"></i> Concluir Tour' : 'Próximo <i class="fas fa-arrow-right ms-1"></i>')}
                    </button>
                    ${isFirst ? '<button class="wt-btn wt-btn-skip" id="wtModalSkip"><i class="fas fa-forward me-1"></i> Pular tour</button>' : ''}
                </div>
            </div>
        `;
        this.modalOverlay.innerHTML = typeof DOMPurify !== 'undefined' ? DOMPurify.sanitize(modalHtml) : modalHtml;

        requestAnimationFrame(() => this.modalOverlay.classList.add('active'));

        document.getElementById('wtModalNext')?.addEventListener('click', () => {
            if (isLast) this.complete();
            else this.next();
        });
        document.getElementById('wtModalPrev')?.addEventListener('click', () => this.prev());
        document.getElementById('wtModalSkip')?.addEventListener('click', () => this.skip());
    }

    _showHighlight(step, index) {
        // Contagem de highlights para numeração
        const highlightSteps = this.steps.filter(s => s.type === 'highlight');
        const highlightIndex = highlightSteps.indexOf(step);
        const totalHighlight = highlightSteps.length;

        // Encontrar elemento — testar vários seletores separados por vírgula
        let el = null;
        const selectors = step.element.split(',').map(s => s.trim());
        for (const sel of selectors) {
            try {
                el = document.querySelector(sel);
                if (el) break;
            } catch (e) { /* seletor inválido, ignora */ }
        }

        if (!el) {
            console.warn(`Walkthrough: Elemento não encontrado: ${step.element}, pulando passo "${step.id}"...`);
            this._closeOpenedDropdown();
            if (index < this.steps.length - 1) {
                this.currentIndex++;
                this._showStep(this.currentIndex);
            } else {
                this.complete();
            }
            return;
        }

        // Scroll suave para o elemento
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });

        setTimeout(() => {
            this.highlightedEl = el;

            // Cutout + anel de destaque
            this._updateCutout(el);
            this._showHighlightRing(el);

            // Verificar se o próximo é modal de conclusão
            const nextStep = this.steps[index + 1];
            const isLastHighlight = !nextStep || nextStep.type === 'modal';

            // Montar popover
            const popoverHtml = `
                <div class="wt-popover-header">
                    <div class="wt-popover-step-info">
                        <span class="wt-popover-step-badge">
                            <i class="fas fa-map-pin"></i> Passo ${highlightIndex + 1} de ${totalHighlight}
                        </span>
                        ${step.page ? '<span class="wt-popover-page-badge"><i class="fas fa-file-alt"></i> ' + AktiWalkthrough._escHtml(this._formatPageName(step.page)) + '</span>' : ''}
                    </div>
                    <h4>${AktiWalkthrough._escHtml(step.title)}</h4>
                </div>
                <div class="wt-popover-body">
                    <p>${AktiWalkthrough._escHtml(step.description)}</p>
                </div>
                <div class="wt-popover-footer">
                    <div class="wt-progress">
                        ${this._buildProgressDots(highlightIndex, totalHighlight)}
                    </div>
                    <div class="wt-btn-actions">
                        ${index > 0 ? '<button class="wt-btn wt-btn-secondary" id="wtPrev"><i class="fas fa-arrow-left"></i> Voltar</button>' : '<button class="wt-btn wt-btn-skip" id="wtSkip"><i class="fas fa-forward"></i> Pular</button>'}
                        <button class="wt-btn wt-btn-primary" id="wtNext">
                            ${isLastHighlight ? 'Finalizar <i class="fas fa-check ms-1"></i>' : 'Próximo <i class="fas fa-arrow-right ms-1"></i>'}
                        </button>
                    </div>
                </div>
            `;
            this.popover.innerHTML = typeof DOMPurify !== 'undefined' ? DOMPurify.sanitize(popoverHtml) : popoverHtml;

            this._positionPopover(el, step.position || 'bottom');
            requestAnimationFrame(() => this.popover.classList.add('active'));

            document.getElementById('wtNext')?.addEventListener('click', () => this.next());
            document.getElementById('wtPrev')?.addEventListener('click', () => this.prev());
            document.getElementById('wtSkip')?.addEventListener('click', () => this.skip());
        }, 450);
    }

    // ═══════════════════════════════════════════
    // Formatador de nome de página
    // ═══════════════════════════════════════════

    _formatPageName(page) {
        const names = {
            'dashboard': 'Dashboard',
            'home': 'Início',
            'orders': 'Pedidos',
            'pipeline': 'Pipeline',
            'customers': 'Clientes',
            'products': 'Produtos',
            'stock': 'Estoque',
            'sectors': 'Setores',
            'settings': 'Configurações',
            'users': 'Usuários',
            'categories': 'Categorias',
            'agenda': 'Agenda',
            'price_tables': 'Tabelas de Preço',
            'production_board': 'Painel de Produção',
            'financial': 'Financeiro',
            'financial_payments': 'Pagamentos',
            'financial_transactions': 'Entradas/Saídas'
        };
        return names[page] || page;
    }

    // ═══════════════════════════════════════════
    // SVG Cutout (buraco no overlay)
    // ═══════════════════════════════════════════

    _updateCutout(el) {
        const rect = el.getBoundingClientRect();
        const padding = 10;
        const cutout = document.getElementById('wtCutout');
        if (cutout) {
            cutout.setAttribute('x', rect.left - padding);
            cutout.setAttribute('y', rect.top - padding);
            cutout.setAttribute('width', rect.width + padding * 2);
            cutout.setAttribute('height', rect.height + padding * 2);
        }
        this.overlay.classList.add('active');
    }

    _hideCutout() {
        const cutout = document.getElementById('wtCutout');
        if (cutout) {
            cutout.setAttribute('width', 0);
            cutout.setAttribute('height', 0);
        }
    }

    // ═══════════════════════════════════════════
    // Highlight Ring
    // ═══════════════════════════════════════════

    _showHighlightRing(el) {
        if (!this.highlightRing) return;
        const rect = el.getBoundingClientRect();
        const padding = 10;
        this.highlightRing.style.top = (rect.top - padding) + 'px';
        this.highlightRing.style.left = (rect.left - padding) + 'px';
        this.highlightRing.style.width = (rect.width + padding * 2) + 'px';
        this.highlightRing.style.height = (rect.height + padding * 2) + 'px';
        this.highlightRing.classList.add('active');
    }

    _hideHighlightRing() {
        if (this.highlightRing) {
            this.highlightRing.classList.remove('active');
        }
    }

    // ═══════════════════════════════════════════
    // Posicionamento do Popover
    // ═══════════════════════════════════════════

    _positionPopover(el, position) {
        const rect = el.getBoundingClientRect();
        const margin = 20;

        this.popover.style.visibility = 'hidden';
        this.popover.style.display = 'block';
        this.popover.classList.add('active');
        const popRect = this.popover.getBoundingClientRect();
        this.popover.style.visibility = '';

        let top, left;

        switch (position) {
            case 'bottom':
                top = rect.bottom + margin;
                left = rect.left + (rect.width / 2) - (popRect.width / 2);
                break;
            case 'top':
                top = rect.top - popRect.height - margin;
                left = rect.left + (rect.width / 2) - (popRect.width / 2);
                break;
            case 'left':
                top = rect.top + (rect.height / 2) - (popRect.height / 2);
                left = rect.left - popRect.width - margin;
                break;
            case 'right':
                top = rect.top + (rect.height / 2) - (popRect.height / 2);
                left = rect.right + margin;
                break;
            case 'bottom-end':
                top = rect.bottom + margin;
                left = rect.right - popRect.width;
                break;
            default:
                top = rect.bottom + margin;
                left = rect.left + (rect.width / 2) - (popRect.width / 2);
        }

        // Ajustes automáticos de overflow
        if (top + popRect.height > window.innerHeight - 10) {
            top = rect.top - popRect.height - margin;
        }
        if (top < 10) {
            top = rect.bottom + margin;
        }
        if (top + popRect.height > window.innerHeight - 10) {
            top = Math.max(12, (window.innerHeight - popRect.height) / 2);
        }

        left = Math.max(12, Math.min(left, window.innerWidth - popRect.width - 12));
        top = Math.max(12, top);

        this.popover.style.top = top + 'px';
        this.popover.style.left = left + 'px';
    }

    _repositionPopover() {
        if (!this.isActive || !this.highlightedEl) return;
        const step = this.steps[this.currentIndex];
        if (step && step.type === 'highlight') {
            this._updateCutout(this.highlightedEl);
            this._showHighlightRing(this.highlightedEl);
            this._positionPopover(this.highlightedEl, step.position || 'bottom');
        }
    }

    // ═══════════════════════════════════════════
    // Progress Dots
    // ═══════════════════════════════════════════

    _buildProgressDots(currentHighlightIndex, totalHighlight) {
        let dots = '';
        const maxVisible = 14;
        const total = totalHighlight;

        for (let i = 0; i < total; i++) {
            if (total > maxVisible && i > 4 && i < total - 4 && i !== currentHighlightIndex) {
                if (i === 5) dots += '<span class="wt-progress-ellipsis">…</span>';
                continue;
            }
            let cls = 'wt-progress-dot';
            if (i === currentHighlightIndex) cls += ' active';
            else if (i < currentHighlightIndex) cls += ' done';
            dots += `<span class="${cls}"></span>`;
        }
        return dots;
    }

    // ═══════════════════════════════════════════
    // Limpeza
    // ═══════════════════════════════════════════

    _clearHighlight() {
        this.highlightedEl = null;
        this._hideCutout();
        this._hideHighlightRing();
    }

    _saveStep(step) {
        const formData = new FormData();
        formData.append('step', step);
        fetch('?page=walkthrough&action=saveStep', { method: 'POST', body: formData });
    }

    _cleanup(silent = false) {
        this.isActive = false;
        this._clearHighlight();
        this._closeOpenedDropdown();
        this._unbindEvents();

        const fade = (el) => {
            if (!el) return;
            el.classList.remove('active');
            setTimeout(() => { if (el && el.parentNode) el.remove(); }, 350);
        };

        fade(this.overlay);
        fade(this.popover);
        fade(this.modalOverlay);
        fade(this.highlightRing);

        this.overlay = null;
        this.popover = null;
        this.modalOverlay = null;
        this.highlightRing = null;
    }
}

// Instância global
window.aktiWalkthrough = new AktiWalkthrough();
