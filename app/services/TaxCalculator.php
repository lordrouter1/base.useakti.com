<?php
namespace Akti\Services;

/**
 * TaxCalculator — Cálculo dinâmico de impostos para NF-e.
 *
 * Suporte a todos os regimes tributários:
 *   - CRT 1 — Simples Nacional (CSOSN)
 *   - CRT 2 — Simples Nacional Excedente
 *   - CRT 3 — Regime Normal (CST)
 *
 * Entradas: Dados do produto (fiscal fields), operação, regime, UFs.
 * Saídas: Arrays com valores calculados de cada tributo.
 *
 * NÃO contém HTML, echo, print ou acesso direto a $_POST/$_GET.
 *
 * @package Akti\Services
 */
class TaxCalculator
{
    /**
     * Alíquotas internas de ICMS por UF (padrão geral).
     * Algumas UFs usam alíquotas diferenciadas por produto — aqui o padrão modal.
     */
    private const ALIQUOTAS_ICMS_INTERNA = [
        'AC' => 19.0, 'AL' => 19.0, 'AP' => 18.0, 'AM' => 20.0, 'BA' => 20.5,
        'CE' => 20.0, 'DF' => 20.0, 'ES' => 17.0, 'GO' => 19.0, 'MA' => 22.0,
        'MT' => 17.0, 'MS' => 17.0, 'MG' => 18.0, 'PA' => 19.0, 'PB' => 20.0,
        'PR' => 19.5, 'PE' => 20.5, 'PI' => 21.0, 'RJ' => 22.0, 'RN' => 18.0,
        'RS' => 17.0, 'RO' => 19.5, 'RR' => 20.0, 'SC' => 17.0, 'SP' => 18.0,
        'SE' => 19.0, 'TO' => 20.0,
    ];

    /**
     * Alíquotas interestaduais de ICMS (origem → destino).
     * Regra geral: 12% (Sul/Sudeste exceto ES → outros)
     *              7% (Sul/Sudeste exceto ES → N/NE/CO/ES)
     */
    private const UFS_SUL_SUDESTE = ['SP', 'RJ', 'MG', 'PR', 'SC', 'RS'];

    /**
     * Calcula todos os impostos de um item para NF-e.
     *
     * @param array  $product   Dados do produto (com fiscal_* fields)
     * @param array  $operation Dados da operação (tipo, UF orig/dest)
     * @param int    $crt       CRT do emitente (1, 2 ou 3)
     * @param string $ufOrig    UF do emitente
     * @param string $ufDest    UF do destinatário
     * @return array Impostos calculados por item
     */
    public function calculateItem(array $product, array $operation, int $crt, string $ufOrig, string $ufDest): array
    {
        $vProd = round(($product['quantity'] ?? 1) * ($product['unit_price'] ?? 0), 2);
        $vDesc = (float) ($product['discount'] ?? 0);
        $baseCalculo = $vProd - $vDesc;

        $result = [
            'vProd'   => $vProd,
            'vDesc'   => $vDesc,
            'icms'    => $this->calculateICMS($product, $crt, $ufOrig, $ufDest, $baseCalculo),
            'pis'     => $this->calculatePIS($product, $crt, $baseCalculo),
            'cofins'  => $this->calculateCOFINS($product, $crt, $baseCalculo),
            'ipi'     => $this->calculateIPI($product, $crt, $baseCalculo),
        ];

        // Calcular DIFAL se interestadual e contribuinte
        if ($ufOrig !== $ufDest && $ufDest !== 'EX') {
            $result['difal'] = $this->calculateDIFAL($product, $ufOrig, $ufDest, $baseCalculo);
        }

        // Valor total de tributos (Lei 12.741 — transparência fiscal)
        $result['vTotTrib'] = round(
            ($result['icms']['valor'] ?? 0)
            + ($result['pis']['valor'] ?? 0)
            + ($result['cofins']['valor'] ?? 0)
            + ($result['ipi']['valor'] ?? 0),
            2
        );

        return $result;
    }

