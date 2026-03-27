<?php
namespace Akti\Services;

use Akti\Services\NfeStorageService;
use Akti\Services\NfeAuditService;
use PDO;

/**
 * NfeBackupService — Realiza backup de XMLs de NF-e para storage externo.
 *
 * Funcionalidades:
 *   - Backup local (ZIP em diretório configurável)
 *   - Backup para S3 (AWS) — requer credenciais
 *   - Backup para FTP — requer credenciais
 *   - Log completo de cada operação de backup
 *   - Execução sob demanda ou agendada (via cron/job)
 *
 * @package Akti\Services
 */
class NfeBackupService
{
    private PDO $db;
    private NfeStorageService $storage;
    private string $basePath;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->storage = new NfeStorageService();
        $this->basePath = defined('AKTI_BASE_PATH') ? AKTI_BASE_PATH : __DIR__ . '/../../';
    }

    /**
     * Executa backup de XMLs para o período especificado.
     *
     * @param string $startDate Data inicial (Y-m-d)
     * @param string $endDate   Data final (Y-m-d)
     * @param string $tipo      Tipo: 'local', 's3', 'ftp'
     * @return array ['success' => bool, 'message' => string, 'backup_id' => int|null, 'file' => string|null]
     */
    public function execute(string $startDate, string $endDate, string $tipo = 'local'): array
    {
        // Registrar início do backup
        $backupId = $this->logStart($tipo, $startDate, $endDate);

        try {
            // Buscar XMLs do período
            $xmlFiles = $this->collectXmlFiles($startDate, $endDate);

            if (empty($xmlFiles)) {
                $this->logFinish($backupId, 'sucesso', 0, 0, null, 'Nenhum XML encontrado para o período.');
                return [
                    'success' => true,
                    'message' => 'Nenhum XML encontrado para backup no período selecionado.',
                    'backup_id' => $backupId,
                    'file' => null,
                ];
            }

            // Criar ZIP local
            $zipPath = $this->createZip($xmlFiles, $startDate, $endDate);
            $zipSize = file_exists($zipPath) ? filesize($zipPath) : 0;
            $totalFiles = count($xmlFiles);

            $destino = $zipPath;

            // Enviar para storage externo, se configurado
            switch ($tipo) {
                case 's3':
                    $destino = $this->uploadToS3($zipPath);
                    break;
                case 'ftp':
                    $destino = $this->uploadToFtp($zipPath);
                    break;
                case 'local':
                default:
                    $destino = str_replace($this->basePath, '', $zipPath);
                    break;
            }

            $this->logFinish($backupId, 'sucesso', $totalFiles, $zipSize, $destino);

            return [
                'success'    => true,
                'message'    => "Backup realizado: {$totalFiles} arquivo(s), " . $this->formatSize($zipSize) . '.',
                'backup_id'  => $backupId,
                'file'       => $destino,
                'total'      => $totalFiles,
                'size'       => $zipSize,
            ];
        } catch (\Throwable $e) {
            $this->logFinish($backupId, 'erro', 0, 0, null, $e->getMessage());
            error_log('[NfeBackupService] Erro no backup: ' . $e->getMessage());

            return [
                'success'   => false,
                'message'   => 'Erro ao realizar backup: ' . $e->getMessage(),
                'backup_id' => $backupId,
                'file'      => null,
            ];
        }
    }

    /**
     * Coleta XMLs de NF-e do período (do banco e/ou disco).
     *
     * @param string $start
     * @param string $end
     * @return array [['filename' => string, 'content' => string], ...]
     */
    private function collectXmlFiles(string $start, string $end): array
    {
        $files = [];

        // Buscar XMLs do banco
        $stmt = $this->db->prepare(
            "SELECT id, chave, numero, serie, modelo, status,
                    xml_autorizado, xml_cancelamento, xml_correcao, xml_path
             FROM nfe_documents
             WHERE DATE(created_at) BETWEEN :start AND :end
               AND status IN ('autorizada', 'cancelada', 'corrigida')
             ORDER BY numero ASC"
        );
        $stmt->execute([':start' => $start, ':end' => $end]);
        $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($docs as $doc) {
            $chave = $doc['chave'] ?? $doc['numero'];
            $modelo = ($doc['modelo'] ?? 55) == 65 ? 'NFCe' : 'NFe';

            // XML autorizado
            $xmlAuth = $doc['xml_autorizado'] ?? '';
            if (empty($xmlAuth) && !empty($doc['xml_path'])) {
                $xmlAuth = $this->storage->readFile($doc['xml_path']) ?? '';
            }
            if (!empty($xmlAuth)) {
                $files[] = [
                    'filename' => "{$modelo}_{$chave}_autorizado.xml",
                    'content'  => $xmlAuth,
                ];
            }

            // XML de cancelamento
            if (!empty($doc['xml_cancelamento'])) {
                $files[] = [
                    'filename' => "{$modelo}_{$chave}_cancelamento.xml",
                    'content'  => $doc['xml_cancelamento'],
                ];
            }

            // XML de CC-e
            if (!empty($doc['xml_correcao'])) {
                $files[] = [
                    'filename' => "{$modelo}_{$chave}_cce.xml",
                    'content'  => $doc['xml_correcao'],
                ];
            }
        }

        return $files;
    }

    /**
     * Cria arquivo ZIP com os XMLs.
     *
     * @param array  $files     Lista de arquivos
     * @param string $start     Data inicial
     * @param string $end       Data final
     * @return string Caminho absoluto do ZIP
     */
    private function createZip(array $files, string $start, string $end): string
    {
        $tenantDb = $_SESSION['tenant']['database'] ?? ($_SESSION['tenant']['db_name'] ?? 'default');
        $safeName = preg_replace('/[^a-zA-Z0-9_]/', '_', $tenantDb);

        $backupDir = $this->basePath . 'storage/nfe_backups/' . $safeName . '/';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        // Proteger com .htaccess
        $htaccess = $this->basePath . 'storage/nfe_backups/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Deny from all\n");
        }

        $zipFilename = "backup_nfe_{$start}_a_{$end}_" . date('YmdHis') . '.zip';
        $zipPath = $backupDir . $zipFilename;

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Não foi possível criar o arquivo ZIP: {$zipPath}");
        }

        foreach ($files as $file) {
            $zip->addFromString($file['filename'], $file['content']);
        }

        $zip->close();

        return $zipPath;
    }

    /**
     * Upload para Amazon S3.
     *
     * @param string $zipPath Caminho do ZIP
     * @return string URL/path do arquivo no S3
     */
    private function uploadToS3(string $zipPath): string
    {
        $config = $this->loadConfig();
        $bucket = $config['backup_s3_bucket'] ?? '';
        $region = $config['backup_s3_region'] ?? '';
        $key    = $config['backup_s3_key'] ?? '';
        $secret = $config['backup_s3_secret'] ?? '';

        if (empty($bucket) || empty($key) || empty($secret)) {
            throw new \RuntimeException('Configurações S3 incompletas. Verifique bucket, chave e segredo.');
        }

        // Se a classe AWS S3 Client estiver disponível
        if (class_exists(\Aws\S3\S3Client::class)) {
            $s3 = new \Aws\S3\S3Client([
                'version'     => 'latest',
                'region'      => $region,
                'credentials' => [
                    'key'    => $key,
                    'secret' => $secret,
                ],
            ]);

            $s3Key = 'nfe_backups/' . basename($zipPath);
            $s3->putObject([
                'Bucket'     => $bucket,
                'Key'        => $s3Key,
                'SourceFile' => $zipPath,
            ]);

            return "s3://{$bucket}/{$s3Key}";
        }

        throw new \RuntimeException('AWS SDK não está disponível. Instale via Composer: composer require aws/aws-sdk-php');
    }

    /**
     * Upload para servidor FTP.
     *
     * @param string $zipPath Caminho do ZIP
     * @return string Path no FTP
     */
    private function uploadToFtp(string $zipPath): string
    {
        $config = $this->loadConfig();
        $host     = $config['backup_ftp_host'] ?? '';
        $user     = $config['backup_ftp_user'] ?? '';
        $password = $config['backup_ftp_password'] ?? '';
        $path     = $config['backup_ftp_path'] ?? '/backups/nfe/';

        if (empty($host) || empty($user)) {
            throw new \RuntimeException('Configurações FTP incompletas. Verifique host e usuário.');
        }

        $conn = ftp_connect($host);
        if (!$conn) {
            throw new \RuntimeException("Não foi possível conectar ao FTP: {$host}");
        }

        if (!ftp_login($conn, $user, $password)) {
            ftp_close($conn);
            throw new \RuntimeException('Falha na autenticação FTP.');
        }

        ftp_pasv($conn, true);

        $remotePath = rtrim($path, '/') . '/' . basename($zipPath);

        if (!ftp_put($conn, $remotePath, $zipPath, FTP_BINARY)) {
            ftp_close($conn);
            throw new \RuntimeException("Falha ao enviar arquivo para FTP: {$remotePath}");
        }

        ftp_close($conn);

        return "ftp://{$host}{$remotePath}";
    }

    /**
     * Registra início de backup no log.
     */
    private function logStart(string $tipo, string $start, string $end): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO nfe_backup_log (tipo, periodo_inicio, periodo_fim, status, user_id)
             VALUES (:tipo, :inicio, :fim, 'executando', :user_id)"
        );
        $stmt->execute([
            ':tipo'    => $tipo,
            ':inicio'  => $start,
            ':fim'     => $end,
            ':user_id' => $_SESSION['user_id'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Registra fim de backup no log.
     */
    private function logFinish(int $id, string $status, int $totalFiles, int $size, ?string $destino, ?string $erro = null): void
    {
        $stmt = $this->db->prepare(
            "UPDATE nfe_backup_log SET 
                status = :status, 
                total_arquivos = :total, 
                tamanho_bytes = :size,
                arquivo_destino = :destino,
                mensagem_erro = :erro,
                completed_at = NOW()
             WHERE id = :id"
        );
        $stmt->execute([
            ':status'  => $status,
            ':total'   => $totalFiles,
            ':size'    => $size,
            ':destino' => $destino,
            ':erro'    => $erro,
            ':id'      => $id,
        ]);
    }

    /**
     * Retorna histórico de backups.
     *
     * @param int $limit Quantidade
     * @return array
     */
    public function getHistory(int $limit = 50): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT bl.*, COALESCE(u.name, 'Sistema') AS user_name
                 FROM nfe_backup_log bl
                 LEFT JOIN users u ON bl.user_id = u.id
                 ORDER BY bl.created_at DESC LIMIT :lim"
            );
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Carrega configurações de backup.
     */
    private function loadConfig(): array
    {
        try {
            $stmt = $this->db->query(
                "SELECT config_key, config_value FROM nfe_fiscal_config WHERE config_key LIKE 'backup_%'"
            );
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $config = [];
            foreach ($rows as $row) {
                $config[$row['config_key']] = $row['config_value'];
            }
            return $config;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Formata tamanho em bytes para exibição.
     */
    private function formatSize(int $bytes): string
    {
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 2) . ' KB';
        return $bytes . ' bytes';
    }
}
