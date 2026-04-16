<?php
namespace Akti\Services;

use Akti\Models\PortalAccess;
use Akti\Models\CatalogLink;
use Akti\Utils\Input;
use PDO;

/**
 * Service: PortalOrderService
 * Lógica de pedidos/aprovação do Portal do Cliente.
 */
class PortalOrderService
{
    private PDO $db;
    private PortalAccess $portalAccess;

    /**
     * Construtor da classe PortalOrderService.
     *
     * @param PDO $db Conexão PDO com o banco de dados
     * @param PortalAccess $portalAccess Portal access
     */
    public function __construct(PDO $db, PortalAccess $portalAccess)
    {
        $this->db = $db;
        $this->portalAccess = $portalAccess;
    }

    /**
     * Carrega lista de pedidos paginada para um cliente.
     */
    public function listOrders(int $customerId, string $filter = 'all', int $page = 1, int $perPage = 10): array
    {
        $validFilters = ['all', 'open', 'approval', 'done'];
        if (!in_array($filter, $validFilters)) {
            $filter = 'all';
        }

        $offset = ($page - 1) * $perPage;

        $orders     = $this->portalAccess->getOrdersByCustomer($customerId, $filter, $perPage, $offset);
        $totalCount = $this->portalAccess->countOrdersByCustomer($customerId, $filter);
        $totalPages = max(1, (int) ceil($totalCount / $perPage));

        return [
            'orders'        => $orders,
            'totalCount'    => $totalCount,
            'totalPages'    => $totalPages,
            'countAll'      => $this->portalAccess->countOrdersByCustomer($customerId, 'all'),
            'countOpen'     => $this->portalAccess->countOrdersByCustomer($customerId, 'open'),
            'countApproval' => $this->portalAccess->countOrdersByCustomer($customerId, 'approval'),
            'countDone'     => $this->portalAccess->countOrdersByCustomer($customerId, 'done'),
        ];
    }

    /**
     * Carrega detalhes completos de um pedido para um cliente.
     *
     * @return array|null null se não encontrado/não pertence ao cliente
     */
    public function getOrderDetail(int $orderId, int $customerId): ?array
    {
        $order = $this->portalAccess->getOrderDetail($orderId, $customerId);
        if (!$order) {
            return null;
        }

        $items        = $this->portalAccess->getOrderItems($orderId);
        $installments = $this->portalAccess->getOrderInstallments($orderId);
        $extraCosts   = $this->portalAccess->getOrderExtraCosts($orderId);
        $timeline     = $this->portalAccess->getOrderTimeline($order);

        // Buscar link de catálogo ativo
        $catalogUrl = null;
        try {
            $catalogModel = new CatalogLink($this->db);
            $activeLink = $catalogModel->findActiveByOrder($orderId);
            if ($activeLink) {
                $catalogUrl = CatalogLink::buildUrl($activeLink['token']);
            }
        } catch (\Exception $e) {
            // Tabela pode não existir
        }

        $allowApproval = $this->portalAccess->getConfig('allow_order_approval', '1') === '1';

        return [
            'order'         => $order,
            'items'         => $items,
            'installments'  => $installments,
            'extraCosts'    => $extraCosts,
            'timeline'      => $timeline,
            'catalogUrl'    => $catalogUrl,
            'allowApproval' => $allowApproval,
        ];
    }

    /**
     * Aprova um orçamento.
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function approveOrder(int $orderId, int $customerId, string $ip, ?string $notes = null): array
    {
        if ($this->portalAccess->getConfig('allow_order_approval', '1') !== '1') {
            return ['success' => false, 'message' => 'disabled'];
        }

        $order = $this->portalAccess->getOrderDetail($orderId, $customerId);
        if (!$order) {
            return ['success' => false, 'message' => 'not_found'];
        }

        if (($order['customer_approval_status'] ?? null) !== 'pendente') {
            return ['success' => false, 'message' => 'not_pending'];
        }

        $success = $this->portalAccess->updateApprovalStatus($orderId, $customerId, 'aprovado', $ip, $notes);

        return ['success' => $success, 'message' => $success ? 'approved' : 'error'];
    }

    /**
     * Rejeita um orçamento.
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function rejectOrder(int $orderId, int $customerId, string $ip, ?string $notes = null): array
    {
        if ($this->portalAccess->getConfig('allow_order_approval', '1') !== '1') {
            return ['success' => false, 'message' => 'disabled'];
        }

        $order = $this->portalAccess->getOrderDetail($orderId, $customerId);
        if (!$order) {
            return ['success' => false, 'message' => 'not_found'];
        }

        if (($order['customer_approval_status'] ?? null) !== 'pendente') {
            return ['success' => false, 'message' => 'not_pending'];
        }

        $success = $this->portalAccess->updateApprovalStatus($orderId, $customerId, 'recusado', $ip, $notes);

        return ['success' => $success, 'message' => $success ? 'rejected' : 'error'];
    }

    /**
     * Cancela aprovação/rejeição (volta para pendente).
     *
     * @return array ['success' => bool, 'message' => string, 'previous_status' => string]
     */
    public function cancelApproval(int $orderId, int $customerId, string $ip): array
    {
        if ($this->portalAccess->getConfig('allow_order_approval', '1') !== '1') {
            return ['success' => false, 'message' => 'disabled', 'previous_status' => ''];
        }

        $order = $this->portalAccess->getOrderDetail($orderId, $customerId);
        if (!$order) {
            return ['success' => false, 'message' => 'not_found', 'previous_status' => ''];
        }

        $approvalStatus = $order['customer_approval_status'] ?? null;
        if (!in_array($approvalStatus, ['aprovado', 'recusado'])) {
            return ['success' => false, 'message' => 'not_applicable', 'previous_status' => $approvalStatus ?? ''];
        }

        $success = $this->portalAccess->cancelApprovalStatus($orderId, $customerId, $ip);

        return [
            'success'         => $success,
            'message'         => $success ? 'cancelled' : 'error',
            'previous_status' => $approvalStatus,
        ];
    }

    /**
     * Submete pedido a partir do carrinho.
     *
     * @return int|null ID do pedido criado ou null em caso de carrinho vazio
     */
    public function submitOrder(int $customerId, array $cart, ?string $notes = null): ?int
    {
        if (empty($cart)) {
            return null;
        }

        return $this->portalAccess->createPortalOrder($customerId, $cart, $notes);
    }
}
