<?php
/**
 * Tickets — Formulário de criação
 * Variáveis: $ticket (null para novo), $categories
 */
?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-headset me-2 text-primary"></i>Novo Ticket</h1>
        </div>
        <a href="?page=tickets" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="post" action="?page=tickets&action=store">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Assunto <span class="text-danger">*</span></label>
                        <input type="text" name="subject" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Categoria</label>
                        <select name="category_id" class="form-select">
                            <option value="">Sem categoria</option>
                            <?php foreach ($categories ?? [] as $cat): ?>
                            <option value="<?= (int) $cat['id'] ?>"><?= e($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Prioridade</label>
                        <select name="priority" class="form-select">
                            <option value="low">Baixa</option>
                            <option value="medium" selected>Média</option>
                            <option value="high">Alta</option>
                            <option value="urgent">Urgente</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">SLA (horas)</label>
                        <input type="number" name="sla_hours" class="form-control" value="48" min="1">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Descrição</label>
                        <textarea name="description" class="form-control" rows="5"></textarea>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Criar Ticket</button>
                </div>
            </form>
        </div>
    </div>
</div>
