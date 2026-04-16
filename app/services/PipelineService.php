<?php
namespace Akti\Services;

use Akti\Models\Pipeline;
use Akti\Models\Order;
use Akti\Models\Product;
use Akti\Models\Stock;
use Akti\Models\Financial;
use Akti\Models\Logger;
use Akti\Utils\Sanitizer;
use PDO;

/**
 * Service responsável pela lógica de movimentação e regras de etapas do pipeline.
 * Extraído do PipelineController (Fase 2 — Refatoração de Controllers Monolíticos).
 */
class PipelineService
{
    private $db;
    private $pipelineModel;
    private $stockModel;

    /**
     * Zonas do pipeline para lógica de estoque:
     * - Pré-produção: sem estoque deduzido
     * - Produção+: estoque deduzido
     */
    private static $preProductionStages = ['contato', 'orcamento', 'venda'];
    private static $productionStages = ['producao', 'preparacao', 'envio', 'financeiro', 'concluido'];

    /**
     * Etapas bloqueadas quando existem parcelas pagas.
     */
    private static $stagesBlockedByPaidInstallments = ['contato', 'orcamento', 'venda', 'producao', 'cancelado'];

    /**
     * Construtor da classe PipelineService.
     *
     * @param PDO $db Conexão PDO com o banco de dados
     * @param Pipeline $pipelineModel Pipeline model
     * @param Stock $stockModel Stock model
     */
    public function __construct(PDO $db, Pipeline $pipelineModel, Stock $stockModel)
    {
        $this->db = $db;
        $this->pipelineModel = $pipelineModel;
        $this->stockModel = $stockModel;
    }

    /**
     * Verifica se a etapa é pré-produção.
     */
    public function isPreProduction(string $stage): bool
    {
        return in_array($stage, self::$preProductionStages);
    }

    /**
     * Verifica se a etapa é de produção.
     */
    public function isProduction(string $stage): bool
    {
        return in_array($stage, self::$productionStages);
    }

    /**
     * Verifica se a transição de etapa precisa de seleção de armazém.
     */
    public function transitionNeedsWarehouse(string $currentStage, string $newStage): bool
    {
        return $this->isPreProduction($currentStage) && $this->isProduction($newStage);
    }

    /**
     * Verifica se a movimentação está bloqueada por parcelas pagas.
     *
     * @return string|null Mensagem de erro ou null se OK
     */
    public function checkPaidInstallmentsBlock(int $orderId, string $newStage): ?string
    {
        if (!in_array($newStage, self::$stagesBlockedByPaidInstallments)) {
            return null;
        }

        $financialModel = new Financial($this->db);
        if ($financialModel->hasAnyPaidInstallment($orderId)) {
            $stageLabel = Pipeline::$stages[$newStage]['label'] ?? $newStage;
            return 'Não é possível mover para "' . $stageLabel . '" porque existem parcelas já pagas. Estorne os pagamentos primeiro.';
        }

        return null;
    }

    /**
     * Busca a etapa atual de um pedido.
     */
    public function getCurrentStage(int $orderId): ?string
    {
        $stmt = $this->db->prepare("SELECT pipeline_stage FROM orders WHERE id = :id");
        $stmt->bindParam(':id', $orderId);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['pipeline_stage'] : null;
    }

