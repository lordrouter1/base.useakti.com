<?php
namespace Akti\Services;

/**
 * NfceXmlBuilder — Monta XML da NFC-e (modelo 65) no formato 4.00.
 *
 * Diferenças em relação à NF-e (modelo 55):
 *   - mod=65
 *   - Destinatário opcional (CPF opcional)
 *   - Sem transporte detalhado
 *   - QR Code obrigatório (via CSC)
 *   - Numeração separada (proximo_numero_nfce)
 *   - Operação sempre presencial (indPres=1)
 *   - Sempre consumidor final (indFinal=1)
 *   - Sempre operação interna (idDest=1)
 *
 * @package Akti\Services
 */
class NfceXmlBuilder
{
    private array $emitente;
    private array $orderData;
    private int $numero;
    private int $serie;
    private TaxCalculator $taxCalc;

    /** @var array Dados fiscais calculados de cada item */
    private array $calculatedItems = [];

    /** @var array Totais calculados pelo TaxCalculator */
    private array $calculatedTotals = [];

    /** @var string URL do QR Code gerado */
    private string $qrcodeUrl = '';

    /**
     * Construtor da classe NfceXmlBuilder.
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
    }

    /**
     * Retorna os dados fiscais calculados de cada item (após build()).
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
     * Retorna URL do QR Code gerado (após build()).
     * @return string
     */
    public function getQrCodeUrl(): string
    {
        return $this->qrcodeUrl;
    }

