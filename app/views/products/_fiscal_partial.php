<?php
/**
 * Partial: Informações Fiscais do Produto (NF-e)
 * Incluído em create.php e edit.php de produtos
 * Variáveis esperadas: $product (array, pode ser vazio para criação)
 */
$p = $product ?? [];

// Opções de Origem da Mercadoria
$origensNfe = [
    '0' => '0 — Nacional',
    '1' => '1 — Estrangeira (importação direta)',
    '2' => '2 — Estrangeira (adquirida mercado interno)',
    '3' => '3 — Nacional (cont. import. 40-70%)',
    '4' => '4 — Nacional (processos prod. básicos)',
    '5' => '5 — Nacional (cont. import. < 40%)',
    '6' => '6 — Estrangeira (import. direta, s/ similar)',
    '7' => '7 — Estrangeira (merc. interno, s/ similar)',
    '8' => '8 — Nacional (cont. import. > 70%)',
];

// Opções de CST ICMS
$cstIcms = [
    ''   => 'Selecione...',
    '00' => '00 — Tributada integralmente',
    '10' => '10 — Tributada com cobrança de ICMS por ST',
    '20' => '20 — Com redução de base de cálculo',
    '30' => '30 — Isenta/não tributada com cobrança de ICMS por ST',
    '40' => '40 — Isenta',
    '41' => '41 — Não tributada',
    '50' => '50 — Suspensão',
    '51' => '51 — Diferimento',
    '60' => '60 — ICMS cobrado anteriormente por ST',
    '70' => '70 — Com red. de BC e cobrança de ICMS por ST',
    '90' => '90 — Outros',
];

// Opções de CSOSN (Simples Nacional)
$csosnOptions = [
    ''    => 'Selecione...',
    '101' => '101 — Tributada com permissão de crédito',
    '102' => '102 — Tributada sem permissão de crédito',
    '103' => '103 — Isenção de ICMS para faixa de receita bruta',
    '201' => '201 — Tributada com permissão de crédito e cobrança por ST',
    '202' => '202 — Tributada sem permissão de crédito e cobrança por ST',
    '203' => '203 — Isenção de ICMS p/ faixa receita e cobrança por ST',
    '300' => '300 — Imune',
    '400' => '400 — Não tributada',
    '500' => '500 — ICMS cobrado anteriormente por ST ou antecipação',
    '900' => '900 — Outros',
];

// CST PIS/COFINS
$cstPisCofins = [
    ''   => 'Selecione...',
    '01' => '01 — Oper. tributável (BC = valor da oper.)',
    '02' => '02 — Oper. tributável (BC = valor da oper. - alíq. dif.)',
    '03' => '03 — Oper. tributável (BC = qtde vendida x alíq. por unid.)',
    '04' => '04 — Oper. tributável (tributação monofásica - alíq. zero)',
    '05' => '05 — Oper. tributável (substituição tributária)',
    '06' => '06 — Oper. tributável (alíquota zero)',
    '07' => '07 — Oper. isenta da contribuição',
    '08' => '08 — Oper. sem incidência da contribuição',
    '09' => '09 — Oper. com suspensão da contribuição',
    '49' => '49 — Outras operações de saída',
    '99' => '99 — Outras operações',
];

// CST IPI
$cstIpi = [
    ''   => 'Selecione...',
    '00' => '00 — Entrada com recuperação de crédito',
    '01' => '01 — Entrada tributável com alíquota zero',
    '02' => '02 — Entrada isenta',
    '03' => '03 — Entrada não-tributada',
    '04' => '04 — Entrada imune',
    '05' => '05 — Entrada com suspensão',
    '49' => '49 — Outras entradas',
    '50' => '50 — Saída tributada',
    '51' => '51 — Saída tributável com alíquota zero',
    '52' => '52 — Saída isenta',
    '53' => '53 — Saída não-tributada',
    '54' => '54 — Saída imune',
    '55' => '55 — Saída com suspensão',
    '99' => '99 — Outras saídas',
];

// Unidades de medida comuns
$unidades = ['UN', 'KG', 'MT', 'M2', 'M3', 'LT', 'PC', 'CX', 'DZ', 'PR', 'CT', 'ML', 'RL', 'FL', 'JG', 'KT'];
?>

