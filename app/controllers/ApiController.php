<?php
namespace Akti\Controllers;

use Akti\Utils\JwtHelper;

/**
 * ApiController — Gera tokens JWT para o frontend consumir a API Node.js.
 *
 * Rota: ?page=api&action=token  (GET, AJAX)
 *
 * O token inclui: user_id, user_name, tenant_id (subdomain key).
 * O segredo é compartilhado com o Node via variável de ambiente JWT_SECRET.
 */
class ApiController
{
    /**
     * GET ?page=api&action=token
     *
     * Retorna JSON com o JWT para autenticação na API Node.
     * O token tem validade de 2 horas (7200 s) para evitar chamadas frequentes.
     */
    public function token(): void
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Não autenticado.']);
            return;
        }

        $secret = getenv('JWT_SECRET') ?: 'dev-only-secret';

        $tenantKey = $_SESSION['tenant']['key'] ?? 'localhost';
        $tenantDb  = $_SESSION['tenant']['database'] ?? '';

        $payload = [
            'sub'       => (int) $_SESSION['user_id'],
            'name'      => $_SESSION['user_name'] ?? '',
            'role'      => $_SESSION['user_role'] ?? 'user',
            'tenant_id' => $tenantKey,
            'tenant_db' => $tenantDb,
        ];

        $token = JwtHelper::encode($payload, $secret, 7200); // 2h

        echo json_encode([
            'success' => true,
            'token'   => $token,
            'expires_in' => 7200,
        ]);
    }
}
