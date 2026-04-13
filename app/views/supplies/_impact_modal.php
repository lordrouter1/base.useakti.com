<?php
/**
 * Partial: Modal de Impacto de Custo (Where Used)
 *
 * Exibido automaticamente quando o CMP de um insumo muda e há produtos afetados.
 * Dados vêm de $_SESSION['supply_price_impact'] preenchido pelo evento model.supply.price_changed.
 *
 * Variáveis esperadas: nenhuma (lê da sessão).
 */

if (!empty($_SESSION['supply_price_impact'])):
    $impact = $_SESSION['supply_price_impact'];
    unset($_SESSION['supply_price_impact']);
    $products = $impact['products'] ?? [];
    $supplyId = (int) ($impact['supply_id'] ?? 0);
    $oldCmp = (float) ($impact['old_cmp'] ?? 0);
    $newCmp = (float) ($impact['new_cmp'] ?? 0);
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const impactData = <?= json_encode($products, JSON_UNESCAPED_UNICODE) ?>;
    const supplyId = <?= $supplyId ?>;
    const oldCmp = <?= $oldCmp ?>;
    const newCmp = <?= $newCmp ?>;
    const csrfToken = document.querySelector('input[name="csrf_token"]')?.value
                   || document.querySelector('meta[name="csrf-token"]')?.content || '';

    if (!impactData.length) return;

    let tableRows = '';
    impactData.forEach(function(p) {
        const variation = parseFloat(p.variation || 0);
        const variationClass = variation > 0 ? 'text-danger' : (variation < 0 ? 'text-success' : '');
        const variationIcon = variation > 0 ? '↑' : (variation < 0 ? '↓' : '=');
        tableRows += `
            <tr>
                <td>${p.name || ''}</td>
                <td class="text-end">R$ ${parseFloat(p.old_cost || 0).toFixed(2)}</td>
                <td class="text-end">R$ ${parseFloat(p.new_cost || 0).toFixed(2)}</td>
                <td class="text-center ${variationClass}">${variationIcon} ${Math.abs(variation).toFixed(2)}%</td>
                <td class="text-center">${parseFloat(p.old_margin || 0).toFixed(1)}%</td>
                <td class="text-center">${parseFloat(p.new_margin || 0).toFixed(1)}%</td>
            </tr>
        `;
    });

    Swal.fire({
        title: '<i class="fas fa-exclamation-triangle text-warning me-2"></i>Impacto de Custo Detectado',
        html: `
            <div class="text-start">
                <p class="text-muted mb-3">
                    O CMP do insumo foi alterado de <strong>R$ ${oldCmp.toFixed(4)}</strong> para <strong>R$ ${newCmp.toFixed(4)}</strong>.
                    Os seguintes produtos podem ser afetados:
                </p>
                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Produto</th>
                                <th class="text-end">Custo Ant.</th>
                                <th class="text-end">Custo Novo</th>
                                <th class="text-center">Variação</th>
                                <th class="text-center">Margem Ant.</th>
                                <th class="text-center">Margem Nova</th>
                            </tr>
                        </thead>
                        <tbody>${tableRows}</tbody>
                    </table>
                </div>
            </div>
        `,
        width: 700,
        showDenyButton: true,
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-sync me-1"></i>Atualizar CMP + Custos BOM',
        denyButtonText: '<i class="fas fa-check me-1"></i>Apenas CMP (sem BOM)',
        cancelButtonText: 'Ignorar',
        confirmButtonColor: '#198754',
        denyButtonColor: '#0d6efd',
    }).then(function(result) {
        if (result.isConfirmed) {
            // Atualizar CMP + recalcular custos BOM dos produtos afetados
            const productIds = impactData.map(p => p.product_id);
            $.post('?page=supplies&action=applyBOMCostUpdate', {
                csrf_token: csrfToken,
                product_ids: productIds
            }, function(resp) {
                if (resp.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Custos BOM atualizados!',
                        text: (resp.updated || productIds.length) + ' produto(s) atualizado(s).',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Erro', text: resp.message || 'Falha ao atualizar custos.' });
                }
            }, 'json').fail(function() {
                Swal.fire({ icon: 'error', title: 'Erro', text: 'Falha na comunicação.' });
            });
        }
        // Se 'deny' ou 'cancel', apenas fechou — CMP já foi atualizado pelo service
    });
});
</script>
<?php endif; ?>
