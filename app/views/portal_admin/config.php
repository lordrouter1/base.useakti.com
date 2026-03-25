<?php
/**
 * Admin do Portal — Configurações
 *
 * Variáveis: $config (array key => value), $success
 */
$c = function(string $key, string $default = '') use ($config) {
    return $config[$key] ?? $default;
};
$isOn = function(string $key, string $default = '0') use ($config) {
    return ($config[$key] ?? $default) === '1';
};
?>

<div class="container-fluid px-4 py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-7">

            <!-- ═══ Header ═══ -->
            <div class="d-flex align-items-center mb-4">
                <a href="?page=portal_admin" class="btn btn-outline-secondary me-3">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-cog me-2 text-primary"></i>
                        Configurações do Portal
                    </h1>
                    <small class="text-muted">Gerencie as funcionalidades e parâmetros do portal do cliente.</small>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-1"></i> <?= e($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="?page=portal_admin&action=saveConfig">
                <?= csrf_field() ?>

                <!-- ═══ Geral ═══ -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-sliders-h me-1"></i> Geral</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="portal_enabled" value="0">
                                <input type="checkbox" name="portal_enabled" value="1"
                                       class="form-check-input" id="portal_enabled"
                                       <?= $isOn('portal_enabled', '1') ? 'checked' : '' ?>>
                                <label class="form-check-label fw-semibold" for="portal_enabled">
                                    Portal Habilitado
                                </label>
                            </div>
                            <small class="text-muted">
                                Desativar bloqueia todo o acesso ao portal do cliente.
                            </small>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-clock me-1"></i>
                                    Expiração do Link Mágico (horas)
                                </label>
                                <input type="number" name="magic_link_expiry_hours" class="form-control"
                                       value="<?= (int) ($c('magic_link_expiry_hours', '24')) ?>"
                                       min="1" max="720" style="max-width:150px;">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-hourglass-half me-1"></i>
                                    Timeout de Sessão (minutos)
                                </label>
                                <input type="number" name="session_timeout_minutes" class="form-control"
                                       value="<?= (int) ($c('session_timeout_minutes', '120')) ?>"
                                       min="5" max="10080" style="max-width:150px;">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══ Autenticação ═══ -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-shield-alt me-1"></i> Autenticação</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="require_password" value="0">
                                <input type="checkbox" name="require_password" value="1"
                                       class="form-check-input" id="require_password"
                                       <?= $isOn('require_password') ? 'checked' : '' ?>>
                                <label class="form-check-label fw-semibold" for="require_password">
                                    Exigir Senha no Login
                                </label>
                            </div>
                            <small class="text-muted">
                                Se desativado, clientes sem senha podem acessar via link mágico.
                            </small>
                        </div>

                        <div class="mb-0">
                            <div class="form-check form-switch">
                                <input type="hidden" name="allow_self_register" value="0">
                                <input type="checkbox" name="allow_self_register" value="1"
                                       class="form-check-input" id="allow_self_register"
                                       <?= $isOn('allow_self_register') ? 'checked' : '' ?>>
                                <label class="form-check-label fw-semibold" for="allow_self_register">
                                    Permitir Auto-Registro
                                </label>
                            </div>
                            <small class="text-muted">
                                Permite que novos clientes criem conta pelo portal sem convite.
                            </small>
                        </div>
                    </div>
                </div>

                <!-- ═══ Funcionalidades ═══ -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-puzzle-piece me-1"></i> Funcionalidades</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        $features = [
                            'allow_order_approval' => [
                                'label' => 'Aprovação de Orçamentos',
                                'desc'  => 'Permitir que clientes aprovem ou recusem orçamentos pelo portal.',
                                'icon'  => 'fas fa-clipboard-check',
                                'default' => '1',
                            ],
                            'allow_new_order' => [
                                'label' => 'Novo Pedido (Catálogo)',
                                'desc'  => 'Permitir que clientes criem novos pedidos/orçamentos pelo catálogo.',
                                'icon'  => 'fas fa-cart-plus',
                                'default' => '1',
                            ],
                            'allow_financial' => [
                                'label' => 'Financeiro (Parcelas)',
                                'desc'  => 'Exibir seção de parcelas e pagamentos.',
                                'icon'  => 'fas fa-wallet',
                                'default' => '1',
                            ],
                            'allow_tracking' => [
                                'label' => 'Rastreamento de Envios',
                                'desc'  => 'Exibir seção de rastreamento de pedidos.',
                                'icon'  => 'fas fa-truck',
                                'default' => '1',
                            ],
                            'allow_messages' => [
                                'label' => 'Mensagens',
                                'desc'  => 'Permitir troca de mensagens entre cliente e empresa.',
                                'icon'  => 'fas fa-comments',
                                'default' => '1',
                            ],
                            'allow_documents' => [
                                'label' => 'Documentos (NF-e)',
                                'desc'  => 'Permitir acesso a notas fiscais e documentos.',
                                'icon'  => 'fas fa-file-alt',
                                'default' => '1',
                            ],
                        ];
                        foreach ($features as $key => $feat):
                        ?>
                            <div class="<?= $key !== array_key_last($features) ? 'mb-3' : '' ?>">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="<?= $key ?>" value="0">
                                    <input type="checkbox" name="<?= $key ?>" value="1"
                                           class="form-check-input" id="<?= $key ?>"
                                           <?= $isOn($key, $feat['default']) ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-semibold" for="<?= $key ?>">
                                        <i class="<?= $feat['icon'] ?> me-1"></i>
                                        <?= $feat['label'] ?>
                                    </label>
                                </div>
                                <small class="text-muted ms-4"><?= $feat['desc'] ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- ═══ Mensagens Personalizadas ═══ -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-comment-alt me-1"></i> Mensagens Personalizadas</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-0">
                            <label class="form-label fw-semibold">
                                Nota do Novo Pedido
                            </label>
                            <textarea name="new_order_notes" class="form-control" rows="3"
                                      placeholder="Mensagem exibida ao cliente na tela de novo pedido..."
                            ><?= e($c('new_order_notes', '')) ?></textarea>
                            <small class="text-muted">
                                Exibida como alerta informativo na tela de criação de pedido.
                            </small>
                        </div>
                    </div>
                </div>

                <!-- ═══ Botões ═══ -->
                <div class="d-flex justify-content-between">
                    <a href="?page=portal_admin" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Voltar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check me-1"></i> Salvar Configurações
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>