    /**
     * Monta e retorna o XML da NFC-e (não assinado).
     *
     * @return string XML
     * @throws \Exception Se faltar dados obrigatórios
     */
    public function build(): string
    {
        if (!class_exists(\NFePHP\NFe\Make::class)) {
            throw new \RuntimeException('Biblioteca sped-nfe não instalada.');
        }

        // Validar CPF do destinatário (opcional em NFC-e, mas se informado, deve ser válido)
        $destDoc = preg_replace('/\D/', '', $this->orderData['customer_cpf_cnpj'] ?? '');
        if (!empty($destDoc)) {
            if (strlen($destDoc) === 11 && !\Akti\Utils\Validator::isValidCpf($destDoc)) {
                throw new \InvalidArgumentException('CPF do consumidor inválido: ' . $destDoc);
            }
            if (strlen($destDoc) === 14 && !\Akti\Utils\Validator::isValidCnpj($destDoc)) {
                throw new \InvalidArgumentException('CNPJ do consumidor inválido: ' . $destDoc);
            }
        }

        $nfe = new \NFePHP\NFe\Make();
        $crt = (int) ($this->emitente['crt'] ?? 1);
        $ufOrig = strtoupper($this->emitente['uf'] ?? 'RS');

        // ── infNFe ──
        $std = new \stdClass();
        $std->versao = '4.00';
        $std->Id = null;
        $std->pk_nItem = null;
        $nfe->taginfNFe($std);

        // ── ide (NFC-e específico) ──
        $ide = new \stdClass();
        $ide->cUF = $this->getCodeUF($ufOrig);
        $ide->cNF = str_pad(random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT);
        $ide->natOp = $this->orderData['natureza_op'] ?? 'VENDA DE MERCADORIA';
        $ide->mod = 65; // NFC-e
        $ide->serie = $this->serie;
        $ide->nNF = $this->numero;
        $ide->dhEmi = date('c');
        $ide->tpNF = 1; // saída
        $ide->idDest = 1; // NFC-e é sempre operação interna
        $ide->cMunFG = $this->emitente['cod_municipio'] ?? '4314902';
        $ide->tpImp = 4; // DANFE NFC-e
        $ide->tpEmis = (int) ($this->emitente['tp_emis'] ?? 1);
        $ide->cDV = 0;
        $ide->tpAmb = ($this->emitente['environment'] ?? 'homologacao') === 'producao' ? 1 : 2;
        $ide->finNFe = 1; // Normal
        $ide->indFinal = 1; // sempre consumidor final
        $ide->indPres = 1; // sempre presencial em NFC-e
        $ide->procEmi = 0;
        $ide->verProc = 'Akti 1.0';
        $nfe->tagide($ide);

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

        // ── dest (opcional em NFC-e) ──
        if (!empty($destDoc)) {
            $dest = new \stdClass();
            if (strlen($destDoc) === 14) {
                $dest->CNPJ = $destDoc;
            } elseif (strlen($destDoc) === 11) {
                $dest->CPF = $destDoc;
            }
            $dest->xNome = $this->orderData['customer_name'] ?? 'CONSUMIDOR';
            $dest->indIEDest = 9; // NFC-e: sempre não contribuinte
            $nfe->tagdest($dest);
        }

        // ── itens ──
        $items = $this->orderData['items'] ?? [];
        $this->calculatedItems = [];
        $taxResults = [];

        foreach ($items as $idx => $item) {
            $nItem = $idx + 1;
            $vProd = round(($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0), 2);
            $vDesc = (float) ($item['discount'] ?? 0);

            $ncm = preg_replace('/[.\-\s]/', '', $item['fiscal_ncm'] ?? $item['ncm'] ?? '');
            if (!TaxCalculator::validateNCM($ncm)) {
                $ncm = '00000000';
            }

            // NFC-e: CFOP sempre interno (5xxx)
            $cfop = $item['fiscal_cfop_interna'] ?? '5102';
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
            $prod->uTrib = $prod->uCom;
            $prod->qTrib = $prod->qCom;
            $prod->vUnTrib = $prod->vUnCom;
            $prod->indTot = 1;
            if ($vDesc > 0) {
                $prod->vDesc = number_format($vDesc, 2, '.', '');
            }
            $nfe->tagprod($prod);

            // Impostos
            $taxData = $this->taxCalc->calculateItem($item, [], $crt, $ufOrig, $ufOrig);
            $taxResults[] = $taxData;

            $imposto = new \stdClass();
            $imposto->item = $nItem;
            if ($taxData['vTotTrib'] > 0) {
                $imposto->vTotTrib = number_format($taxData['vTotTrib'], 2, '.', '');
            }
            $nfe->tagimposto($imposto);

            // ICMS
            $icmsData = $taxData['icms'];
            if ($icmsData['type'] === 'ICMSSN') {
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
                $icms = new \stdClass();
                $icms->item = $nItem;
                $icms->orig = $icmsData['orig'];
                $icms->CST = $icmsData['CST'];
                $icms->modBC = 3;
                $icms->vBC = number_format($icmsData['vBC'], 2, '.', '');
                $icms->pICMS = number_format($icmsData['pICMS'], 2, '.', '');
                $icms->vICMS = number_format($icmsData['valor'], 2, '.', '');
                $nfe->tagICMS($icms);
            }

            // PIS
            $pisData = $taxData['pis'];
            $pis = new \stdClass();
            $pis->item = $nItem;
            $pis->CST = $pisData['CST'];
            $pis->vBC = number_format($pisData['vBC'], 2, '.', '');
            $pis->pPIS = number_format($pisData['pPIS'] ?? 0, 4, '.', '');
            $pis->vPIS = number_format($pisData['vPIS'] ?? $pisData['valor'] ?? 0, 2, '.', '');
            $nfe->tagPIS($pis);

            // COFINS
            $cofinsData = $taxData['cofins'];
            $cofins = new \stdClass();
            $cofins->item = $nItem;
            $cofins->CST = $cofinsData['CST'];
            $cofins->vBC = number_format($cofinsData['vBC'], 2, '.', '');
            $cofins->pCOFINS = number_format($cofinsData['pCOFINS'] ?? 0, 4, '.', '');
            $cofins->vCOFINS = number_format($cofinsData['vCOFINS'] ?? $cofinsData['valor'] ?? 0, 2, '.', '');
            $nfe->tagCOFINS($cofins);

            // Salvar item calculado
            $this->calculatedItems[] = [
                'nItem'     => $nItem,
                'cProd'     => $item['product_id'] ?? $nItem,
                'xProd'     => $item['product_name'] ?? 'Produto',
                'ncm'       => $ncm,
                'cfop'      => $cfop,
                'uCom'      => $prod->uCom,
                'qCom'      => $item['quantity'] ?? 1,
                'vUnCom'    => $item['unit_price'] ?? 0,
                'vProd'     => $vProd,
                'vDesc'     => $vDesc,
                'origem'    => $icmsData['orig'],
                'icms_cst'  => $icmsData['type'] === 'ICMS' ? $icmsData['CST'] : null,
                'icms_csosn' => $icmsData['type'] === 'ICMSSN' ? $icmsData['CSOSN'] : null,
                'icms_vbc'  => $icmsData['vBC'],
                'icms_aliquota' => $icmsData['pICMS'],
                'icms_valor' => $icmsData['valor'],
                'pis_cst'   => $pisData['CST'],
                'pis_valor' => $pisData['valor'],
                'cofins_cst'   => $cofinsData['CST'],
                'cofins_valor' => $cofinsData['valor'],
                'vTotTrib'  => $taxData['vTotTrib'],
            ];
        }

        // ── Totais ──
        $this->calculatedTotals = $this->taxCalc->calculateTotal($taxResults);
        $totals = $this->calculatedTotals;
        $vFrete = 0; // NFC-e não tem frete
        $vDescTotal = (float) ($this->orderData['discount'] ?? 0);
        $vNF = $totals['vProd'] - $vDescTotal;

        $icmsTot = new \stdClass();
        $icmsTot->vBC = number_format($totals['vBC'], 2, '.', '');
        $icmsTot->vICMS = number_format($totals['vICMS'], 2, '.', '');
        $icmsTot->vICMSDeson = '0.00';
        $icmsTot->vFCP = '0.00';
        $icmsTot->vBCST = '0.00';
        $icmsTot->vST = '0.00';
        $icmsTot->vFCPST = '0.00';
        $icmsTot->vFCPSTRet = '0.00';
        $icmsTot->vProd = number_format($totals['vProd'], 2, '.', '');
        $icmsTot->vFrete = '0.00';
        $icmsTot->vSeg = '0.00';
        $icmsTot->vDesc = number_format($vDescTotal, 2, '.', '');
        $icmsTot->vII = '0.00';
        $icmsTot->vIPI = '0.00';
        $icmsTot->vIPIDevol = '0.00';
        $icmsTot->vPIS = number_format($totals['vPIS'], 2, '.', '');
        $icmsTot->vCOFINS = number_format($totals['vCOFINS'], 2, '.', '');
        $icmsTot->vOutro = '0.00';
        $icmsTot->vNF = number_format($vNF, 2, '.', '');
        $icmsTot->vTotTrib = number_format($totals['vTotTrib'], 2, '.', '');
        $nfe->tagICMSTot($icmsTot);

        // ── transp (simplificado em NFC-e) ──
        $transp = new \stdClass();
        $transp->modFrete = 9; // Sem frete
        $nfe->tagtransp($transp);

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
        if ($crt === 1 || $crt === 2) {
            $infCpl .= ($infCpl ? ' | ' : '') . 'Documento emitido por ME ou EPP optante pelo Simples Nacional.';
        }
        $infAdic->infCpl = substr(trim($infCpl), 0, 5000);
        $nfe->taginfAdic($infAdic);

        // ── Gerar XML ──
        $xml = $nfe->getXML();
        if (empty($xml)) {
            $errors = $nfe->getErrors();
            throw new \RuntimeException('Erro ao gerar XML NFC-e: ' . implode('; ', $errors));
        }

        return $xml;
    }

