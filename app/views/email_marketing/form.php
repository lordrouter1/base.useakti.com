<?php
/**
 * E-mail Marketing — Formulário de campanha
 * FEAT-013
 * Variáveis: $campaign (null = nova), $templates, $stats (edit mode)
 */
$isEdit = !empty($campaign);
$c = $campaign ?? [];
?>

<div class="container-fluid py-3">

    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-envelope me-2 text-primary"></i><?= $isEdit ? 'Editar Campanha' : 'Nova Campanha' ?></h1>
        </div>
        <a href="?page=email_marketing" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
    </div>

    <?php if ($isEdit && !empty($stats)): ?>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-primary border-4">
                <div class="card-body p-3 text-center">
                    <div class="text-muted small">Enviados</div>
                    <div class="fw-bold fs-4"><?= (int) ($stats['sent'] ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-success border-4">
                <div class="card-body p-3 text-center">
                    <div class="text-muted small">Abertos</div>
                    <div class="fw-bold fs-4"><?= (int) ($stats['opened'] ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-info border-4">
                <div class="card-body p-3 text-center">
                    <div class="text-muted small">Clicados</div>
                    <div class="fw-bold fs-4"><?= (int) ($stats['clicked'] ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-danger border-4">
                <div class="card-body p-3 text-center">
                    <div class="text-muted small">Bounced</div>
                    <div class="fw-bold fs-4"><?= (int) ($stats['bounced'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="post" action="?page=email_marketing&action=<?= $isEdit ? 'update' : 'store' ?>">
                <?= csrf_field() ?>
                <?php if ($isEdit): ?>
                    <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                <?php endif; ?>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Nome da Campanha <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?= eAttr($c['name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Template</label>
                        <select name="template_id" class="form-select">
                            <option value="">Nenhum</option>
                            <?php foreach ($templates ?? [] as $t): ?>
                            <option value="<?= (int) $t['id'] ?>" <?= ($c['template_id'] ?? 0) == $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Agendamento</label>
                        <input type="datetime-local" name="scheduled_at" class="form-control" value="<?= eAttr($c['scheduled_at'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Assunto <span class="text-danger">*</span></label>
                        <input type="text" name="subject" class="form-control" value="<?= eAttr($c['subject'] ?? '') ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Conteúdo HTML</label>
                        <textarea name="body_html" class="form-control" rows="10"><?= e($c['body_html'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Salvar</button>
                    <a href="?page=email_marketing" class="btn btn-outline-secondary ms-2">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
