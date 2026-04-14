<?php
/**
 * Gamificação — Leaderboard/Ranking
 * Variáveis: $leaderboard, $userScore, $userAchievements
 */
?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div><h1 class="h2 mb-1"><i class="fas fa-medal me-2 text-warning"></i>Ranking da Equipe</h1></div>
        <a href="?page=achievements" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
    </div>

    <!-- Meu score -->
    <?php if (!empty($userScore)): ?>
    <div class="card border-0 shadow-sm mb-4 bg-gradient" style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);">
        <div class="card-body text-white text-center py-4">
            <h4 class="mb-1">Sua Pontuação</h4>
            <h2 class="display-4 fw-bold"><?= (int) ($userScore['total_points'] ?? 0) ?> pts</h2>
            <span class="badge bg-light text-dark fs-6">Nível <?= (int) ($userScore['level'] ?? 1) ?></span>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><strong><i class="fas fa-trophy text-warning me-2"></i>Top 20</strong></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light"><tr><th>#</th><th>Usuário</th><th>Pontos</th><th>Nível</th></tr></thead>
                            <tbody>
                            <?php if (empty($leaderboard)): ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">Nenhum ranking ainda.</td></tr>
                            <?php else: ?>
                                <?php $pos = 1; foreach ($leaderboard as $l): ?>
                                <tr>
                                    <td>
                                        <?php if ($pos <= 3): ?>
                                            <i class="fas fa-medal text-<?= $pos === 1 ? 'warning' : ($pos === 2 ? 'secondary' : 'danger') ?>"></i>
                                        <?php else: ?>
                                            <?= $pos ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e($l['user_name'] ?? 'Usuário #' . ($l['user_id'] ?? '?')) ?></td>
                                    <td><strong><?= (int) $l['total_points'] ?></strong></td>
                                    <td><span class="badge bg-primary">Nv. <?= (int) $l['level'] ?></span></td>
                                </tr>
                                <?php $pos++; endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><strong>Minhas Conquistas</strong></div>
                <div class="card-body">
                    <?php if (empty($userAchievements)): ?>
                        <p class="text-muted">Nenhuma conquista desbloqueada.</p>
                    <?php else: ?>
                        <?php foreach ($userAchievements as $ua): ?>
                        <div class="d-flex align-items-center mb-2">
                            <i class="<?= e($ua['icon'] ?? 'fas fa-trophy') ?> fa-lg text-warning me-3"></i>
                            <div>
                                <strong><?= e($ua['name']) ?></strong>
                                <small class="text-muted d-block"><?= e(date('d/m/Y', strtotime($ua['awarded_at']))) ?></small>
                            </div>
                            <span class="badge bg-primary ms-auto"><?= (int) $ua['points'] ?> pts</span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
