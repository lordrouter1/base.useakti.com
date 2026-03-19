<?php
/**
 * View: Gateways de Pagamento — Listagem
 * Exibe gateways configurados, status, e transações recentes.
 * Variáveis disponíveis via controller:
 *   $gateways           — Array de gateways do banco
 *   $availableGateways  — Array de gateways registrados no GatewayManager
 *   $recentTransactions — Últimas transações de gateway
 */
use Akti\Utils\Escape;
$e = new Escape();
?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-credit-card me-2"></i>Gateways de Pagamento</h1>
        <a href="?page=payment_gateways&action=transactions" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-list me-1"></i> Log de Transações
        </a>
    </div>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i><?= $e->html($_SESSION['flash_success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle me-2"></i><?= $e->html($_SESSION['flash_error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <!-- Gateways -->
    <div class="row g-4 mb-4">
        <?php foreach ($gateways as $gw): ?>
            <?php
            $isActive = (bool)$gw['is_active'];
            $isDefault = (bool)$gw['is_default'];
            $env = $gw['environment'] ?? 'sandbox';
            $creds = json_decode($gw['credentials'] ?? '{}', true) ?: [];
            $hasCredentials = !empty(array_filter($creds));
            ?>
            <div class="col-md-4">
                <div class="card h-100 <?= $isActive ? 'border-success' : 'border-secondary' ?>">
                    <div class="card-header d-flex justify-content-between align-items-center <?= $isActive ? 'bg-success bg-opacity-10' : '' ?>">
                        <div>
                            <h5 class="mb-0">
                                <?= $e->html($gw['display_name']) ?>
                                <?php if ($isDefault): ?>
                                    <span class="badge bg-primary ms-1">Padrão</span>
                                <?php endif; ?>
                            </h5>
                            <small class="text-muted"><?= $e->html($gw['gateway_slug']) ?></small>
                        </div>
                        <div>
                            <?php if ($isActive): ?>
                                <span class="badge bg-success"><i class="fas fa-check me-1"></i>Ativo</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><i class="fas fa-times me-1"></i>Inativo</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Ambiente -->
                        <div class="mb-2">
                            <span class="fw-bold small">Ambiente:</span>
                            <?php if ($env === 'production'): ?>
                                <span class="badge bg-danger">Produção</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Sandbox</span>
                            <?php endif; ?>
                        </div>

                        <!-- Credenciais -->
                        <div class="mb-2">
                            <span class="fw-bold small">Credenciais:</span>
                            <?php if ($hasCredentials): ?>
                                <span class="text-success"><i class="fas fa-key me-1"></i>Configuradas</span>
                            <?php else: ?>
                                <span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>Não configuradas</span>
                            <?php endif; ?>
                        </div>

                        <!-- Métodos suportados -->
                        <div class="mb-3">
                            <span class="fw-bold small d-block mb-1">Métodos:</span>
                            <?php
                            $methodLabels = \Akti\Gateways\GatewayManager::getMethodLabels();
                            // Encontrar os métodos suportados no availableGateways
                            $methods = [];
                            foreach ($availableGateways as $ag) {
                                if ($ag['slug'] === $gw['gateway_slug']) {
                                    $methods = $ag['supported_methods'];
                                    break;
                                }
                            }
                            foreach ($methods as $m):
                            ?>
                                <span class="badge bg-light text-dark border me-1"><?= $e->html($methodLabels[$m] ?? $m) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <div class="d-flex gap-2">
                            <a href="?page=payment_gateways&action=edit&id=<?= $gw['id'] ?>" class="btn btn-sm btn-primary flex-fill">
                                <i class="fas fa-cog me-1"></i> Configurar
                            </a>
                            <?php if ($isActive && $hasCredentials): ?>
                                <button type="button" class="btn btn-sm btn-outline-info" onclick="testGatewayConnection(<?= $gw['id'] ?>)">
                                    <i class="fas fa-plug"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($gateways)): ?>
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Nenhum gateway de pagamento configurado. Execute a migration SQL para criar os registros padrão.
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Transações Recentes -->
    <?php if (!empty($recentTransactions)): ?>
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>Transações Recentes</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Data</th>
                            <th>Gateway</th>
                            <th>Evento</th>
                            <th>ID Externo</th>
                            <th>Status</th>
                            <th class="text-end">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($recentTransactions, 0, 10) as $tx): ?>
                            <tr>
                                <td class="small"><?= date('d/m H:i', strtotime($tx['created_at'])) ?></td>
                                <td><span class="badge bg-secondary"><?= $e->html($tx['gateway_name'] ?? $tx['gateway_slug']) ?></span></td>
                                <td class="small"><?= $e->html($tx['event_type'] ?? '-') ?></td>
                                <td class="small text-truncate" style="max-width:150px" title="<?= $e->attr($tx['external_id'] ?? '') ?>"><?= $e->html($tx['external_id'] ?? '-') ?></td>
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
function testGatewayConnection(id) {
    Swal.fire({title: 'Testando conexão...', allowOutsideClick: false, didOpen: () => Swal.showLoading()});
    fetch(`?page=payment_gateways&action=testConnection&id=${id}`, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
        .then(r => r.json())
        .then(data => {
            Swal.fire({
                icon: data.success ? 'success' : 'error',
                title: data.success ? 'Conexão OK' : 'Falha na Conexão',
                text: data.message,
                confirmButtonColor: '#3085d6'
            });
        })
        .catch(() => Swal.fire({icon: 'error', title: 'Erro', text: 'Falha ao testar conexão.'}));
}
</script>