    /**
     * Calcula ICMS conforme CRT e dados do produto.
     *
     * CRT 1/2 → Simples Nacional (CSOSN)
     * CRT 3   → Regime Normal (CST)
     *
     * @param array  $product     Dados do produto
     * @param int    $crt         CRT do emitente
     * @param string $ufOrig      UF do emitente
     * @param string $ufDest      UF do destinatário
     * @param float  $baseCalculo Valor base para cálculo
     * @return array ['type'=>'ICMSSN'|'ICMS', 'orig'=>int, 'cst'|'csosn'=>string, 'vBC'=>float, 'pICMS'=>float, 'vICMS'=>float, 'pRedBC'=>float]
     */
    public function calculateICMS(array $product, int $crt, string $ufOrig, string $ufDest, float $baseCalculo): array
    {
        $origem = (int) ($product['fiscal_origem'] ?? $product['origem'] ?? 0);

        // ─── Simples Nacional (CRT 1 e 2) ───
        if ($crt === 1 || $crt === 2) {
            $csosn = $product['fiscal_csosn'] ?? $product['csosn'] ?? '102';
            return $this->calculateICMSSN($csosn, $origem, $baseCalculo, $product);
        }

        // ─── Regime Normal (CRT 3) ───
        $cst = $product['fiscal_cst_icms'] ?? $product['icms_cst'] ?? '00';
        $aliquota = (float) ($product['fiscal_aliq_icms'] ?? $product['icms_aliquota'] ?? 0);
        $reducaoBC = (float) ($product['fiscal_icms_reducao_bc'] ?? $product['icms_reducao_bc'] ?? 0);

        // Se alíquota não informada no produto, usar tabela da UF
        if ($aliquota <= 0) {
            if ($ufOrig === $ufDest) {
                $aliquota = self::ALIQUOTAS_ICMS_INTERNA[strtoupper($ufOrig)] ?? 18.0;
            } else {
                $aliquota = $this->getAliquotaInterestadual($ufOrig, $ufDest);
            }
        }

        return $this->calculateICMSNormal($cst, $origem, $baseCalculo, $aliquota, $reducaoBC);
    }

    /**
     * Calcula ICMS para Simples Nacional (CSOSN).
     */
    private function calculateICMSSN(string $csosn, int $origem, float $baseCalculo, array $product): array
    {
        $base = [
            'type'  => 'ICMSSN',
            'orig'  => $origem,
            'CSOSN' => $csosn,
            'vBC'   => 0,
            'pICMS' => 0,
            'valor' => 0,
        ];

        switch ($csosn) {
            case '101': // Tributada com permissão de crédito
                $pCredSN = (float) ($product['fiscal_aliq_icms'] ?? 0);
                $vCredSN = round($baseCalculo * $pCredSN / 100, 2);
                $base['pCredSN'] = $pCredSN;
                $base['vCredICMSSN'] = $vCredSN;
                break;

            case '102': // Tributada sem permissão de crédito
            case '103': // Isenção para faixa de receita bruta
            case '300': // Imune
            case '400': // Não tributada
                // Sem valores — apenas informativo
                break;

            case '201': // Tributada com crédito e cobrança por ST
            case '202': // Tributada sem crédito e cobrança por ST
            case '203': // Isenção e cobrança por ST
                // ST — simplificado (valores de ST devem ser informados externamente)
                break;

            case '500': // ICMS cobrado anteriormente por ST
                $base['vBCSTRet'] = 0;
                $base['vICMSSTRet'] = 0;
                break;

            case '900': // Outros
                $pICMS = (float) ($product['fiscal_aliq_icms'] ?? 0);
                $vBC = $baseCalculo;
                $vICMS = round($vBC * $pICMS / 100, 2);
                $base['vBC'] = $vBC;
                $base['pICMS'] = $pICMS;
                $base['valor'] = $vICMS;
                break;
        }

        return $base;
    }

