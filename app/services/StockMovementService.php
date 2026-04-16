<?php
namespace Akti\Services;

use Akti\Models\Stock;
use Akti\Models\Logger;
use Akti\Utils\Sanitizer;
use PDO;

/**
 * StockMovementService — Lógica de negócio para movimentações de estoque.
 *
 * Responsabilidades:
 *   - Processar movimentações (entrada, saída, ajuste, transferência)
 *   - Validar dados de movimentação
 *   - Registrar logs de movimentação
 *
 * @package Akti\Services
 */
class StockMovementService
{
    private PDO $db;
    private Stock $stockModel;
    private Logger $logger;

    private const TYPE_LABELS = [
        'entrada'       => 'Entrada',
        'saida'         => 'Saída',
        'ajuste'        => 'Ajuste',
        'transferencia' => 'Transferência',
    ];

    /**
     * Construtor da classe StockMovementService.
     *
     * @param PDO $db Conexão PDO com o banco de dados
     * @param Stock $stockModel Stock model
     * @param Logger $logger Logger
     */
    public function __construct(PDO $db, Stock $stockModel, Logger $logger)
    {
        $this->db = $db;
        $this->stockModel = $stockModel;
        $this->logger = $logger;
    }

    /**
     * Processar movimentação de estoque (múltiplos itens).
     *
     * @param int $warehouseId
     * @param string $type 'entrada'|'saida'|'ajuste'|'transferencia'
     * @param string|null $reason
     * @param array $items
     * @param int $destWarehouseId (apenas para transferência)
     * @return array ['success' => bool, 'processed' => int, 'errors' => array, 'message' => string]
     */
    public function processMovement(
        int $warehouseId,
        string $type,
        ?string $reason,
        array $items,
        int $destWarehouseId = 0
    ): array {
        if (!$warehouseId || empty($items)) {
            return [
                'success'   => false,
                'processed' => 0,
                'errors'    => ['Selecione um armazém e pelo menos um produto.'],
                'message'   => 'Selecione um armazém e pelo menos um produto.',
            ];
        }

        if ($type === 'transferencia' && !$destWarehouseId) {
            return [
                'success'   => false,
                'processed' => 0,
                'errors'    => ['Selecione o armazém de destino para transferência.'],
                'message'   => 'Selecione o armazém de destino para transferência.',
            ];
        }

        $processed = 0;
        $errors = [];

        foreach ($items as $i => $item) {
            $productId = Sanitizer::int($item['product_id'] ?? 0, 0);
            $combinationId = !empty($item['combination_id']) ? Sanitizer::int($item['combination_id']) : null;
            $quantity = Sanitizer::float($item['quantity'] ?? 0, 0);

            if (!$productId || $quantity <= 0) {
                $errors[] = "Item #" . ($i + 1) . ": produto ou quantidade inválida.";
                continue;
            }

            try {
                $this->stockModel->addMovement([
                    'warehouse_id'            => $warehouseId,
                    'product_id'              => $productId,
                    'combination_id'          => $combinationId,
                    'type'                    => $type,
                    'quantity'                => $quantity,
                    'reason'                  => $reason,
                    'reference_type'          => 'manual',
                    'destination_warehouse_id' => $type === 'transferencia' ? $destWarehouseId : null,
                ]);
                $processed++;
            } catch (\Exception $e) {
                $errors[] = "Item #" . ($i + 1) . ": " . $e->getMessage();
            }
        }

        $typeLabel = self::TYPE_LABELS[$type] ?? $type;
        $this->logger->log('STOCK_MOVEMENT', "{$typeLabel}: $processed item(s) processado(s) no armazém #$warehouseId");

        return [
            'success'   => true,
            'processed' => $processed,
            'errors'    => $errors,
            'message'   => "$processed item(s) processado(s) com sucesso.",
        ];
    }

    /**
     * Atualizar uma movimentação existente.
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateMovement(int $id, array $data): array
    {
        $movement = $this->stockModel->getMovement($id);
        if (!$movement) {
            return ['success' => false, 'message' => 'Movimentação não encontrada.'];
        }

        // Não permitir edição de movimentações automáticas
        $autoTypes = ['order', 'order_reversal', 'transfer'];
        if (in_array($movement['reference_type'], $autoTypes)) {
            return ['success' => false, 'message' => 'Movimentações automáticas não podem ser editadas.'];
        }

        if (($data['quantity'] ?? 0) <= 0) {
            return ['success' => false, 'message' => 'Quantidade deve ser maior que zero.'];
        }

        $result = $this->stockModel->updateMovement($id, $data);
        if ($result) {
            $typeLabel = self::TYPE_LABELS[$data['type'] ?? ''] ?? $data['type'] ?? '';
            $this->logger->log(
                'STOCK_MOVEMENT_UPDATE',
                "Movimentação #{$id} atualizada: {$typeLabel} — Qtd: {$data['quantity']} — Produto: {$movement['product_name']}"
            );
            return ['success' => true, 'message' => 'Movimentação atualizada com sucesso.'];
        }

        return ['success' => false, 'message' => 'Erro ao atualizar movimentação.'];
    }

    /**
     * Excluir uma movimentação.
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteMovement(int $id): array
    {
        $movement = $this->stockModel->getMovement($id);
        if (!$movement) {
            return ['success' => false, 'message' => 'Movimentação não encontrada.'];
        }

        $autoTypes = ['order', 'order_reversal', 'transfer'];
        if (in_array($movement['reference_type'], $autoTypes)) {
            return ['success' => false, 'message' => 'Movimentações automáticas não podem ser excluídas.'];
        }

        $result = $this->stockModel->deleteMovement($id);
        if ($result) {
            $typeLabel = self::TYPE_LABELS[$movement['type']] ?? $movement['type'];
            $this->logger->log(
                'STOCK_MOVEMENT_DELETE',
                "Movimentação #{$id} excluída: {$typeLabel} — Qtd: {$movement['quantity']} — Produto: {$movement['product_name']}"
            );
            return ['success' => true, 'message' => 'Movimentação excluída com sucesso. Saldo revertido.'];
        }

        return ['success' => false, 'message' => 'Erro ao excluir movimentação.'];
    }
}
