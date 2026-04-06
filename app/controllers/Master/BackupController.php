<?php

namespace Akti\Controllers\Master;

use Akti\Models\Master\Backup;
use Akti\Models\Master\AdminUser;

class BackupController extends MasterBaseController
{
    public function index(): void
    {
        $this->requireMasterAuth();

        $backupResult = Backup::listBackups();
        $diagnostic = Backup::diagnose();

        // Extrair dados do resultado
        $files = $backupResult['files'] ?? [];
        $backupPath = $backupResult['path'] ?? '';
        $listError = $backupResult['error'] ?? null;
        $isTestEnv = !$diagnostic['path_exists'];

        // Calcular estatísticas
        $totalFiles = count($files);
        $totalSize = 0;
        foreach ($files as $f) {
            $totalSize += $f['size'] ?? 0;
        }
        $totalSizeHuman = $this->formatBytesHelper($totalSize);
        $lastBackup = !empty($files) ? ($files[0]['modified'] ?? 'N/A') : 'Nenhum';

        $this->renderMaster('backup/index', compact(
            'files', 'diagnostic', 'backupPath', 'listError', 'isTestEnv',
            'totalFiles', 'totalSize', 'totalSizeHuman', 'lastBackup'
        ));
    }

    private function formatBytesHelper(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function run(): void
    {
        $this->requireMasterAuth();

        $dbName = trim($_POST['db_name'] ?? '');
        if (empty($dbName)) {
            $this->json(['success' => false, 'message' => 'Nome do banco não informado']);
        }

        $result = Backup::runBackup($dbName);
        $this->logAction('backup_run', 'backup', null, "DB: {$dbName} — " . ($result['success'] ? 'success' : 'failed') . " — " . mb_substr($result['output'] ?? '', 0, 200));

        $this->json($result);
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

        $downloadPath = Backup::getDownloadPath($filename);

        if (!$downloadPath || !file_exists($downloadPath)) {
            http_response_code(404);
            echo 'Arquivo não encontrado.';
            exit;
        }

        $path = $downloadPath;
        $size = filesize($path);
        $mimeType = 'application/gzip';

        $safeFilename = preg_replace('/[\x00-\x1f"\\\\]/', '', $filename);
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
        header('Content-Length: ' . $size);
        header('Cache-Control: no-cache, must-revalidate');

        if ($size > 50 * 1024 * 1024) {
            $handle = fopen($path, 'rb');
            while (!feof($handle)) {
                echo fread($handle, 8192);
                flush();
            }
            fclose($handle);
        } else {
            readfile($path);
        }
        exit;
    }

    public function diagnoseJson(): void
    {
        $this->requireMasterAuth();

        $diagnostic = Backup::diagnose();
        $this->json(['success' => true, 'diagnostic' => $diagnostic]);
    }

    public function delete(): void
    {
        $this->requireMasterAuth();

        $filename = trim($_POST['filename'] ?? '');
        $confirmName = trim($_POST['confirm_name'] ?? '');
        $password = $_POST['admin_password'] ?? '';

        if (empty($filename) || empty($confirmName) || empty($password)) {
            $this->json(['success' => false, 'message' => 'Dados incompletos para exclusão']);
        }

        if ($filename !== $confirmName) {
            $this->json(['success' => false, 'message' => 'Nome de confirmação não corresponde']);
        }

        $adminUser = new AdminUser($this->db);
        $admin = $adminUser->findById($this->getMasterAdminId());
        if (!$admin || !password_verify($password, $admin['password'])) {
            $this->json(['success' => false, 'message' => 'Senha de admin incorreta']);
        }

        $result = Backup::deleteBackup($filename);
        $this->logAction('backup_delete', 'backup', null, "File: {$filename} — " . ($result['success'] ? 'success' : 'failed'));

        $this->json($result);
    }
}