    /**
     * Calcula ICMS para Regime Normal (CST).
     */
    private function calculateICMSNormal(string $cst, int $origem, float $baseCalculo, float $aliquota, float $reducaoBC): array
    {
        $base = [
            'type'    => 'ICMS',
            'orig'    => $origem,
            'CST'     => $cst,
            'vBC'     => 0,
            'pICMS'   => 0,
            'valor'   => 0,
            'pRedBC'  => 0,
        ];

        switch ($cst) {
            case '00': // Tributada integralmente
                $base['vBC'] = $baseCalculo;
                $base['pICMS'] = $aliquota;
                $base['valor'] = round($baseCalculo * $aliquota / 100, 2);
                break;

            case '10': // Tributada com cobrança de ICMS por ST
                $base['vBC'] = $baseCalculo;
                $base['pICMS'] = $aliquota;
                $base['valor'] = round($baseCalculo * $aliquota / 100, 2);
                // ST — valores de ST devem ser calculados separadamente
                break;

            case '20': // Com redução de base de cálculo
                $base['pRedBC'] = $reducaoBC;
                $bcReduzida = $baseCalculo * (1 - $reducaoBC / 100);
                $base['vBC'] = round($bcReduzida, 2);
                $base['pICMS'] = $aliquota;
                $base['valor'] = round($bcReduzida * $aliquota / 100, 2);
                break;

            case '30': // Isenta/não tributada com cobrança por ST
            case '40': // Isenta
            case '41': // Não tributada
            case '50': // Suspensão
                // Sem valores
                $base['vICMSDeson'] = $baseCalculo > 0 ? round($baseCalculo * $aliquota / 100, 2) : 0;
                break;

            case '51': // Diferimento
                $base['vBC'] = $baseCalculo;
                $base['pICMS'] = $aliquota;
                $base['valor'] = round($baseCalculo * $aliquota / 100, 2);
                $base['vICMSOp'] = $base['valor'];
                $base['pDif'] = 100.0; // Diferimento total (ajustável)
                $base['vICMSDif'] = $base['valor'];
                $base['valor'] = 0; // ICMS diferido = não recolhido na operação
                break;

            case '60': // ICMS cobrado anteriormente por ST
                // Apenas informativo
                $base['vBCSTRet'] = 0;
                $base['pST'] = 0;
                $base['vICMSSTRet'] = 0;
                break;

            case '70': // Com redução de BC e cobrança por ST
                $base['pRedBC'] = $reducaoBC;
                $bcReduzida = $baseCalculo * (1 - $reducaoBC / 100);
                $base['vBC'] = round($bcReduzida, 2);
                $base['pICMS'] = $aliquota;
                $base['valor'] = round($bcReduzida * $aliquota / 100, 2);
                break;

            case '90': // Outros
                $base['vBC'] = $baseCalculo;
                $base['pICMS'] = $aliquota;
                $base['valor'] = round($baseCalculo * $aliquota / 100, 2);
                break;
        }

        return $base;
    }

    /**
     * Calcula PIS.
     *
     * @param array $product     Dados do produto
     * @param int   $crt         CRT do emitente
     * @param float $baseCalculo Base de cálculo
     * @return array ['CST'=>string, 'vBC'=>float, 'pPIS'=>float, 'valor'=>float]
     */
    public function calculatePIS(array $product, int $crt, float $baseCalculo): array
    {
        $cst = $product['fiscal_cst_pis'] ?? $product['pis_cst'] ?? null;
        $aliquota = (float) ($product['fiscal_aliq_pis'] ?? $product['pis_aliquota'] ?? 0);

        // Se CST não informado, definir padrão conforme CRT
        if ($cst === null || $cst === '') {
            if ($crt === 1 || $crt === 2) {
                // Simples Nacional: geralmente isento (CST 99 com alíq. 0)
                $cst = '99';
                $aliquota = 0;
            } else {
                // Regime Normal: cumulativo padrão
                $cst = '01';
                $aliquota = $aliquota > 0 ? $aliquota : 0.65;
            }
        }

        return $this->calculatePISCOFINS($cst, $aliquota, $baseCalculo, 'PIS');
    }

    /**
     * Calcula COFINS.
     *
     * @param array $product     Dados do produto
     * @param int   $crt         CRT do emitente
     * @param float $baseCalculo Base de cálculo
     * @return array ['CST'=>string, 'vBC'=>float, 'pCOFINS'=>float, 'valor'=>float]
     */
    public function calculateCOFINS(array $product, int $crt, float $baseCalculo): array
    {
        $cst = $product['fiscal_cst_cofins'] ?? $product['cofins_cst'] ?? null;
        $aliquota = (float) ($product['fiscal_aliq_cofins'] ?? $product['cofins_aliquota'] ?? 0);

        if ($cst === null || $cst === '') {
            if ($crt === 1 || $crt === 2) {
                $cst = '99';
                $aliquota = 0;
            } else {
                $cst = '01';
                $aliquota = $aliquota > 0 ? $aliquota : 3.00;
            }
        }

        return $this->calculatePISCOFINS($cst, $aliquota, $baseCalculo, 'COFINS');
    }

