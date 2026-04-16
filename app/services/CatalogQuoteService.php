<?php
namespace Akti\Services;

use Akti\Models\CatalogLink;
use Akti\Models\Order;
use Akti\Models\Logger;
use PDO;

/**
 * CatalogQuoteService — Lógica de confirmação/revogação de orçamento via catálogo.
 *
 * Responsabilidades:
 *   - Confirmar orçamento pelo cliente (via link de catálogo)
 *   - Revogar confirmação de orçamento
 *   - Sincronizar customer_approval_status
 *   - Captura e registro de IP do cliente
 *
 * @package Akti\Services
 */
class CatalogQuoteService
{
    private PDO $db;

    /**
     * Construtor da classe CatalogQuoteService.
     *
     * @param PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Confirmar orçamento pelo cliente.
     *
     * @param string $token Token do catálogo
     * @param string $clientIp IP do cliente
     * @return array ['success' => bool, ...]
     */
    public function confirmQuote(string $token, string $clientIp): array
    {
        $catalogModel = new CatalogLink($this->db);
        $link = $catalogModel->findByToken($token);

        if (!$link) {
            return ['success' => false, 'message' => 'Link inválido ou expirado'];
        }

        if (empty($link['require_confirmation'])) {
            return ['success' => false, 'message' => 'Este link não permite confirmação de orçamento'];
        }

        if (!empty($link['quote_confirmed_at'])) {
            return ['success' => false, 'message' => 'Este orçamento já foi confirmado anteriormente'];
        }

        $orderId = $link['order_id'];

        // Verificar se tem itens no pedido
        $orderModel = new Order($this->db);
        $items = $orderModel->getItems($orderId);
        if (empty($items)) {
            return ['success' => false, 'message' => 'Não é possível confirmar um orçamento sem produtos'];
        }

        // Marcar a confirmação do orçamento com IP
        $stmt = $this->db->prepare(
            "UPDATE orders SET quote_confirmed_at = NOW(), quote_confirmed_ip = :ip WHERE id = :id"
        );
        $stmt->bindParam(':ip', $clientIp);
        $stmt->bindParam(':id', $orderId, PDO::PARAM_INT);
        $stmt->execute();

        // Sincronizar: marcar customer_approval_status como 'aprovado'
        $orderModel->setCustomerApprovalStatus($orderId, 'aprovado');
        $stmtApproval = $this->db->prepare(
            "UPDATE orders SET customer_approval_at = NOW(), customer_approval_ip = :ip,
                    customer_approval_notes = 'Aprovado via link de catálogo'
             WHERE id = :id"
        );
        $stmtApproval->execute([':ip' => $clientIp, ':id' => $orderId]);

        // Log
        $logger = new Logger($this->db);
        $logger->log('QUOTE_CONFIRMED', "Orçamento do pedido #{$orderId} confirmado pelo cliente via catálogo (IP: {$clientIp})");

        return [
            'success'      => true,
            'message'      => 'Orçamento confirmado com sucesso!',
            'confirmed_at' => date('Y-m-d H:i:s'),
            'confirmed_ip' => $clientIp,
        ];
    }

    /**
     * Revogar confirmação de orçamento.
     *
     * @param string $token Token do catálogo
     * @param string $clientIp IP do cliente
     * @return array ['success' => bool, ...]
     */
    public function revokeQuote(string $token, string $clientIp): array
    {
        $catalogModel = new CatalogLink($this->db);
        $link = $catalogModel->findByToken($token);

        if (!$link) {
            return ['success' => false, 'message' => 'Link inválido ou expirado'];
        }

        if (empty($link['require_confirmation'])) {
            return ['success' => false, 'message' => 'Este link não permite confirmação de orçamento'];
        }

        if (empty($link['quote_confirmed_at'])) {
            return ['success' => false, 'message' => 'O orçamento ainda não foi confirmado'];
        }

        $orderId = $link['order_id'];

        // Revogar a confirmação
        $stmt = $this->db->prepare(
            "UPDATE orders SET quote_confirmed_at = NULL, quote_confirmed_ip = NULL WHERE id = :id"
        );
        $stmt->bindParam(':id', $orderId, PDO::PARAM_INT);
        $stmt->execute();

        // Sincronizar: voltar customer_approval_status para 'pendente'
        $orderModel = new Order($this->db);
        $orderModel->setCustomerApprovalStatus($orderId, 'pendente');
        $stmtApproval = $this->db->prepare(
            "UPDATE orders SET customer_approval_at = NULL, customer_approval_ip = NULL,
                    customer_approval_notes = NULL
             WHERE id = :id"
        );
        $stmtApproval->execute([':id' => $orderId]);

        // Log
        $logger = new Logger($this->db);
        $logger->log('QUOTE_REVOKED', "Orçamento do pedido #{$orderId} revogado pelo cliente via catálogo (IP: {$clientIp})");

        return [
            'success' => true,
            'message' => 'Confirmação revogada. Agora você pode editar o orçamento.',
        ];
    }

    /**
     * Extrair IP real do cliente a partir dos headers HTTP.
     *
     * @return string
     */
    public static function getClientIp(): string
    {
        $clientIp = $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['HTTP_X_REAL_IP']
            ?? $_SERVER['REMOTE_ADDR']
            ?? 'desconhecido';

        if (strpos($clientIp, ',') !== false) {
            $clientIp = trim(explode(',', $clientIp)[0]);
        }

        return $clientIp;
    }
}
