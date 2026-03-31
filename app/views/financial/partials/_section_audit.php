<?php
/**
 * Partial: Seção Auditoria Financeira (Relatório de Movimentações).
 *
 * Exibe todas as ações registradas no financial_audit_log,
 * incluindo criações, edições, exclusões, pagamentos e estornos.
 *
 * Variáveis esperadas:
 *   $activeSection — seção ativa
 */
?>
<div class="fin-section <?= $activeSection === 'audit' ? 'active' : '' ?>" id="fin-audit">

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="d-flex align-items-center">
            <div class="icon-circle icon-circle-grape me-2">
                <i class="fas fa-shield-alt text-grape" style="font-size:.85rem;"></i>
            </div>
            <div>
                <h5 class="mb-0" style="font-size:1rem;">Auditoria Financeira</h5>
                <p class="text-muted mb-0" style="font-size:.72rem;">Histórico completo de todas as movimentações financeiras, incluindo exclusões.</p>
            </div>
        </div>
        <button class="btn btn-sm btn-outline-secondary" id="btnExportAudit" title="Exportar auditoria em CSV">
            <i class="fas fa-file-csv me-1"></i>Exportar CSV
        </button>
    </div>

    <!-- Filtros -->
    <div class="row g-2 mb-3 align-items-end">
        <div class="col-auto">
            <label class="form-label small fw-bold mb-1">Entidade</label>
            <select id="fAuditEntity" class="form-select form-select-sm" style="width:160px">
                <option value="">Todas</option>
                <option value="transaction">Transação</option>
                <option value="installment">Parcela</option>
                <option value="order">Pedido</option>
                <option value="recurring">Recorrência</option>
            </select>
        </div>
        <div class="col-auto">
            <label class="form-label small fw-bold mb-1">Ação</label>
            <select id="fAuditAction" class="form-select form-select-sm" style="width:160px">
                <option value="">Todas</option>
                <option value="created">✅ Criado</option>
                <option value="updated">📝 Atualizado</option>
                <option value="deleted">🗑️ Excluído</option>
                <option value="paid">💰 Pago</option>
                <option value="confirmed">✔️ Confirmado</option>
                <option value="cancelled">❌ Cancelado</option>
                <option value="reversed">↩️ Estornado</option>
            </select>
        </div>
        <div class="col-auto">
            <label class="form-label small fw-bold mb-1">De</label>
            <input type="date" id="fAuditFrom" class="form-control form-control-sm" style="width:150px">
        </div>
        <div class="col-auto">
            <label class="form-label small fw-bold mb-1">Até</label>
            <input type="date" id="fAuditTo" class="form-control form-control-sm" style="width:150px">
        </div>
        <div class="col">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                <input type="text" class="form-control" id="fAuditSearch" placeholder="Buscar nos dados..." autocomplete="off">
            </div>
        </div>
    </div>

    <!-- Tabela de Auditoria -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-grape"><i class="fas fa-shield-alt me-2"></i>Registro de Auditoria</h6>
            <span class="badge bg-secondary" id="auditTotalBadge">—</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.82rem;">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-3 py-3" style="width:140px;">Data/Hora</th>
                            <th class="py-3" style="width:100px;">Entidade</th>
                            <th class="py-3" style="width:60px;">ID</th>
                            <th class="py-3" style="width:110px;">Ação</th>
                            <th class="py-3">Motivo / Detalhes</th>
                            <th class="py-3" style="width:130px;">Usuário</th>
                            <th class="py-3 text-end pe-3" style="width:110px;">IP</th>
                        </tr>
                    </thead>
                    <tbody id="auditTableBody">
                        <tr><td colspan="7" class="text-center text-muted py-5">
                            <i class="fas fa-spinner fa-spin fa-2x mb-2 d-block opacity-50"></i>Carregando...
                        </td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="fin-pagination" id="auditPagination"></div>

    <!-- Legenda -->
    <div class="mt-3 p-3 bg-light rounded" style="font-size:.72rem;">
        <strong><i class="fas fa-info-circle me-1 text-muted"></i>Legenda de Ações:</strong>
        <span class="badge bg-success ms-2">Criado</span>
        <span class="badge bg-primary ms-1">Atualizado</span>
        <span class="badge bg-danger ms-1">Excluído</span>
        <span class="badge bg-warning text-dark ms-1">Pago</span>
        <span class="badge bg-info text-dark ms-1">Confirmado</span>
        <span class="badge bg-secondary ms-1">Cancelado</span>
        <span class="badge bg-dark ms-1">Estornado</span>
    </div>
</div>