    /**
     * Cálculo genérico de PIS/COFINS (mesma lógica, CSTs idênticos).
     */
    private function calculatePISCOFINS(string $cst, float $aliquota, float $baseCalculo, string $tipo): array
    {
        $keyAliq = $tipo === 'PIS' ? 'pPIS' : 'pCOFINS';
        $keyVal  = $tipo === 'PIS' ? 'vPIS' : 'vCOFINS';

        $result = [
            'CST'    => $cst,
            'vBC'    => 0,
            $keyAliq => 0,
            $keyVal  => 0,
            'valor'  => 0,
        ];

        // CSTs que geram valor (01, 02, 03)
        if (in_array($cst, ['01', '02', '03'])) {
            $result['vBC'] = $baseCalculo;
            $result[$keyAliq] = $aliquota;
            $valor = round($baseCalculo * $aliquota / 100, 2);
            $result[$keyVal] = $valor;
            $result['valor'] = $valor;
        }
        // CSTs 04-09, 49, 99 — sem valor calculado (isento, suspensão, alíquota zero, outros)
        // Manter CST informado com valores zerados

        return $result;
    }

    /**
     * Calcula IPI (Imposto sobre Produtos Industrializados).
     *
     * @param array $product     Dados do produto
     * @param int   $crt         CRT do emitente
     * @param float $baseCalculo Base de cálculo
     * @return array ['CST'=>string, 'vBC'=>float, 'pIPI'=>float, 'valor'=>float]
     */
    public function calculateIPI(array $product, int $crt, float $baseCalculo): array
    {
        $cst = $product['fiscal_cst_ipi'] ?? $product['ipi_cst'] ?? null;
        $aliquota = (float) ($product['fiscal_aliq_ipi'] ?? $product['ipi_aliquota'] ?? 0);

        $result = [
            'CST'      => $cst ?? '99',
            'vBC'      => 0,
            'pIPI'     => 0,
            'vIPI'     => 0,
            'valor'    => 0,
        ];

        // Simples Nacional geralmente não calcula IPI na saída (exceto industrialização)
        if (($crt === 1 || $crt === 2) && ($cst === null || $cst === '')) {
            $result['CST'] = '99';
            return $result;
        }

        // CST 50 — Saída tributada
        if ($cst === '50' && $aliquota > 0) {
            $result['vBC'] = $baseCalculo;
            $result['pIPI'] = $aliquota;
            $valor = round($baseCalculo * $aliquota / 100, 2);
            $result['vIPI'] = $valor;
            $result['valor'] = $valor;
        }

        return $result;
    }

    /**
     * Calcula DIFAL — Diferencial de Alíquota Interestadual.
     * Aplica-se a vendas interestaduais para consumidor final não contribuinte.
     *
     * @param array  $product     Dados do produto
     * @param string $ufOrig      UF do emitente
     * @param string $ufDest      UF do destinatário
     * @param float  $baseCalculo Base de cálculo
     * @return array ['vBCUFDest'=>float, 'pFCPUFDest'=>float, 'pICMSUFDest'=>float, 'pICMSInter'=>float, 'vFCPUFDest'=>float, 'vICMSUFDest'=>float, 'vICMSUFRemet'=>float]
     */
    public function calculateDIFAL(array $product, string $ufOrig, string $ufDest, float $baseCalculo): array
    {
        $aliqInterna = self::ALIQUOTAS_ICMS_INTERNA[strtoupper($ufDest)] ?? 18.0;
        $aliqInter = $this->getAliquotaInterestadual($ufOrig, $ufDest);
        $pFCP = (float) ($product['fcp_aliquota'] ?? 0); // Fundo de Combate à Pobreza (varia por UF e produto)

        $diffAliq = $aliqInterna - $aliqInter;

        // Base de cálculo (pode ser dupla — BC cheia, mas aqui simplificado)
        $vBCUFDest = $baseCalculo;

        // 100% para destino desde 2019
        $partilhaDestino = 100.0;

        $vDifal = round($vBCUFDest * $diffAliq / 100, 2);
        $vFCP = round($vBCUFDest * $pFCP / 100, 2);

        return [
            'vBCUFDest'    => $vBCUFDest,
            'pFCPUFDest'   => $pFCP,
            'pICMSUFDest'  => $aliqInterna,
            'pICMSInter'   => $aliqInter,
            'pICMSInterPart' => $partilhaDestino,
            'vFCPUFDest'   => $vFCP,
            'vICMSUFDest'  => $vDifal,
            'vICMSUFRemet' => 0, // Desde 2019, 100% para o destino
        ];
    }

