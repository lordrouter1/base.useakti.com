<?php
/**
 * Controller: LogController
 * Visualização de logs do Nginx (erros e acesso)
 */

class LogController
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Página principal — lista arquivos de log
     */
    public function index()
    {
        $diagnostic = NginxLog::diagnose();
        $result = NginxLog::listLogFiles();
        $logFiles = $result['files'] ?? [];
        $logPath = $result['path'] ?? '';
        $listError = $result['error'] ?? null;

        // Se um arquivo foi selecionado para visualização
        $selectedFile = $_GET['file'] ?? null;
        $logContent = null;
        $logLines = (int)($_GET['lines'] ?? 200);
        $searchQuery = $_GET['q'] ?? '';

        if ($selectedFile) {
            if (!empty($searchQuery)) {
                $searchResult = NginxLog::search($selectedFile, $searchQuery);
                $logContent = [
                    'success'  => $searchResult['success'],
                    'content'  => implode("\n", $searchResult['results'] ?? []),
                    'filename' => $selectedFile,
                    'lines'    => $searchResult['count'] ?? 0,
                    'is_search'=> true,
                    'query'    => $searchQuery,
                    'error'    => $searchResult['error'] ?? null,
                ];
            } else {
                $logContent = NginxLog::readTail($selectedFile, $logLines);
            }
        }

        // Análise de erros se é um arquivo de erro
        $errorAnalysis = null;
        if ($selectedFile && stripos($selectedFile, 'error') !== false) {
            $errorAnalysis = NginxLog::analyzeErrors($selectedFile);
        }

        require_once __DIR__ . '/../views/logs/index.php';
    }

    /**
     * Leitura de log via AJAX (para refresh automático)
     */
    public function read()
    {
        header('Content-Type: application/json; charset=utf-8');

        $file = $_GET['file'] ?? '';
        $lines = (int)($_GET['lines'] ?? 200);

        if (empty($file)) {
            echo json_encode(['success' => false, 'error' => 'Arquivo não especificado']);
            exit;
        }

        $result = NginxLog::readTail($file, $lines);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Busca via AJAX
     */
    public function search()
    {
        header('Content-Type: application/json; charset=utf-8');

        $file = $_GET['file'] ?? '';
        $query = $_GET['q'] ?? '';

        if (empty($file) || empty($query)) {
            echo json_encode(['success' => false, 'error' => 'Parâmetros inválidos']);
            exit;
        }

        $result = NginxLog::search($file, $query);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Download de arquivo de log
     */
    public function download()
    {
        $filename = $_GET['file'] ?? '';

        if (empty($filename)) {
            $_SESSION['error'] = 'Arquivo não especificado.';
            header('Location: ?page=logs');
            exit;
        }

        $filePath = NginxLog::getDownloadPath($filename);

        if (!$filePath) {
            $_SESSION['error'] = 'Arquivo não encontrado ou sem permissão.';
            header('Location: ?page=logs');
            exit;
        }

        $fsize = filesize($filePath);
        $fname = basename($filePath);

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $fname . '"');
        header('Content-Length: ' . $fsize);
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');

        if ($fsize < 50 * 1024 * 1024) {
            readfile($filePath);
        } else {
            $fp = fopen($filePath, 'rb');
            while (!feof($fp)) {
                echo fread($fp, 8192);
                ob_flush();
                flush();
            }
            fclose($fp);
        }
        exit;
    }
}
