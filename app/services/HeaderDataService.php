<?php
namespace Akti\Services;

use Akti\Core\Log;

use Akti\Utils\SimpleCache;
use PDO;

/**
 * HeaderDataService — Centraliza todas as queries que alimentam o header/layout.
 *
 * Substitui as 70+ linhas de SQL que estavam diretamente em header.php,
 * restaurando a separação MVC. Os dados são cacheados via SimpleCache
 * para evitar consultas repetidas a cada page load.
 *
 * @see ROADMAP Fase 2 — Item 3.2
 * @see ROADMAP Fase 4 — Item 4.1 (SimpleCache)
 */
class HeaderDataService
{
    /** @var PDO */
    private $conn;

    /** TTL do cache em segundos */
    private const CACHE_TTL = 120; // 2 minutos

    /** Chave do cache de permissões */
    private const CACHE_KEY_PERMISSIONS = 'header_permissions';

    /** Chave do cache de dados de header (pedidos/produtos atrasados) */
    private const CACHE_KEY_HEADER = 'header_data';

    /**
     * Construtor da classe HeaderDataService.
     *
     * @param PDO $conn Conn
     */
    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Retorna todos os dados necessários para o header em uma única chamada.
     *
     * @param int|null $userId
     * @param int|null $groupId
     * @param bool     $isAdmin
     * @return array{
     *     delayedCount: int,
     *     delayedOrders: array,
     *     delayedProducts: array,
     *     userPermissions: string[]
     * }
     */
    public function getAllHeaderData(?int $userId, ?int $groupId, bool $isAdmin): array
    {
        $conn = $this->conn;

        return SimpleCache::remember(self::CACHE_KEY_HEADER, self::CACHE_TTL, function () use ($userId, $groupId, $isAdmin, $conn) {
            $data = [
                'delayedCount'    => 0,
                'delayedOrders'   => [],
                'delayedProducts' => [],
                'userPermissions' => [],
            ];

            // Permissões do menu (cache separado com TTL maior)
            if (!$isAdmin && $userId && $groupId) {
                $data['userPermissions'] = SimpleCache::remember(
                    self::CACHE_KEY_PERMISSIONS . '_' . $groupId,
                    300, // 5 min para permissões
                    function () use ($groupId) {
                        return $this->getUserMenuPermissions($groupId);
                    }
                );
            }

            // Pedidos e produtos atrasados
            if ($userId) {
                $delayed = $this->getDelayedOrders();
                $data['delayedCount']    = $delayed['count'];
                $data['delayedOrders']   = $delayed['orders'];
                $data['delayedProducts'] = $this->getDelayedProducts();
            }

            return $data;
        });
    }

    /**
     * Retorna as permissões de menu para o grupo do usuário.
     *
     * @param int $groupId
     * @return string[]
     */
    public function getUserMenuPermissions(int $groupId): array
    {
        try {
            $stmt = $this->conn->prepare("SELECT page_name FROM group_permissions WHERE group_id = :gid");
            $stmt->bindParam(':gid', $groupId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (\Exception $e) {
            Log::error('HeaderDataService: Erro ao buscar permissões', ['exception' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Retorna a contagem e lista de pedidos atrasados no pipeline.
     *
     * @return array{count: int, orders: array}
     */
    public function getDelayedOrders(): array
    {
        $result = ['count' => 0, 'orders' => []];

        try {
            // Buscar metas por etapa
            $stmtGoals = $this->conn->query("SELECT stage, max_hours FROM pipeline_stage_goals");
            $goals = [];
            while ($row = $stmtGoals->fetch(PDO::FETCH_ASSOC)) {
                $goals[$row['stage']] = (int)$row['max_hours'];
            }

            // Buscar pedidos ativos
            $stmtOrders = $this->conn->query("
                SELECT o.id, o.pipeline_stage, o.pipeline_entered_at, o.priority, o.deadline,
                       c.name as customer_name
                FROM orders o
                LEFT JOIN customers c ON o.customer_id = c.id
                WHERE o.pipeline_stage NOT IN ('concluido','cancelado') AND o.status != 'cancelado'
                ORDER BY o.pipeline_entered_at ASC
            ");

            while ($order = $stmtOrders->fetch(PDO::FETCH_ASSOC)) {
                $hoursInStage = round((time() - strtotime($order['pipeline_entered_at'])) / 3600);
                $maxHours = $goals[$order['pipeline_stage']] ?? 24;

                if ($maxHours > 0 && $hoursInStage > $maxHours) {
                    $order['hours_in_stage'] = $hoursInStage;
                    $order['max_hours']      = $maxHours;
                    $order['delay_hours']    = $hoursInStage - $maxHours;
                    $result['orders'][]      = $order;
                    $result['count']++;
                }
            }
        } catch (\Exception $e) {
            Log::error('HeaderDataService: Erro ao buscar pedidos atrasados', ['exception' => $e->getMessage()]);
        }

        return $result;
    }

    /**
     * Retorna os produtos atrasados nos setores de produção.
     *
     * @return array
     */
    public function getDelayedProducts(): array
    {
        try {
            $stmt = $this->conn->query("
                SELECT ops.order_id, ops.order_item_id, ops.sector_id, ops.status, ops.started_at,
                       s.name as sector_name, s.color as sector_color,
                       p.name as product_name,
                       o.pipeline_stage,
                       oi.quantity,
                       c.name as customer_name
                FROM order_production_sectors ops
                JOIN production_sectors s ON ops.sector_id = s.id
                JOIN order_items oi ON ops.order_item_id = oi.id
                JOIN products p ON oi.product_id = p.id
                JOIN orders o ON ops.order_id = o.id
                LEFT JOIN customers c ON o.customer_id = c.id
                WHERE ops.status = 'pendente'
                  AND o.pipeline_stage IN ('producao','preparacao')
                  AND o.status != 'cancelado'
                ORDER BY ops.order_id ASC, ops.sort_order ASC
            ");
            $allPending = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Agrupar: primeiro setor pendente por item (setor atual)
            $currentByItem = [];
            foreach ($allPending as $row) {
                $key = $row['order_id'] . '_' . $row['order_item_id'];
                if (!isset($currentByItem[$key])) {
                    $currentByItem[$key] = $row;
                }
            }

            return array_values($currentByItem);
        } catch (\Exception $e) {
            Log::error('HeaderDataService: Erro ao buscar produtos atrasados', ['exception' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Invalida o cache do header (chamar após operações que alteram dados do pipeline).
     */
    public static function invalidateCache(): void
    {
        SimpleCache::forget(self::CACHE_KEY_HEADER);
        SimpleCache::forgetByPrefix(self::CACHE_KEY_PERMISSIONS);
        // Retrocompatibilidade: limpar cache antigo também
        unset($_SESSION['_header_cache']);
    }
}
