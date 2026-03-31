<?php
namespace Akti\Services;

use PDO;

/**
 * Service: NfeBatchDownloadService
 * Monta e envia download em lote de XMLs de NF-e (ZIP).
 */
class NfeBatchDownloadService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Busca documentos NF-e por IDs específicos.
     *
     * @param int[] $ids
     * @return array
     */
    public function fetchByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare(
            "SELECT id, chave, numero, serie, modelo, status,
                    xml_autorizado, xml_cancelamento, xml_correcao, xml_path
             FROM nfe_documents WHERE id IN ({$placeholders})"
        );
        $stmt->execute($ids);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca documentos NF-e por período.
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function fetchByPeriod(string $startDate, string $endDate): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, chave, numero, serie, modelo, status,
                    xml_autorizado, xml_cancelamento, xml_correcao, xml_path
             FROM nfe_documents
             WHERE DATE(created_at) BETWEEN :start AND :end
               AND status IN ('autorizada', 'cancelada', 'corrigida')
             ORDER BY numero ASC"
        );
        $stmt->execute([':start' => $startDate, ':end' => $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Gera o ZIP com os XMLs e envia para download.
     *
     * @param array $docs Documentos retornados por fetchByIds ou fetchByPeriod
     * @return array{success: bool, addedCount: int, message?: string}
     */
    public function buildZip(array $docs): array
    {
        if (empty($docs)) {
            return ['success' => false, 'addedCount' => 0, 'message' => 'Nenhum XML encontrado.'];
        }

        $storageService = new NfeStorageService();

        $zipFilename = 'XMLs_NFe_' . date('YmdHis') . '.zip';
        $tmpZip = tempnam(sys_get_temp_dir(), 'nfe_zip_');

        $zip = new \ZipArchive();
        if ($zip->open($tmpZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return ['success' => false, 'addedCount' => 0, 'message' => 'Erro ao criar arquivo ZIP.'];
        }

        $addedCount = 0;
        foreach ($docs as $doc) {
            $chave = $doc['chave'] ?? $doc['numero'];
            $modelo = ($doc['modelo'] ?? 55) == 65 ? 'NFCe' : 'NFe';

            // XML autorizado
            $xml = $doc['xml_autorizado'] ?? '';
            if (empty($xml) && !empty($doc['xml_path'])) {
                $xml = $storageService->readFile($doc['xml_path']) ?? '';
            }
            if (!empty($xml)) {
                $zip->addFromString("{$modelo}_{$chave}_autorizado.xml", $xml);
                $addedCount++;
            }

            // XML cancelamento
            if (!empty($doc['xml_cancelamento'])) {
                $zip->addFromString("{$modelo}_{$chave}_cancelamento.xml", $doc['xml_cancelamento']);
                $addedCount++;
            }

            // XML CC-e
            if (!empty($doc['xml_correcao'])) {
                $zip->addFromString("{$modelo}_{$chave}_cce.xml", $doc['xml_correcao']);
                $addedCount++;
            }
        }

        $zip->close();

        if ($addedCount === 0) {
            @unlink($tmpZip);
            return ['success' => false, 'addedCount' => 0, 'message' => 'Nenhum XML disponível para download.'];
        }

        return [
            'success'     => true,
            'addedCount'  => $addedCount,
            'tmpZip'      => $tmpZip,
            'zipFilename' => $zipFilename,
            'docCount'    => count($docs),
        ];
    }

    /**
     * Envia o ZIP para download e remove o temporário.
     *
     * @param string $tmpZip
     * @param string $zipFilename
     */
    public function sendZip(string $tmpZip, string $zipFilename): void
    {
        $zipSize = filesize($tmpZip);
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
        header('Content-Length: ' . $zipSize);
        header('Cache-Control: no-cache, must-revalidate');

        readfile($tmpZip);
        @unlink($tmpZip);
        exit;
    }
}
