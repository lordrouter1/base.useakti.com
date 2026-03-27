<?php
namespace Akti\Services;

/**
 * NfceDanfeGenerator — Gera DANFE para NFC-e (modelo 65) em formato de cupom térmico.
 *
 * A NFC-e usa layout simplificado (cupom) diferente do DANFE A4 da NF-e.
 * Inclui:
 *   - Dados do emitente
 *   - Itens com quantidade e valor
 *   - Totais
 *   - QR Code
 *   - Informações adicionais
 *
 * Dependência: sped-da (NFePHP\DA\NFCe\Danfce) ou geração HTML.
 *
 * @package Akti\Services
 */
class NfceDanfeGenerator
{
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db;
    }

    /**
     * Gera DANFE NFC-e a partir do XML autorizado.
     * Tenta usar a biblioteca sped-da; se não disponível, gera HTML para impressão térmica.
     *
     * @param string $xmlAutorizado XML autorizado da NFC-e
     * @param array  $options       Opções: 'format' => 'pdf'|'html', 'width' => int (mm)
     * @return string|null Conteúdo do DANFE (PDF binário ou HTML) ou null se erro
     */
    public function generate(string $xmlAutorizado, array $options = []): ?string
    {
        $format = $options['format'] ?? 'html';
        $width = $options['width'] ?? 80; // 80mm padrão impressora térmica

        try {
            // Tentar usar sped-da para NFC-e
            if ($format === 'pdf' && class_exists(\NFePHP\DA\NFCe\Danfce::class)) {
                return $this->generateWithSpedDa($xmlAutorizado, $width);
            }

            // Fallback: gerar HTML para impressão térmica
            return $this->generateHtml($xmlAutorizado, $width);
        } catch (\Throwable $e) {
            error_log('[NfceDanfeGenerator] Erro ao gerar DANFE NFC-e: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Gera DANFE NFC-e via biblioteca sped-da.
     *
     * @param string $xml   XML autorizado
     * @param int    $width Largura em mm
     * @return string PDF binário
     */
    private function generateWithSpedDa(string $xml, int $width): string
    {
        $danfce = new \NFePHP\DA\NFCe\Danfce($xml, [
            'margens' => [2, 2, 2, 2],
            'papel'   => [$width, 0], // altura automática
        ]);

        // Tentar aplicar logo personalizado
        $logoPath = $this->getLogoPath();
        if ($logoPath && file_exists($logoPath)) {
            $danfce->logoParameters($logoPath, 'C', false);
        }

        return $danfce->render();
    }

    /**
     * Gera DANFE NFC-e em HTML para impressão direta ou conversão.
     *
     * @param string $xml   XML autorizado
     * @param int    $width Largura em mm
     * @return string HTML do cupom
     */
    private function generateHtml(string $xml, int $width): string
    {
        $data = $this->parseXml($xml);
        if (!$data) {
            return '<p>Erro ao processar XML da NFC-e.</p>';
        }

        $widthPx = $width * 3; // aprox 3px per mm para tela
        $logoPath = $this->getLogoPath();
        $logoHtml = '';
        if ($logoPath && file_exists($logoPath)) {
            $logoData = base64_encode(file_get_contents($logoPath));
            $ext = pathinfo($logoPath, PATHINFO_EXTENSION);
            $logoHtml = "<img src=\"data:image/{$ext};base64,{$logoData}\" style=\"max-width:120px;max-height:60px;display:block;margin:0 auto 5px;\">";
        }

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        $html .= '<style>
            @page { size: ' . $width . 'mm auto; margin: 2mm; }
            body { font-family: "Courier New", monospace; font-size: 10px; width: ' . $widthPx . 'px; margin: 0 auto; }
            .center { text-align: center; }
            .right { text-align: right; }
            .bold { font-weight: bold; }
            .separator { border-top: 1px dashed #000; margin: 4px 0; }
            table { width: 100%; border-collapse: collapse; }
            td { padding: 1px 0; vertical-align: top; }
            .items td { font-size: 9px; }
            .qrcode { text-align: center; margin: 8px 0; }
            .qrcode img { max-width: 150px; }
        </style></head><body>';

        // Cabeçalho - Emitente
        $html .= '<div class="center">';
        $html .= $logoHtml;
        $html .= '<div class="bold">' . htmlspecialchars($data['emit']['xNome'] ?? '') . '</div>';
        $html .= '<div>' . htmlspecialchars($data['emit']['xFant'] ?? '') . '</div>';
        $html .= '<div>CNPJ: ' . $this->formatCnpj($data['emit']['CNPJ'] ?? '') . '</div>';
        $html .= '<div>' . htmlspecialchars($data['emit']['endereco'] ?? '') . '</div>';
        $html .= '</div>';

        $html .= '<div class="separator"></div>';
        $html .= '<div class="center bold">DANFE NFC-e - Documento Auxiliar</div>';
        $html .= '<div class="center bold">da Nota Fiscal de Consumidor Eletrônica</div>';
        $html .= '<div class="separator"></div>';

        // Itens
        $html .= '<table class="items">';
        $html .= '<tr><td class="bold">Cód</td><td class="bold">Descrição</td><td class="bold right">Qtd</td><td class="bold right">Vl.Un</td><td class="bold right">Total</td></tr>';
        $html .= '<tr><td colspan="5"><div class="separator"></div></td></tr>';

        foreach ($data['items'] as $item) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($item['cProd'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars(mb_substr($item['xProd'] ?? '', 0, 30)) . '</td>';
            $html .= '<td class="right">' . number_format((float)($item['qCom'] ?? 0), 2, ',', '.') . '</td>';
            $html .= '<td class="right">' . number_format((float)($item['vUnCom'] ?? 0), 2, ',', '.') . '</td>';
            $html .= '<td class="right">' . number_format((float)($item['vProd'] ?? 0), 2, ',', '.') . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';

        // Totais
        $html .= '<div class="separator"></div>';
        $html .= '<table>';
        $html .= '<tr><td class="bold">Qtd Total de Itens</td><td class="right">' . count($data['items']) . '</td></tr>';
        $html .= '<tr><td class="bold">Valor Total</td><td class="right bold">R$ ' . number_format((float)($data['total']['vNF'] ?? 0), 2, ',', '.') . '</td></tr>';

        if (($data['total']['vDesc'] ?? 0) > 0) {
            $html .= '<tr><td>Desconto</td><td class="right">R$ ' . number_format((float)$data['total']['vDesc'], 2, ',', '.') . '</td></tr>';
        }

        // Pagamento
        foreach ($data['pagamentos'] as $pag) {
            $html .= '<tr><td>' . $this->getPaymentLabel($pag['tPag'] ?? '99') . '</td>';
            $html .= '<td class="right">R$ ' . number_format((float)($pag['vPag'] ?? 0), 2, ',', '.') . '</td></tr>';
        }

        if (($data['total']['vTroco'] ?? 0) > 0) {
            $html .= '<tr><td>Troco</td><td class="right">R$ ' . number_format((float)$data['total']['vTroco'], 2, ',', '.') . '</td></tr>';
        }
        $html .= '</table>';

        // QR Code
        $html .= '<div class="separator"></div>';
        if (!empty($data['qrcode'])) {
            $html .= '<div class="qrcode">';
            $html .= '<div class="bold">Consulte pela Chave de Acesso em:</div>';
            $html .= '<div style="font-size:8px;word-break:break-all;">' . htmlspecialchars($data['urlConsulta'] ?? 'www.nfce.fazenda.gov.br') . '</div>';
            $html .= '<div style="font-size:8px;word-break:break-all;">Chave: ' . htmlspecialchars($data['chave'] ?? '') . '</div>';
            $html .= '</div>';
        }

        // Destinatário
        if (!empty($data['dest']['CPF'] ?? $data['dest']['CNPJ'] ?? '')) {
            $html .= '<div class="separator"></div>';
            $html .= '<div>Consumidor: ' . htmlspecialchars($data['dest']['xNome'] ?? 'NÃO IDENTIFICADO') . '</div>';
            $cpfCnpj = $data['dest']['CPF'] ?? $data['dest']['CNPJ'] ?? '';
            if (!empty($cpfCnpj)) {
                $html .= '<div>CPF/CNPJ: ' . htmlspecialchars($cpfCnpj) . '</div>';
            }
        }

        // Rodapé
        $html .= '<div class="separator"></div>';
        $html .= '<div class="center">';
        $html .= '<div>NFC-e nº ' . ($data['ide']['nNF'] ?? '') . ' Série ' . ($data['ide']['serie'] ?? '') . '</div>';
        $html .= '<div>Emissão: ' . ($data['ide']['dhEmi'] ?? '') . '</div>';
        $html .= '<div style="font-size:8px;">Protocolo: ' . ($data['protocolo'] ?? '') . '</div>';
        $html .= '</div>';

        // Tributos aproximados
        if (($data['total']['vTotTrib'] ?? 0) > 0) {
            $html .= '<div class="separator"></div>';
            $html .= '<div class="center" style="font-size:8px;">';
            $html .= 'Tributos Totais Incidentes (Lei 12.741/2012): R$ ' . number_format((float)$data['total']['vTotTrib'], 2, ',', '.');
            $html .= '</div>';
        }

        $html .= '</body></html>';

        return $html;
    }

    /**
     * Faz parse do XML da NFC-e para extrair dados de impressão.
     *
     * @param string $xml XML da NFC-e
     * @return array|null Dados parseados ou null se erro
     */
    public function parseXml(string $xml): ?array
    {
        try {
            $dom = new \DOMDocument();
            $dom->loadXML($xml);

            $ns = 'http://www.portalfiscal.inf.br/nfe';

            $data = [
                'emit'       => [],
                'dest'       => [],
                'ide'        => [],
                'items'      => [],
                'total'      => [],
                'pagamentos' => [],
                'qrcode'     => '',
                'urlConsulta' => '',
                'chave'      => '',
                'protocolo'  => '',
            ];

            // IDE
            $ide = $dom->getElementsByTagNameNS($ns, 'ide')->item(0);
            if ($ide) {
                $data['ide'] = [
                    'nNF'   => $this->getTagValue($ide, 'nNF', $ns),
                    'serie' => $this->getTagValue($ide, 'serie', $ns),
                    'dhEmi' => $this->formatDateTime($this->getTagValue($ide, 'dhEmi', $ns)),
                    'tpAmb' => $this->getTagValue($ide, 'tpAmb', $ns),
                ];
            }

            // Emitente
            $emit = $dom->getElementsByTagNameNS($ns, 'emit')->item(0);
            if ($emit) {
                $enderEmit = $emit->getElementsByTagNameNS($ns, 'enderEmit')->item(0);
                $data['emit'] = [
                    'xNome' => $this->getTagValue($emit, 'xNome', $ns),
                    'xFant' => $this->getTagValue($emit, 'xFant', $ns),
                    'CNPJ'  => $this->getTagValue($emit, 'CNPJ', $ns),
                    'IE'    => $this->getTagValue($emit, 'IE', $ns),
                    'endereco' => $enderEmit ? trim(
                        $this->getTagValue($enderEmit, 'xLgr', $ns) . ', ' .
                        $this->getTagValue($enderEmit, 'nro', $ns) . ' - ' .
                        $this->getTagValue($enderEmit, 'xBairro', $ns) . ' - ' .
                        $this->getTagValue($enderEmit, 'xMun', $ns) . '/' .
                        $this->getTagValue($enderEmit, 'UF', $ns)
                    ) : '',
                ];
            }

            // Destinatário
            $dest = $dom->getElementsByTagNameNS($ns, 'dest')->item(0);
            if ($dest) {
                $data['dest'] = [
                    'xNome' => $this->getTagValue($dest, 'xNome', $ns),
                    'CPF'   => $this->getTagValue($dest, 'CPF', $ns),
                    'CNPJ'  => $this->getTagValue($dest, 'CNPJ', $ns),
                ];
            }

            // Itens
            $dets = $dom->getElementsByTagNameNS($ns, 'det');
            foreach ($dets as $det) {
                $prod = $det->getElementsByTagNameNS($ns, 'prod')->item(0);
                if ($prod) {
                    $data['items'][] = [
                        'cProd'  => $this->getTagValue($prod, 'cProd', $ns),
                        'xProd'  => $this->getTagValue($prod, 'xProd', $ns),
                        'qCom'   => $this->getTagValue($prod, 'qCom', $ns),
                        'vUnCom' => $this->getTagValue($prod, 'vUnCom', $ns),
                        'vProd'  => $this->getTagValue($prod, 'vProd', $ns),
                    ];
                }
            }

            // Totais
            $icmsTot = $dom->getElementsByTagNameNS($ns, 'ICMSTot')->item(0);
            if ($icmsTot) {
                $data['total'] = [
                    'vNF'      => $this->getTagValue($icmsTot, 'vNF', $ns),
                    'vDesc'    => $this->getTagValue($icmsTot, 'vDesc', $ns),
                    'vTotTrib' => $this->getTagValue($icmsTot, 'vTotTrib', $ns),
                ];
            }

            // Pagamento
            $detPags = $dom->getElementsByTagNameNS($ns, 'detPag');
            foreach ($detPags as $detPag) {
                $data['pagamentos'][] = [
                    'tPag' => $this->getTagValue($detPag, 'tPag', $ns),
                    'vPag' => $this->getTagValue($detPag, 'vPag', $ns),
                ];
            }

            // Troco
            $pag = $dom->getElementsByTagNameNS($ns, 'pag')->item(0);
            if ($pag) {
                $data['total']['vTroco'] = $this->getTagValue($pag, 'vTroco', $ns);
            }

            // QR Code
            $infNFeSupl = $dom->getElementsByTagNameNS($ns, 'infNFeSupl')->item(0);
            if ($infNFeSupl) {
                $data['qrcode']      = $this->getTagValue($infNFeSupl, 'qrCode', $ns);
                $data['urlConsulta'] = $this->getTagValue($infNFeSupl, 'urlChave', $ns);
            }

            // Chave de acesso
            $infNFe = $dom->getElementsByTagNameNS($ns, 'infNFe')->item(0);
            if ($infNFe) {
                $id = $infNFe->getAttribute('Id');
                $data['chave'] = str_replace('NFe', '', $id);
            }

            // Protocolo
            $nProt = $dom->getElementsByTagNameNS($ns, 'nProt');
            if ($nProt->length > 0) {
                $data['protocolo'] = $nProt->item(0)->nodeValue;
            }

            return $data;
        } catch (\Throwable $e) {
            error_log('[NfceDanfeGenerator] parseXml error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Retorna o valor de uma tag XML.
     */
    private function getTagValue(\DOMElement $parent, string $tag, string $ns): string
    {
        $elements = $parent->getElementsByTagNameNS($ns, $tag);
        return $elements->length > 0 ? trim($elements->item(0)->nodeValue) : '';
    }

    /**
     * Formata CNPJ para exibição.
     */
    private function formatCnpj(string $cnpj): string
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);
        if (strlen($cnpj) !== 14) return $cnpj;
        return substr($cnpj, 0, 2) . '.' . substr($cnpj, 2, 3) . '.' . substr($cnpj, 5, 3) . '/' . substr($cnpj, 8, 4) . '-' . substr($cnpj, 12, 2);
    }

    /**
     * Formata data/hora para exibição.
     */
    private function formatDateTime(string $datetime): string
    {
        if (empty($datetime)) return '';
        try {
            $dt = new \DateTime($datetime);
            return $dt->format('d/m/Y H:i:s');
        } catch (\Throwable $e) {
            return $datetime;
        }
    }

    /**
     * Retorna label legível para forma de pagamento.
     */
    private function getPaymentLabel(string $tPag): string
    {
        $labels = [
            '01' => 'Dinheiro',
            '02' => 'Cheque',
            '03' => 'Cartão Crédito',
            '04' => 'Cartão Débito',
            '05' => 'Crédito Loja',
            '10' => 'Vale Alimentação',
            '11' => 'Vale Refeição',
            '12' => 'Vale Presente',
            '13' => 'Vale Combustível',
            '14' => 'Duplicata Mercantil',
            '15' => 'Boleto',
            '16' => 'Depósito',
            '17' => 'PIX',
            '18' => 'Transferência',
            '90' => 'Sem Pagamento',
            '99' => 'Outros',
        ];
        return $labels[$tPag] ?? "Forma {$tPag}";
    }

    /**
     * Retorna path do logo personalizado do DANFE.
     */
    private function getLogoPath(): ?string
    {
        if (!$this->db) return null;

        try {
            $q = $this->db->prepare("SELECT setting_value FROM company_settings WHERE setting_key = 'danfe_logo_path' LIMIT 1");
            $q->execute();
            $path = $q->fetchColumn();
            if ($path && file_exists($path)) return $path;
        } catch (\Throwable $e) {
            // silencioso
        }
        return null;
    }
}
