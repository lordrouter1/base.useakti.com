<?php
/**
 * Conquistas — Formulário
 * Variáveis: $achievement (null para novo)
 */
$isEdit = !empty($achievement);
?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div><h1 class="h2 mb-1"><i class="fas fa-trophy me-2 text-warning"></i><?= $isEdit ? 'Editar Conquista' : 'Nova Conquista' ?></h1></div>
        <a href="?page=achievements" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="post" action="?page=achievements&action=<?= $isEdit ? 'update' : 'store' ?>">
                <?= csrf_field() ?>
                <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int) $achievement['id'] ?>"><?php endif; ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nome <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?= eAttr($achievement['name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Ícone (Font Awesome)</label>
                        <input type="text" name="icon" class="form-control" value="<?= eAttr($achievement['icon'] ?? 'fas fa-trophy') ?>" placeholder="fas fa-trophy">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Pontos</label>
                        <input type="number" name="points" class="form-control" value="<?= (int) ($achievement['points'] ?? 10) ?>" min="0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Categoria</label>
                        <select name="category" class="form-select">
                            <?php foreach (['production' => 'Produção', 'quality' => 'Qualidade', 'attendance' => 'Assiduidade', 'sales' => 'Vendas', 'teamwork' => 'Trabalho em Equipe'] as $k => $v): ?>
                            <option value="<?= $k ?>" <?= ($achievement['category'] ?? 'production') === $k ? 'selected' : '' ?>><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Critério</label>
                        <input type="text" name="criteria" class="form-control" value="<?= eAttr($achievement['criteria'] ?? '') ?>" placeholder="Ex: Completar 100 pedidos">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Descrição</label>
                        <textarea name="description" class="form-control" rows="3"><?= e($achievement['description'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check mt-2">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="chkActive" <?= ($achievement['is_active'] ?? 1) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="chkActive">Ativa</label>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i><?= $isEdit ? 'Atualizar' : 'Cadastrar' ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
