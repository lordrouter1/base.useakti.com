<?php
namespace Akti\Services;

use Akti\Models\PortalAccess;
use PDO;

/**
 * Service: Portal2faService
 * Lógica de autenticação de dois fatores do Portal do Cliente.
 */
class Portal2faService
{
    private PDO $db;
    private PortalAccess $portalAccess;

    public function __construct(PDO $db, PortalAccess $portalAccess)
    {
        $this->db = $db;
        $this->portalAccess = $portalAccess;
    }

    /**
     * Valida código 2FA informado pelo cliente.
     *
     * @param int    $accessId
     * @param string $code
     * @return bool true se código válido
     */
    public function validateCode(int $accessId, string $code): bool
    {
        if (empty($code) || strlen($code) !== 6) {
            return false;
        }

        return $this->portalAccess->validate2faCode($accessId, $code);
    }

    /**
     * Gera e retorna novo código 2FA.
     */
    public function resendCode(int $accessId): string
    {
        return $this->portalAccess->generate2faCode($accessId);
    }

    /**
     * Ativa ou desativa 2FA para um acesso.
     */
    public function toggle(int $accessId, bool $enable): void
    {
        $this->portalAccess->toggle2fa($accessId, $enable);
    }
}
