<?php
namespace Akti\Services;

/**
 * NfeXmlBuilder — Monta o XML da NF-e no formato 4.00.
 *
 * Usa a biblioteca sped-nfe (NFePHP\NFe\Make) para construir o XML
 * com os dados do emitente, destinatário e itens do pedido.
 *
 * Fase 2: integração com TaxCalculator para cálculo dinâmico de impostos,
 *         idDest dinâmico, infRespTec, cobr (fatura/duplicatas),
 *         modFrete e indPres dinâmicos.
 *
 * @package Akti\Services
 */
class NfeXmlBuilder
{
    private array $emitente;
    private array $orderData;
    private int $numero;
    private int $serie;
    private TaxCalculator $taxCalc;

    /** @var \Akti\Models\IbptaxModel|null IBPTax model para cálculo de tributos aproximados (Lei 12.741) */
    private $ibptaxModel = null;

    /** @var bool Indica se IBPTax está habilitado */
    private bool $ibptaxEnabled = false;

    /** @var array Dados fiscais calculados de cada item (para persistência em nfe_document_items) */
    private array $calculatedItems = [];

    /** @var array Totais calculados pelo TaxCalculator */
    private array $calculatedTotals = [];

    /**
     * Construtor da classe NfeXmlBuilder.
     *
     * @param array $emitente Emitente
     * @param array $orderData Order data
     * @param int $numero Numero
     * @param int $serie Serie
     */
    public function __construct(array $emitente, array $orderData, int $numero, int $serie)
    {
        $this->emitente  = $emitente;
        $this->orderData = $orderData;
        $this->numero    = $numero;
        $this->serie     = $serie;
        $this->taxCalc   = new TaxCalculator();

        // Inicializar IBPTax se disponível e habilitado
        $this->initIbptax();
    }

    /**
     * Inicializa o IBPTax model se a tabela existir e estiver habilitado.
     */
    private function initIbptax(): void
    {
        try {
            if (!class_exists(\Database::class)) return;
            $db = (new \Database())->getConnection();

            // Verificar configuração
            $q = $db->prepare("SELECT setting_value FROM company_settings WHERE setting_key = 'nfe_ibptax_enabled' LIMIT 1");
            $q->execute();
            $val = $q->fetchColumn();
            if ($val === false || $val === '0') return;

            // Verificar se tabela existe e tem dados
            $check = $db->query("SELECT COUNT(*) FROM tax_ibptax LIMIT 1");
            if ((int) $check->fetchColumn() === 0) return;

            $this->ibptaxModel = new \Akti\Models\IbptaxModel($db);
            $this->ibptaxEnabled = true;
        } catch (\Throwable $e) {
            // Silencioso — IBPTax é opcional
            $this->ibptaxEnabled = false;
        }
    }

    /**
     * Retorna os dados fiscais calculados de cada item (após build()).
     * Útil para persistir na tabela nfe_document_items.
     * @return array
     */
    public function getCalculatedItems(): array
    {
        return $this->calculatedItems;
    }

    /**
     * Retorna os totais fiscais calculados (após build()).
     * @return array
     */
    public function getCalculatedTotals(): array
    {
        return $this->calculatedTotals;
    }

