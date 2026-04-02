<?php
/**
 * Breadcrumb Component
 * 
 * Renderiza um breadcrumb contextual baseado na página atual.
 * Em mobile, exibe apenas "← Voltar".
 *
 * Uso no controller/view:
 *   $breadcrumb = ['Início', 'Cadastros', 'Clientes'];
 *   require 'app/views/components/breadcrumb.php';
 *
 * Se $breadcrumb não estiver definido, tenta resolver pelo routes.php.
 */

// Resolve breadcrumb automaticamente se não definido
if (empty($breadcrumb)) {
    $__page = $_GET['page'] ?? 'dashboard';
    $__action = $_GET['action'] ?? 'index';
    
    // Mapa básico de breadcrumbs por página
    $__breadcrumbMap = [
        'dashboard'   => ['Início', 'Dashboard'],
        'customers'   => ['Início', 'Comercial', 'Clientes'],
        'orders'      => ['Início', 'Comercial', 'Pedidos'],
        'products'    => ['Início', 'Catálogo', 'Produtos'],
        'categories'  => ['Início', 'Catálogo', 'Categorias'],
        'pipeline'    => ['Início', 'Produção', 'Pipeline'],
        'stock'       => ['Início', 'Produção', 'Estoque'],
        'financial'   => ['Início', 'Financeiro', 'Transações'],
        'commissions' => ['Início', 'Financeiro', 'Comissões'],
        'reports'     => ['Início', 'Relatórios'],
        'settings'    => ['Início', 'Configurações'],
        'users'       => ['Início', 'Admin', 'Usuários'],
        'profile'     => ['Início', 'Meu Perfil'],
        'nfe'         => ['Início', 'Fiscal', 'NF-e'],
        'sectors'         => ['Início', 'Produção', 'Setores'],
        'email_marketing' => ['Início', 'Marketing', 'E-mail Marketing'],
    ];

    $breadcrumb = $__breadcrumbMap[$__page] ?? ['Início', ucfirst($__page)];

    // Append action label if not index
    $__actionLabels = [
        'create' => 'Novo',
        'edit'   => 'Editar',
        'detail' => 'Detalhes',
        'store'  => 'Salvar',
        'import' => 'Importar',
        'export' => 'Exportar',
    ];
    if ($__action !== 'index' && isset($__actionLabels[$__action])) {
        $breadcrumb[] = $__actionLabels[$__action];
    }
}

// Link map for each breadcrumb level
$__linkMap = [
    'Início'       => '?page=dashboard',
    'Comercial'    => '#',
    'Catálogo'     => '#',
    'Produção'     => '#',
    'Financeiro'   => '?page=financial',
    'Admin'        => '#',
    'Fiscal'       => '#',
    'Clientes'     => '?page=customers',
    'Pedidos'      => '?page=orders',
    'Produtos'     => '?page=products',
    'Categorias'   => '?page=categories',
    'Pipeline'     => '?page=pipeline',
    'Estoque'      => '?page=stock',
    'Transações'   => '?page=financial',
    'Comissões'    => '?page=commissions',
    'Relatórios'   => '?page=reports',
    'Configurações'=> '?page=settings',
    'Usuários'     => '?page=users',
    'NF-e'         => '?page=nfe',
    'Setores'          => '?page=sectors',
    'Marketing'        => '#',
    'E-mail Marketing' => '?page=email_marketing',
];
?>

<!-- Breadcrumb: Desktop -->
<nav aria-label="Breadcrumb" class="akti-breadcrumb d-none d-md-block">
    <ol class="akti-breadcrumb-list">
        <?php foreach ($breadcrumb as $i => $item): ?>
            <?php $isLast = ($i === count($breadcrumb) - 1); ?>
            <li class="akti-breadcrumb-item <?= $isLast ? 'active' : '' ?>">
                <?php if (!$isLast && isset($__linkMap[$item]) && $__linkMap[$item] !== '#'): ?>
                    <a href="<?= $__linkMap[$item] ?>"><?= e($item) ?></a>
                <?php else: ?>
                    <span><?= e($item) ?></span>
                <?php endif; ?>
            </li>
            <?php if (!$isLast): ?>
            <li class="akti-breadcrumb-sep" aria-hidden="true">
                <i class="fas fa-chevron-right"></i>
            </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ol>
</nav>

<!-- Breadcrumb: Mobile (back link) -->
<?php if (count($breadcrumb) > 1): ?>
<nav aria-label="Voltar" class="akti-breadcrumb-mobile d-md-none">
    <?php
    // Find the parent page to link back to
    $__parentItem = $breadcrumb[count($breadcrumb) - 2] ?? 'Início';
    $__parentLink = $__linkMap[$__parentItem] ?? '?page=dashboard';
    ?>
    <a href="<?= $__parentLink ?>" class="akti-back-link">
        <i class="fas fa-arrow-left me-1"></i>
        <?= e($__parentItem) ?>
    </a>
</nav>
<?php endif; ?>