    /**
     * Processa a lógica de estoque ao mudar de etapa.
     *
     * @return array ['success' => bool, 'notes' => string]
     */
    public function handleStockTransition(int $orderId, ?string $currentStage, string $newStage, ?int $warehouseId = null, ?int $userId = null): array
    {
        $notes = '';
        $wasPreProd = $currentStage ? $this->isPreProduction($currentStage) : false;
        $willBeProd = $this->isProduction($newStage);
        $willBePreProd = $this->isPreProduction($newStage);
        $wasProd = $currentStage ? $this->isProduction($currentStage) : false;

        // PRÉ-PRODUÇÃO → PRODUÇÃO+: deduzir estoque
        if ($wasPreProd && $willBeProd) {
            $notes = $this->deductStock($orderId, $newStage, $warehouseId);
        }

        // PRODUÇÃO+ → PRÉ-PRODUÇÃO: devolver estoque
        if ($wasProd && $willBePreProd) {
            $reversed = $this->stockModel->reverseDeductions($orderId, $userId);
            if ($reversed > 0) {
                $notes = "Estoque devolvido: $reversed item(ns) retornados ao armazém.";
            }
        }

        // Qualquer → CANCELADO: devolver estoque se existir
        if ($newStage === 'cancelado') {
            $reversed = $this->stockModel->reverseDeductions($orderId, $userId);
            if ($reversed > 0) {
                $notes = "Estoque devolvido: $reversed item(ns) retornados ao armazém (cancelamento).";
            }
        }

        return ['success' => true, 'notes' => $notes];
    }

    /**
     * Deduz estoque ao entrar em produção.
     */
    private function deductStock(int $orderId, string $newStage, ?int $warehouseId): string
    {
        $orderModel = new Order($this->db);
        $productModel = new Product($this->db);
        $orderItems = $orderModel->getItems($orderId);

        // Determinar armazém
        if (!$warehouseId) {
            $stmtWh = $this->db->prepare("SELECT stock_warehouse_id FROM orders WHERE id = :id");
            $stmtWh->bindParam(':id', $orderId);
            $stmtWh->execute();
            $whRow = $stmtWh->fetch(PDO::FETCH_ASSOC);
            $warehouseId = $whRow['stock_warehouse_id'] ?? null;
        }
        if (!$warehouseId) {
            $defaultWarehouse = $this->stockModel->getDefaultWarehouse();
            $warehouseId = $defaultWarehouse ? $defaultWarehouse['id'] : null;
        }

        // Salvar armazém no pedido
        if ($warehouseId) {
            $stmtWh2 = $this->db->prepare("UPDATE orders SET stock_warehouse_id = :wid WHERE id = :id");
            $stmtWh2->bindParam(':wid', $warehouseId, PDO::PARAM_INT);
            $stmtWh2->bindParam(':id', $orderId, PDO::PARAM_INT);
            $stmtWh2->execute();
        }

        $deducted = 0;
        if ($warehouseId && !empty($orderItems)) {
            foreach ($orderItems as $item) {
                $product = $productModel->readOne($item['product_id']);
                if (!$product || empty($product['use_stock_control'])) {
                    continue;
                }

                $combinationId = $item['grade_combination_id'] ?? null;
                $qty = (int)$item['quantity'];

                $movementId = $this->stockModel->addMovement([
                    'warehouse_id'   => $warehouseId,
                    'product_id'     => $item['product_id'],
                    'combination_id' => $combinationId,
                    'type'           => 'saida',
                    'quantity'       => $qty,
                    'reason'         => 'Dedução automática — Pedido #' . $orderId . ' entrou em ' . $newStage,
                    'reference_type' => 'order',
                    'reference_id'   => $orderId,
                ]);

                $this->stockModel->addStockDeduction([
                    'order_id'       => $orderId,
                    'order_item_id'  => $item['id'],
                    'warehouse_id'   => $warehouseId,
                    'product_id'     => $item['product_id'],
                    'combination_id' => $combinationId,
                    'quantity'       => $qty,
                    'movement_id'    => $movementId,
                ]);
                $deducted++;
            }
        }

        if ($deducted > 0) {
            return "Estoque deduzido: $deducted item(ns) do armazém.";
        }

        return '';
    }

