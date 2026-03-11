<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-box-open me-2"></i>Produtos</h1>
    <div class="btn-toolbar mb-2 mb-md-0 gap-2">
        <div class="btn-group">
            <a href="?page=products&action=downloadImportTemplate" class="btn btn-sm btn-outline-success" title="Baixar modelo de planilha para importação">
                <i class="fas fa-file-excel me-1"></i> Modelo Importação
            </a>
            <?php if (!empty($limitReached)): ?>
                <button type="button" class="btn btn-sm btn-outline-info disabled" disabled title="Limite do plano atingido">
                    <i class="fas fa-file-import me-1"></i> Importar Produtos
                </button>
            <?php else: ?>
                <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#importModal" title="Importar produtos em massa via planilha">
                    <i class="fas fa-file-import me-1"></i> Importar Produtos
                </button>
            <?php endif; ?>
        </div>
        <?php if (!empty($limitReached)): ?>
            <button class="btn btn-sm btn-primary disabled" disabled title="Limite do plano atingido">
                <i class="fas fa-plus me-1"></i> Novo Produto
            </button>
        <?php else: ?>
            <a href="?page=products&action=create" class="btn btn-sm btn-primary">
                <i class="fas fa-plus me-1"></i> Novo Produto
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($limitReached)): ?>
<div class="alert alert-warning border-warning d-flex align-items-center mb-3" role="alert">
    <i class="fas fa-exclamation-triangle fs-5 me-3 text-warning"></i>
    <div>
        <strong>Limite do plano atingido!</strong> Você possui <strong><?= $limitInfo['current'] ?></strong> de <strong><?= $limitInfo['max'] ?></strong> produtos permitidos.
        <span class="text-muted">Para cadastrar mais produtos, entre em contato com o suporte para fazer um upgrade do seu plano.</span>
    </div>
</div>
<?php endif; ?>

<!-- ══════ Modal de Importação de Produtos ══════ -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="importModalLabel"><i class="fas fa-file-import me-2"></i>Importar Produtos em Massa</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Instruções -->
                <div class="alert alert-info py-2">
                    <h6 class="mb-1 fw-bold"><i class="fas fa-info-circle me-1"></i>Como importar:</h6>
                    <ol class="mb-0 small ps-3">
                        <li>Baixe o <a href="?page=products&action=downloadImportTemplate" class="fw-bold">modelo de planilha</a> (CSV).</li>
                        <li>Preencha uma linha para cada produto. Os campos <strong>Nome</strong> e <strong>Preço</strong> são obrigatórios.</li>
                        <li>Salve o arquivo e faça o upload abaixo.</li>
                    </ol>
                </div>

                <!-- Tabela de campos disponíveis -->
                <div class="mb-3">
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFieldInfo">
                        <i class="fas fa-columns me-1"></i>Ver colunas disponíveis
                    </button>
                    <div class="collapse mt-2" id="collapseFieldInfo">
                        <div class="table-responsive" style="padding:0; border:none; box-shadow:none;">
                            <table class="table table-sm table-bordered mb-0" style="font-size:0.8rem;">
                                <thead class="table-light"><tr><th>Coluna</th><th>Obrigatória</th><th>Descrição</th></tr></thead>
                                <tbody>
                                    <tr><td><code>nome</code></td><td><span class="badge bg-danger">Sim</span></td><td>Nome do produto</td></tr>
                                    <tr><td><code>preco</code></td><td><span class="badge bg-danger">Sim</span></td><td>Preço de venda (ex: 19.90)</td></tr>
                                    <tr><td><code>preco_custo</code></td><td><span class="badge bg-secondary">Não</span></td><td>Preço de custo</td></tr>
                                    <tr><td><code>estoque</code></td><td><span class="badge bg-secondary">Não</span></td><td>Ignorado (o estoque é gerenciado via armazéns)</td></tr>
                                    <tr><td><code>categoria</code></td><td><span class="badge bg-secondary">Não</span></td><td>Nome da categoria (criada automaticamente se não existir)</td></tr>
                                    <tr><td><code>subcategoria</code></td><td><span class="badge bg-secondary">Não</span></td><td>Nome da subcategoria</td></tr>
                                    <tr><td><code>descricao</code></td><td><span class="badge bg-secondary">Não</span></td><td>Descrição detalhada</td></tr>
                                    <tr><td><code>formato</code></td><td><span class="badge bg-secondary">Não</span></td><td>Formato/dimensões (ex: A4)</td></tr>
                                    <tr><td><code>material</code></td><td><span class="badge bg-secondary">Não</span></td><td>Material/papel (ex: Couché 300g)</td></tr>
                                    <tr><td><code>ncm</code></td><td><span class="badge bg-secondary">Não</span></td><td>NCM fiscal</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Upload do arquivo -->
                <form id="importForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="importFile" class="form-label fw-bold">Arquivo para importação</label>
                        <input type="file" class="form-control" id="importFile" name="import_file" accept=".csv,.xls,.xlsx" required>
                        <div class="form-text">Formatos aceitos: <strong>CSV</strong> (separado por <code>;</code> ou <code>,</code>), <strong>XLS</strong>, <strong>XLSX</strong>.</div>
                    </div>
                </form>

                <!-- Resultado da importação -->
                <div id="importResult" style="display:none;">
                    <hr>
                    <div id="importResultContent"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-info text-white" id="btnDoImport" disabled>
                    <i class="fas fa-upload me-1"></i>Importar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Busca rápida -->
