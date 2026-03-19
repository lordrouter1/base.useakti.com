<?php
/**
 * View: Gateways de Pagamento — Log de Transações
 * Variáveis disponíveis via controller:
 *   $transactions — Array de transações recentes
 */
use Akti\Utils\Escape;
$e = new Escape();
?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <a href="?page=payment_gateways" class="text-decoration-none text-muted me-2"><i class="fas fa-arrow-left"></i></a>
            <i class="fas fa-list me-2"></i>Log de Transações de Gateway
        </h1>
    </div>

    <?php if (empty($transactions)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>Nenhuma transação de gateway registrada ainda.
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Data</th>
                                <th>Gateway</th>
                                <th>Evento</th>
                                <th>ID Externo</th>
                                <th>Pedido</th>
                                <th>Parcela</th>
                                <th>Método</th>
                                <th>Status</th>
                                <th class="text-end">Valor</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $tx): ?>
                                <tr>
                                    <td class="small text-muted"><?= (int)$tx['id'] ?></td>
                                    <td class="small"><?= date('d/m/Y H:i', strtotime($tx['created_at'])) ?></td>
                                    <td><span class="badge bg-secondary"><?= $e->html($tx['gateway_name'] ?? $tx['gateway_slug']) ?></span></td>
                                    <td class="small"><?= $e->html($tx['event_type'] ?? '-') ?></td>
                                    <td class="small text-truncate" style="max-width:120px" title="<?= $e->attr($tx['external_id'] ?? '') ?>">
                                        <?= $e->html($tx['external_id'] ?? '-') ?>
                                    </td>
                                    <td>
                                        <?php if ($tx['order_id']): ?>
                                            <a href="?page=orders&action=edit&id=<?= (int)$tx['order_id'] ?>" class="text-decoration-none">#<?= (int)$tx['order_id'] ?></a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($tx['installment_id']): ?>
                                            <span class="badge bg-light text-dark border">#<?= (int)$tx['installment_id'] ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small"><?= $e->html($tx['payment_method_type'] ?? '-') ?></td>
                                    <td>
                                        <?php
                                        $statusBadge = match ($tx['external_status'] ?? '') {
                                            'approved' => 'bg-success',
                                            'pending' => 'bg-warning text-dark',
                                            'rejected' => 'bg-danger',
                                            'refunded' => 'bg-info',
                                            'cancelled' => 'bg-dark',
                                            default => 'bg-secondary',
                                        };
                                        ?>
                                        <span class="badge <?= $statusBadge ?>"><?= $e->html($tx['external_status'] ?? '-') ?></span>
                                    </td>
                                    <td class="text-end fw-bold">R$ <?= number_format((float)($tx['amount'] ?? 0), 2, ',', '.') ?></td>
                                    <td>
                                        <?php if (!empty($tx['raw_payload'])): ?>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                    title="Ver payload"
                                                    onclick="showPayload(<?= (int)$tx['id'] ?>)">
                                                <i class="fas fa-code"></i>
                                            </button>
                                            <pre id="payload-<?= (int)$tx['id'] ?>" class="d-none"><?= $e->html($tx['raw_payload']) ?></pre>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function showPayload(id) {
    const pre = document.getElementById('payload-' + id);
    if (!pre) return;
    let raw = pre.textContent;
    try { raw = JSON.stringify(JSON.parse(raw), null, 2); } catch(e) {}
    Swal.fire({
        title: 'Payload #' + id,
        html: '<pre class="text-start small" style="max-height:400px;overflow:auto">' + raw + '</pre>',
        width: '700px',
        confirmButtonColor: '#3085d6'
    });
}
</script>