<p class="text-muted small mb-3"><i class="fas fa-info-circle me-1"></i>Campos utilizados para emissão de Nota Fiscal Eletrônica (NF-e). Preencha conforme a classificação fiscal do produto.</p>

        <!-- Classificação Fiscal -->
        <div class="card border-0 bg-light mb-3">
            <div class="card-header bg-transparent border-bottom py-2">
                <h6 class="mb-0 fw-bold small text-grape"><i class="fas fa-barcode me-2"></i>Classificação Fiscal</h6>
            </div>
            <div class="card-body py-3">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="fiscal_ncm" class="form-label fw-bold small text-muted">NCM</label>
                        <input type="text" class="form-control" id="fiscal_ncm" name="fiscal_ncm" 
                               value="<?= eAttr($p['fiscal_ncm'] ?? '') ?>" 
                               placeholder="00000000" maxlength="10"
                               title="Nomenclatura Comum do Mercosul (8 dígitos)">
                        <small class="text-muted" style="font-size:0.65rem;">8 dígitos. Ex: 49019900</small>
                    </div>
                    <div class="col-md-3">
                        <label for="fiscal_cest" class="form-label fw-bold small text-muted">CEST</label>
                        <input type="text" class="form-control" id="fiscal_cest" name="fiscal_cest" 
                               value="<?= eAttr($p['fiscal_cest'] ?? '') ?>" 
                               placeholder="0000000" maxlength="10"
                               title="Código Especificador da Substituição Tributária (7 dígitos)">
                        <small class="text-muted" style="font-size:0.65rem;">7 dígitos (se aplicável)</small>
                    </div>
                    <div class="col-md-3">
                        <label for="fiscal_cfop" class="form-label fw-bold small text-muted">CFOP</label>
                        <input type="text" class="form-control" id="fiscal_cfop" name="fiscal_cfop" 
                               value="<?= eAttr($p['fiscal_cfop'] ?? '') ?>" 
                               placeholder="5102" maxlength="10"
                               title="Código Fiscal de Operações e Prestações">
                        <small class="text-muted" style="font-size:0.65rem;">Ex: 5102 (venda merc.)</small>
                    </div>
                    <div class="col-md-3">
                        <label for="fiscal_ean" class="form-label fw-bold small text-muted">EAN/GTIN</label>
                        <input type="text" class="form-control" id="fiscal_ean" name="fiscal_ean" 
                               value="<?= eAttr($p['fiscal_ean'] ?? '') ?>" 
                               placeholder="Código de barras" maxlength="14">
                        <small class="text-muted" style="font-size:0.65rem;">Código de barras (se houver)</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Origem e Unidade -->
        <div class="card border-0 bg-light mb-3">
            <div class="card-header bg-transparent border-bottom py-2">
                <h6 class="mb-0 fw-bold small text-grape"><i class="fas fa-globe me-2"></i>Origem e Unidade</h6>
            </div>
            <div class="card-body py-3">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="fiscal_origem" class="form-label fw-bold small text-muted">Origem da Mercadoria</label>
                        <select class="form-select" id="fiscal_origem" name="fiscal_origem">
                            <?php foreach ($origensNfe as $val => $label): ?>
                            <option value="<?= $val ?>" <?= ($p['fiscal_origem'] ?? '0') == $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="fiscal_unidade" class="form-label fw-bold small text-muted">Unidade Fiscal</label>
                        <select class="form-select" id="fiscal_unidade" name="fiscal_unidade">
                            <?php foreach ($unidades as $un): ?>
                            <option value="<?= $un ?>" <?= ($p['fiscal_unidade'] ?? 'UN') === $un ? 'selected' : '' ?>><?= $un ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="fiscal_beneficio" class="form-label fw-bold small text-muted">Código Benefício Fiscal</label>
                        <input type="text" class="form-control" id="fiscal_beneficio" name="fiscal_beneficio" 
                               value="<?= eAttr($p['fiscal_beneficio'] ?? '') ?>" 
                               placeholder="cBenef" maxlength="20">
                    </div>
                </div>
            </div>
        </div>

        <!-- Tributação ICMS -->
        <div class="card border-0 bg-light mb-3">
            <div class="card-header bg-transparent border-bottom py-2">
                <h6 class="mb-0 fw-bold small text-grape"><i class="fas fa-percentage me-2"></i>Tributação ICMS</h6>
            </div>
            <div class="card-body py-3">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="fiscal_cst_icms" class="form-label fw-bold small text-muted">CST ICMS</label>
                        <select class="form-select" id="fiscal_cst_icms" name="fiscal_cst_icms">
                            <?php foreach ($cstIcms as $val => $label): ?>
                            <option value="<?= $val ?>" <?= ($p['fiscal_cst_icms'] ?? '') == $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted" style="font-size:0.65rem;">Regime Normal</small>
                    </div>
                    <div class="col-md-4">
                        <label for="fiscal_csosn" class="form-label fw-bold small text-muted">CSOSN</label>
                        <select class="form-select" id="fiscal_csosn" name="fiscal_csosn">
                            <?php foreach ($csosnOptions as $val => $label): ?>
                            <option value="<?= $val ?>" <?= ($p['fiscal_csosn'] ?? '') == $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted" style="font-size:0.65rem;">Simples Nacional</small>
                    </div>
                    <div class="col-md-4">
                        <label for="fiscal_aliq_icms" class="form-label fw-bold small text-muted">Alíquota ICMS (%)</label>
                        <div class="input-group">
                            <input type="number" step="0.01" class="form-control" id="fiscal_aliq_icms" name="fiscal_aliq_icms" 
                                   value="<?= eAttr($p['fiscal_aliq_icms'] ?? '') ?>" placeholder="0.00" min="0" max="100">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tributação PIS / COFINS / IPI -->
        <div class="card border-0 bg-light mb-3">
            <div class="card-header bg-transparent border-bottom py-2">
                <h6 class="mb-0 fw-bold small text-grape"><i class="fas fa-calculator me-2"></i>PIS / COFINS / IPI</h6>
            </div>
            <div class="card-body py-3">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="fiscal_cst_pis" class="form-label fw-bold small text-muted">CST PIS</label>
                        <select class="form-select form-select-sm" id="fiscal_cst_pis" name="fiscal_cst_pis">
                            <?php foreach ($cstPisCofins as $val => $label): ?>
                            <option value="<?= $val ?>" <?= ($p['fiscal_cst_pis'] ?? '') == $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="fiscal_aliq_pis" class="form-label fw-bold small text-muted">Alíq. PIS (%)</label>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.0001" class="form-control" id="fiscal_aliq_pis" name="fiscal_aliq_pis" 
                                   value="<?= eAttr($p['fiscal_aliq_pis'] ?? '') ?>" placeholder="0.65" min="0" max="100">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="fiscal_cst_cofins" class="form-label fw-bold small text-muted">CST COFINS</label>
                        <select class="form-select form-select-sm" id="fiscal_cst_cofins" name="fiscal_cst_cofins">
                            <?php foreach ($cstPisCofins as $val => $label): ?>
                            <option value="<?= $val ?>" <?= ($p['fiscal_cst_cofins'] ?? '') == $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="fiscal_aliq_cofins" class="form-label fw-bold small text-muted">Alíq. COFINS (%)</label>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.0001" class="form-control" id="fiscal_aliq_cofins" name="fiscal_aliq_cofins" 
                                   value="<?= eAttr($p['fiscal_aliq_cofins'] ?? '') ?>" placeholder="3.00" min="0" max="100">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="col-md-3 mt-3">
                        <label for="fiscal_cst_ipi" class="form-label fw-bold small text-muted">CST IPI</label>
                        <select class="form-select form-select-sm" id="fiscal_cst_ipi" name="fiscal_cst_ipi">
                            <?php foreach ($cstIpi as $val => $label): ?>
                            <option value="<?= $val ?>" <?= ($p['fiscal_cst_ipi'] ?? '') == $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mt-3">
                        <label for="fiscal_aliq_ipi" class="form-label fw-bold small text-muted">Alíq. IPI (%)</label>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.01" class="form-control" id="fiscal_aliq_ipi" name="fiscal_aliq_ipi" 
                                   value="<?= eAttr($p['fiscal_aliq_ipi'] ?? '') ?>" placeholder="0.00" min="0" max="100">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informações Adicionais -->
        <div class="card border-0 bg-light mb-3">
            <div class="card-header bg-transparent border-bottom py-2">
                <h6 class="mb-0 fw-bold small text-grape"><i class="fas fa-sticky-note me-2"></i>Informações Adicionais</h6>
            </div>
            <div class="card-body py-3">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="fiscal_info_adicional" class="form-label fw-bold small text-muted">Informações Adicionais do Produto (NF-e)</label>
                        <textarea class="form-control" id="fiscal_info_adicional" name="fiscal_info_adicional" rows="2" 
                                  placeholder="Texto livre que aparecerá na NF-e como info adicional do produto"><?= e($p['fiscal_info_adicional'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Máscara simples para NCM (8 dígitos, somente números e ponto)
    const ncmInput = document.getElementById('fiscal_ncm');
    if (ncmInput) {
        ncmInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9.]/g, '');
        });
    }

    // Máscara simples para CEST (7 dígitos)
    const cestInput = document.getElementById('fiscal_cest');
    if (cestInput) {
        cestInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9.]/g, '');
        });
    }
});
</script>
