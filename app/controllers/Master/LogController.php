<?php

namespace Akti\Controllers\Master;

use Akti\Models\Master\NginxLog;

class LogController extends MasterBaseController
{
    public function index(): void
    {
        $this->requireMasterAuth();

        $logResult = NginxLog::listLogFiles();
        $diagnostic = NginxLog::diagnose();

        // Extrair dados do resultado
        $logFiles = $logResult['files'] ?? [];
        $logPath = $logResult['path'] ?? '';
        $listError = $logResult['error'] ?? null;
        $isTestEnv = !$diagnostic['path_exists'];

        $selectedFile = basename(trim($_GET['file'] ?? ''));
        $searchQuery = trim($_GET['q'] ?? '');

        $logContent = null;
        $errorAnalysis = null;

        if (!empty($selectedFile)) {
            $logContent = NginxLog::readTail($selectedFile, 200);
            $errorAnalysis = NginxLog::analyzeErrors($selectedFile);
        }

        if (!empty($searchQuery) && !empty($selectedFile)) {
            $searchResult = NginxLog::search($selectedFile, $searchQuery);
            if ($searchResult['success'] && !empty($searchResult['results'])) {
                $logContent = [
                    'success'   => true,
                    'is_search' => true,
                    'content'   => implode("\n", $searchResult['results']),
                    'lines'     => $searchResult['count'],
                    'query'     => $searchQuery,
                ];
            }
        }

        $this->renderMaster('logs/index', compact(
            'logFiles', 'diagnostic', 'logPath', 'listError', 'isTestEnv',
            'selectedFile', 'searchQuery', 'logContent', 'errorAnalysis'
        ));
    }

    public function read(): void
    {
        $this->requireMasterAuth();

        $file = basename(trim($_GET['file'] ?? ''));
        $lines = (int) ($_GET['lines'] ?? 200);
        $lines = min(max($lines, 50), 1000);

        if (empty($file)) {
            $this->json(['success' => false, 'message' => 'Arquivo não informado']);
        }

        $content = NginxLog::readTail($file, $lines);

        $this->json([
            'success' => true,
            'content' => $content,
            'file'    => basename($file),
            'lines'   => $lines,
        ]);
    }

    public function search(): void
    {
        $this->requireMasterAuth();

        $file = basename(trim($_GET['file'] ?? ''));
        $query = trim($_GET['q'] ?? '');

        if (empty($file) || empty($query)) {
            $this->json(['success' => false, 'message' => 'Parâmetros incompletos']);
        }

        $results = NginxLog::search($file, $query);

        $this->json([
            'success' => true,
            'results' => $results,
            'query'   => $query,
            'file'    => basename($file),
        ]);
    }

    public function download(): void
    {
        $this->requireMasterAuth();

        $filename = basename(trim($_GET['file'] ?? ''));
        if (empty($filename)) {
            http_response_code(400);
            echo 'Arquivo não informado.';
            exit;
        }

        $downloadPath = NginxLog::getDownloadPath($filename);

        if (!$downloadPath || !file_exists($downloadPath)) {
            http_response_code(404);
            echo 'Arquivo não encontrado.';
            exit;
        }

        $path = $downloadPath;
        $isGz = str_ends_with($filename, '.gz');
        $safeFilename = preg_replace('/[\x00-\x1f"\\\\]/', '', $filename);

        header('Content-Type: ' . ($isGz ? 'application/gzip' : 'text/plain'));
        header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: no-cache, must-revalidate');

        readfile($path);
        exit;
    }
}