    /**
     * Gera URL do QR Code da NFC-e.
     *
     * @param string $chave    Chave de acesso (44 dígitos)
     * @param int    $tpAmb    Ambiente: 1=Produção, 2=Homologação
     * @param string $cscId    ID do CSC
     * @param string $cscToken Token do CSC
     * @return string URL do QR Code
     */
    public static function generateQrCode(string $chave, int $tpAmb, string $cscId, string $cscToken): string
    {
        // URL base por UF — simplificado (usar sped-nfe para URLs reais)
        $urlBase = $tpAmb === 1
            ? 'https://www.nfce.fazenda.sp.gov.br/NFCeConsultaPublica/Paginas/ConsultaQRCode.aspx'
            : 'https://homologacao.nfce.fazenda.sp.gov.br/NFCeConsultaPublica/Paginas/ConsultaQRCode.aspx';

        // Montar dados para hash
        $dados = $chave . '|2|' . $tpAmb . '|' . $cscId;
        $hash = strtoupper(sha1($dados . $cscToken));

        return $urlBase . '?p=' . $chave . '|2|' . $tpAmb . '|' . $cscId . '|' . $hash;
    }

    /**
     * Mapeia forma de pagamento.
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
            'pix'            => '17',
            'boleto'         => '15',
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
}
