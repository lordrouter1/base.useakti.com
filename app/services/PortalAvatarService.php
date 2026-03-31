<?php
namespace Akti\Services;

use Akti\Models\PortalAccess;
use Akti\Models\Logger;
use Akti\Middleware\PortalAuthMiddleware;
use PDO;

/**
 * Service: PortalAvatarService
 *
 * Encapsula lógica de upload e gestão de avatar do portal do cliente.
 *
 * @package Akti\Services
 */
class PortalAvatarService
{
    private PortalAccess $portalAccess;
    private Logger $logger;

    /** @var string[] Tipos MIME permitidos */
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    /** @var int Tamanho máximo em bytes (2MB) */
    private const MAX_SIZE = 2 * 1024 * 1024;

    /** @var string Diretório base de upload */
    private const UPLOAD_DIR = 'assets/uploads/portal/avatars/';

    public function __construct(PortalAccess $portalAccess, Logger $logger)
    {
        $this->portalAccess = $portalAccess;
        $this->logger = $logger;
    }

    /**
     * Processa upload de avatar.
     *
     * @param array $file      Entrada de $_FILES['avatar']
     * @param int   $accessId  ID do acesso portal
     * @param int   $customerId ID do cliente
     * @return array ['success' => bool, 'message' => string, 'path' => string|null]
     */
    public function upload(array $file, int $accessId, int $customerId): array
    {
        // Validar presença do arquivo
        if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'avatar_upload_error', 'path' => null];
        }

        // Validar tipo
        if (!in_array($file['type'], self::ALLOWED_TYPES)) {
            return ['success' => false, 'message' => 'avatar_invalid_type', 'path' => null];
        }

        // Validar tamanho
        if ($file['size'] > self::MAX_SIZE) {
            return ['success' => false, 'message' => 'avatar_too_large', 'path' => null];
        }

        // Determinar extensão
        $ext = $this->getExtension($file['type']);

        // Gerar nome único
        $filename = 'portal_avatar_' . $customerId . '_' . time() . '.' . $ext;

        // Garantir diretório
        if (!is_dir(self::UPLOAD_DIR)) {
            mkdir(self::UPLOAD_DIR, 0755, true);
        }

        $destPath = self::UPLOAD_DIR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            return ['success' => false, 'message' => 'avatar_upload_error', 'path' => null];
        }

        // Remover avatar antigo
        $oldAvatar = $this->portalAccess->getAvatar($accessId);
        if ($oldAvatar && file_exists($oldAvatar)) {
            @unlink($oldAvatar);
        }

        // Atualizar no banco e sessão
        $this->portalAccess->updateAvatar($accessId, $destPath);
        $_SESSION['portal_customer_avatar'] = $destPath;

        $this->logger->log(
            'portal_avatar_uploaded',
            "Cliente ID: {$customerId} | Avatar atualizado | IP: " . PortalAuthMiddleware::getClientIp()
        );

        return ['success' => true, 'message' => 'avatar_updated', 'path' => $destPath];
    }

    /**
     * Determina a extensão do arquivo pelo MIME type.
     *
     * @param string $mimeType
     * @return string
     */
    private function getExtension(string $mimeType): string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];

        return $map[$mimeType] ?? 'jpg';
    }
}
