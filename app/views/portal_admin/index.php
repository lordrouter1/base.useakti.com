<?php
/**
 * Admin do Portal — Listagem de Acessos + Métricas
 *
 * Variáveis: $accesses, $metrics, $pendingMessages, $search, $filter
 */
$search = $search ?? ($_GET['q'] ?? '');
$filter = $filter ?? ($_GET['filter'] ?? 'all');
$successMsg = '';
if (!empty($_GET['success'])) {
    $msgs = [
        'created' => 'Acesso criado com sucesso!',
        'deleted' => 'Acesso removido com sucesso!',
    ];
    $successMsg = $msgs[$_GET['success']] ?? '';
}
?>

<div class="container-fluid px-4 py-4">
    <!-- ═══ Título ═══ -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <i class="fas fa-globe me-2 text-primary"></i>
                Portal do Cliente — Administração
            </h1>
            <p class="text-muted mb-0">Gerencie acessos, configurações e métricas do portal.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="?page=portal_admin&action=config" class="btn btn-outline-secondary">
                <i class="fas fa-cog me-1"></i> Configurações
            </a>
            <a href="?page=portal_admin&action=create" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Novo Acesso
            </a>
        </div>
    </div>

    <?php if ($successMsg): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-1"></i> <?= e($successMsg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ═══ Cards de Métricas ═══ -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-3">
                    <div class="fs-3 fw-bold text-primary"><?= (int) $metrics['total_accesses'] ?></div>
                    <small class="text-muted">Total de Acessos</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-3">
                    <div class="fs-3 fw-bold text-success"><?= (int) $metrics['active_accesses'] ?></div>
                    <small class="text-muted">Ativos</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-3">
                    <div class="fs-3 fw-bold text-info"><?= (int) $metrics['logins_last_7d'] ?></div>
                    <small class="text-muted">Logins (7 dias)</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-3">
                    <div class="fs-3 fw-bold text-<?= $pendingMessages > 0 ? 'warning' : 'secondary' ?>">
                        <?= (int) $pendingMessages ?>
                    </div>
                    <small class="text-muted">Mensagens Pendentes</small>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ Filtros ═══ -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body py-2">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex flex-wrap gap-1">
                    <?php
                    $filters = [
                        'all'      => ['label' => 'Todos',       'icon' => 'fas fa-list'],
                        'active'   => ['label' => 'Ativos',      'icon' => 'fas fa-check-circle'],
                        'inactive' => ['label' => 'Inativos',    'icon' => 'fas fa-ban'],
                        'locked'   => ['label' => 'Bloqueados',  'icon' => 'fas fa-lock'],
                        'recent'   => ['label' => 'Recentes',    'icon' => 'fas fa-clock'],
                    ];
                    foreach ($filters as $key => $f):
                    ?>
                        <a href="?page=portal_admin&filter=<?= $key ?><?= $search ? '&q=' . urlencode($search) : '' ?>"
                           class="btn btn-sm <?= $filter === $key ? 'btn-primary' : 'btn-outline-secondary' ?>">
                            <i class="<?= $f['icon'] ?> me-1"></i> <?= $f['label'] ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <form method="GET" class="d-flex gap-2" style="min-width:250px;">
                    <input type="hidden" name="page" value="portal_admin">
                    <input type="hidden" name="filter" value="<?= eAttr($filter) ?>">
                    <div class="input-group input-group-sm">
                        <input type="text" name="q" class="form-control" placeholder="Buscar por nome, e-mail..."
                               value="<?= eAttr($search) ?>">
                        <button class="btn btn-outline-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ═══ Tabela de Acessos ═══ -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <?php if (empty($accesses)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-users fa-3x mb-3 opacity-25"></i>
                    <p>Nenhum acesso encontrado.</p>
                    <a href="?page=portal_admin&action=create" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-1"></i> Criar Acesso
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40px;">#</th>
                                <th>Cliente</th>
                                <th>E-mail Portal</th>
                                <th>Status</th>
                                <th>Último Login</th>
                                <th>Tentativas</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($accesses as $acc): ?>
                                <?php
                                    $isActive = (bool) $acc['is_active'];
                                    $isLocked = !empty($acc['locked_until']) && strtotime($acc['locked_until']) > time();
                                    $lastLogin = $acc['last_login_at'] ?? null;
                                ?>
                                <tr class="<?= !$isActive ? 'table-secondary' : '' ?>">
                                    <td class="text-muted"><?= (int) $acc['id'] ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= e($acc['customer_name'] ?? '—') ?></div>
                                        <?php if (!empty($acc['customer_phone'])): ?>
                                            <small class="text-muted"><?= e($acc['customer_phone']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <code class="text-dark"><?= e($acc['email']) ?></code>
                                    </td>
                                    <td>
                                        <?php if ($isLocked): ?>
                                            <span class="badge bg-danger">
                                                <i class="fas fa-lock me-1"></i> Bloqueado
                                            </span>
                                        <?php elseif ($isActive): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i> Ativo
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-ban me-1"></i> Inativo
                                            </span>
                                        <?php endif; ?>
                                        <?php if (empty($acc['password_hash'])): ?>
                                            <span class="badge bg-warning text-dark" title="Sem senha definida">
                                                <i class="fas fa-key"></i>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($lastLogin): ?>
                                            <span title="<?= e($lastLogin) ?>">
                                                <?= date('d/m/Y H:i', strtotime($lastLogin)) ?>
                                            </span>
                                            <?php if (!empty($acc['last_login_ip'])): ?>
                                                <br><small class="text-muted"><?= e($acc['last_login_ip']) ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">Nunca</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ((int) $acc['failed_attempts'] > 0): ?>
                                            <span class="badge bg-warning text-dark">
                                                <?= (int) $acc['failed_attempts'] ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="?page=portal_admin&action=edit&id=<?= (int) $acc['id'] ?>"
                                               class="btn btn-outline-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-outline-info" title="Enviar Link Mágico"
                                                    onclick="sendMagicLink(<?= (int) $acc['id'] ?>)">
                                                <i class="fas fa-link"></i>
                                            </button>
                                            <button class="btn btn-outline-warning" title="Resetar Senha"
                                                    onclick="resetPortalPassword(<?= (int) $acc['id'] ?>)">
                                                <i class="fas fa-key"></i>
                                            </button>
                                            <button class="btn btn-outline-<?= $isActive ? 'secondary' : 'success' ?>"
                                                    title="<?= $isActive ? 'Desativar' : 'Ativar' ?>"
                                                    onclick="togglePortalAccess(<?= (int) $acc['id'] ?>, this)">
                                                <i class="fas fa-<?= $isActive ? 'ban' : 'check' ?>"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php if (!empty($accesses)): ?>
            <div class="card-footer text-muted py-2">
                <small>
                    <i class="fas fa-info-circle me-1"></i>
                    <?= count($accesses) ?> acesso(s) encontrado(s)
                    <?php if ($metrics['locked_accounts'] > 0): ?>
                        — <span class="text-danger"><?= (int) $metrics['locked_accounts'] ?> bloqueado(s)</span>
                    <?php endif; ?>
                </small>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ═══ Modal: Resultado do Magic Link ═══ -->
<div class="modal fade" id="magicLinkModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-link me-2"></i> Link Mágico</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Link gerado com sucesso:</p>
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control" id="magicLinkUrl" readonly>
                    <button class="btn btn-outline-primary" type="button"
                            onclick="navigator.clipboard.writeText(document.getElementById('magicLinkUrl').value); this.innerHTML='<i class=\'fas fa-check\'></i>';">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
                <small class="text-muted mt-2 d-block">
                    <i class="fas fa-info-circle me-1"></i>
                    Compartilhe este link com o cliente. Válido por tempo limitado.
                </small>
            </div>
        </div>
    </div>
</div>

<!-- ═══ Modal: Senha Temporária ═══ -->
<div class="modal fade" id="tempPasswordModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-key me-2"></i> Nova Senha</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Senha temporária gerada:</p>
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control fw-bold" id="tempPasswordValue" readonly>
                    <button class="btn btn-outline-primary" type="button"
                            onclick="navigator.clipboard.writeText(document.getElementById('tempPasswordValue').value); this.innerHTML='<i class=\'fas fa-check\'></i>';">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
                <small class="text-muted mt-2 d-block">
                    <i class="fas fa-exclamation-triangle me-1 text-warning"></i>
                    Envie esta senha para o cliente. Ela só será exibida uma vez.
                </small>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * Envia magic link via AJAX.
 */
function sendMagicLink(accessId) {
    if (!confirm('Gerar link mágico de acesso para este cliente?')) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    fetch('?page=portal_admin&action=sendMagicLink', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: `id=${accessId}&csrf_token=${csrfToken}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.magic_link) {
            document.getElementById('magicLinkUrl').value = data.magic_link;
            new bootstrap.Modal(document.getElementById('magicLinkModal')).show();
        } else {
            alert(data.message || 'Erro ao gerar link.');
        }
    })
    .catch(() => alert('Erro na requisição.'));
}

/**
 * Reseta senha via AJAX.
 */
function resetPortalPassword(accessId) {
    if (!confirm('Resetar a senha deste acesso? Uma nova senha temporária será gerada.')) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    fetch('?page=portal_admin&action=resetPassword', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: `id=${accessId}&csrf_token=${csrfToken}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.temp_password) {
            document.getElementById('tempPasswordValue').value = data.temp_password;
            new bootstrap.Modal(document.getElementById('tempPasswordModal')).show();
        } else {
            alert(data.message || 'Erro ao resetar senha.');
        }
    })
    .catch(() => alert('Erro na requisição.'));
}

/**
 * Toggle ativar/desativar acesso via AJAX.
 */
function togglePortalAccess(accessId, btn) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    fetch('?page=portal_admin&action=toggleAccess', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: `id=${accessId}&csrf_token=${csrfToken}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Erro ao alterar status.');
        }
    })
    .catch(() => alert('Erro na requisição.'));
}
</script>
