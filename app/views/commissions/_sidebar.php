<?php
/**
 * Comissões — Sidebar de navegação interna do módulo
 * Incluído por todas as views do módulo de comissões.
 * Segue o padrão visual do módulo Financeiro (sidebar em card + dicas).
 *
 * Variáveis esperadas: nenhuma obrigatória (autodetecta seção ativa).
 */
$currentAction = $_GET['action'] ?? 'index';

$commissionSections = [
    ['action' => 'index',          'icon' => 'fas fa-tachometer-alt',  'label' => 'Dashboard',           'color' => '#3498db', 'bg' => 'rgba(52,152,219,.1)'],
    ['action' => 'formas',         'icon' => 'fas fa-file-alt',        'label' => 'Formas de Comissão',   'color' => '#27ae60', 'bg' => 'rgba(39,174,96,.1)'],
    ['divider' => true],
    ['action' => 'grupos',         'icon' => 'fas fa-users',           'label' => 'Regras por Grupo',     'color' => '#8e44ad', 'bg' => 'rgba(142,68,173,.1)'],
    ['action' => 'usuarios',       'icon' => 'fas fa-user-tag',        'label' => 'Regras por Usuário',   'color' => '#e67e22', 'bg' => 'rgba(230,126,34,.1)'],
    ['action' => 'produtos',       'icon' => 'fas fa-box',             'label' => 'Regras por Produto',   'color' => '#16a085', 'bg' => 'rgba(22,160,133,.1)'],
    ['divider' => true],
    ['action' => 'simulador',      'icon' => 'fas fa-calculator',      'label' => 'Simulador',            'color' => '#2980b9', 'bg' => 'rgba(41,128,185,.1)'],
    ['action' => 'historico',      'icon' => 'fas fa-check-double',   'label' => 'Aprovação',            'color' => '#c0392b', 'bg' => 'rgba(192,57,43,.1)'],
    ['divider' => true],
    ['action' => 'configuracoes',  'icon' => 'fas fa-cog',             'label' => 'Configurações',        'color' => '#7f8c8d', 'bg' => 'rgba(127,140,141,.1)'],
];

// Dicas contextuais por seção
$_tips = [
    'index'         => 'O <strong>Dashboard</strong> mostra um resumo mensal de comissões, ranking de vendedores e gráfico de evolução.',
    'formas'        => 'Cadastre <strong>Formas de Comissão</strong> reutilizáveis (percentual, valor fixo ou faixa progressiva) e vincule a grupos ou usuários.',
    'grupos'        => 'Vincule formas de comissão a <strong>grupos de usuários</strong>. Regras de grupo são a <strong>prioridade 2</strong> no motor de cálculo.',
    'usuarios'      => 'Atribua regras individuais a cada vendedor. <strong>Prioridade 1</strong> — sobrepõe a regra do grupo.',
    'produtos'      => 'Defina comissões específicas por <strong>produto</strong> ou <strong>categoria</strong>. Prioridade 3 no motor de cálculo.',
    'simulador'     => 'Simule o cálculo de comissão <strong>sem registrar</strong>, usando a mesma lógica do motor de regras.',
    'historico'     => 'Gerencie aprovações e pagamentos de comissões. Aprove, pague ou cancele individualmente ou em lote, inclusive por vendedor.',
    'configuracoes' => 'Defina o <strong>percentual padrão</strong>, base de cálculo e comportamento de aprovação automática.',
];
$_currentTip = $_tips[$currentAction] ?? $_tips['index'];
?>

<style>
    /* ── Sidebar nav (padrão Financeiro) ── */
    .com-sidebar .com-nav-item{display:flex;align-items:center;gap:.75rem;padding:.7rem 1rem;border-radius:10px;text-decoration:none;color:#555;font-size:.82rem;font-weight:500;transition:all .15s ease;margin-bottom:2px;border:1px solid transparent;cursor:pointer}
    .com-sidebar .com-nav-item:hover{background:#f1f5f9;color:#333}
    .com-sidebar .com-nav-item.active{background:var(--bs-primary,#3498db);color:#fff;box-shadow:0 2px 8px rgba(52,152,219,.3)}
    .com-sidebar .com-nav-item.active .com-nav-icon{background:rgba(255,255,255,.2) !important;color:#fff !important}
    .com-nav-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.8rem;flex-shrink:0;transition:all .15s ease}
    .com-sidebar-divider{height:1px;background:#e9ecef;margin:.5rem 1rem}
    .com-sidebar-label{font-size:.65rem;text-transform:uppercase;letter-spacing:.8px;color:#aaa;font-weight:700;padding:0 1rem;margin-bottom:.3rem;margin-top:.6rem}

    /* ── Mobile sidebar ── */
    @media(max-width:991.98px){
        .com-sidebar-col{margin-bottom:1rem}
        .com-sidebar{display:flex;gap:.4rem;overflow-x:auto;padding-bottom:.5rem;scrollbar-width:thin}
        .com-sidebar .com-nav-item{white-space:nowrap;flex-shrink:0;padding:.5rem .85rem;font-size:.75rem}
        .com-sidebar-divider{display:none}
        .com-sidebar-label{display:none}
        .com-tip-card{display:none !important}
    }
</style>

<div class="col-lg-3 com-sidebar-col">
    <!-- Card Sidebar -->
    <div class="card border-0 shadow-sm" style="border-radius:12px;">
        <div class="card-body p-3">
            <nav class="com-sidebar">
                <div class="com-sidebar-label">Comissões</div>
                <?php foreach ($commissionSections as $sec): ?>
                    <?php if (!empty($sec['divider'])): ?>
                        <div class="com-sidebar-divider"></div>
                    <?php else: ?>
                        <a href="?page=commissions&action=<?= e($sec['action']) ?>"
                           class="com-nav-item <?= $currentAction === $sec['action'] ? 'active' : '' ?>">
                            <span class="com-nav-icon" style="background:<?= $sec['bg'] ?>;color:<?= $sec['color'] ?>">
                                <i class="<?= $sec['icon'] ?>"></i>
                            </span>
                            <span><?= e($sec['label']) ?></span>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>

    <!-- Card Dica Contextual -->
    <div class="card border-0 shadow-sm mt-3 com-tip-card" style="border-radius:12px;">
        <div class="card-body p-3">
            <h6 class="mb-2 fw-bold" style="font-size:.78rem;color:#17a2b8;">
                <i class="fas fa-lightbulb me-1"></i>Dica
            </h6>
            <p class="mb-0 text-muted" style="font-size:.72rem;line-height:1.55;">
                <?= $_currentTip ?>
            </p>
        </div>
    </div>

    <!-- Card Hierarquia de Regras -->
    <div class="card border-0 shadow-sm mt-3 com-tip-card" style="border-radius:12px;">
        <div class="card-body p-3">
            <h6 class="mb-2 fw-bold" style="font-size:.78rem;color:#8e44ad;">
                <i class="fas fa-sitemap me-1"></i>Hierarquia de Regras
            </h6>
            <div style="font-size:.70rem;line-height:1.7;color:#666;">
                <div><span class="badge bg-primary me-1" style="font-size:.6rem;width:18px;">1</span> Regra do Usuário</div>
                <div><span class="badge bg-info me-1" style="font-size:.6rem;width:18px;">2</span> Regra do Grupo</div>
                <div><span class="badge bg-secondary me-1" style="font-size:.6rem;width:18px;">3</span> Produto / Categoria</div>
                <div><span class="badge bg-warning text-dark me-1" style="font-size:.6rem;width:18px;">4</span> Regra Padrão</div>
            </div>
        </div>
    </div>
</div>
