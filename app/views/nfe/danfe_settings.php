<?php
/**
 * View: Personalização DANFE
 * Permite upload de logo e customização do rodapé do DANFE.
 *
 * @var array $danfeSettings Configurações atuais do DANFE
 */
$pageTitle = 'Personalização DANFE — NF-e';
$isAjax = $isAjax ?? false;
?>

<?php if (!$isAjax): ?>
<div class="container py-4">

    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center pt-2 pb-2 mb-3 border-bottom">
        <div>
            <h1 class="h2 mb-0"><i class="fas fa-palette me-2 text-primary"></i> Personalização DANFE</h1>
            <small class="text-muted">Configure logotipo e informações personalizadas do DANFE</small>
        </div>
        <div class="d-flex gap-2">
            <a href="?page=nfe_documents" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-file-invoice me-1"></i> NF-e
            </a>
            <a href="?page=nfe_documents&sec=dashboard" class="btn btn-outline-info btn-sm">
                <i class="fas fa-chart-bar me-1"></i> Dashboard
            </a>
        </div>
    </div>
<?php endif; ?>

    <!-- Flash messages -->
    <?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check me-2"></i> <?= e($_SESSION['flash_success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_success']); endif; ?>
    <?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-times me-2"></i> <?= e($_SESSION['flash_error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_error']); endif; ?>

    <form method="POST" action="?page=nfe_documents&action=saveDanfeSettings" enctype="multipart/form-data">
        <div class="row g-4">

            <!-- Coluna Esquerda: Configurações -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><i class="fas fa-cog me-2 text-secondary"></i> Configurações</h5>
                    </div>
                    <div class="card-body">

                        <!-- Logo do DANFE -->
                        <div class="mb-4">
                            <label class="form-label fw-bold"><i class="fas fa-image me-1"></i> Logotipo do Emitente</label>
                            <p class="small text-muted mb-2">
                                O logotipo aparecerá no cabeçalho do DANFE. Formatos aceitos: PNG, JPG, GIF. 
                                Tamanho máximo: 500KB. Dimensão recomendada: 300x100px.
                            </p>

                            <?php if (!empty($danfeSettings['logo_path'])): ?>
                            <div class="mb-3 p-3 bg-light rounded text-center">
                                <img src="<?= e($danfeSettings['logo_path']) ?>" alt="Logo DANFE" 
                                     class="img-fluid border rounded" style="max-height: 80px;">
                                <div class="mt-2">
                                    <small class="text-success"><i class="fas fa-check-circle me-1"></i> Logo configurado</small>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="mb-3 p-3 bg-light rounded text-center">
                                <i class="fas fa-image fa-3x text-muted opacity-25 mb-2"></i>
                                <p class="text-muted small mb-0">Nenhum logo configurado</p>
                            </div>
                            <?php endif; ?>

                            <input type="file" class="form-control" name="danfe_logo" id="danfeLogo"
                                   accept="image/png,image/jpeg,image/gif">
                            <div class="form-text">Selecione uma imagem para substituir o logo atual.</div>
                        </div>

                        <hr>

                        <!-- Rodapé Customizado -->
                        <div class="mb-4">
                            <label class="form-label fw-bold"><i class="fas fa-align-left me-1"></i> Rodapé Customizado</label>
                            <p class="small text-muted mb-2">
                                Texto adicional exibido no rodapé do DANFE. Útil para informações como 
                                garantia, condições de venda, informações de contato, etc.
                            </p>
                            <textarea class="form-control" name="custom_footer" rows="4"
                                      placeholder="Ex: Mercadoria sujeita a conferência na entrega. Dúvidas: (11) 1234-5678"
                            ><?= e($danfeSettings['custom_footer'] ?? '') ?></textarea>
                            <div class="form-text">Máximo 500 caracteres.</div>
                        </div>

                        <!-- Botão Salvar -->
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Salvar Configurações
                            </button>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Coluna Direita: Preview -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><i class="fas fa-eye me-2 text-secondary"></i> Preview do DANFE</h5>
                    </div>
                    <div class="card-body">
                        <div class="preview-area rounded p-3" style="min-height: 400px;">
                            <!-- Preview Header -->
                            <div class="border-bottom pb-2 mb-3">
                                <div class="row align-items-center">
                                    <div class="col-4 text-center">
                                        <div id="previewLogo" class="bg-light border rounded p-2" style="min-height:60px; display:flex; align-items:center; justify-content:center;">
                                            <?php if (!empty($danfeSettings['logo_path'])): ?>
                                            <img src="<?= e($danfeSettings['logo_path']) ?>" alt="Logo" class="img-fluid" style="max-height:50px;">
                                            <?php else: ?>
                                            <small class="text-muted">LOGO</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-8">
                                        <div class="fw-bold small">EMPRESA EXEMPLO LTDA</div>
                                        <div style="font-size:0.65rem;" class="text-muted">
                                            CNPJ: 12.345.678/0001-90<br>
                                            Rua Exemplo, 123 - Centro<br>
                                            Cidade/UF - CEP 01234-567
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Preview DANFE Title -->
                            <div class="text-center mb-3">
                                <div class="fw-bold" style="font-size:0.9rem;">DANFE</div>
                                <div style="font-size:0.6rem;" class="text-muted">DOCUMENTO AUXILIAR DA NOTA FISCAL ELETRÔNICA</div>
                            </div>

                            <!-- Preview Body (mock) -->
                            <div class="mb-2" style="font-size:0.6rem;">
                                <div class="bg-light border rounded p-1 mb-1"><strong>NATUREZA DA OPERAÇÃO:</strong> Venda de mercadoria</div>
                                <div class="bg-light border rounded p-1 mb-1"><strong>DESTINATÁRIO:</strong> Cliente Exemplo LTDA</div>
                            </div>

                            <!-- Preview Products (mock) -->
                            <div class="mb-2">
                                <table class="table table-sm table-bordered mb-0" style="font-size:0.55rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Código</th>
                                            <th>Descrição</th>
                                            <th>Qtd</th>
                                            <th>Vlr Unit</th>
                                            <th>Vlr Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>001</td>
                                            <td>Produto Exemplo</td>
                                            <td>10</td>
                                            <td>R$ 50,00</td>
                                            <td>R$ 500,00</td>
                                        </tr>
                                        <tr>
                                            <td>002</td>
                                            <td>Produto Teste</td>
                                            <td>5</td>
                                            <td>R$ 30,00</td>
                                            <td>R$ 150,00</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Preview Footer -->
                            <div class="border-top pt-2 mt-3">
                                <div id="previewFooter" style="font-size:0.6rem;" class="text-muted fst-italic">
                                    <?= e($danfeSettings['custom_footer'] ?? 'Texto do rodapé personalizado aparecerá aqui.') ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dicas -->
                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-body">
                        <h6 class="mb-3"><i class="fas fa-lightbulb me-2 text-warning"></i> Dicas</h6>
                        <ul class="small text-muted mb-0">
                            <li class="mb-1">O logo deve ser em alta resolução para melhor impressão.</li>
                            <li class="mb-1">Prefira logos com fundo transparente (PNG).</li>
                            <li class="mb-1">O rodapé pode conter informações de garantia ou contato.</li>
                            <li>As alterações serão aplicadas nas próximas NF-e emitidas.</li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </form>
<?php if (!$isAjax): ?>
</div>
<?php endif; ?>

<script>
(function(__run){if(typeof jQuery!=='undefined'){jQuery(__run);}else{document.addEventListener('DOMContentLoaded',__run);}})(function(){
    // Preview logo ao selecionar arquivo
    $('#danfeLogo').on('change', function(e){
        var file = e.target.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(ev) {
                $('#previewLogo').html('<img src="' + ev.target.result + '" alt="Preview" class="img-fluid" style="max-height:50px;">');
            };
            reader.readAsDataURL(file);
        }
    });

    // Preview rodapé em tempo real
    $('textarea[name="custom_footer"]').on('input', function(){
        var text = $(this).val().trim() || 'Texto do rodapé personalizado aparecerá aqui.';
        $('#previewFooter').text(text);
    });
});
</script>