    /**
     * Monta e retorna o XML da NF-e (ainda não assinado).
     * @return string XML
     * @throws \Exception Se faltar dados obrigatórios
     */
    public function build(): string
    {
        if (!class_exists(\NFePHP\NFe\Make::class)) {
            throw new \RuntimeException('Biblioteca sped-nfe não instalada. Execute: composer require nfephp-org/sped-nfe');
        }

        // ── FASE4-05: Validar CPF/CNPJ do destinatário antes de montar XML ──
        $destDoc = preg_replace('/\D/', '', $this->orderData['customer_cpf_cnpj'] ?? '');
        if (!empty($destDoc)) {
            if (strlen($destDoc) === 11) {
                if (!\Akti\Utils\Validator::isValidCpf($destDoc)) {
                    throw new \InvalidArgumentException('CPF do destinatário inválido: ' . $destDoc);
                }
            } elseif (strlen($destDoc) === 14) {
                if (!\Akti\Utils\Validator::isValidCnpj($destDoc)) {
                    throw new \InvalidArgumentException('CNPJ do destinatário inválido: ' . $destDoc);
                }
            } else {
                throw new \InvalidArgumentException('Documento do destinatário deve ter 11 (CPF) ou 14 (CNPJ) dígitos. Recebido: ' . strlen($destDoc) . ' dígitos.');
            }
        }

        $nfe = new \NFePHP\NFe\Make();
        $crt = (int) ($this->emitente['crt'] ?? 1);
        $ufOrig = strtoupper($this->emitente['uf'] ?? 'RS');
        $ufDest = strtoupper($this->orderData['customer_uf'] ?? ($this->emitente['uf'] ?? 'RS'));

        // ── infNFe ──
        $std = new \stdClass();
        $std->versao = '4.00';
        $std->Id = null;
        $std->pk_nItem = null;
        $nfe->taginfNFe($std);

        // ── ide ── (com idDest dinâmico, modFrete e indPres dinâmicos)
        $ide = new \stdClass();
        $ide->cUF = $this->getCodeUF($ufOrig);
        $ide->cNF = str_pad(random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT);
        $ide->natOp = $this->orderData['natureza_op'] ?? 'VENDA DE MERCADORIA';
        $ide->mod = 55;
        $ide->serie = $this->serie;
        $ide->nNF = $this->numero;
        $ide->dhEmi = date('c');
        $ide->dhSaiEnt = date('c');
        $ide->tpNF = 1; // saída
        $ide->idDest = TaxCalculator::calculateIdDest($ufOrig, $ufDest);
        $ide->cMunFG = $this->emitente['cod_municipio'] ?? '4314902';
        $ide->tpImp = 1; // retrato
        $ide->tpEmis = (int) ($this->emitente['tp_emis'] ?? 1);
        $ide->cDV = 0;
        $ide->tpAmb = ($this->emitente['environment'] ?? 'homologacao') === 'producao' ? 1 : 2;
        $ide->finNFe = (int) ($this->orderData['fin_nfe'] ?? 1);
        // 1=Normal, 2=Complementar, 3=Ajuste, 4=Devolução
        $ide->indFinal = 1; // consumidor final
        $ide->indPres = TaxCalculator::mapIndPres($this->orderData['sale_type'] ?? null);
        $ide->procEmi = 0; // emissão por aplicativo
        $ide->verProc = 'Akti 1.0';
        $nfe->tagide($ide);

        // ── NFref — Referência para NF-e de devolução, complementar ou ajuste ──
        if (in_array($ide->finNFe, [2, 3, 4]) && !empty($this->orderData['chave_ref'])) {
            $refNFe = new \stdClass();
            $refNFe->refNFe = $this->orderData['chave_ref'];
            $nfe->tagrefNFe($refNFe);
        }

        // ── emit ──
        $emit = new \stdClass();
        $emit->xNome = $this->emitente['razao_social'] ?? '';
        $emit->xFant = $this->emitente['nome_fantasia'] ?? '';
        $emit->IE = preg_replace('/\D/', '', $this->emitente['ie'] ?? '');
        $emit->CRT = $crt;
        $emit->CNPJ = preg_replace('/\D/', '', $this->emitente['cnpj'] ?? '');
        $nfe->tagemit($emit);

        // ── enderEmit ──
        $endEmit = new \stdClass();
        $endEmit->xLgr = $this->emitente['logradouro'] ?? '';
        $endEmit->nro = $this->emitente['numero'] ?? 'S/N';
        $endEmit->xCpl = $this->emitente['complemento'] ?? '';
        $endEmit->xBairro = $this->emitente['bairro'] ?? '';
        $endEmit->cMun = $this->emitente['cod_municipio'] ?? '';
        $endEmit->xMun = $this->emitente['municipio'] ?? '';
        $endEmit->UF = $ufOrig;
        $endEmit->CEP = preg_replace('/\D/', '', $this->emitente['cep'] ?? '');
        $endEmit->cPais = '1058';
        $endEmit->xPais = 'Brasil';
        $endEmit->fone = preg_replace('/\D/', '', $this->emitente['telefone'] ?? '');
        $nfe->tagenderEmit($endEmit);

        // ── dest ──
        $dest = new \stdClass();
        $cpfCnpj = preg_replace('/\D/', '', $this->orderData['customer_cpf_cnpj'] ?? '');
        if (strlen($cpfCnpj) === 14) {
            $dest->CNPJ = $cpfCnpj;
        } elseif (strlen($cpfCnpj) === 11) {
            $dest->CPF = $cpfCnpj;
        }
        $dest->xNome = $this->orderData['customer_name'] ?? 'NF-E EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL';
        $dest->indIEDest = 9; // não contribuinte
        if (!empty($this->orderData['customer_ie'])) {
            $dest->IE = preg_replace('/\D/', '', $this->orderData['customer_ie']);
            $dest->indIEDest = 1;
        }
        $nfe->tagdest($dest);

        // ── enderDest ──
        $endDest = new \stdClass();
        $endDest->xLgr = $this->orderData['customer_address'] ?? '';
        $endDest->nro = $this->orderData['customer_number'] ?? 'S/N';
        $endDest->xBairro = $this->orderData['customer_bairro'] ?? '';
        $endDest->cMun = $this->orderData['customer_cod_municipio'] ?? ($this->emitente['cod_municipio'] ?? '');
        $endDest->xMun = $this->orderData['customer_municipio'] ?? ($this->emitente['municipio'] ?? '');
        $endDest->UF = $ufDest;
        $endDest->CEP = preg_replace('/\D/', '', $this->orderData['customer_cep'] ?? '');
        $endDest->cPais = '1058';
        $endDest->xPais = 'Brasil';
        $nfe->tagenderDest($endDest);

        // ── itens — com TaxCalculator dinâmico ──
        $items = $this->orderData['items'] ?? [];
        $this->calculatedItems = [];
        $taxResults = [];

        foreach ($items as $idx => $item) {
            $nItem = $idx + 1;
            $vProd = round(($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0), 2);
            $vDesc = (float) ($item['discount'] ?? 0);

            // ── Validar NCM ──
            $ncm = preg_replace('/[.\-\s]/', '', $item['fiscal_ncm'] ?? $item['ncm'] ?? '');
            if (!TaxCalculator::validateNCM($ncm)) {
                $ncm = '00000000'; // Fallback — será rejeitado pela SEFAZ em produção
            }

            // ── Determinar CFOP dinâmico ──
            $cfop = TaxCalculator::determineCFOP($item, $ufOrig, $ufDest);

            // ── EAN ──
            $ean = !empty($item['fiscal_ean']) ? $item['fiscal_ean'] : 'SEM GTIN';

            $prod = new \stdClass();
            $prod->item = $nItem;
            $prod->cProd = $item['product_id'] ?? $nItem;
            $prod->cEAN = $ean;
            $prod->xProd = $item['product_name'] ?? 'Produto';
            $prod->NCM = $ncm;
            if (!empty($item['fiscal_cest'])) {
                $prod->CEST = preg_replace('/\D/', '', $item['fiscal_cest']);
            }
            $prod->CFOP = $cfop;
            $prod->uCom = $item['fiscal_unidade'] ?? $item['unit'] ?? 'UN';
            $prod->qCom = number_format($item['quantity'] ?? 1, 4, '.', '');
            $prod->vUnCom = number_format($item['unit_price'] ?? 0, 10, '.', '');
            $prod->vProd = number_format($vProd, 2, '.', '');
            $prod->cEANTrib = $ean;
            $prod->uTrib = $item['fiscal_unidade'] ?? $item['unit'] ?? 'UN';
            $prod->qTrib = number_format($item['quantity'] ?? 1, 4, '.', '');
            $prod->vUnTrib = number_format($item['unit_price'] ?? 0, 10, '.', '');
            $prod->indTot = 1;
            if ($vDesc > 0) {
                $prod->vDesc = number_format($vDesc, 2, '.', '');
            }
            $nfe->tagprod($prod);

            // ── Impostos via TaxCalculator ──
            $taxData = $this->taxCalc->calculateItem($item, [], $crt, $ufOrig, $ufDest);

            // ── IBPTax — Cálculo de tributos aproximados (Lei 12.741) ──
            $ibptaxItemData = ['vTotTrib' => 0.00, 'federal' => 0.00, 'estadual' => 0.00, 'municipal' => 0.00, 'found' => false, 'fonte' => ''];
            if ($this->ibptaxEnabled && $this->ibptaxModel !== null && !empty($ncm) && $ncm !== '00000000') {
                $baseCalcIbptax = $vProd - $vDesc;
                $origemItem = (string) ($item['fiscal_origem'] ?? $item['origem'] ?? '0');
                $ibptaxItemData = $this->ibptaxModel->calculateTaxApprox($ncm, $baseCalcIbptax, $origemItem);
            }

            // Usar IBPTax se disponível, senão fallback para cálculo do TaxCalculator
            if ($ibptaxItemData['found']) {
                $taxData['vTotTrib'] = $ibptaxItemData['vTotTrib'];
                $taxData['ibptax'] = $ibptaxItemData;
            }

            $taxResults[] = $taxData;

            $imposto = new \stdClass();
            $imposto->item = $nItem;
            if ($taxData['vTotTrib'] > 0) {
                $imposto->vTotTrib = number_format($taxData['vTotTrib'], 2, '.', '');
            }
            $nfe->tagimposto($imposto);

            // ── ICMS ──
            $icmsData = $taxData['icms'];
            if ($icmsData['type'] === 'ICMSSN') {
                // Simples Nacional — CSOSN
                $icms = new \stdClass();
                $icms->item = $nItem;
                $icms->orig = $icmsData['orig'];
                $icms->CSOSN = $icmsData['CSOSN'];
                if (isset($icmsData['pCredSN'])) {
                    $icms->pCredSN = number_format($icmsData['pCredSN'], 2, '.', '');
                    $icms->vCredICMSSN = number_format($icmsData['vCredICMSSN'] ?? 0, 2, '.', '');
                }
                $nfe->tagICMSSN($icms);
            } else {
                // Regime Normal — CST
                $icms = new \stdClass();
                $icms->item = $nItem;
                $icms->orig = $icmsData['orig'];
                $icms->CST = $icmsData['CST'];
                $icms->modBC = 3; // Valor da operação
                $icms->vBC = number_format($icmsData['vBC'], 2, '.', '');
                $icms->pICMS = number_format($icmsData['pICMS'], 2, '.', '');
                $icms->vICMS = number_format($icmsData['valor'], 2, '.', '');
                if (($icmsData['pRedBC'] ?? 0) > 0) {
                    $icms->pRedBC = number_format($icmsData['pRedBC'], 2, '.', '');
                }
                $nfe->tagICMS($icms);
            }

            // ── PIS ──
            $pisData = $taxData['pis'];
            $pis = new \stdClass();
            $pis->item = $nItem;
            $pis->CST = $pisData['CST'];
            $pis->vBC = number_format($pisData['vBC'], 2, '.', '');
            $pis->pPIS = number_format($pisData['pPIS'] ?? 0, 4, '.', '');
            $pis->vPIS = number_format($pisData['vPIS'] ?? $pisData['valor'] ?? 0, 2, '.', '');
            $nfe->tagPIS($pis);

            // ── COFINS ──
            $cofinsData = $taxData['cofins'];
            $cofins = new \stdClass();
            $cofins->item = $nItem;
            $cofins->CST = $cofinsData['CST'];
            $cofins->vBC = number_format($cofinsData['vBC'], 2, '.', '');
            $cofins->pCOFINS = number_format($cofinsData['pCOFINS'] ?? 0, 4, '.', '');
            $cofins->vCOFINS = number_format($cofinsData['vCOFINS'] ?? $cofinsData['valor'] ?? 0, 2, '.', '');
            $nfe->tagCOFINS($cofins);

            // ── IPI (se aplicável — CST 50 com alíquota) ──
            $ipiData = $taxData['ipi'];
            if ($ipiData['valor'] > 0) {
                $ipi = new \stdClass();
                $ipi->item = $nItem;
                $ipi->CST = $ipiData['CST'];
                $ipi->vBC = number_format($ipiData['vBC'], 2, '.', '');
                $ipi->pIPI = number_format($ipiData['pIPI'], 2, '.', '');
                $ipi->vIPI = number_format($ipiData['vIPI'], 2, '.', '');
                $nfe->tagIPI($ipi);
            }

            // ── DIFAL (ICMSUFDest) — Operações interestaduais para consumidor final ──
            if (!empty($taxData['difal']) && ($taxData['difal']['vICMSUFDest'] ?? 0) > 0) {
                $difal = $taxData['difal'];
                $icmsUFDest = new \stdClass();
                $icmsUFDest->item = $nItem;
                $icmsUFDest->vBCUFDest    = number_format($difal['vBCUFDest'] ?? 0, 2, '.', '');
                $icmsUFDest->vBCFCPUFDest = number_format($difal['vBCFCPUFDest'] ?? $difal['vBCUFDest'] ?? 0, 2, '.', '');
                $icmsUFDest->pFCPUFDest   = number_format($difal['pFCPUFDest'] ?? 0, 2, '.', '');
                $icmsUFDest->pICMSUFDest  = number_format($difal['pICMSUFDest'] ?? 0, 2, '.', '');
                $icmsUFDest->pICMSInter   = number_format($difal['pICMSInter'] ?? 0, 2, '.', '');
                $icmsUFDest->pICMSInterPart = '100.00'; // 100% para UF destino desde 2019
                $icmsUFDest->vFCPUFDest   = number_format($difal['vFCPUFDest'] ?? 0, 2, '.', '');
                $icmsUFDest->vICMSUFDest  = number_format($difal['vICMSUFDest'] ?? 0, 2, '.', '');
                $icmsUFDest->vICMSUFRemet = number_format($difal['vICMSUFRemet'] ?? 0, 2, '.', '');
                $nfe->tagICMSUFDest($icmsUFDest);
            }

            // Salvar dados calculados para persistência em nfe_document_items
            $this->calculatedItems[] = [
                'nItem'          => $nItem,
                'cProd'          => $item['product_id'] ?? $nItem,
                'xProd'          => $item['product_name'] ?? 'Produto',
                'ncm'            => $ncm,
                'cest'           => $item['fiscal_cest'] ?? null,
                'cfop'           => $cfop,
                'uCom'           => $item['fiscal_unidade'] ?? $item['unit'] ?? 'UN',
                'qCom'           => $item['quantity'] ?? 1,
                'vUnCom'         => $item['unit_price'] ?? 0,
                'vProd'          => $vProd,
                'vDesc'          => $vDesc,
                'origem'         => $icmsData['orig'],
                'icms_cst'       => $icmsData['type'] === 'ICMS' ? $icmsData['CST'] : null,
                'icms_csosn'     => $icmsData['type'] === 'ICMSSN' ? $icmsData['CSOSN'] : null,
                'icms_vbc'       => $icmsData['vBC'],
                'icms_aliquota'  => $icmsData['pICMS'],
                'icms_valor'     => $icmsData['valor'],
                'icms_reducao_bc'=> $icmsData['pRedBC'] ?? 0,
                'pis_cst'        => $pisData['CST'],
                'pis_vbc'        => $pisData['vBC'],
                'pis_aliquota'   => $pisData['pPIS'] ?? 0,
                'pis_valor'      => $pisData['valor'],
                'cofins_cst'     => $cofinsData['CST'],
                'cofins_vbc'     => $cofinsData['vBC'],
                'cofins_aliquota'=> $cofinsData['pCOFINS'] ?? 0,
                'cofins_valor'   => $cofinsData['valor'],
                'ipi_cst'        => $ipiData['CST'],
                'ipi_vbc'        => $ipiData['vBC'],
                'ipi_aliquota'   => $ipiData['pIPI'],
                'ipi_valor'      => $ipiData['valor'],
                'vTotTrib'       => $taxData['vTotTrib'],
                'ibptax_federal' => $ibptaxItemData['federal'] ?? 0,
                'ibptax_estadual'=> $ibptaxItemData['estadual'] ?? 0,
                'ibptax_municipal'=> $ibptaxItemData['municipal'] ?? 0,
                // DIFAL
                'difal_vbc'       => $taxData['difal']['vBCUFDest'] ?? 0,
                'difal_fcp'       => $taxData['difal']['vFCPUFDest'] ?? 0,
                'difal_icms_dest' => $taxData['difal']['vICMSUFDest'] ?? 0,
                'difal_icms_remet'=> $taxData['difal']['vICMSUFRemet'] ?? 0,
            ];
        }

        // ── ICMSTot — com totais calculados pelo TaxCalculator ──
        $this->calculatedTotals = $this->taxCalc->calculateTotal($taxResults);
        $totals = $this->calculatedTotals;

        $vFrete = (float) ($this->orderData['shipping_cost'] ?? 0);
        $vDescTotal = (float) ($this->orderData['discount'] ?? 0);
        $vNF = $totals['vProd'] - $vDescTotal + $vFrete + $totals['vIPI'];

        $icmsTot = new \stdClass();
        $icmsTot->vBC = number_format($totals['vBC'], 2, '.', '');
        $icmsTot->vICMS = number_format($totals['vICMS'], 2, '.', '');
        $icmsTot->vICMSDeson = number_format($totals['vICMSDeson'], 2, '.', '');
        $icmsTot->vFCP = number_format($totals['vFCP'], 2, '.', '');
        $icmsTot->vBCST = number_format($totals['vBCST'], 2, '.', '');
        $icmsTot->vST = number_format($totals['vST'], 2, '.', '');
        $icmsTot->vFCPST = number_format($totals['vFCPST'], 2, '.', '');
        $icmsTot->vFCPSTRet = number_format($totals['vFCPSTRet'], 2, '.', '');
        $icmsTot->vProd = number_format($totals['vProd'], 2, '.', '');
        $icmsTot->vFrete = number_format($vFrete, 2, '.', '');
        $icmsTot->vSeg = '0.00';
        $icmsTot->vDesc = number_format($vDescTotal, 2, '.', '');
        $icmsTot->vII = '0.00';
        $icmsTot->vIPI = number_format($totals['vIPI'], 2, '.', '');
        $icmsTot->vIPIDevol = '0.00';
        $icmsTot->vPIS = number_format($totals['vPIS'], 2, '.', '');
        $icmsTot->vCOFINS = number_format($totals['vCOFINS'], 2, '.', '');
        $icmsTot->vOutro = '0.00';
        $icmsTot->vNF = number_format($vNF, 2, '.', '');
        $icmsTot->vTotTrib = number_format($totals['vTotTrib'], 2, '.', '');
        // DIFAL — totais de ICMS UF Destino/Remetente
        $icmsTot->vFCPUFDest  = number_format($totals['vFCPUFDest'] ?? 0, 2, '.', '');
        $icmsTot->vICMSUFDest = number_format($totals['vICMSUFDest'] ?? 0, 2, '.', '');
        $icmsTot->vICMSUFRemet = number_format($totals['vICMSUFRemet'] ?? 0, 2, '.', '');
        $nfe->tagICMSTot($icmsTot);

        // ── transp — modFrete dinâmico ──
        $transp = new \stdClass();
        $transp->modFrete = TaxCalculator::mapModFrete(
            $this->orderData['shipping_type'] ?? null,
            $vFrete
        );
        $nfe->tagtransp($transp);

        // ── cobr — Fatura/Duplicatas (se vendas a prazo) ──
        $this->buildCobr($nfe, $vNF);

        // ── pag ──
        $pag = new \stdClass();
        $pag->vTroco = number_format($this->orderData['troco'] ?? 0, 2, '.', '');
        $nfe->tagpag($pag);

        $detPag = new \stdClass();
        $detPag->tPag = $this->mapPaymentMethod($this->orderData['payment_method'] ?? '');
        $detPag->vPag = number_format($vNF > 0 ? $vNF : 0, 2, '.', '');
        $nfe->tagdetPag($detPag);

        // ── infAdic ──
        $infAdic = new \stdClass();
        $infCpl = $this->orderData['observation'] ?? '';
        // Adicionar informação de tributos aproximados se Simples Nacional
        if ($crt === 1 || $crt === 2) {
            $infCpl .= ($infCpl ? ' | ' : '') . 'Documento emitido por ME ou EPP optante pelo Simples Nacional.';
        }

        // Adicionar mensagem de tributos aproximados (Lei 12.741 / IBPTax)
        if ($this->ibptaxEnabled && $totals['vTotTrib'] > 0) {
            // Identificar fonte dos dados IBPTax
            $ibptaxFonte = '';
            foreach ($this->calculatedItems as $ci) {
                if (!empty($ci['ibptax_federal']) || !empty($ci['ibptax_estadual'])) {
                    $ibptaxFonte = 'IBPT';
                    break;
                }
            }
            $msgTrib = \Akti\Models\IbptaxModel::buildTributosMensagem($totals['vTotTrib'], $ibptaxFonte ?: 'IBPT');
            if ($msgTrib) {
                $infCpl .= ($infCpl ? ' | ' : '') . $msgTrib;
            }
        }

        $infAdic->infCpl = substr(trim($infCpl), 0, 5000); // Limite NF-e
        $nfe->taginfAdic($infAdic);

        // ── infRespTec — Responsável técnico ──
        $this->buildInfRespTec($nfe);

        // ── Gerar XML ──
        $xml = $nfe->getXML();
        if (empty($xml)) {
            $errors = $nfe->getErrors();
            throw new \RuntimeException('Erro ao gerar XML: ' . implode('; ', $errors));
        }

        return $xml;
    }

