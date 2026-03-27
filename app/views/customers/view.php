<!--
    Ficha Detalhada do Cliente — Fase 3 (UX Minimalista)
    Variáveis: $customer, $contacts, $stats, $recentOrders, $priceTable, $sellerName
-->
<link rel="stylesheet" href="assets/css/customers.css">

<?php
    $c = $customer;
    $isPJ = (($c['person_type'] ?? 'PF') === 'PJ');
    $statusMap = [
        'active'   => ['Ativo', 'cst-status-active', 'fa-check-circle', 'success'],
        'inactive' => ['Inativo', 'cst-status-inactive', 'fa-pause-circle', 'warning'],
        'blocked'  => ['Bloqueado', 'cst-status-blocked', 'fa-ban', 'danger'],
    ];
    $st = $c['status'] ?? 'active';
    $stInfo = $statusMap[$st] ?? $statusMap['active'];

    // Helper: formatar documento
    $doc = $c['document'] ?? '';
    $docFormatted = $doc;
    $docClean = preg_replace('/\D/', '', $doc);
    if (strlen($docClean) === 11) {
        $docFormatted = substr($docClean,0,3).'.'.substr($docClean,3,3).'.'.substr($docClean,6,3).'-'.substr($docClean,9,2);
    } elseif (strlen($docClean) === 14) {
        $docFormatted = substr($docClean,0,2).'.'.substr($docClean,2,3).'.'.substr($docClean,5,3).'/'.substr($docClean,8,4).'-'.substr($docClean,12,2);
    }

    $genderMap = ['M' => 'Masculino', 'F' => 'Feminino', 'O' => 'Outro'];
    $paymentMap = [
        'a_vista' => 'À Vista', '7_dias' => '7 dias', '14_dias' => '14 dias',
        '21_dias' => '21 dias', '30_dias' => '30 dias', '30_60' => '30/60 dias',
        '30_60_90' => '30/60/90 dias'
    ];
    $originMap = [
        'indicacao' => 'Indicação', 'google' => 'Google / Busca',
        'redes_sociais' => 'Redes Sociais', 'site' => 'Site',
        'visita' => 'Visita Presencial', 'telefone' => 'Telefone',
        'feira_evento' => 'Feira / Evento', 'outro' => 'Outro'
    ];
?>