    /**
     * Gera automaticamente as parcelas de pagamento quando o pedido
     * chega nas etapas financeiro/concluido e ainda não possui parcelas.
     *
     * @return bool true se parcelas foram geradas
     */
    public function autoGenerateInstallments(int $orderId): bool
    {
        $financialModel = new Financial($this->db);

        $existingCount = $financialModel->countInstallments($orderId);
        if ($existingCount > 0) {
            return false;
        }

        $q = "SELECT total_amount, COALESCE(discount, 0) as discount, 
                     payment_method, COALESCE(installments, 0) as installments,
                     COALESCE(down_payment, 0) as down_payment
              FROM orders WHERE id = :id";
        $s = $this->db->prepare($q);
        $s->execute([':id' => $orderId]);
        $order = $s->fetch(PDO::FETCH_ASSOC);

        if (!$order || (float)$order['total_amount'] <= 0) {
            return false;
        }

        $totalAmount = (float)$order['total_amount'] - (float)$order['discount'];
        if ($totalAmount <= 0) {
            return false;
        }

        $paymentMethod = $order['payment_method'] ?? '';
        $numInstallments = (int)$order['installments'];
        $downPayment = (float)$order['down_payment'];

        $parcelableMethods = ['cartao_credito', 'boleto'];

        if (in_array($paymentMethod, $parcelableMethods) && $numInstallments >= 2) {
            $financialModel->generateInstallments($orderId, $totalAmount, $numInstallments, $downPayment);
        } else {
            $singleAmount = $totalAmount - $downPayment;
            if ($singleAmount <= 0) $singleAmount = $totalAmount;
            $financialModel->generateInstallments($orderId, $totalAmount, 1, $downPayment);
        }

        $logger = new Logger($this->db);
        $logger->log('INSTALLMENTS_AUTO', "Auto-generated installments for order #$orderId (method: $paymentMethod, installments: $numInstallments)");

        return true;
    }

    /**
     * Remove a confirmação de orçamento quando o pedido é modificado.
     */
    public function clearQuoteConfirmation(int $orderId): void
    {
        if (!$orderId) return;

        $stmt = $this->db->prepare("UPDATE orders SET quote_confirmed_at = NULL, quote_confirmed_ip = NULL WHERE id = :id AND quote_confirmed_at IS NOT NULL");
        $stmt->execute([':id' => $orderId]);

        if ($stmt->rowCount() > 0) {
            $logger = new Logger($this->db);
            $logger->log('QUOTE_CONFIRMATION_CLEARED', "Confirmação de orçamento do pedido #{$orderId} removida devido a alteração no pipeline");
        }

        // Sincronizar: voltar customer_approval_status para 'pendente'
        $orderModel = new Order($this->db);
        $order = $orderModel->readOne($orderId);
        $approvalStatus = $order['customer_approval_status'] ?? null;
        if ($approvalStatus === 'aprovado' || $approvalStatus === 'recusado') {
            $orderModel->setCustomerApprovalStatus($orderId, 'pendente');
            $this->db->prepare(
                "UPDATE orders SET customer_approval_at = NULL, customer_approval_ip = NULL, customer_approval_notes = NULL WHERE id = :id"
            )->execute([':id' => $orderId]);
        }
    }

    /**
     * Move pedido para outra etapa com todas as regras de negócio.
     *
     * @return array ['success' => bool, 'message' => string, 'stock_notes' => string]
     */
    public function moveOrder(int $orderId, string $newStage, ?int $userId, ?int $warehouseId = null, string $notes = ''): array
    {
        $currentStage = $this->getCurrentStage($orderId);

        // Verificar bloqueio por parcelas pagas
        $blockMessage = $this->checkPaidInstallmentsBlock($orderId, $newStage);
        if ($blockMessage) {
            return ['success' => false, 'message' => $blockMessage, 'blocked_by_paid' => true];
        }

        // Processar estoque
        $stockResult = $this->handleStockTransition($orderId, $currentStage, $newStage, $warehouseId, $userId);
        if (!empty($stockResult['notes'])) {
            $notes = ($notes ? $notes . ' | ' : '') . $stockResult['notes'];
        }

        $this->pipelineModel->moveToStage($orderId, $newStage, $userId, $notes);

        $logger = new Logger($this->db);
        $logger->log('PIPELINE_MOVE', "Order #$orderId moved from $currentStage to stage: $newStage");

        // Auto-gerar parcelas ao mover para financeiro/concluido
        if (in_array($newStage, ['financeiro', 'concluido'])) {
            $this->autoGenerateInstallments($orderId);
        }

        return [
            'success'     => true,
            'message'     => 'Pedido movido com sucesso',
            'stock_notes' => $stockResult['notes'] ?? '',
        ];
    }

