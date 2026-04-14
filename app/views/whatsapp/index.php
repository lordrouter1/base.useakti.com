<?php
/**
 * WhatsApp — Painel principal
 * Variáveis: $config, $templates, $messages, $stats
 */
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<script nonce="<?= cspNonce() ?>">document.addEventListener('DOMContentLoaded',()=>{if(typeof AktiToast!=='undefined')AktiToast.success('<?= eJs($_SESSION['flash_success']) ?>');});</script>
<?php unset($_SESSION['flash_success']); endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
<script nonce="<?= cspNonce() ?>">document.addEventListener('DOMContentLoaded',()=>{if(typeof AktiToast!=='undefined')AktiToast.error('<?= eJs($_SESSION['flash_error']) ?>');});</script>
<?php unset($_SESSION['flash_error']); endif; ?>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fab fa-whatsapp me-2 text-success"></i>WhatsApp</h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">Configuração e envio de mensagens via WhatsApp.</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm text-center"><div class="card-body py-3"><i class="fas fa-paper-plane fa-2x text-success mb-2"></i><h3 class="mb-0"><?= (int) ($stats['sent'] ?? 0) ?></h3><small class="text-muted">Enviadas (30d)</small></div></div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm text-center"><div class="card-body py-3"><i class="fas fa-check-double fa-2x text-primary mb-2"></i><h3 class="mb-0"><?= (int) ($stats['delivered'] ?? 0) ?></h3><small class="text-muted">Entregues</small></div></div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm text-center"><div class="card-body py-3"><i class="fas fa-eye fa-2x text-info mb-2"></i><h3 class="mb-0"><?= (int) ($stats['read'] ?? 0) ?></h3><small class="text-muted">Lidas</small></div></div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm text-center"><div class="card-body py-3"><i class="fas fa-times-circle fa-2x text-danger mb-2"></i><h3 class="mb-0"><?= (int) ($stats['failed'] ?? 0) ?></h3><small class="text-muted">Falhas</small></div></div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Config -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><strong>Configuração da API</strong></div>
                <div class="card-body">
                    <form method="post" action="?page=whatsapp&action=saveConfig">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label">Provedor</label>
                            <select name="provider" class="form-select">
                                <?php foreach (['evolution_api' => 'Evolution API', 'z_api' => 'Z-API', 'meta_cloud' => 'Meta Cloud API'] as $k => $v): ?>
                                <option value="<?= $k ?>" <?= ($config['provider'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">URL da API</label>
                            <input type="url" name="api_url" class="form-control" value="<?= eAttr($config['api_url'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">API Key</label>
                            <input type="password" name="api_key" class="form-control" value="<?= eAttr($config['api_key'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nome da Instância</label>
                            <input type="text" name="instance_name" class="form-control" value="<?= eAttr($config['instance_name'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone Number ID (Meta)</label>
                            <input type="text" name="phone_number_id" class="form-control" value="<?= eAttr($config['phone_number_id'] ?? '') ?>">
                        </div>
                        <div class="form-check mb-3">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="chkActive" <?= !empty($config['is_active']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="chkActive">Ativo</label>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Salvar</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Envio rápido -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white"><strong>Envio Rápido</strong></div>
                <div class="card-body">
                    <form method="post" action="?page=whatsapp&action=send">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label">Telefone</label>
                            <input type="text" name="phone" class="form-control" placeholder="(00) 00000-0000" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mensagem</label>
                            <textarea name="message" class="form-control" rows="4" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-success"><i class="fab fa-whatsapp me-1"></i>Enviar</button>
                    </form>
                </div>
            </div>

            <!-- Templates -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><strong>Templates</strong></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light"><tr><th>Nome</th><th>Evento</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php if (empty($templates)): ?>
                                <tr><td colspan="3" class="text-center text-muted py-3">Nenhum template.</td></tr>
                            <?php else: ?>
                                <?php foreach ($templates as $tpl): ?>
                                <tr>
                                    <td><?= e($tpl['name']) ?></td>
                                    <td><span class="badge bg-light text-dark"><?= e($tpl['event_type']) ?></span></td>
                                    <td><?= $tpl['is_active'] ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-secondary">Inativo</span>' ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mensagens recentes -->
    <?php if (!empty($messages['data'])): ?>
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white"><strong>Mensagens Recentes</strong></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Telefone</th><th>Mensagem</th><th>Status</th><th>Data</th></tr></thead>
                    <tbody>
                    <?php foreach ($messages['data'] as $m): ?>
                        <tr>
                            <td><?= e($m['phone']) ?></td>
                            <td class="text-truncate" style="max-width:300px"><?= e($m['message']) ?></td>
                            <td>
                                <?php
                                $mColors = ['pending' => 'warning', 'sent' => 'primary', 'delivered' => 'info', 'read' => 'success', 'failed' => 'danger'];
                                ?>
                                <span class="badge bg-<?= $mColors[$m['status']] ?? 'secondary' ?>"><?= e($m['status']) ?></span>
                            </td>
                            <td><?= e(date('d/m/Y H:i', strtotime($m['created_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
