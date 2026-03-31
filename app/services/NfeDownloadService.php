<?php
namespace Akti\Services;

use PDO;

/**
 * Service: NfeDownloadService
 *
 * Encapsula lógica de download de XML/DANFE das NF-e.
 *
 * @package Akti\Services
 */
class NfeDownloadService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Obtém o conteúdo XML autorizado de uma NF-e.
     *
     * @param array $doc Dados do documento NF-e
     * @return string|null XML ou null se indisponível
     */
    public function getAuthorizedXml(array $doc): ?string
    {
        $xml = $doc['xml_autorizado'] ?? $doc['xml_envio'] ?? '';

        if (empty($xml) && !empty($doc['xml_path'])) {
            try {
                $storage = new \Akti\Services\NfeStorageService();
                $xml = $storage->readFile($doc['xml_path']);
            } catch (\Throwable $e) {
                return null;
            }
        }

        return !empty($xml) ? $xml : null;
    }

    /**
     * Gera o DANFE (PDF) de uma NF-e autorizada.
     *
     * @param string $xmlAutorizado XML autorizado da NF-e
     * @return string|null Conteúdo do PDF ou null em caso de falha
     */
    public function generateDanfe(string $xmlAutorizado): ?string
    {
        if (empty($xmlAutorizado)) {
            return null;
        }

        $customizer = new \Akti\Services\NfeDanfeCustomizer($this->db);
        return $customizer->generate($xmlAutorizado);
    }

    /**
     * Obtém XML de cancelamento de uma NF-e.
     *
     * @param array $doc Dados do documento NF-e
     * @return string|null
     */
    public function getCancelXml(array $doc): ?string
    {
        $xml = $doc['xml_cancelamento'] ?? '';
        return !empty($xml) ? $xml : null;
    }

    /**
     * Obtém XML de carta de correção de uma NF-e.
     *
     * @param array $doc Dados do documento NF-e
     * @return string|null
     */
    public function getCceXml(array $doc): ?string
    {
        $xml = $doc['xml_correcao'] ?? '';
        return !empty($xml) ? $xml : null;
    }

    /**
     * Envia cabeçalhos e conteúdo XML para download.
     *
     * @param string $xml      Conteúdo XML
     * @param string $prefix   Prefixo do filename (ex: 'NFe', 'Cancel', 'CCe')
     * @param string $chave    Chave da NF-e para o nome do arquivo
     */
    public function sendXmlDownload(string $xml, string $prefix, string $chave): void
    {
        header('Content-Type: application/xml; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $prefix . '_' . $chave . '.xml"');
        header('Content-Length: ' . strlen($xml));
        echo $xml;
        exit;
    }

    /**
     * Envia cabeçalhos e conteúdo PDF (DANFE) para visualização inline.
     *
     * @param string $pdf   Conteúdo PDF
     * @param string $chave Chave da NF-e para o nome do arquivo
     */
    public function sendDanfeDownload(string $pdf, string $chave): void
    {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="DANFE_' . $chave . '.pdf"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }
}