<div class="mb-3">
    <div class="input-group">
        <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
        <input type="text" class="form-control" id="searchTable" placeholder="Buscar por nome, categoria ou preço..." autocomplete="off">
    </div>
</div>

<div class="table-responsive bg-white rounded shadow-sm">
    <table class="table table-hover align-middle mb-0">
        <thead class="bg-light">
            <tr>
                <th class="py-3 ps-4">Imagem</th>
                <th class="py-3">Nome</th>
                <th class="py-3">Categoria</th>
                <th class="py-3">Preço</th>
                <th class="py-3">Controle de Estoque</th>
                <th class="py-3 text-end pe-4">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($products) > 0): ?>
            <?php foreach($products as $product): ?>
            <tr>
                <td class="ps-4">
                    <div class="bg-light rounded d-flex align-items-center justify-content-center border" style="width: 50px; height: 50px; overflow: hidden;">
                        <?php if(!empty($product['main_image_path'])): ?>
                            <img src="<?= eAttr($product['main_image_path']) ?>" class="w-100 h-100 object-fit-cover">
                        <?php else: ?>
                            <i class="fas fa-image text-secondary"></i>
                        <?php endif; ?>
                    </div>
                </td>
                <td class="fw-bold"><?= e($product['name']) ?></td>
                <td>
                    <span class="badge bg-light text-dark border">
                        <?= !empty($product['category_name']) ? e($product['category_name']) : 'Geral' ?>
                    </span>
                    <?php if(!empty($product['subcategory_name'])): ?>
                    <small class="text-muted d-block mt-1"><?= e($product['subcategory_name']) ?></small>
                    <?php endif; ?>
                </td>
                <td class="fw-bold">R$ <?= eNum($product['price']) ?></td>
                <td>
                    <?php if(!empty($product['use_stock_control'])): ?>
                        <span class="badge bg-info px-3"><i class="fas fa-boxes me-1"></i>Controle ativo</span>
                    <?php else: ?>
                        <span class="badge bg-light text-muted border px-3">Sem controle</span>
                    <?php endif; ?>
                </td>
                <td class="text-end pe-4">
                    <div class="btn-group">
                        <a href="?page=products&action=edit&id=<?= (int)$product['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-danger ms-1 btn-delete-product" data-id="<?= (int)$product['id'] ?>" data-name="<?= eAttr($product['name']) ?>" title="Excluir">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php else: ?>
            <tr>
                <td colspan="6" class="text-center text-muted py-5">
                    <i class="fas fa-box-open fa-3x mb-3 d-block text-secondary"></i>
                    Nenhum produto cadastrado ainda.
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if(isset($_GET['status'])): ?>
    if (window.history.replaceState) { const url = new URL(window.location); url.searchParams.delete('status'); url.searchParams.delete('imported'); url.searchParams.delete('errors'); window.history.replaceState({}, '', url); }
    <?php endif; ?>
    <?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
    Swal.fire({ icon: 'success', title: 'Sucesso!', text: 'Produto salvo com sucesso!', timer: 2000, showConfirmButton: false });
    <?php endif; ?>
    <?php if(isset($_GET['status']) && $_GET['status'] == 'limit_products'): ?>
    Swal.fire({ icon: 'warning', title: 'Limite atingido!', text: 'Você atingiu o limite de produtos do seu plano. Entre em contato com o suporte para fazer um upgrade.', confirmButtonColor: '#3498db' });
    <?php endif; ?>
    <?php if(isset($_GET['status']) && $_GET['status'] == 'imported'): ?>
    Swal.fire({ 
        icon: 'success', 
        title: 'Importação Concluída!', 
        html: '<?= isset($_GET['imported']) ? intval($_GET['imported']) : 0 ?> produto(s) importado(s) com sucesso.<?= isset($_GET['errors']) && intval($_GET['errors']) > 0 ? "<br><span class=\"text-danger\">" . intval($_GET["errors"]) . " linha(s) com erro.</span>" : "" ?>', 
        timer: 4000, 
        showConfirmButton: true 
    });
    <?php endif; ?>

    // ── Import Modal logic ──
    const importFileInput = document.getElementById('importFile');
    const btnDoImport = document.getElementById('btnDoImport');
    const importResult = document.getElementById('importResult');
    const importResultContent = document.getElementById('importResultContent');

    if (importFileInput) {
        importFileInput.addEventListener('change', function() {
            btnDoImport.disabled = !this.files.length;
            importResult.style.display = 'none';
        });
    }

    if (btnDoImport) {
        btnDoImport.addEventListener('click', function() {
            const file = importFileInput.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('import_file', file);

            btnDoImport.disabled = true;
            btnDoImport.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Importando...';
            importResult.style.display = 'none';

            fetch('?page=products&action=importProducts', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                importResult.style.display = 'block';
                if (data.success) {
                    let html = `<div class="alert alert-success py-2"><i class="fas fa-check-circle me-1"></i><strong>${data.imported}</strong> produto(s) importado(s) com sucesso!</div>`;
                    if (data.errors && data.errors.length > 0) {
                        html += `<div class="alert alert-warning py-2"><i class="fas fa-exclamation-triangle me-1"></i><strong>${data.errors.length}</strong> linha(s) com erro:</div>`;
                        html += '<div class="list-group" style="max-height:200px; overflow-y:auto;">';
                        data.errors.forEach(err => {
                            html += `<div class="list-group-item list-group-item-danger py-1 small"><strong>Linha ${err.line}:</strong> ${err.message}</div>`;
                        });
                        html += '</div>';
                    }
                    importResultContent.innerHTML = html;
                    // Reload page after a short delay
                    setTimeout(() => { window.location.reload(); }, 2500);
                } else {
                    importResultContent.innerHTML = `<div class="alert alert-danger py-2"><i class="fas fa-times-circle me-1"></i>${data.message || 'Erro ao importar.'}</div>`;
                }
                btnDoImport.disabled = false;
                btnDoImport.innerHTML = '<i class="fas fa-upload me-1"></i>Importar';
            })
            .catch(err => {
                importResult.style.display = 'block';
                importResultContent.innerHTML = `<div class="alert alert-danger py-2"><i class="fas fa-times-circle me-1"></i>Erro de comunicação com o servidor.</div>`;
                btnDoImport.disabled = false;
                btnDoImport.innerHTML = '<i class="fas fa-upload me-1"></i>Importar';
            });
        });
    }

    document.querySelectorAll('.btn-delete-product').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            Swal.fire({
                title: 'Excluir produto?',
                html: `Deseja realmente excluir <strong>${name}</strong>?<br>Esta ação não pode ser desfeita.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#c0392b',
                cancelButtonColor: '#95a5a6',
                confirmButtonText: '<i class="fas fa-trash me-1"></i> Sim, excluir',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `?page=products&action=delete&id=${id}`;
                }
            });
        });
    });

    // Busca rápida na tabela
    const searchInput = document.getElementById('searchTable');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const q = this.value.toLowerCase().trim();
            document.querySelectorAll('table tbody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        });
    }
});
</script>
