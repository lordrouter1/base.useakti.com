<?php
/**
 * Partial: Sidebar do módulo financeiro.
 *
 * Variáveis esperadas:
 *   $activeSection        — seção ativa (payments|transactions|import|new|reports|cashflow|recurring)
 *   $overdueCount         — quantidade de parcelas em atraso
 *   $pendingConfirmCount  — quantidade de pagamentos aguardando confirmação
 */
?>
<div class="card border-0 shadow-sm" style="border-radius:12px;">
    <div class="card-body p-3">
        <nav class="fin-sidebar">

            <div class="fin-sidebar-label">Financeiro</div>

            <a href="#" class="fin-nav-item <?= $activeSection === 'payments' ? 'active' : '' ?>" data-section="payments">
                <span class="fin-nav-icon nav-icon-blue">
                    <i class="fas fa-file-invoice-dollar"></i>
                </span>
                <span>Pagamentos</span>
                <?php if ($overdueCount > 0): ?>
                <span class="fin-nav-count nav-icon-red-alt"><?= $overdueCount ?></span>
                <?php endif; ?>
            </a>

            <a href="#" class="fin-nav-item <?= $activeSection === 'transactions' ? 'active' : '' ?>" data-section="transactions">
                <span class="fin-nav-icon nav-icon-green">
                    <i class="fas fa-exchange-alt"></i>
                </span>
                <span>Visão Geral</span>
            </a>

            <div class="fin-sidebar-divider"></div>

            <a href="#" class="fin-nav-item <?= $activeSection === 'import' ? 'active' : '' ?>" data-section="import">
                <span class="fin-nav-icon nav-icon-info">
                    <i class="fas fa-file-import"></i>
                </span>
                <span>Importação</span>
            </a>

            <a href="#" class="fin-nav-item <?= $activeSection === 'new' ? 'active' : '' ?>" data-section="new">
                <span class="fin-nav-icon nav-icon-purple">
                    <i class="fas fa-plus-circle"></i>
                </span>
                <span>Nova Transação</span>
            </a>

            <a href="#" class="fin-nav-item <?= $activeSection === 'recurring' ? 'active' : '' ?>" data-section="recurring">
                <span class="fin-nav-icon nav-icon-yellow">
                    <i class="fas fa-redo-alt"></i>
                </span>
                <span>Recorrências</span>
            </a>

            <div class="fin-sidebar-divider"></div>
            <div class="fin-sidebar-label">Relatórios</div>

            <a href="#" class="fin-nav-item <?= $activeSection === 'reports' ? 'active' : '' ?>" data-section="reports">
                <span class="fin-nav-icon nav-icon-carrot">
                    <i class="fas fa-chart-pie"></i>
                </span>
                <span>DRE Simplificado</span>
            </a>

            <a href="#" class="fin-nav-item <?= $activeSection === 'cashflow' ? 'active' : '' ?>" data-section="cashflow">
                <span class="fin-nav-icon nav-icon-mint">
                    <i class="fas fa-chart-area"></i>
                </span>
                <span>Fluxo de Caixa</span>
            </a>

            <a href="#" class="fin-nav-item <?= $activeSection === 'audit' ? 'active' : '' ?>" data-section="audit">
                <span class="fin-nav-icon nav-icon-grape">
                    <i class="fas fa-shield-alt"></i>
                </span>
                <span>Auditoria</span>
            </a>

        </nav>
    </div>
</div>

<!-- Mini-dica -->
<div class="card border-0 shadow-sm mt-3 d-none d-lg-block" style="border-radius:12px;">
    <div class="card-body p-3">
        <h6 class="mb-2 fw-bold text-info-alt" style="font-size:.78rem;">
            <i class="fas fa-lightbulb me-1"></i>Dica
        </h6>
        <p class="mb-0 text-muted" style="font-size:.72rem;line-height:1.55;">
            Use <span class="fw-bold text-primary">Pagamentos</span> para gerenciar parcelas de pedidos,
            <span class="fw-bold text-success">Visão Geral</span> para entradas e saídas,
            <span class="fw-bold text-yellow">Recorrências</span> para despesas/receitas fixas,
            <span class="fw-bold text-mint">Fluxo de Caixa</span> para projeção futura
            e <span class="fw-bold text-grape">Auditoria</span> para rastrear todas as movimentações.
        </p>
    </div>
</div>

<!-- Alertas -->
<?php if ($pendingConfirmCount > 0): ?>
<div class="card border-0 shadow-sm mt-3 border-start border-warning border-4" style="border-radius:12px;">
    <div class="card-body p-3">
        <h6 class="mb-1 fw-bold text-warning" style="font-size:.78rem;">
            <i class="fas fa-user-clock me-1"></i>Aguardando Confirmação
        </h6>
        <p class="mb-0 text-muted" style="font-size:.72rem;">
            <strong><?= $pendingConfirmCount ?></strong> pagamento(s) pendente(s) de confirmação.
        </p>
    </div>
</div>
<?php endif; ?>