    /**
     * Monta tag cobr (fatura/duplicatas) se o pedido tiver parcelas.
     *
     * @param \NFePHP\NFe\Make $nfe
     * @param float $vNF Valor total da NF-e
     */
    private function buildCobr($nfe, float $vNF): void
    {
        $installments = $this->orderData['installments'] ?? [];
        if (empty($installments)) {
            return;
        }

        // Tag fat (fatura)
        $fat = new \stdClass();
        $fat->nFat = $this->numero;
        $fat->vOrig = number_format($vNF, 2, '.', '');
        $fat->vDesc = number_format($this->orderData['discount'] ?? 0, 2, '.', '');
        $fat->vLiq = number_format($vNF, 2, '.', '');
        $nfe->tagfat($fat);

        // Tags dup (duplicatas)
        foreach ($installments as $idx => $inst) {
            $dup = new \stdClass();
            $dup->nDup = str_pad($idx + 1, 3, '0', STR_PAD_LEFT);
            $dup->dVenc = $inst['due_date'] ?? date('Y-m-d', strtotime('+' . (($idx + 1) * 30) . ' days'));
            $dup->vDup = number_format($inst['amount'] ?? ($vNF / count($installments)), 2, '.', '');
            $nfe->tagdup($dup);
        }
    }

    /**
     * Monta tag infRespTec (responsável técnico pelo software emissor).
     *
     * @param \NFePHP\NFe\Make $nfe
     */
    private function buildInfRespTec($nfe): void
    {
        // Dados do responsável técnico — configuráveis via variáveis de ambiente
        $cnpj = akti_env('AKTI_RESP_TEC_CNPJ') ?: '';
        $contato = akti_env('AKTI_RESP_TEC_CONTATO') ?: 'Akti Sistemas';
        $email = akti_env('AKTI_RESP_TEC_EMAIL') ?: 'suporte@useakti.com';
        $fone = akti_env('AKTI_RESP_TEC_FONE') ?: '';

        // Só adicionar se CNPJ do resp. técnico estiver configurado
        if (empty($cnpj)) {
            return;
        }

        $respTec = new \stdClass();
        $respTec->CNPJ = preg_replace('/\D/', '', $cnpj);
        $respTec->xContato = substr($contato, 0, 60);
        $respTec->email = substr($email, 0, 60);
        $respTec->fone = preg_replace('/\D/', '', $fone);
        $nfe->taginfRespTec($respTec);
    }

