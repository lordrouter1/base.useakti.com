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
        $fileManager = new FileManager();

        // Remover avatar antigo
        $oldAvatar = $this->portalAccess->getAvatar($accessId);

        $result = $fileManager->replace($oldAvatar ?: null, $file, 'avatars', [
            'prefix'       => 'portal_avatar_' . $customerId,
            'entityType'   => 'portal_access',
            'entityId'     => $accessId,
        ]);

        if (!$result['success']) {
            $errorKey = match (true) {
                str_contains($result['error'] ?? '', 'tamanho') => 'avatar_too_large',
                str_contains($result['error'] ?? '', 'tipo') => 'avatar_invalid_type',
                default => 'avatar_upload_error',
            };
            return ['success' => false, 'message' => $errorKey, 'path' => null];
        }

        // Atualizar no banco e sessão
        $this->portalAccess->updateAvatar($accessId, $result['path']);
        $_SESSION['portal_customer_avatar'] = $result['path'];

        $this->logger->log(
            'portal_avatar_uploaded',
            "Cliente ID: {$customerId} | Avatar atualizado | IP: " . PortalAuthMiddleware::getClientIp()
        );

        return ['success' => true, 'message' => 'avatar_updated', 'path' => $result['path']];
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
