<?php
/**
 * Comissões — Simulador
 * Permite simular o cálculo de comissão sem registrar.
 * Padrão visual: Financeiro (sidebar em card, layout col-lg-9).
 * Variáveis: $aux (users, formas)
 */
$users = $aux['users'] ?? [];
?>

<div class="container-fluid py-3">

    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-hand-holding-usd me-2 text-primary"></i>Comissões</h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">Dashboard, regras, simulação e histórico de comissões.</p>
        </div>
    </div>

    <div class="row g-4">
        <?php require 'app/views/commissions/_sidebar.php'; ?>

        <div class="col-lg-9">

            <div class="d-flex align-items-center mb-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center me-2" style="width:34px;height:34px;background:rgba(41,128,185,.1);">
                    <i class="fas fa-calculator" style="color:#2980b9;font-size:.85rem;"></i>
                </div>
                <div>
                    <h5 class="mb-0" style="font-size:1rem;">Simulador de Comissão</h5>
                    <p class="text-muted mb-0" style="font-size:.72rem;">Simule o cálculo usando a mesma lógica do motor de regras — sem registrar.</p>
                </div>
            </div>

            <div class="row g-4">
                <!-- Formulário -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 p-3">
                            <h6 class="mb-0 fw-bold text-primary" style="font-size:.85rem;">
                                <i class="fas fa-sliders-h me-2"></i>Parâmetros da Simulação
                            </h6>
                        </div>
                        <div class="card-body">
                            <form id="simForm">
                                <?= csrf_field() ?>
                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Vendedor / Comissionado <span class="text-danger">*</span></label>
                                    <select name="user_id" id="sim_user" class="form-select form-select-sm" required>
                                        <option value="">Selecione...</option>
                                        <?php foreach ($users as $u): ?>
                                        <option value="<?= $u['id'] ?>"><?= e($u['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Valor da Venda (R$) <span class="text-danger">*</span></label>
                                    <input type="number" name="valor_venda" id="sim_valor" class="form-control form-control-sm" step="0.01" min="0.01" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Margem de Lucro (%)</label>
                                    <input type="number" name="margem_lucro" id="sim_margem" class="form-control form-control-sm" step="0.01" value="0">
                                    <small class="text-muted">Opcional — usado quando a base de cálculo é "margem de lucro".</small>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-play me-1"></i>Simular
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Resultado -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm" id="simResultCard" style="display:none">
                        <div class="card-header bg-white border-0 p-3">
                            <h6 class="mb-0 fw-bold text-success" style="font-size:.85rem;">
                                <i class="fas fa-receipt me-2"></i>Resultado da Simulação
                            </h6>
                        </div>
                        <div class="card-body" id="simResultBody"></div>
                    </div>

                    <!-- Explicação do fluxo -->
                    <div class="card border-0 shadow-sm mt-3">
                        <div class="card-header bg-white border-0 p-3">
                            <h6 class="mb-0 fw-bold text-info" style="font-size:.85rem;">
                                <i class="fas fa-sitemap me-2"></i>Fluxo de Resolução de Regras
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <div class="list-group-item d-flex align-items-center gap-3 py-2">
                                    <span class="badge bg-primary rounded-pill" style="width:26px;text-align:center;font-size:.65rem;">1</span>
                                    <div>
                                        <div class="fw-semibold" style="font-size:.82rem;">Regra Individual (Usuário)</div>
                                        <small class="text-muted" style="font-size:.7rem;">Verifica se o vendedor tem uma regra específica.</small>
                                    </div>
                                </div>
                                <div class="list-group-item d-flex align-items-center gap-3 py-2">
                                    <span class="badge bg-info rounded-pill" style="width:26px;text-align:center;font-size:.65rem;">2</span>
                                    <div>
                                        <div class="fw-semibold" style="font-size:.82rem;">Regra do Grupo</div>
                                        <small class="text-muted" style="font-size:.7rem;">Se não há regra individual, busca no grupo do vendedor.</small>
                                    </div>
                                </div>
                                <div class="list-group-item d-flex align-items-center gap-3 py-2">
                                    <span class="badge bg-secondary rounded-pill" style="width:26px;text-align:center;font-size:.65rem;">3</span>
                                    <div>
                                        <div class="fw-semibold" style="font-size:.82rem;">Regra por Produto/Categoria</div>
                                        <small class="text-muted" style="font-size:.7rem;">Regras específicas por produto ou categoria da venda.</small>
                                    </div>
                                </div>
                                <div class="list-group-item d-flex align-items-center gap-3 py-2">
                                    <span class="badge bg-warning text-dark rounded-pill" style="width:26px;text-align:center;font-size:.65rem;">4</span>
                                    <div>
                                        <div class="fw-semibold" style="font-size:.82rem;">Regra Padrão</div>
                                        <small class="text-muted" style="font-size:.7rem;">Percentual padrão definido nas configurações do módulo.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

document.getElementById('simForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.set('csrf_token', csrfToken);

    fetch('?page=commissions&action=simularCalculo', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            const card = document.getElementById('simResultCard');
            const body = document.getElementById('simResultBody');

            if (!res.success) {
                card.style.display = 'block';
                body.innerHTML = `<div class="alert alert-danger mb-0 small"><i class="fas fa-exclamation-triangle me-2"></i>${res.message || 'Erro na simulação.'}</div>`;
                return;
            }

            const d = res.data;
            const origemBadge = {
                'usuario': '<span class="badge bg-primary"><i class="fas fa-user me-1"></i>Usuário</span>',
                'grupo': '<span class="badge bg-info"><i class="fas fa-users me-1"></i>Grupo</span>',
                'produto': '<span class="badge bg-secondary"><i class="fas fa-box me-1"></i>Produto</span>',
                'padrao': '<span class="badge bg-warning text-dark"><i class="fas fa-cog me-1"></i>Padrão</span>',
            };
            const tipoBadge = {
                'percentual': 'Percentual',
                'valor_fixo': 'Valor Fixo',
                'faixa': 'Faixa Progressiva',
            };
            const baseBadge = {
                'valor_venda': 'Valor da Venda',
                'margem_lucro': 'Margem de Lucro',
                'valor_produto': 'Valor do Produto',
            };

            card.style.display = 'block';
            body.innerHTML = `
                <div class="text-center mb-4">
                    <div class="display-5 fw-bold text-success">
                        R$ ${parseFloat(d.valor_comissao || 0).toLocaleString('pt-BR', {minimumFractionDigits:2})}
                    </div>
                    <div class="text-muted small">Valor da Comissão</div>
                </div>
                <table class="table table-sm mb-0">
                    <tr><td class="text-muted small">Origem da Regra</td><td class="text-end">${origemBadge[d.origem_regra] || d.origem_regra}</td></tr>
                    <tr><td class="text-muted small">Tipo de Cálculo</td><td class="text-end small fw-semibold">${tipoBadge[d.tipo_calculo] || d.tipo_calculo}</td></tr>
                    <tr><td class="text-muted small">Base de Cálculo</td><td class="text-end small fw-semibold">${baseBadge[d.base_calculo] || d.base_calculo}</td></tr>
                    <tr><td class="text-muted small">Valor Base</td><td class="text-end fw-semibold">R$ ${parseFloat(d.valor_base || 0).toLocaleString('pt-BR', {minimumFractionDigits:2})}</td></tr>
                    ${d.percentual_aplicado !== null ? `<tr><td class="text-muted small">Percentual Aplicado</td><td class="text-end fw-semibold">${parseFloat(d.percentual_aplicado).toLocaleString('pt-BR', {minimumFractionDigits:2})}%</td></tr>` : ''}
                </table>
                ${d.error ? `<div class="alert alert-warning mt-3 mb-0 small"><i class="fas fa-exclamation-triangle me-1"></i>${d.error}</div>` : ''}
            `;
        })
        .catch(() => {
            Swal.fire({icon:'error', title:'Erro', text:'Falha na comunicação com o servidor.'});
        });
});
</script>