    /**
     * Mapeia forma de pagamento do sistema para código NFe.
     */
    private function mapPaymentMethod(string $method): string
    {
        $map = [
            'dinheiro'       => '01',
            'cheque'         => '02',
            'cartao_credito' => '03',
            'credit_card'    => '03',
            'cartao_debito'  => '04',
            'debit_card'     => '04',
            'credito_loja'   => '05',
            'vale_alimentacao' => '10',
            'vale_refeicao'  => '11',
            'vale_presente'  => '12',
            'vale_combustivel' => '13',
            'duplicata'      => '14',
            'boleto'         => '15',
            'deposito'       => '16',
            'pix'            => '17',
            'transferencia'  => '18',
            'sem_pagamento'  => '90',
            'outros'         => '99',
        ];
        return $map[strtolower($method)] ?? '99';
    }

    /**
     * Retorna código UF para SEFAZ.
     */
    private function getCodeUF(string $uf): int
    {
        $codes = [
            'AC' => 12, 'AL' => 27, 'AP' => 16, 'AM' => 13, 'BA' => 29,
            'CE' => 23, 'DF' => 53, 'ES' => 32, 'GO' => 52, 'MA' => 21,
            'MT' => 51, 'MS' => 50, 'MG' => 31, 'PA' => 15, 'PB' => 25,
            'PR' => 41, 'PE' => 26, 'PI' => 22, 'RJ' => 33, 'RN' => 24,
            'RS' => 43, 'RO' => 11, 'RR' => 14, 'SC' => 42, 'SP' => 35,
            'SE' => 28, 'TO' => 17,
        ];
        return $codes[strtoupper($uf)] ?? 43;
    }

    /**
     * Calcula e retorna os tributos aproximados para os itens informados,
     * usando os dados da IBPTax se habilitado.
     */
    private function calculateApproximateTaxes(array $items): string
    {
        if (!$this->ibptaxEnabled) {
            return '';
        }

        $totalTaxes = 0;
        foreach ($items as $item) {
            $ncm = preg_replace('/[.\-\s]/', '', $item['fiscal_ncm'] ?? $item['ncm'] ?? '');
            $quantity = (float) ($item['quantity'] ?? 1);
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $vProd = round($quantity * $unitPrice, 2);

            // Obter dados da IBPTax
            $ibptaxData = $this->ibptaxModel->getTaxData($ncm, $vProd);
            if ($ibptaxData) {
                $totalTaxes += $ibptaxData['vTotTrib'] ?? 0;
            }
        }

        return number_format($totalTaxes, 2, ',', '.');
    }
}
