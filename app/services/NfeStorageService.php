<?php
namespace Akti\Services;

use Akti\Core\Log;

/**
 * NfeStorageService — Salva XMLs e DANFEs em disco, organizados por tenant/ano/mês.
 *
 * Estrutura: storage/nfe/{tenant_db}/{YYYY}/{MM}/{chave}-{tipo}.{ext}
 *
 * A legislação brasileira exige guarda dos XMLs por 5 anos.
 * Armazenar apenas no banco de dados é frágil — este serviço persiste em disco.
 *
 * @package Akti\Services
 */
class NfeStorageService
{
    private string $basePath;
    private string $tenantDir;

    /**
     * Construtor da classe NfeStorageService.
     */
    public function __construct()
    {
        $this->basePath = defined('AKTI_BASE_PATH') ? AKTI_BASE_PATH : __DIR__ . '/../../';
        $tenantDb = $_SESSION['tenant']['database'] ?? ($_SESSION['tenant']['db_name'] ?? 'default');
        $safeName = preg_replace('/[^a-zA-Z0-9_]/', '_', $tenantDb);
        $this->tenantDir = $this->basePath . 'storage/nfe/' . $safeName . '/';
    }

    /**
     * Salva o XML autorizado em disco.
     *
     * @param string $chave  Chave de acesso (44 dígitos)
     * @param string $xml    Conteúdo XML
     * @param string $tipo   Tipo: 'nfe', 'cancel', 'cce'
     * @return string|null   Caminho relativo do arquivo ou null se erro
     */
    public function saveXml(string $chave, string $xml, string $tipo = 'nfe'): ?string
    {
        if (empty($chave) || empty($xml)) {
            return null;
        }

        $dir = $this->getDirectory();
        $filename = "{$chave}-{$tipo}.xml";
        $fullPath = $dir . $filename;

        try {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            // Proteger diretório com .htaccess
            $this->ensureHtaccess();

            file_put_contents($fullPath, $xml);

            // Retornar path relativo ao basePath
            return str_replace($this->basePath, '', $fullPath);
        } catch (\Exception $e) {
            Log::error('NfeStorageService: Erro ao salvar XML', ['exception' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Salva o DANFE (PDF) em disco.
     *
     * @param string $chave Chave de acesso
     * @param string $pdf   Conteúdo binário do PDF
     * @return string|null  Caminho relativo ou null se erro
     */
    public function saveDanfe(string $chave, string $pdf): ?string
    {
        if (empty($chave) || empty($pdf)) {
            return null;
        }

        $dir = $this->getDirectory();
        $filename = "{$chave}-danfe.pdf";
        $fullPath = $dir . $filename;

        try {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $this->ensureHtaccess();

            file_put_contents($fullPath, $pdf);

            return str_replace($this->basePath, '', $fullPath);
        } catch (\Exception $e) {
            Log::error('NfeStorageService: Erro ao salvar DANFE', ['exception' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Lê um XML salvo em disco.
     *
     * @param string $relativePath Caminho relativo
     * @return string|null Conteúdo ou null
     */
    public function readFile(string $relativePath): ?string
    {
        $fullPath = $this->basePath . $relativePath;
        if (file_exists($fullPath)) {
            return file_get_contents($fullPath);
        }
        return null;
    }

    /**
     * Verifica se um arquivo existe em disco.
     *
     * @param string $relativePath
     * @return bool
     */
    public function fileExists(string $relativePath): bool
    {
        return file_exists($this->basePath . $relativePath);
    }

    /**
     * Retorna o diretório de armazenamento para o mês/ano atual.
     */
    private function getDirectory(): string
    {
        $year = date('Y');
        $month = date('m');
        return $this->tenantDir . $year . '/' . $month . '/';
    }

    /**
     * Garante que o diretório storage/nfe está protegido com .htaccess.
     */
    private function ensureHtaccess(): void
    {
        $htaccessPath = $this->basePath . 'storage/nfe/.htaccess';
        if (!file_exists($htaccessPath)) {
            $dir = dirname($htaccessPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($htaccessPath, "Deny from all\n");
        }
    }
}
