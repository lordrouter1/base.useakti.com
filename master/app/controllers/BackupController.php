<?php
/**
 * Controller: BackupController
 * Gerencia backups do servidor
 */

class BackupController
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Página principal — lista backups
     */
    public function index()
    {
        $diagnostic = Backup::diagnose();
        $result = Backup::listBackups();
        $files = $result['files'] ?? [];
        $backupPath = $result['path'] ?? '';
        $listError = $result['error'] ?? null;

        // Estatísticas
        $totalFiles = count($files);
        $totalSize = array_sum(array_column($files, 'size'));
        $totalSizeHuman = $this->formatBytes($totalSize);
        $lastBackup = !empty($files) ? $files[0]['modified'] : '—';

        require_once __DIR__ . '/../views/backup/index.php';
    }

    /**
     * Executar backup (AJAX — apenas POST)
     */
    public function run()
    {
        header('Content-Type: application/json; charset=utf-8');

        // Segurança: só aceitar POST para evitar execução acidental
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'output' => 'Método não permitido. Use POST.']);
            exit;
        }

        $result = Backup::runBackup();

        // Log
        $this->logAction('backup_run', $result['success'] ? 'success' : 'failed', $result['output']);

        echo json_encode([
            'success' => $result['success'],
            'output'  => $result['output'],
        ]);
        exit;
    }

    /**
     * Download de arquivo de backup
     */
    public function download()
    {
        $filename = $_GET['file'] ?? '';
        
        if (empty($filename)) {
            $_SESSION['error'] = 'Arquivo não especificado.';
            header('Location: ?page=backup');
            exit;
        }

        $filePath = Backup::getDownloadPath($filename);

        if (!$filePath) {
            $_SESSION['error'] = 'Arquivo não encontrado ou sem permissão.';
            header('Location: ?page=backup');
            exit;
        }

        // Log
        $this->logAction('backup_download', 'success', basename($filePath));

        // Enviar arquivo
        $fsize = filesize($filePath);
        $fname = basename($filePath);

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $fname . '"');
        header('Content-Length: ' . $fsize);
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');

        // Usar readfile para arquivos pequenos, chunked para grandes
        if ($fsize < 50 * 1024 * 1024) { // < 50MB
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

    /**
     * Diagnóstico (AJAX)
     */
    public function diagnoseJson()
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(Backup::diagnose(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Excluir arquivo de backup (AJAX — apenas POST)
     * Requer confirmação do nome do arquivo e senha do admin
     */
    public function delete()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Método não permitido. Use POST.']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $filename = $input['filename'] ?? '';
        $confirmName = $input['confirm_name'] ?? '';
        $password = $input['password'] ?? '';

        // Validar campos
        if (empty($filename) || empty($confirmName) || empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Preencha todos os campos: nome do arquivo e senha.']);
            exit;
        }

        // Validar que o nome digitado confere
        if (basename($filename) !== basename($confirmName)) {
            echo json_encode(['success' => false, 'error' => 'O nome digitado não confere com o arquivo selecionado.']);
            exit;
        }

        // Validar senha do admin logado
        $adminId = $_SESSION['admin_id'] ?? null;
        if (!$adminId) {
            echo json_encode(['success' => false, 'error' => 'Sessão expirada. Faça login novamente.']);
            exit;
        }

        $adminUser = new AdminUser($this->db);
        $admin = $adminUser->findById($adminId);
        if (!$admin || !password_verify($password, $admin['password'])) {
            echo json_encode(['success' => false, 'error' => 'Senha incorreta.']);
            exit;
        }

        // Executar exclusão
        $result = Backup::deleteBackup($filename);

        // Log
        $this->logAction('backup_delete', $result['success'] ? 'success' : 'failed', $filename . ' — ' . ($result['error'] ?? $result['message'] ?? ''));

        echo json_encode($result);
        exit;
    }

    // ─── Helpers ─────────────────────────────────────────

    private function logAction($action, $status, $details = null)
    {
        try {
            $adminId = $_SESSION['admin_id'] ?? null;
            if ($adminId) {
                $log = new AdminLog($this->db);
                $log->log($adminId, $action, 'backup', null,
                    "Status: {$status}" . ($details ? " — " . mb_substr($details, 0, 300) : ''));
            }
        } catch (Exception $e) {
            // Não bloquear
        }
    }

    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