    /**
     * Totaliza impostos de todos os itens.
     *
     * @param array $items Array de resultados de calculateItem()
     * @return array Totais de cada tributo
     */
    public function calculateTotal(array $items): array
    {
        $totals = [
            'vBC'         => 0, // BC do ICMS
            'vICMS'       => 0,
            'vICMSDeson'  => 0,
            'vFCP'        => 0,
            'vBCST'       => 0,
            'vST'         => 0,
            'vFCPST'      => 0,
            'vFCPSTRet'   => 0,
            'vProd'       => 0,
            'vDesc'       => 0,
            'vII'         => 0,
            'vIPI'        => 0,
            'vIPIDevol'   => 0,
            'vPIS'        => 0,
            'vCOFINS'     => 0,
            'vOutro'      => 0,
            'vTotTrib'    => 0,
            // DIFAL
            'vFCPUFDest'  => 0,
            'vICMSUFDest' => 0,
            'vICMSUFRemet' => 0,
        ];

        foreach ($items as $item) {
            $totals['vProd'] += ($item['vProd'] ?? 0);
            $totals['vDesc'] += ($item['vDesc'] ?? 0);

            // ICMS
            $icms = $item['icms'] ?? [];
            $totals['vBC'] += ($icms['vBC'] ?? 0);
            $totals['vICMS'] += ($icms['valor'] ?? 0);
            $totals['vICMSDeson'] += ($icms['vICMSDeson'] ?? 0);

            // PIS
            $totals['vPIS'] += ($item['pis']['valor'] ?? 0);

            // COFINS
            $totals['vCOFINS'] += ($item['cofins']['valor'] ?? 0);

            // IPI
            $totals['vIPI'] += ($item['ipi']['valor'] ?? 0);

            // Transparência fiscal
            $totals['vTotTrib'] += ($item['vTotTrib'] ?? 0);

            // DIFAL
            if (isset($item['difal'])) {
                $totals['vFCPUFDest'] += ($item['difal']['vFCPUFDest'] ?? 0);
                $totals['vICMSUFDest'] += ($item['difal']['vICMSUFDest'] ?? 0);
                $totals['vICMSUFRemet'] += ($item['difal']['vICMSUFRemet'] ?? 0);
            }
        }

        // Arredondar todos os totais
        foreach ($totals as $k => $v) {
            $totals[$k] = round($v, 2);
        }

        return $totals;
    }

    // ══════════════════════════════════════════════════════════════
    // Helpers
    // ══════════════════════════════════════════════════════════════

    /**
     * Retorna alíquota interestadual de ICMS.
     */
    public function getAliquotaInterestadual(string $ufOrig, string $ufDest): float
    {
        $ufOrig = strtoupper($ufOrig);
        $ufDest = strtoupper($ufDest);

        // Mesma UF — usar alíquota interna
        if ($ufOrig === $ufDest) {
            return self::ALIQUOTAS_ICMS_INTERNA[$ufOrig] ?? 18.0;
        }

        $origSulSudeste = in_array($ufOrig, self::UFS_SUL_SUDESTE);
        $destSulSudeste = in_array($ufDest, self::UFS_SUL_SUDESTE);

        // Sul/Sudeste → Sul/Sudeste = 12%
        // Sul/Sudeste → N/NE/CO/ES = 7%
        // N/NE/CO/ES → qualquer = 12%
        if ($origSulSudeste && !$destSulSudeste) {
            return 7.0;
        }

        return 12.0;
    }

    /**
     * Calcula o idDest (indicador de destino) dinamicamente.
     *
     * @param string $ufEmitente    UF do emitente
     * @param string $ufDestinatario UF do destinatário
     * @return int 1=interna, 2=interestadual, 3=exterior
     */
    public static function calculateIdDest(string $ufEmitente, string $ufDestinatario): int
    {
        $ufEmitente = strtoupper(trim($ufEmitente));
        $ufDestinatario = strtoupper(trim($ufDestinatario));

        if ($ufDestinatario === 'EX') {
            return 3; // Exportação
        }

        if ($ufEmitente === $ufDestinatario) {
            return 1; // Operação interna
        }

        return 2; // Interestadual
    }