<div class="container-fluid py-4" style="max-width:1200px;">

    <!-- ═══════ HEADER ═══════ -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <a href="?page=customers" class="btn btn-sm btn-outline-secondary" aria-label="Voltar para lista de clientes"><i class="fas fa-arrow-left"></i></a>
            <nav aria-label="Navegação">
                <ol class="breadcrumb mb-0" style="font-size:.82rem;">
                    <li class="breadcrumb-item"><a href="?page=customers">Clientes</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= e($c['name']) ?></li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="?page=customers&action=edit&id=<?= (int)$c['id'] ?>" class="btn btn-sm btn-primary" aria-label="Editar cliente">
                <i class="fas fa-edit me-1"></i>Editar
            </a>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Mais ações">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item small" href="?page=customers&action=export&format=csv&ids=<?= (int)$c['id'] ?>"><i class="fas fa-file-csv me-2 text-success"></i>Exportar CSV</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item small text-danger" href="#" onclick="deleteCustomer(<?= (int)$c['id'] ?>, '<?= addslashes(e($c['name'])) ?>')"><i class="fas fa-trash me-2"></i>Excluir</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- ═══════ HERO — Perfil do Cliente ═══════ -->
    <div class="cst-profile-hero mb-4">
        <div class="row align-items-center">
            <div class="col-auto">
                <?php if (!empty($c['photo'])): ?>
                    <img src="<?= eAttr($c['photo']) ?>" alt="Foto de <?= eAttr($c['name']) ?>" class="cst-profile-avatar">
                <?php else: ?>
                    <div class="cst-profile-avatar-placeholder" aria-label="Avatar">
                        <?= strtoupper(mb_substr($c['name'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col">
                <h2 class="mb-1" style="font-size:1.4rem;font-weight:700;"><?= e($c['name']) ?></h2>
                <div class="d-flex flex-wrap align-items-center gap-2" style="font-size:.82rem;">
                    <span class="badge <?= $isPJ ? 'bg-info bg-opacity-10 text-info' : 'bg-secondary bg-opacity-10 text-secondary' ?>" style="font-size:.72rem;">
                        <i class="fas <?= $isPJ ? 'fa-building' : 'fa-user' ?> me-1"></i><?= $isPJ ? 'Pessoa Jurídica' : 'Pessoa Física' ?>
                    </span>
                    <span class="badge <?= $stInfo[1] ?>" style="font-size:.72rem;padding:.35em .7em;border-radius:6px;">
                        <i class="fas <?= $stInfo[2] ?> me-1"></i><?= $stInfo[0] ?>
                    </span>
                    <?php if (!empty($c['code'])): ?>
                        <span class="text-muted"><i class="fas fa-barcode me-1"></i><?= e($c['code']) ?></span>
                    <?php endif; ?>
                    <?php if ($docFormatted): ?>
                        <span class="text-muted"><i class="fas fa-id-card me-1"></i><?= e($docFormatted) ?></span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($c['fantasy_name'])): ?>
                    <div class="text-muted mt-1" style="font-size:.78rem;">
                        <i class="fas fa-tag me-1"></i><?= e($c['fantasy_name']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ═══════ STAT CARDS ═══════ -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="cst-profile-stat-card">
                <div class="stat-value text-primary"><?= (int)($stats['total_orders'] ?? 0) ?></div>
                <div class="stat-label"><i class="fas fa-shopping-cart me-1"></i>Pedidos</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="cst-profile-stat-card">
                <div class="stat-value text-success">R$ <?= number_format($stats['total_value'] ?? 0, 2, ',', '.') ?></div>
                <div class="stat-label"><i class="fas fa-money-bill-wave me-1"></i>Total</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="cst-profile-stat-card">
                <div class="stat-value text-info">
                    <?= !empty($stats['last_order_date']) ? date('d/m/Y', strtotime($stats['last_order_date'])) : '—' ?>
                </div>
                <div class="stat-label"><i class="fas fa-calendar-alt me-1"></i>Último Pedido</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="cst-profile-stat-card">
                <div class="stat-value" style="color:#9b59b6;">R$ <?= number_format($stats['avg_ticket'] ?? 0, 2, ',', '.') ?></div>
                <div class="stat-label"><i class="fas fa-receipt me-1"></i>Ticket Médio</div>
            </div>
        </div>
    </div>

    <!-- ═══════ TABS ═══════ -->
    <ul class="nav nav-tabs mb-0" id="viewTabs" role="tablist" style="font-size:.82rem;">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-dados" data-bs-toggle="tab" data-bs-target="#pane-dados" type="button" role="tab" aria-controls="pane-dados" aria-selected="true">
                <i class="fas fa-id-card me-1"></i>Dados
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-contato" data-bs-toggle="tab" data-bs-target="#pane-contato" type="button" role="tab" aria-controls="pane-contato" aria-selected="false">
                <i class="fas fa-phone me-1"></i>Contato
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-endereco" data-bs-toggle="tab" data-bs-target="#pane-endereco" type="button" role="tab" aria-controls="pane-endereco" aria-selected="false">
                <i class="fas fa-map-marker-alt me-1"></i>Endereço
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-comercial" data-bs-toggle="tab" data-bs-target="#pane-comercial" type="button" role="tab" aria-controls="pane-comercial" aria-selected="false">
                <i class="fas fa-briefcase me-1"></i>Comercial
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-historico" data-bs-toggle="tab" data-bs-target="#pane-historico" type="button" role="tab" aria-controls="pane-historico" aria-selected="false">
                <i class="fas fa-history me-1"></i>Histórico
            </button>
        </li>
    </ul>

    <div class="tab-content border border-top-0 bg-white" style="border-radius:0 0 var(--cst-radius) var(--cst-radius);">

        <!-- TAB: Dados de Identificação -->
        <div class="tab-pane fade show active p-4" id="pane-dados" role="tabpanel" aria-labelledby="tab-dados">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="cst-form-section" style="margin-bottom:0;">
                        <div class="cst-form-section-title">
                            <i class="fas fa-id-card" style="background:rgba(52,152,219,.1);color:#3498db;"></i>
                            Identificação
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label text-muted" style="font-size:.72rem;">Tipo</label>
                                <p class="mb-0 fw-bold" style="font-size:.85rem;"><?= $isPJ ? 'Pessoa Jurídica' : 'Pessoa Física' ?></p>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted" style="font-size:.72rem;"><?= $isPJ ? 'CNPJ' : 'CPF' ?></label>
                                <p class="mb-0 fw-bold" style="font-size:.85rem;"><?= $docFormatted ?: '—' ?></p>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted" style="font-size:.72rem;"><?= $isPJ ? 'Inscrição Estadual' : 'RG' ?></label>
                                <p class="mb-0" style="font-size:.85rem;"><?= e($c['rg_ie'] ?? '') ?: '—' ?></p>
                            </div>
                            <?php if ($isPJ): ?>
                            <div class="col-6">
                                <label class="form-label text-muted" style="font-size:.72rem;">Inscrição Municipal</label>
                                <p class="mb-0" style="font-size:.85rem;"><?= e($c['im'] ?? '') ?: '—' ?></p>
                            </div>
                            <?php endif; ?>
                            <div class="col-6">
                                <label class="form-label text-muted" style="font-size:.72rem;"><?= $isPJ ? 'Data de Fundação' : 'Data de Nascimento' ?></label>
                                <p class="mb-0" style="font-size:.85rem;">
                                    <?= !empty($c['birth_date']) ? date('d/m/Y', strtotime($c['birth_date'])) : '—' ?>
                                </p>
                            </div>
                            <?php if (!$isPJ && !empty($c['gender'])): ?>
                            <div class="col-6">
                                <label class="form-label text-muted" style="font-size:.72rem;">Gênero</label>
                                <p class="mb-0" style="font-size:.85rem;"><?= $genderMap[$c['gender']] ?? '—' ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Tags + Observações -->
                <div class="col-md-6">
                    <?php if (!empty($c['tags'])): ?>
                    <div class="cst-form-section">
                        <div class="cst-form-section-title">
                            <i class="fas fa-tags" style="background:rgba(155,89,182,.1);color:#9b59b6;"></i>
                            Tags
                        </div>
                        <div class="d-flex flex-wrap gap-1">
                            <?php foreach (explode(',', $c['tags']) as $tag): $tag = trim($tag); if ($tag): ?>
                                <span class="cst-tag"><?= e($tag) ?></span>
                            <?php endif; endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($c['observations'])): ?>
                    <div class="cst-form-section" style="margin-bottom:0;">
                        <div class="cst-form-section-title">
                            <i class="fas fa-sticky-note" style="background:rgba(243,156,18,.1);color:#f39c12;"></i>
                            Observações
                        </div>
                        <p class="mb-0" style="font-size:.85rem;line-height:1.6;white-space:pre-wrap;"><?= e($c['observations']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- TAB: Contato -->
        <div class="tab-pane fade p-4" id="pane-contato" role="tabpanel" aria-labelledby="tab-contato">
            <div class="cst-form-section" style="margin-bottom:0;">
                <div class="cst-form-section-title">
                    <i class="fas fa-address-book" style="background:rgba(39,174,96,.1);color:#27ae60;"></i>
                    Informações de Contato
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted" style="font-size:.72rem;">E-mail Principal</label>
                        <p class="mb-0" style="font-size:.85rem;">
                            <?php if (!empty($c['email'])): ?>
                                <a href="mailto:<?= eAttr($c['email']) ?>"><i class="fas fa-envelope me-1"></i><?= e($c['email']) ?></a>
                            <?php else: ?>—<?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted" style="font-size:.72rem;">E-mail Secundário</label>
                        <p class="mb-0" style="font-size:.85rem;">
                            <?= !empty($c['email_secondary']) ? '<a href="mailto:'.eAttr($c['email_secondary']).'"><i class="fas fa-envelope me-1"></i>'.e($c['email_secondary']).'</a>' : '—' ?>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted" style="font-size:.72rem;">Telefone Fixo</label>
                        <p class="mb-0" style="font-size:.85rem;"><i class="fas fa-phone me-1 text-muted"></i><?= e($c['phone'] ?? '') ?: '—' ?></p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted" style="font-size:.72rem;">Celular / WhatsApp</label>
                        <p class="mb-0" style="font-size:.85rem;">
                            <?php if (!empty($c['cellphone'])): ?>
                                <i class="fab fa-whatsapp me-1 text-success"></i>
                                <a href="https://wa.me/55<?= preg_replace('/\D/', '', $c['cellphone']) ?>" target="_blank" title="Abrir WhatsApp"><?= e($c['cellphone']) ?></a>
                            <?php else: ?>—<?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted" style="font-size:.72rem;">Telefone Comercial</label>
                        <p class="mb-0" style="font-size:.85rem;"><i class="fas fa-building me-1 text-muted"></i><?= e($c['phone_commercial'] ?? '') ?: '—' ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted" style="font-size:.72rem;">Website</label>
                        <p class="mb-0" style="font-size:.85rem;">
                            <?php if (!empty($c['website'])): ?>
                                <a href="<?= eAttr($c['website']) ?>" target="_blank"><i class="fas fa-globe me-1"></i><?= e($c['website']) ?></a>
                            <?php else: ?>—<?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted" style="font-size:.72rem;">Instagram</label>
                        <p class="mb-0" style="font-size:.85rem;">
                            <?php if (!empty($c['instagram'])): ?>
                                <a href="https://instagram.com/<?= eAttr($c['instagram']) ?>" target="_blank"><i class="fab fa-instagram me-1 text-danger"></i>@<?= e($c['instagram']) ?></a>
                            <?php else: ?>—<?php endif; ?>
                        </p>
                    </div>
                </div>

                <?php if ($isPJ && (!empty($c['contact_name']) || !empty($c['contact_role']))): ?>
                <hr class="my-3">
                <div class="cst-form-section-title" style="margin-bottom:.5rem;">
                    <i class="fas fa-user-tie" style="background:rgba(23,162,184,.1);color:#17a2b8;"></i>
                    Contato Principal (PJ)
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted" style="font-size:.72rem;">Nome</label>
                        <p class="mb-0" style="font-size:.85rem;"><?= e($c['contact_name'] ?? '') ?: '—' ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted" style="font-size:.72rem;">Cargo</label>
                        <p class="mb-0" style="font-size:.85rem;"><?= e($c['contact_role'] ?? '') ?: '—' ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($contacts)): ?>
                <hr class="my-3">
                <div class="cst-form-section-title" style="margin-bottom:.5rem;">
                    <i class="fas fa-users" style="background:rgba(52,152,219,.1);color:#3498db;"></i>
                    Contatos Adicionais
                </div>
                <div class="row g-3">
                    <?php foreach ($contacts as $contact): ?>
                    <div class="col-md-6">
                        <div class="border rounded p-3" style="border-radius:var(--cst-radius-sm) !important;">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <strong style="font-size:.85rem;"><?= e($contact['name']) ?></strong>
                                <?php if (!empty($contact['is_primary'])): ?>
                                    <span class="badge bg-primary" style="font-size:.6rem;">Principal</span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($contact['role'])): ?>
                                <div class="text-muted" style="font-size:.75rem;"><i class="fas fa-briefcase me-1"></i><?= e($contact['role']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($contact['email'])): ?>
                                <div style="font-size:.78rem;"><a href="mailto:<?= eAttr($contact['email']) ?>"><i class="fas fa-envelope me-1"></i><?= e($contact['email']) ?></a></div>
                            <?php endif; ?>
                            <?php if (!empty($contact['phone'])): ?>
                                <div style="font-size:.78rem;"><i class="fas fa-phone me-1 text-muted"></i><?= e($contact['phone']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($contact['notes'])): ?>
                                <div class="text-muted mt-1" style="font-size:.72rem;"><?= e($contact['notes']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- TAB: Endereço -->
        <div class="tab-pane fade p-4" id="pane-endereco" role="tabpanel" aria-labelledby="tab-endereco">
            <div class="cst-form-section" style="margin-bottom:0;">
                <div class="cst-form-section-title">
                    <i class="fas fa-map-marker-alt" style="background:rgba(243,156,18,.1);color:#f39c12;"></i>
                    Endereço
                </div>
                <?php
                $hasAddress = !empty($c['address_street']) || !empty($c['zipcode']);
                if ($hasAddress):
                ?>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label text-muted" style="font-size:.72rem;">CEP</label>
                        <p class="mb-0 fw-bold" style="font-size:.85rem;"><?= e($c['zipcode'] ?? '') ?: '—' ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted" style="font-size:.72rem;">Logradouro</label>
                        <p class="mb-0" style="font-size:.85rem;"><?= e($c['address_street'] ?? '') ?: '—' ?></p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted" style="font-size:.72rem;">Número</label>
                        <p class="mb-0" style="font-size:.85rem;"><?= e($c['address_number'] ?? '') ?: '—' ?></p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted" style="font-size:.72rem;">Complemento</label>
                        <p class="mb-0" style="font-size:.85rem;"><?= e($c['address_complement'] ?? '') ?: '—' ?></p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted" style="font-size:.72rem;">Bairro</label>
                        <p class="mb-0" style="font-size:.85rem;"><?= e($c['address_neighborhood'] ?? '') ?: '—' ?></p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted" style="font-size:.72rem;">Cidade / UF</label>
                        <p class="mb-0" style="font-size:.85rem;">
                            <?= e($c['address_city'] ?? '') ?>
                            <?php if (!empty($c['address_state'])): ?> / <?= e($c['address_state']) ?><?php endif; ?>
                            <?php if (empty($c['address_city']) && empty($c['address_state'])): ?>—<?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted" style="font-size:.72rem;">País</label>
                        <p class="mb-0" style="font-size:.85rem;"><?= e($c['address_country'] ?? 'Brasil') ?></p>
                    </div>
                    <?php if (!empty($c['address_ibge'])): ?>
                    <div class="col-md-4">
                        <label class="form-label text-muted" style="font-size:.72rem;">Código IBGE</label>
                        <p class="mb-0" style="font-size:.85rem;"><?= e($c['address_ibge']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-map-marker-alt fa-2x mb-2 d-block" style="opacity:.3;"></i>
                    <p class="mb-0">Endereço não informado.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- TAB: Comercial -->
        <div class="tab-pane fade p-4" id="pane-comercial" role="tabpanel" aria-labelledby="tab-comercial">
            <div class="cst-form-section" style="margin-bottom:0;">
                <div class="cst-form-section-title">
                    <i class="fas fa-briefcase" style="background:rgba(155,89,182,.1);color:#9b59b6;"></i>
                    Dados Comerciais
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted" style="font-size:.72rem;">Tabela de Preço</label>
                        <p class="mb-0" style="font-size:.85rem;"><?= e($priceTable['name'] ?? '') ?: 'Padrão' ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted" style="font-size:.72rem;">Condição de Pagamento</label>
                        <p class="mb-0" style="font-size:.85rem;"><?= $paymentMap[$c['payment_term'] ?? ''] ?? (e($c['payment_term'] ?? '') ?: '—') ?></p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted" style="font-size:.72rem;">Limite de Crédito</label>
                        <p class="mb-0 fw-bold" style="font-size:.85rem;">
                            <?= ($c['credit_limit'] !== null && $c['credit_limit'] !== '') ? 'R$ ' . number_format((float)$c['credit_limit'], 2, ',', '.') : '—' ?>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted" style="font-size:.72rem;">Desconto Padrão</label>
                        <p class="mb-0" style="font-size:.85rem;">
                            <?= ($c['discount_default'] !== null && $c['discount_default'] !== '') ? number_format((float)$c['discount_default'], 2, ',', '.') . '%' : '—' ?>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted" style="font-size:.72rem;">Vendedor Responsável</label>
                        <p class="mb-0" style="font-size:.85rem;"><?= e($sellerName ?? '') ?: '—' ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted" style="font-size:.72rem;">Origem do Cliente</label>
                        <p class="mb-0" style="font-size:.85rem;"><?= $originMap[$c['origin'] ?? ''] ?? (e($c['origin'] ?? '') ?: '—') ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB: Histórico de Pedidos (Fase 4 — Paginação AJAX) -->
        <div class="tab-pane fade p-4" id="pane-historico" role="tabpanel" aria-labelledby="tab-historico">
            <div class="cst-form-section" style="margin-bottom:0;">
                <div class="cst-form-section-title d-flex justify-content-between align-items-center">
                    <span>
                        <i class="fas fa-history" style="background:rgba(52,152,219,.1);color:#3498db;"></i>
                        Histórico de Pedidos
                    </span>
                    <span id="order-history-total" class="badge bg-secondary bg-opacity-10 text-muted" style="font-size:.72rem;"></span>
                </div>

                <div id="order-history-container">
                    <?php if (!empty($recentOrders)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size:.82rem;">
                            <thead class="table-light">
                                <tr>
                                    <th># Pedido</th>
                                    <th>Data</th>
                                    <th>Valor</th>
                                    <th>Status</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="order-history-tbody">
                                <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td><a href="?page=orders&action=edit&id=<?= (int)$order['id'] ?>" class="fw-bold">#<?= (int)$order['id'] ?></a></td>
                                    <td><?= !empty($order['created_at']) ? date('d/m/Y', strtotime($order['created_at'])) : '—' ?></td>
                                    <td class="fw-bold">R$ <?= number_format($order['total_amount'] ?? 0, 2, ',', '.') ?></td>
                                    <td><span class="badge bg-secondary bg-opacity-25 text-dark" style="font-size:.68rem;"><?= e($order['status'] ?? '') ?></span></td>
                                    <td class="text-end">
                                        <a href="?page=orders&action=edit&id=<?= (int)$order['id'] ?>" class="btn btn-sm btn-outline-info" aria-label="Ver pedido"><i class="fas fa-eye"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4 text-muted" id="order-history-empty">
                        <i class="fas fa-shopping-cart fa-2x mb-2 d-block" style="opacity:.3;"></i>
                        <p class="mb-0">Nenhum pedido encontrado para este cliente.</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Paginação AJAX -->
                <div id="order-history-pagination" class="d-flex justify-content-between align-items-center mt-3" style="display:none!important;">
                    <small class="text-muted" id="order-history-info"></small>
                    <nav aria-label="Paginação do histórico">
                        <ul class="pagination pagination-sm mb-0" id="order-history-pages"></ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════ AUDITORIA ═══════ -->
    <div class="card border-0 mt-3" style="background:var(--cst-bg);border-radius:var(--cst-radius);">
        <div class="card-body py-2 px-3 d-flex flex-wrap gap-3" style="font-size:.72rem;color:var(--cst-muted);">
            <span>
                <i class="fas fa-user-plus me-1"></i>Cadastrado em:
                <strong><?= !empty($c['created_at']) ? date('d/m/Y H:i', strtotime($c['created_at'])) : '—' ?></strong>
            </span>
            <?php if (!empty($c['updated_at'])): ?>
            <span>
                <i class="fas fa-edit me-1"></i>Atualizado em:
                <strong><?= date('d/m/Y H:i', strtotime($c['updated_at'])) ?></strong>
            </span>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Delete helper -->
<script>
function deleteCustomer(id, name) {
    if (typeof Swal === 'undefined') { if (confirm('Excluir ' + name + '?')) { /* fallback */ } return; }
    Swal.fire({
        title: 'Excluir cliente?',
        html: 'Deseja realmente excluir <strong>' + name + '</strong>?<br><small class="text-muted">O registro será inativado (soft delete).</small>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#c0392b',
        cancelButtonColor: '#95a5a6',
        confirmButtonText: '<i class="fas fa-trash me-1"></i> Sim, excluir',
        cancelButtonText: 'Cancelar'
    }).then(function(result) {
        if (result.isConfirmed) {
            var csrfMeta = document.querySelector('meta[name="csrf-token"]');
            var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
            var fd = new FormData();
            fd.append('id', id);
            fetch('?page=customers&action=delete', { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': csrfToken } })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        Swal.fire({ icon: 'success', title: 'Excluído', text: 'Cliente excluído com sucesso.', timer: 1500, showConfirmButton: false })
                            .then(function() { window.location.href = '?page=customers'; });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Erro', text: data.message || 'Não foi possível excluir.' });
                    }
                })
                .catch(function() { Swal.fire({ icon: 'error', title: 'Erro de comunicação' }); });
        }
    });
}

// ─── Histórico de Pedidos — Paginação AJAX (Fase 4) ───
(function() {
    var customerId = <?= (int)($customer['id'] ?? 0) ?>;
    var currentPage = 1;
    var perPage = 10;

    function loadOrders(page) {
        currentPage = page || 1;
        var tbody = document.getElementById('order-history-tbody');
        var container = document.getElementById('order-history-container');
        var pagination = document.getElementById('order-history-pagination');
        var info = document.getElementById('order-history-info');
        var pages = document.getElementById('order-history-pages');
        var totalBadge = document.getElementById('order-history-total');
        var empty = document.getElementById('order-history-empty');

        if (!container) return;

        // Loading
        if (tbody) tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3"><i class="fas fa-spinner fa-spin me-1"></i>Carregando...</td></tr>';

        fetch('?page=customers&action=getOrderHistory&id=' + customerId + '&page_num=' + currentPage + '&per_page=' + perPage)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success || !data.orders || data.orders.length === 0) {
                    if (empty) { empty.style.display = ''; }
                    if (tbody) tbody.innerHTML = '';
                    if (pagination) pagination.style.display = 'none';
                    if (totalBadge) totalBadge.textContent = '0 pedidos';
                    return;
                }

                if (empty) empty.style.display = 'none';
                if (totalBadge) totalBadge.textContent = data.total + ' pedido(s)';

                // Garantir que a tabela existe
                if (!tbody) {
                    container.innerHTML = '<div class="table-responsive"><table class="table table-hover align-middle mb-0" style="font-size:.82rem;">' +
                        '<thead class="table-light"><tr><th># Pedido</th><th>Data</th><th>Valor</th><th>Status</th><th class="text-end">Ações</th></tr></thead>' +
                        '<tbody id="order-history-tbody"></tbody></table></div>';
                    tbody = document.getElementById('order-history-tbody');
                }

                var html = '';
                data.orders.forEach(function(order) {
                    html += '<tr>' +
                        '<td><a href="?page=orders&action=edit&id=' + order.id + '" class="fw-bold">#' + order.id + '</a></td>' +
                        '<td>' + order.created_at + '</td>' +
                        '<td class="fw-bold">R$ ' + order.total_amount + '</td>' +
                        '<td><span class="badge bg-secondary bg-opacity-25 text-dark" style="font-size:.68rem;">' + (order.status || '') + '</span></td>' +
                        '<td class="text-end"><a href="?page=orders&action=edit&id=' + order.id + '" class="btn btn-sm btn-outline-info" aria-label="Ver pedido"><i class="fas fa-eye"></i></a></td>' +
                        '</tr>';
                });
                tbody.innerHTML = html;

                // Paginação
                if (data.total_pages > 1) {
                    pagination.style.display = '';
                    pagination.style.cssText = 'display:flex!important;';

                    var start = ((currentPage - 1) * perPage) + 1;
                    var end = Math.min(currentPage * perPage, data.total);
                    if (info) info.textContent = 'Exibindo ' + start + '-' + end + ' de ' + data.total;

                    var pHtml = '';
                    pHtml += '<li class="page-item ' + (currentPage <= 1 ? 'disabled' : '') + '">' +
                        '<a class="page-link" href="#" data-page="' + (currentPage - 1) + '" aria-label="Anterior">‹</a></li>';
                    for (var p = 1; p <= data.total_pages; p++) {
                        pHtml += '<li class="page-item ' + (p === currentPage ? 'active' : '') + '">' +
                            '<a class="page-link" href="#" data-page="' + p + '">' + p + '</a></li>';
                    }
                    pHtml += '<li class="page-item ' + (currentPage >= data.total_pages ? 'disabled' : '') + '">' +
                        '<a class="page-link" href="#" data-page="' + (currentPage + 1) + '" aria-label="Próxima">›</a></li>';
                    pages.innerHTML = pHtml;

                    // Bind clicks
                    pages.querySelectorAll('a[data-page]').forEach(function(a) {
                        a.addEventListener('click', function(e) {
                            e.preventDefault();
                            var pg = parseInt(this.getAttribute('data-page'));
                            if (pg >= 1 && pg <= data.total_pages) loadOrders(pg);
                        });
                    });
                } else {
                    pagination.style.display = 'none';
                }
            })
            .catch(function() {
                if (tbody) tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-3"><i class="fas fa-exclamation-circle me-1"></i>Erro ao carregar pedidos.</td></tr>';
            });
    }

    // Carregar ao clicar na tab de histórico
    var tabHistorico = document.getElementById('tab-historico');
    if (tabHistorico) {
        tabHistorico.addEventListener('shown.bs.tab', function() {
            loadOrders(1);
        });
    }
})();
</script>
