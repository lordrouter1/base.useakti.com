<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center pt-2 pb-2 mb-4 border-bottom">
        <h1 class="h2 mb-0"><i class="fas fa-sliders-h me-2"></i>Metas da Linha de Produção</h1>
        <a href="?page=pipeline" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Voltar</a>
    </div>

    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary p-3">
                    <h5 class="mb-0 text-white"><i class="fas fa-clock me-2"></i>Tempo Máximo por Etapa (em horas)</h5>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted small mb-4">
                        <i class="fas fa-info-circle me-1"></i>
                        Defina o tempo máximo (em horas) que um pedido pode permanecer em cada etapa antes de ser considerado <strong class="text-danger">atrasado</strong>. 
                        Coloque <strong>0</strong> para desativar o alerta em uma etapa.
                    </p>

                    <form method="POST" action="?page=pipeline&action=saveSettings">
                        <?= csrf_field() ?>
                        <?php foreach ($stages as $stageKey => $stageInfo): ?>
                        <?php $currentGoal = isset($goals[$stageKey]) ? (int)$goals[$stageKey]['max_hours'] : 24; ?>
                        <div class="row align-items-center mb-3 py-2 px-3 rounded" style="background: rgba(0,0,0,0.02);">
                            <div class="col-md-1 text-center">
                                <div class="rounded-circle d-inline-flex align-items-center justify-content-center" 
                                     style="width:36px;height:36px;background:<?= $stageInfo['color'] ?>;color:#fff;">
                                    <i class="<?= $stageInfo['icon'] ?>" style="font-size:0.85rem;"></i>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <span class="fw-bold"><?= $stageInfo['label'] ?></span>
                                <?php if ($stageKey === 'concluido'): ?>
                                    <span class="text-muted small ms-1">(etapa final)</span>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group">
                                    <input type="number" class="form-control" name="max_hours[<?= $stageKey ?>]" 
                                           value="<?= $currentGoal ?>" min="0" step="1">
                                    <span class="input-group-text">horas</span>
                                </div>
                            </div>
                            <div class="col-md-2 text-center">
                                <?php if ($currentGoal > 0): ?>
                                <span class="small text-muted">
                                    ≈ <?= round($currentGoal / 24, 1) ?> dia(s)
                                </span>
                                <?php else: ?>
                                <span class="small text-muted">Desativado</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <div class="text-end mt-4 pt-3 border-top">
                            <a href="?page=pipeline" class="btn btn-secondary me-2">Cancelar</a>
                            <button type="submit" class="btn btn-primary fw-bold"><i class="fas fa-save me-2"></i>Salvar Metas</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Legenda -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body p-4">
                    <h6 class="text-primary fw-bold mb-3"><i class="fas fa-info-circle me-2"></i>Sobre as Etapas</h6>
                    <div class="row g-3">
                        <?php foreach ($stages as $sKey => $sInfo): ?>
                        <div class="col-md-6">
                            <div class="d-flex align-items-start">
                                <div class="rounded-circle d-flex align-items-center justify-content-center me-2 flex-shrink-0" 
                                     style="width:28px;height:28px;background:<?= $sInfo['color'] ?>;color:#fff;font-size:0.7rem;">
                                    <i class="<?= $sInfo['icon'] ?>"></i>
                                </div>
                                <div>
                                    <div class="fw-bold small"><?= $sInfo['label'] ?></div>
                                    <div class="text-muted" style="font-size:0.75rem;">
                                        <?php
                                        $descriptions = [
                                            'contato'    => 'Primeiro contato com o cliente, entendimento da necessidade.',
                                            'orcamento'  => 'Elaboração e envio do orçamento ao cliente.',
                                            'venda'      => 'Orçamento aprovado, venda confirmada.',
                                            'producao'   => 'Pedido em produção na gráfica.',
                                            'preparacao' => 'Acabamento, corte, empacotamento.',
                                            'envio'      => 'Pronto para envio ou entrega ao cliente.',
                                            'financeiro' => 'Cobrança, conferência de pagamento.',
                                            'concluido'  => 'Pedido finalizado com sucesso.',
                                        ];
                                        echo $descriptions[$sKey] ?? '';
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if(isset($_GET['status'])): ?>
    if (window.history.replaceState) { const url = new URL(window.location); url.searchParams.delete('status'); window.history.replaceState({}, '', url); }
    <?php endif; ?>
    <?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
    Swal.fire({ icon: 'success', title: 'Metas salvas!', text: 'As metas foram atualizadas com sucesso.', timer: 2000, showConfirmButton: false });
    <?php endif; ?>
});
</script>