    /**
     * Verifica se as parcelas precisam ser regeneradas com base nos dados atualizados do pedido.
     * Chamado quando os detalhes financeiros do pedido são alterados no pipeline.
     *
     * @param int $orderId
     * @param array $paymentData Dados de pagamento ['payment_method', 'installments', 'down_payment', 'discount']
     * @return void
     */
    public function regenerateInstallmentsIfNeeded(int $orderId, array $paymentData): void
    {
        $stmtStage = $this->db->prepare("SELECT pipeline_stage, total_amount FROM orders WHERE id = :id");
        $stmtStage->execute([':id' => $orderId]);
        $currentOrderData = $stmtStage->fetch(PDO::FETCH_ASSOC);
        $currentOrderStage = $currentOrderData['pipeline_stage'] ?? '';

        if (!in_array($currentOrderStage, ['venda', 'financeiro', 'concluido']) || empty($paymentData['payment_method'])) {
            return;
        }

        $financialModel = new Financial($this->db);
        $existingCount = $financialModel->countInstallments($orderId);

        if ($existingCount === 0) {
            $this->autoGenerateInstallments($orderId);
            return;
        }

        // Parcelas existem — verificar se a configuração de pagamento mudou
        $existingInstallments = $financialModel->getInstallments($orderId);
        $regularInstallments = array_filter($existingInstallments, function($i) {
            return (int)$i['installment_number'] > 0;
        });
        $existingRegularCount = count($regularInstallments);
        $hasExistingDownPayment = !empty(array_filter($existingInstallments, function($i) {
            return (int)$i['installment_number'] === 0;
        }));

        // Verificar se alguma parcela já foi paga
        $anyPaid = false;
        foreach ($existingInstallments as $inst) {
            if ($inst['status'] === 'pago') {
                $anyPaid = true;
                break;
            }
        }

        if ($anyPaid) {
            return; // Não regenerar se há parcelas pagas
        }

        $newInstallments = (int)($paymentData['installments'] ?? 0);
        $newDownPayment = (float)($paymentData['down_payment'] ?? 0);

        $parcelableMethods = ['cartao_credito', 'boleto'];
        $isParcelable = in_array($paymentData['payment_method'], $parcelableMethods);
        $expectedRegularCount = ($isParcelable && $newInstallments >= 2) ? $newInstallments : 1;
        $expectDownPayment = ($newDownPayment > 0);

        $newTotalAmount = (float)($currentOrderData['total_amount'] ?? 0) - (float)($paymentData['discount'] ?? 0);
        $expectedRemaining = $newTotalAmount - $newDownPayment;
        if ($expectedRemaining <= 0) $expectedRemaining = $newTotalAmount;

        $existingParcelasTotal = 0;
        foreach ($regularInstallments as $inst) {
            $existingParcelasTotal += (float)($inst['amount'] ?? 0);
        }

        $needsRegeneration = ($existingRegularCount !== $expectedRegularCount)
            || ($hasExistingDownPayment !== $expectDownPayment)
            || (abs($existingParcelasTotal - $expectedRemaining) > 0.01);

        if ($needsRegeneration && $newTotalAmount > 0) {
            $financialModel->generateInstallments($orderId, $newTotalAmount, $expectedRegularCount, $newDownPayment);
            $logger = new Logger($this->db);
            $logger->log('INSTALLMENTS_REGENERATED', "Regenerated installments for order #$orderId (method: {$paymentData['payment_method']}, count: $expectedRegularCount, down_payment: $newDownPayment, total: $newTotalAmount)");
        }
    }

