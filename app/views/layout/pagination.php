<?php
/**
 * Componente reutilizável de paginação (Bootstrap 5).
 *
 * Variáveis esperadas (definidas pelo controller antes do require):
 *   $ctPage  — Página atual (int, 1-based)
 *   $totalPages   — Total de páginas (int)
 *   $totalItems   — Total de registros (int)
 *   $perPage      — Itens por página (int)
 *   $baseUrl      — URL base sem o parâmetro pg (ex: "?page=customers")
 *
 * O parâmetro de página na URL é "pg" (para não conflitar com "page" do router).
 */

if (!isset($ctPage, $totalPages, $totalItems, $perPage, $baseUrl)) return;

// Garantir tipos inteiros (proteção contra string vinda de $_GET ou contexto inesperado)
$ctPage = (int) $ctPage;
$totalPages  = (int) $totalPages;
$totalItems  = (int) $totalItems;
$perPage     = (int) $perPage;

if ($totalPages <= 1 && $totalItems <= $perPage) return;

// Separador de query string
$glue = (strpos($baseUrl, '?') !== false) ? '&' : '?';

// Quantas páginas vizinhas mostrar de cada lado
$neighbours = 2;

$startPage = max(1, $ctPage - $neighbours);
$endPage   = min($totalPages, $ctPage + $neighbours);
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-center mt-3 gap-2">
    <!-- Info -->
    <div class="text-muted small">
        <?php
        $firstItem = ($ctPage - 1) * $perPage + 1;
        $lastItem  = min($ctPage * $perPage, $totalItems);
        ?>
        Mostrando <strong><?= $firstItem ?></strong>–<strong><?= $lastItem ?></strong> de <strong><?= $totalItems ?></strong> registro<?= $totalItems !== 1 ? 's' : '' ?>
    </div>

    <!-- Navegação -->
    <?php if ($totalPages > 1): ?>
    <nav aria-label="Paginação">
        <ul class="pagination pagination mb-0">
            <!-- Primeira -->
            
            <li class="page-item <?= $ctPage <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $baseUrl . $glue ?>pg=1" title="Primeira página">
                    <i class="fas fa-angle-double-left"></i>
                </a>
            </li>
            <!-- Anterior -->
            <li class="page-item <?= $ctPage <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $baseUrl . $glue ?>pg=<?= $ctPage - 1 ?>" title="Anterior">
                    <i class="fas fa-angle-left"></i>
                </a>
            </li>

            <?php if ($startPage > 1): ?>
                <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php endif; ?>

            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
            <li class="page-item <?= $i === $ctPage ? 'active' : '' ?>">
                <a class="page-link" href="<?= $baseUrl . $glue ?>pg=<?= $i ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>

            <?php if ($endPage < $totalPages): ?>
                <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php endif; ?>

            <!-- Próxima -->
            <li class="page-item <?= $ctPage >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $baseUrl . $glue ?>pg=<?= $ctPage + 1 ?>" title="Próxima">
                    <i class="fas fa-angle-right"></i>
                </a>
            </li>
            <!-- Última -->
            <li class="page-item <?= $ctPage >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $baseUrl . $glue ?>pg=<?= $totalPages ?>" title="Última página">
                    <i class="fas fa-angle-double-right"></i>
                </a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>
