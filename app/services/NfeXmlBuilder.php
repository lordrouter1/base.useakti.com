<?php
namespace Akti\Services;

/**
 * NfeXmlBuilder — Monta o XML da NF-e no formato 4.00.
 *
 * Usa a biblioteca sped-nfe (NFePHP\NFe\Make) para construir o XML
 * com os dados do emitente, destinatário e itens do pedido.
 *
 * @package Akti\Services
 */
class NfeXmlBuilder
{
    private array $emitente;
    private array $orderData;
    private int $numero;
    private int $serie;

    public function __construct(array $emitente, array $orderData, int $numero, int $serie)
    {
        $this->emitente  = $emitente;
        $this->orderData = $orderData;
        $this->numero    = $numero;
        $this->serie     = $serie;
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

        $nfe = new \NFePHP\NFe\Make();

        // ── infNFe ──
        $std = new \stdClass();
        $std->versao = '4.00';
        $std->Id = null;
        $std->pk_nItem = null;
        $nfe->taginfNFe($std);

        // ── ide ──
        $ide = new \stdClass();
        $ide->cUF = $this->getCodeUF($this->emitente['uf'] ?? 'RS');
        $ide->cNF = str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
        $ide->natOp = $this->orderData['natureza_op'] ?? 'VENDA DE MERCADORIA';
        $ide->mod = 55;
        $ide->serie = $this->serie;
        $ide->nNF = $this->numero;
        $ide->dhEmi = date('c');
        $ide->dhSaiEnt = date('c');
        $ide->tpNF = 1; // saída
        $ide->idDest = 1; // operação interna
        $ide->cMunFG = $this->emitente['cod_municipio'] ?? '4314902';
        $ide->tpImp = 1; // retrato
        $ide->tpEmis = 1; // normal
        $ide->cDV = 0;
        $ide->tpAmb = ($this->emitente['environment'] ?? 'homologacao') === 'producao' ? 1 : 2;
        $ide->finNFe = 1; // normal
        $ide->indFinal = 1; // consumidor final
        $ide->indPres = 1; // presencial
        $ide->procEmi = 0; // emissão por aplicativo
        $ide->verProc = 'Akti 1.0';
        $nfe->tagide($ide);

        // ── emit ──
        $emit = new \stdClass();
        $emit->xNome = $this->emitente['razao_social'] ?? '';
        $emit->xFant = $this->emitente['nome_fantasia'] ?? '';
        $emit->IE = preg_replace('/\D/', '', $this->emitente['ie'] ?? '');
        $emit->CRT = (int) ($this->emitente['crt'] ?? 1);
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
        $endEmit->UF = $this->emitente['uf'] ?? 'RS';
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
        $endDest->UF = $this->orderData['customer_uf'] ?? ($this->emitente['uf'] ?? 'RS');
        $endDest->CEP = preg_replace('/\D/', '', $this->orderData['customer_cep'] ?? '');
        $endDest->cPais = '1058';
        $endDest->xPais = 'Brasil';
        $nfe->tagenderDest($endDest);

        // ── itens ──
        $items = $this->orderData['items'] ?? [];
        $totalProd = 0;

        foreach ($items as $idx => $item) {
            $nItem = $idx + 1;
            $vProd = round(($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0), 2);
            $totalProd += $vProd;

            $prod = new \stdClass();
            $prod->item = $nItem;
            $prod->cProd = $item['product_id'] ?? $nItem;
            $prod->cEAN = 'SEM GTIN';
            $prod->xProd = $item['product_name'] ?? 'Produto';
            $prod->NCM = $item['ncm'] ?? '00000000';
            $prod->CFOP = $item['cfop'] ?? '5102';
            $prod->uCom = $item['unit'] ?? 'UN';
            $prod->qCom = number_format($item['quantity'] ?? 1, 4, '.', '');
            $prod->vUnCom = number_format($item['unit_price'] ?? 0, 10, '.', '');
            $prod->vProd = number_format($vProd, 2, '.', '');
            $prod->cEANTrib = 'SEM GTIN';
            $prod->uTrib = $item['unit'] ?? 'UN';
            $prod->qTrib = number_format($item['quantity'] ?? 1, 4, '.', '');
            $prod->vUnTrib = number_format($item['unit_price'] ?? 0, 10, '.', '');
            $prod->indTot = 1;
            $nfe->tagprod($prod);

            // Impostos — Simples Nacional (CSOSN 102)
            $imposto = new \stdClass();
            $imposto->item = $nItem;
            $nfe->tagimposto($imposto);

            $icms = new \stdClass();
            $icms->item = $nItem;
            $icms->orig = 0;
            $icms->CSOSN = '102';
            $nfe->tagICMSSN($icms);

            $pis = new \stdClass();
            $pis->item = $nItem;
            $pis->CST = '99';
            $pis->vBC = 0;
            $pis->pPIS = 0;
            $pis->vPIS = 0;
            $nfe->tagPIS($pis);

            $cofins = new \stdClass();
            $cofins->item = $nItem;
            $cofins->CST = '99';
            $cofins->vBC = 0;
            $cofins->pCOFINS = 0;
            $cofins->vCOFINS = 0;
            $nfe->tagCOFINS($cofins);
        }

        // ── ICMSTot ──
        $icmsTot = new \stdClass();
        $icmsTot->vBC = 0;
        $icmsTot->vICMS = 0;
        $icmsTot->vICMSDeson = 0;
        $icmsTot->vFCP = 0;
        $icmsTot->vBCST = 0;
        $icmsTot->vST = 0;
        $icmsTot->vFCPST = 0;
        $icmsTot->vFCPSTRet = 0;
        $icmsTot->vProd = number_format($totalProd, 2, '.', '');
        $icmsTot->vFrete = number_format($this->orderData['shipping_cost'] ?? 0, 2, '.', '');
        $icmsTot->vSeg = 0;
        $icmsTot->vDesc = number_format($this->orderData['discount'] ?? 0, 2, '.', '');
        $icmsTot->vII = 0;
        $icmsTot->vIPI = 0;
        $icmsTot->vIPIDevol = 0;
        $icmsTot->vPIS = 0;
        $icmsTot->vCOFINS = 0;
        $icmsTot->vOutro = 0;
        $icmsTot->vNF = number_format($this->orderData['total_amount'] ?? $totalProd, 2, '.', '');
        $nfe->tagICMSTot($icmsTot);

        // ── transp ──
        $transp = new \stdClass();
        $transp->modFrete = 9; // sem frete
        $nfe->tagtransp($transp);

        // ── pag ──
        $pag = new \stdClass();
        $pag->vTroco = 0;
        $nfe->tagpag($pag);

        $detPag = new \stdClass();
        $detPag->tPag = $this->mapPaymentMethod($this->orderData['payment_method'] ?? '');
        $detPag->vPag = number_format($this->orderData['total_amount'] ?? $totalProd, 2, '.', '');
        $nfe->tagdetPag($detPag);

        // ── infAdic ──
        $infAdic = new \stdClass();
        $infAdic->infCpl = $this->orderData['observation'] ?? '';
        $nfe->taginfAdic($infAdic);

        // ── Gerar XML ──
        $xml = $nfe->getXML();
        if (empty($xml)) {
            $errors = $nfe->getErrors();
            throw new \RuntimeException('Erro ao gerar XML: ' . implode('; ', $errors));
        }

        return $xml;
    }

    /**
     * Mapeia forma de pagamento do sistema para código NFe.
     */
    private function mapPaymentMethod(string $method): string
    {
        $map = [
            'dinheiro'      => '01',
            'cheque'        => '02',
            'cartao_credito' => '03',
            'credit_card'   => '03',
            'cartao_debito' => '04',
            'debit_card'    => '04',
            'pix'           => '17',
            'boleto'        => '15',
            'transferencia' => '18',
            'outros'        => '99',
        ];
        return $map[$method] ?? '99';
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