    /**
     * Sincroniza parcelas do pedido com base nos dados financeiros.
     * Valida estágio, verifica parcelas pagas, gera/regenera parcelas, atualiza campos financeiros.
     *
     * @param int    $orderId       ID do pedido
     * @param string $paymentMethod Forma de pagamento
     * @param int    $numInst       Número de parcelas
     * @param float  $downPayment   Valor da entrada
     * @param float  $discount      Desconto
     * @param array  $dueDates      Datas de vencimento customizadas [installment_number => date]
     * @return array Resultado com success, message, installments, etc.
     */
    public function syncInstallments(int $orderId, string $paymentMethod, int $numInst, float $downPayment, float $discount, array $dueDates = []): array
    {
        // Buscar dados atuais do pedido
        $q = "SELECT total_amount, pipeline_stage, payment_method, installments, down_payment, discount FROM orders WHERE id = :id";
        $s = $this->db->prepare($q);
        $s->execute([':id' => $orderId]);
        $order = $s->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            return ['success' => false, 'message' => 'Pedido não encontrado.'];
        }

        // Só permitir sync em etapas relevantes
        if (!in_array($order['pipeline_stage'], ['venda', 'financeiro', 'concluido'])) {
            return ['success' => false, 'message' => 'Parcelas só podem ser geradas nas etapas Venda, Financeiro ou Concluído.'];
        }

        $financialModel = new Financial($this->db);

        // Verificar se há parcelas já pagas
        if ($financialModel->hasAnyPaidInstallment($orderId)) {
            return [
                'success' => false,
                'message' => 'Existem parcelas já pagas. Não é possível regenerar automaticamente. Estorne os pagamentos primeiro se necessário.',
                'has_paid' => true,
            ];
        }

        $totalAmount = (float)$order['total_amount'] - $discount;
        if ($totalAmount <= 0) {
            return ['success' => false, 'message' => 'Valor total do pedido inválido.'];
        }

        // Formas parceláveis
        $parcelableMethods = ['cartao_credito', 'boleto'];
        $isParcelable = in_array($paymentMethod, $parcelableMethods);
        $effectiveCount = ($isParcelable && $numInst >= 2) ? $numInst : 1;

        // Gerar/regenerar parcelas
        $financialModel->generateInstallments($orderId, $totalAmount, $effectiveCount, $downPayment, null, $dueDates);

        // Calcular valor da parcela para atualizar na tabela orders
        $remaining = $totalAmount - $downPayment;
        if ($remaining <= 0) $remaining = $totalAmount;
        $installmentValue = round($remaining / $effectiveCount, 2);

        // Atualizar campos financeiros no pedido
        $financialModel->updateOrderFinancialFields($orderId, [
            'payment_method' => $paymentMethod,
            'installments' => ($effectiveCount >= 2) ? $effectiveCount : null,
            'installment_value' => ($effectiveCount >= 2) ? $installmentValue : null,
            'down_payment' => $downPayment,
        ]);

        // Atualizar desconto se mudou
        if (abs($discount - (float)($order['discount'] ?? 0)) > 0.001) {
            $qd = "UPDATE orders SET discount = :disc WHERE id = :id";
            $sd = $this->db->prepare($qd);
            $sd->execute([':disc' => $discount, ':id' => $orderId]);
        }

        $logger = new Logger($this->db);
        $logger->log('INSTALLMENTS_SYNCED', "Synced installments for order #$orderId (method: $paymentMethod, count: $effectiveCount, down: $downPayment)");

        // Retornar as parcelas geradas
        $installments = $financialModel->getInstallments($orderId);

        return [
            'success' => true,
            'message' => "Parcelas atualizadas: $effectiveCount parcela(s) gerada(s).",
            'installments' => $installments,
            'count' => count($installments),
            'installment_value' => $installmentValue,
        ];
    }
}
