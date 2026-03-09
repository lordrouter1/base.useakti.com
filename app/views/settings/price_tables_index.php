<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-tags me-2"></i>Tabelas de Preço</h1>
        <?php if (!empty($limitReached)): ?>
            <button class="btn btn-primary btn-sm disabled" disabled title="Limite do plano atingido">
                <i class="fas fa-plus me-1"></i> Nova Tabela
            </button>
        <?php else: ?>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNewTable">
                <i class="fas fa-plus me-1"></i> Nova Tabela
            </button>
        <?php endif; ?>
    </div>

    <?php if (!empty($limitReached)): ?>
    <div class="alert alert-warning border-warning d-flex align-items-center mb-3" role="alert">
        <i class="fas fa-exclamation-triangle fs-5 me-3 text-warning"></i>
        <div>
            <strong>Limite do plano atingido!</strong> Você possui <strong><?= $limitInfo['current'] ?></strong> de <strong><?= $limitInfo['max'] ?></strong> tabelas de preço permitidas.
            <span class="text-muted">Para cadastrar mais tabelas, entre em contato com o suporte para fazer um upgrade do seu plano.</span>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Nome</th>
                                    <th>Descrição</th>
                                    <th class="text-center">Produtos</th>
                                    <th class="text-center">Tipo</th>
                                    <th class="text-center" style="width:120px;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($priceTables)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-5">
                                    <i class="fas fa-tags d-block mb-2" style="font-size:2rem; opacity:0.3;"></i>
                                    Nenhuma tabela de preço cadastrada.
                                </td></tr>
                                <?php else: ?>
                                <?php foreach ($priceTables as $pt): ?>
                                <tr>
                                    <td class="ps-3 fw-bold"><?= htmlspecialchars($pt['name']) ?></td>
                                    <td class="text-muted small"><?= htmlspecialchars($pt['description'] ?? '') ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary rounded-pill"><?= $pt['item_count'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($pt['is_default']): ?>
                                            <span class="badge bg-success"><i class="fas fa-star me-1"></i>Padrão</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Personalizada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="?page=price_tables&action=editPriceTable&id=<?= $pt['id'] ?>&ref=price_tables" class="btn btn-outline-primary" title="Editar Preços">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if (!$pt['is_default']): ?>
                                            <a href="?page=price_tables&action=deletePriceTable&id=<?= $pt['id'] ?>&ref=price_tables" class="btn btn-outline-danger btn-delete-table" title="Excluir">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info lateral -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0 text-primary fw-bold"><i class="fas fa-info-circle me-2"></i>Como Funciona</h6>
                </div>
                <div class="card-body small">
                    <p><strong>Tabelas de preço</strong> permitem definir preços diferenciados para cada produto.</p>
                    <ul class="mb-2">
                        <li>A <strong>Tabela Padrão</strong> é usada quando o cliente não tem tabela atribuída.</li>
                        <li>Cadastre os preços por tabela diretamente na <strong>edição do produto</strong>.</li>
                        <li>Associe uma tabela a um cliente no <strong>cadastro do cliente</strong>.</li>
                        <li>Ao criar um orçamento, o preço será carregado automaticamente da tabela do cliente.</li>
                        <li>O preço pode ser <strong>alterado manualmente</strong> durante o orçamento.</li>
                    </ul>
                    <p class="text-muted mb-0"><strong>Prioridade:</strong> Tabela do Cliente → Tabela Padrão → Preço Padrão do Produto.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nova Tabela -->
<div class="modal fade" id="modalNewTable" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="?page=price_tables&action=createPriceTable">
            <?= csrf_field() ?>
            <input type="hidden" name="ref_page" value="price_tables">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Nova Tabela de Preço</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nome da Tabela</label>
                        <input type="text" class="form-control" name="name" required placeholder="Ex: Tabela Atacado">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Descrição</label>
                        <textarea class="form-control" name="description" rows="2" placeholder="Descrição opcional..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Criar Tabela</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if(isset($_GET['status']) && in_array($_GET['status'], ['table_created','table_updated','table_deleted'])): ?>
    Swal.fire({ icon: 'success', title: 'Sucesso!', timer: 1500, showConfirmButton: false });
    <?php endif; ?>
    <?php if(isset($_GET['status']) && $_GET['status'] === 'table_default_error'): ?>
    Swal.fire({ icon: 'error', title: 'Erro', text: 'Não é possível excluir a tabela padrão.', confirmButtonColor: '#3498db' });
    <?php endif; ?>
    <?php if(isset($_GET['status']) && $_GET['status'] === 'limit_price_tables'): ?>
    Swal.fire({ icon: 'warning', title: 'Limite atingido!', text: 'Você atingiu o limite de tabelas de preço do seu plano. Entre em contato com o suporte para fazer um upgrade.', confirmButtonColor: '#3498db' });
    <?php endif; ?>

    // Confirmar exclusão de tabela
    document.querySelectorAll('.btn-delete-table').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.href;
            Swal.fire({
                title: 'Excluir tabela de preço?',
                text: 'Clientes associados a esta tabela ficarão sem tabela de preço.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-trash me-1"></i> Excluir',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#e74c3c'
            }).then(r => { if (r.isConfirmed) window.location.href = href; });
        });
    });
});
</script>
