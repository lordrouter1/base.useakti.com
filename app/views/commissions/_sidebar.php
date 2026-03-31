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
    ['action' => 'index',          'icon' => 'fas fa-tachometer-alt',  'label' => 'Dashboard',           'navClass' => 'nav-icon-blue'],
    ['action' => 'formas',         'icon' => 'fas fa-file-alt',        'label' => 'Formas de Comissão',   'navClass' => 'nav-icon-green'],
    ['divider' => true],
    ['action' => 'grupos',         'icon' => 'fas fa-users',           'label' => 'Regras por Grupo',     'navClass' => 'nav-icon-grape'],
    ['action' => 'usuarios',       'icon' => 'fas fa-user-tag',        'label' => 'Regras por Usuário',   'navClass' => 'nav-icon-carrot'],
    ['action' => 'produtos',       'icon' => 'fas fa-box',             'label' => 'Regras por Produto',   'navClass' => 'nav-icon-teal'],
    ['divider' => true],
    ['action' => 'simulador',      'icon' => 'fas fa-calculator',      'label' => 'Simulador',            'navClass' => 'nav-icon-navy'],
    ['action' => 'historico',      'icon' => 'fas fa-check-double',   'label' => 'Aprovação',            'navClass' => 'nav-icon-red'],
    ['divider' => true],
    ['action' => 'configuracoes',  'icon' => 'fas fa-cog',             'label' => 'Configurações',        'navClass' => 'nav-icon-dark'],
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

<!-- Styles loaded from assets/css/modules/commissions.css via header.php -->

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
                            <span class="com-nav-icon <?= $sec['navClass'] ?>">
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
            <h6 class="mb-2 fw-bold text-info-alt" style="font-size:.78rem;">
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
            <h6 class="mb-2 fw-bold text-grape" style="font-size:.78rem;">
                <i class="fas fa-sitemap me-1"></i>Hierarquia de Regras
            </h6>
            <div class="text-muted" style="font-size:.70rem;line-height:1.7;">
                <div><span class="badge bg-primary me-1" style="font-size:.6rem;width:18px;">1</span> Regra do Usuário</div>
                <div><span class="badge bg-info me-1" style="font-size:.6rem;width:18px;">2</span> Regra do Grupo</div>
                <div><span class="badge bg-secondary me-1" style="font-size:.6rem;width:18px;">3</span> Produto / Categoria</div>
                <div><span class="badge bg-warning text-dark me-1" style="font-size:.6rem;width:18px;">4</span> Regra Padrão</div>
            </div>
        </div>
    </div>
</div>