    /**
     * Determina o CFOP correto com base na operação e UFs.
     *
     * @param array  $product Dados do produto (com fiscal_cfop, fiscal_cfop_interestadual)
     * @param string $ufOrig  UF do emitente
     * @param string $ufDest  UF do destinatário
     * @return string CFOP
     */
    public static function determineCFOP(array $product, string $ufOrig, string $ufDest): string
    {
        $ufOrig = strtoupper(trim($ufOrig));
        $ufDest = strtoupper(trim($ufDest));

        if ($ufDest === 'EX') {
            return '7102'; // Exportação de mercadoria
        }

        if ($ufOrig === $ufDest) {
            // Operação interna — CFOP iniciado com 5
            return $product['fiscal_cfop'] ?? $product['cfop'] ?? '5102';
        }

        // Interestadual — CFOP iniciado com 6
        if (!empty($product['fiscal_cfop_interestadual'])) {
            return $product['fiscal_cfop_interestadual'];
        }

        // Converter CFOP interno para interestadual (5xxx → 6xxx)
        $cfopInterno = $product['fiscal_cfop'] ?? $product['cfop'] ?? '5102';
        if (substr($cfopInterno, 0, 1) === '5') {
            return '6' . substr($cfopInterno, 1);
        }

        return '6102'; // Fallback
    }

    /**
     * Valida NCM — 8 dígitos numéricos.
     *
     * @param string|null $ncm
     * @return bool
     */
    public static function validateNCM(?string $ncm): bool
    {
        if ($ncm === null || $ncm === '') {
            return false;
        }
        $ncm = preg_replace('/[.\-\s]/', '', $ncm);
        return preg_match('/^\d{8}$/', $ncm) === 1;
    }

    /**
     * Valida CFOP — 4 dígitos numéricos, iniciando com 1-7.
     *
     * @param string|null $cfop
     * @return bool
     */
    public static function validateCFOP(?string $cfop): bool
    {
        if ($cfop === null || $cfop === '') {
            return false;
        }
        return preg_match('/^[1-7]\d{3}$/', $cfop) === 1;
    }

    /**
     * Mapeia modFrete do pedido para código NF-e.
     *
     * @param string|null $shippingType Tipo de frete do pedido
     * @param float       $shippingCost Valor do frete
     * @return int Código modFrete NF-e
     */
    public static function mapModFrete(?string $shippingType, float $shippingCost = 0): int
    {
        // Códigos NF-e 4.00:
        // 0 = Frete por conta do remetente (CIF)
        // 1 = Frete por conta do destinatário (FOB)
        // 2 = Frete por conta de terceiros
        // 3 = Transporte próprio por conta do remetente
        // 4 = Transporte próprio por conta do destinatário
        // 9 = Sem frete

        $map = [
            'retirada'    => 9, // Sem frete (cliente retira)
            'entrega'     => 0, // CIF — remetente paga o frete
            'correios'    => 0, // CIF — remetente despacha
            'cif'         => 0,
            'fob'         => 1,
            'terceiros'   => 2,
            'proprio_rem' => 3,
            'proprio_des' => 4,
            'sem_frete'   => 9,
        ];

        $type = strtolower(trim($shippingType ?? ''));
        if (isset($map[$type])) {
            return $map[$type];
        }

        // Se não mapeado mas tem valor de frete, assume CIF
        return $shippingCost > 0 ? 0 : 9;
    }

    /**
     * Mapeia indPres (indicador de presença) da venda.
     *
     * @param string|null $saleType Tipo de venda do pedido
     * @return int Código indPres NF-e
     */
    public static function mapIndPres(?string $saleType): int
    {
        // 0 = Não se aplica
        // 1 = Presencial
        // 2 = Internet
        // 3 = Teleatendimento
        // 4 = Entrega em domicílio (NFC-e)
        // 5 = Presencial fora do estabelecimento
        // 9 = Outros

        $map = [
            'presencial'     => 1,
            'internet'       => 2,
            'online'         => 2,
            'telemarketing'  => 3,
            'telefone'       => 3,
            'entrega'        => 4,
            'delivery'       => 4,
            'externo'        => 5,
            'outros'         => 9,
        ];

        $type = strtolower(trim($saleType ?? ''));
        return $map[$type] ?? 1; // Padrão: presencial
    }
}
