<?php
/**
 * Comissões — Configurações
 * Parâmetros gerais do módulo de comissão.
 * Padrão visual: Financeiro (sidebar em card, col-lg-9).
 * Variáveis: $config
 */
$baseOptions = [
    'valor_venda'   => 'Valor da Venda',
    'margem_lucro'  => 'Margem de Lucro',
    'valor_produto' => 'Valor do Produto',
];

$pipelineStages = [
    'contato'     => 'Contato',
    'orcamento'   => 'Orçamento',
    'aprovado'    => 'Aprovado',
    'producao'    => 'Produção',
    'pronto'      => 'Pronto',
    'entregue'    => 'Entregue',
    'concluido'   => 'Concluído',
];
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
                <div class="rounded-circle d-flex align-items-center justify-content-center me-2" style="width:34px;height:34px;background:rgba(127,140,141,.1);">
                    <i class="fas fa-cog" style="color:#7f8c8d;font-size:.85rem;"></i>
                </div>
                <div>
                    <h5 class="mb-0" style="font-size:1rem;">Configurações do Módulo</h5>
                    <p class="text-muted mb-0" style="font-size:.72rem;">Defina o percentual padrão, base de cálculo e comportamento de aprovação automática.</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form id="configForm">
                        <?= csrf_field() ?>

                        <div class="row g-4">
                            <!-- Regra Padrão -->
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center me-2" style="width:28px;height:28px;background:rgba(52,152,219,.1);">
                                        <i class="fas fa-percentage" style="color:#3498db;font-size:.7rem;"></i>
                                    </div>
                                    <h6 class="mb-0 fw-bold" style="font-size:.88rem;">Regra Padrão</h6>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Percentual Padrão (%)</label>
                                    <input type="number" name="comissao_padrao_percentual" class="form-control form-control-sm"
                                           step="0.01" value="<?= e($config['comissao_padrao_percentual'] ?? '5.00') ?>">
                                    <small class="text-muted">Usado quando nenhuma regra específica é encontrada.</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Base de Cálculo Padrão</label>
                                    <select name="base_calculo_padrao" class="form-select form-select-sm">
                                        <?php foreach ($baseOptions as $val => $label): ?>
                                        <option value="<?= $val ?>" <?= ($config['base_calculo_padrao'] ?? 'valor_venda') === $val ? 'selected' : '' ?>>
                                            <?= $label ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Comportamento -->
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center me-2" style="width:28px;height:28px;background:rgba(39,174,96,.1);">
                                        <i class="fas fa-sliders-h" style="color:#27ae60;font-size:.7rem;"></i>
                                    </div>
                                    <h6 class="mb-0 fw-bold" style="font-size:.88rem;">Comportamento</h6>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="aprovacao_automatica" value="1"
                                               id="sw_auto_approve" <?= ($config['aprovacao_automatica'] ?? '0') === '1' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="sw_auto_approve">
                                            <strong class="small">Aprovação Automática</strong>
                                            <br><small class="text-muted">Se ativado, comissões são aprovadas automaticamente ao calcular.</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="permite_comissao_cancelado" value="1"
                                               id="sw_allow_cancel" <?= ($config['permite_comissao_cancelado'] ?? '0') === '1' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="sw_allow_cancel">
                                            <strong class="small">Permitir Comissão em Pedido Cancelado</strong>
                                            <br><small class="text-muted">Se ativado, permite calcular comissão mesmo se o pedido estiver cancelado.</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Etapa do Pipeline para Cálculo Automático</label>
                                    <select name="pipeline_stage_comissao" class="form-select form-select-sm">
                                        <?php foreach ($pipelineStages as $val => $label): ?>
                                        <option value="<?= $val ?>" <?= ($config['pipeline_stage_comissao'] ?? 'concluido') === $val ? 'selected' : '' ?>>
                                            <?= $label ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Etapa na qual a comissão pode ser calculada automaticamente.</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Critério de Liberação da Comissão</label>
                                    <select name="criterio_liberacao_comissao" class="form-select form-select-sm" id="selCriterioLiberacao">
                                        <option value="sem_confirmacao" <?= ($config['criterio_liberacao_comissao'] ?? 'pagamento_total') === 'sem_confirmacao' ? 'selected' : '' ?>>
                                            Sem confirmação de pagamento (Liberação imediata)
                                        </option>
                                        <option value="primeira_parcela" <?= ($config['criterio_liberacao_comissao'] ?? 'pagamento_total') === 'primeira_parcela' ? 'selected' : '' ?>>
                                            Pagamento da primeira parcela
                                        </option>
                                        <option value="pagamento_total" <?= ($config['criterio_liberacao_comissao'] ?? 'pagamento_total') === 'pagamento_total' ? 'selected' : '' ?>>
                                            Pagamento total da venda
                                        </option>
                                    </select>
                                    <small class="text-muted">Define quando a comissão é liberada/gerada automaticamente.</small>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i>Salvar Configurações</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Info box -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-start gap-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:28px;height:28px;background:rgba(23,162,184,.1);">
                            <i class="fas fa-info-circle" style="color:#17a2b8;font-size:.7rem;"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1" style="font-size:.82rem;">Sobre o Motor de Comissões</h6>
                            <p class="mb-2 small text-muted">O motor de regras segue a seguinte <strong>hierarquia de prioridade</strong>:</p>
                            <ol class="small mb-2 text-muted ps-3">
                                <li><strong>Regra Individual (Usuário)</strong> — Mais alta prioridade. Configurada em "Regras por Usuário".</li>
                                <li><strong>Regra do Grupo</strong> — Herda a regra do grupo. Configurada em "Regras por Grupo".</li>
                                <li><strong>Regra por Produto/Categoria</strong> — Regras específicas por item vendido.</li>
                                <li><strong>Regra Padrão</strong> — Percentual padrão definido acima.</li>
                            </ol>
                            <p class="small text-muted mb-2">O sistema suporta três tipos de cálculo: <strong>Percentual</strong>, <strong>Valor Fixo</strong> e <strong>Faixa Progressiva</strong>.</p>
                            <hr class="my-2">
                            <h6 class="fw-bold mb-1" style="font-size:.82rem;">Fluxo de Status da Comissão</h6>
                            <p class="small text-muted mb-0">
                                <span class="badge bg-warning text-dark">Calculada</span>
                                <i class="fas fa-arrow-right mx-1 text-muted" style="font-size:.6rem;"></i>
                                <span class="badge bg-info">Aprovada / Ag. Pagamento</span>
                                <i class="fas fa-arrow-right mx-1 text-muted" style="font-size:.6rem;"></i>
                                <span class="badge bg-success">Paga</span>
                                <br><small>Se "Aprovação Automática" estiver ativa, a comissão pula de "Calculada" direto para "Aguardando Pagamento".</small>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

document.getElementById('configForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.set('csrf_token', csrfToken);

    // Checkboxes unchecked não são enviados — enviar 0 explicitamente
    if (!document.getElementById('sw_auto_approve').checked) formData.set('aprovacao_automatica', '0');
    if (!document.getElementById('sw_allow_cancel').checked) formData.set('permite_comissao_cancelado', '0');

    fetch('?page=commissions&action=saveConfig', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                Swal.mixin({toast:true, position:'top-end', showConfirmButton:false, timer:2000, timerProgressBar:true})
                    .fire({icon:'success', title: res.message});
            } else {
                Swal.fire({icon:'error', title:'Erro', text: res.message});
            }
        });
});
</script>
