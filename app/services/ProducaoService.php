<?php

namespace Akti\Services;

use Akti\Models\ProductionConsumption;
use Akti\Services\InsumoService;
use Akti\Services\SupplyStockMovementService;
use PDO;

/**
 * Service de produção — integra BOM, disponibilidade e consumo.
 */
class ProducaoService
{
    private PDO $db;
    private InsumoService $insumoService;
    private SupplyStockMovementService $movementService;
    private ProductionConsumption $consumptionModel;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->insumoService = new InsumoService($db, new \Akti\Models\Supply($db), new \Akti\Models\SupplyStock($db));
        $this->movementService = new SupplyStockMovementService($db, new \Akti\Models\SupplyStock($db), new \Akti\Models\Supply($db), new \Akti\Models\Logger($db));
        $this->consumptionModel = new ProductionConsumption($db);
    }

    /**
     * Inicia produção: calcula BOM, verifica estoque, baixa insumos.
     *
     * @param int      $orderId
     * @param int      $productId
     * @param int|null $variationId
     * @param int      $lotSize
     * @param int      $warehouseId
     * @param int      $createdBy
     * @return array {success: bool, message: string, items?: array}
     */
    public function startProduction(
        int $orderId,
        int $productId,
        ?int $variationId,
        int $lotSize,
        int $warehouseId,
        int $createdBy
    ): array {
        // 1. Calcular BOM para o lote
        $bom = $this->insumoService->calculateBomForLot($productId, $lotSize, $variationId);

        if (empty($bom)) {
            return ['success' => false, 'message' => 'Nenhum insumo na ficha técnica deste produto.'];
        }

        // 2. Verificar disponibilidade
        $availability = $this->insumoService->checkAvailability($productId, $lotSize, $variationId, $warehouseId);

        if (!$availability['available']) {
            $shortages = array_filter($availability['items'], fn($i) => ($i['shortage'] ?? 0) > 0);
            $names = array_map(fn($i) => $i['supply_name'] . ' (faltam ' . $i['shortage'] . ')', $shortages);
            return [
                'success' => false,
                'message' => 'Estoque insuficiente: ' . implode(', ', $names),
                'shortages' => $shortages,
            ];
        }

        // 3. Processar baixa FEFO
        $this->movementService->processProductionConsumption(
            $orderId,
            $warehouseId,
            $bom,
            $createdBy,
            $productId,
            $variationId
        );

        return [
            'success' => true,
            'message' => 'Produção iniciada. Insumos baixados com sucesso.',
            'items'   => $bom,
        ];
    }

    /**
     * Registra consumo real de um item e calcula variância.
     *
     * @param int        $logId
     * @param float      $actualQuantity
     * @param int        $createdBy
     * @param string|null $notes
     * @return array {success: bool, variance: float, variance_percent: float}
     */
    public function reportActualConsumption(int $logId, float $actualQuantity, int $createdBy, ?string $notes = null): array
    {
        // Buscar dados do log
        $stmt = $this->db->prepare(
            "SELECT * FROM production_consumption_log WHERE id = :id"
        );
        $stmt->execute([':id' => $logId]);
        $log = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$log) {
            return ['success' => false, 'message' => 'Registro não encontrado.'];
        }

        $planned = (float) $log['planned_quantity'];
        $variance = $actualQuantity - $planned;
        $variancePercent = $planned > 0 ? ($variance / $planned) * 100 : 0;

        // Atualizar registro
        $upd = $this->db->prepare(
            "UPDATE production_consumption_log
             SET actual_quantity = :actual,
                 variance = :variance,
                 variance_percent = :variance_pct,
                 notes = :notes
             WHERE id = :id"
        );
        $upd->execute([
            ':actual'       => $actualQuantity,
            ':variance'     => $variance,
            ':variance_pct' => $variancePercent,
            ':notes'        => $notes,
            ':id'           => $logId,
        ]);

        // Se consumiu MENOS que o planejado, devolver ao estoque
        if ($variance < 0) {
            $returnQty = abs($variance);
            $this->movementService->processEntry(
                $log['supply_id'],
                $log['warehouse_id'],
                $returnQty,
                null, // batch_id
                'Devolução automática - variância negativa (Ordem #' . $log['order_id'] . ')',
                $createdBy
            );
        }

        return [
            'success'          => true,
            'variance'         => round($variance, 4),
            'variance_percent' => round($variancePercent, 2),
        ];
    }

    /**
     * Dados para o dashboard de eficiência.
     *
     * @param array $filters {date_from?, date_to?, product_id?}
     * @return array
     */
    public function getEfficiencyDashboard(array $filters = []): array
    {
        $dateFrom  = $filters['date_from'] ?? null;
        $dateTo    = $filters['date_to'] ?? null;
        $productId = $filters['product_id'] ?? null;

        return [
            'kpis'      => $this->consumptionModel->getEfficiencyStats($dateFrom, $dateTo, $productId),
            'top_waste' => $this->consumptionModel->getTopWaste(10, $dateFrom, $dateTo),
            'chart'     => $this->consumptionModel->getChartData($dateFrom, $dateTo),
        ];
    }
}
