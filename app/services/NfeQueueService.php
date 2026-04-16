<?php
namespace Akti\Services;

use Akti\Models\NfeQueue;
use Akti\Models\NfeDocument;
use Akti\Models\Order;
use PDO;

/**
 * NfeQueueService — Gerencia a fila de emissão assíncrona de NF-e.
 *
 * Funcionalidades:
 *   - Enfileirar pedidos individuais ou em lote
 *   - Processar fila (worker)
 *   - Consultar status
 *
 * @package Akti\Services
 */
class NfeQueueService
{
    private PDO $db;
    private NfeQueue $queueModel;

    /**
     * Construtor da classe NfeQueueService.
     *
     * @param PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->queueModel = new NfeQueue($db);
    }

    /**
     * Enfileira um pedido para emissão.
     *
     * @param int $orderId
     * @param int $modelo  55 ou 65
     * @param int $priority 1=alta, 5=normal, 10=baixa
     * @return array ['success' => bool, 'queue_id' => int|null, 'message' => string]
     */
    public function enqueue(int $orderId, int $modelo = 55, int $priority = 5): array
    {
        // Verificar se pedido já está na fila (pendente ou processando)
        $existing = $this->db->prepare(
            "SELECT id, status FROM nfe_queue WHERE order_id = :oid AND status IN ('pending','processing') LIMIT 1"
        );
        $existing->execute([':oid' => $orderId]);
        $row = $existing->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return [
                'success'  => false,
                'queue_id' => (int) $row['id'],
                'message'  => "Pedido #{$orderId} já está na fila (status: {$row['status']}).",
            ];
        }

        $queueId = $this->queueModel->enqueue([
            'order_id' => $orderId,
            'modelo'   => $modelo,
            'priority' => $priority,
        ]);

        return [
            'success'  => true,
            'queue_id' => $queueId,
            'message'  => "Pedido #{$orderId} adicionado à fila de emissão.",
        ];
    }

    /**
     * Enfileira múltiplos pedidos (emissão em lote).
     *
     * @param array $orderIds
     * @param int   $modelo
     * @return array ['success' => bool, 'batch_id' => string, 'enqueued' => int, 'skipped' => int, 'message' => string]
     */
    public function enqueueBatch(array $orderIds, int $modelo = 55): array
    {
        $batchId = 'BATCH_' . date('YmdHis') . '_' . uniqid();
        $enqueued = 0;
        $skipped = 0;

        // Verificar limite configurável
        $limit = 50;
        try {
            $q = $this->db->prepare("SELECT setting_value FROM company_settings WHERE setting_key = 'nfe_batch_limit' LIMIT 1");
            $q->execute();
            $val = $q->fetchColumn();
            if ($val !== false && (int) $val > 0) {
                $limit = (int) $val;
            }
        } catch (\Throwable $e) {
            // usar default
        }

        if (count($orderIds) > $limit) {
            return [
                'success'  => false,
                'batch_id' => '',
                'enqueued' => 0,
                'skipped'  => 0,
                'message'  => "Limite máximo de {$limit} pedidos por lote excedido.",
            ];
        }

        foreach ($orderIds as $orderId) {
            $result = $this->enqueue((int) $orderId, $modelo);
            if ($result['success']) {
                $enqueued++;
                // Marcar batch_id no documento quando for criado
            } else {
                $skipped++;
            }
        }

        return [
            'success'  => $enqueued > 0,
            'batch_id' => $batchId,
            'enqueued' => $enqueued,
            'skipped'  => $skipped,
            'message'  => "{$enqueued} pedido(s) enfileirado(s), {$skipped} ignorado(s).",
        ];
    }

    /**
     * Processa o próximo item da fila.
     * Deve ser chamado por um worker (cron ou endpoint).
     *
     * @return array ['processed' => bool, 'queue_id' => int|null, 'message' => string]
     */
    public function processNext(): array
    {
        $this->db->beginTransaction();
        try {
            $item = $this->queueModel->fetchNext();
            if (!$item) {
                $this->db->commit();
                return ['processed' => false, 'queue_id' => null, 'message' => 'Fila vazia.'];
            }

            $queueId = (int) $item['id'];
            $this->queueModel->markProcessing($queueId);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            error_log('[ROLLBACK] NfeQueueService::processNext - ' . $e->getMessage());
            return ['processed' => false, 'queue_id' => null, 'message' => 'Erro ao buscar item: ' . $e->getMessage()];
        }

        // Processar emissão
        try {
            $orderId = (int) $item['order_id'];
            $orderModel = new Order($this->db);
            $order = $orderModel->readOne($orderId);

            if (!$order) {
                $this->queueModel->markFailed($queueId, "Pedido #{$orderId} não encontrado.");
                return ['processed' => true, 'queue_id' => $queueId, 'message' => "Pedido #{$orderId} não encontrado."];
            }

            // Carregar itens do pedido
            $items = $orderModel->getItems($orderId);

            // Enriquecer itens com dados fiscais dos produtos
            $productModel = new \Akti\Models\Product($this->db);
            foreach ($items as &$it) {
                if (!empty($it['product_id'])) {
                    $product = $productModel->readOne($it['product_id']);
                    if ($product) {
                        $it['fiscal_ncm']     = $product['fiscal_ncm'] ?? $product['ncm'] ?? '';
                        $it['fiscal_cest']    = $product['fiscal_cest'] ?? $product['cest'] ?? '';
                        $it['fiscal_cfop_interna']       = $product['fiscal_cfop_venda_interna'] ?? $product['cfop_venda_interna'] ?? '';
                        $it['fiscal_cfop_interestadual']  = $product['fiscal_cfop_venda_interestadual'] ?? $product['cfop_venda_interestadual'] ?? '';
                        $it['fiscal_icms_cst']            = $product['fiscal_icms_cst'] ?? $product['icms_cst'] ?? '';
                        $it['fiscal_icms_aliquota']       = $product['fiscal_icms_aliquota'] ?? $product['icms_aliquota'] ?? 0;
                        $it['fiscal_pis_cst']             = $product['fiscal_pis_cst'] ?? $product['pis_cst'] ?? '';
                        $it['fiscal_cofins_cst']          = $product['fiscal_cofins_cst'] ?? $product['cofins_cst'] ?? '';
                        $it['fiscal_ipi_cst']             = $product['fiscal_ipi_cst'] ?? $product['ipi_cst'] ?? '';
                        $it['fiscal_ipi_aliquota']        = $product['fiscal_ipi_aliquota'] ?? $product['ipi_aliquota'] ?? 0;
                        $it['fiscal_origem']              = $product['fiscal_origem'] ?? $product['origem'] ?? 0;
                        $it['fiscal_ean']                 = $product['fiscal_ean'] ?? $product['ean'] ?? '';
                        $it['fiscal_unidade']             = $product['fiscal_unidade'] ?? $product['unidade'] ?? 'UN';
                    }
                }
            }
            unset($it);

            // Carregar dados do cliente
            $customer = null;
            if (!empty($order['customer_id'])) {
                $customerModel = new \Akti\Models\Customer($this->db);
                $customer = $customerModel->readOne($order['customer_id']);
            }

            // Parcelas financeiras
            $installments = [];
            try {
                $installmentModel = new \Akti\Models\Installment($this->db);
                $installments = $installmentModel->getByOrderId($orderId);
            } catch (\Throwable $e) {
                // sem parcelas
            }

            $orderData = array_merge($order, [
                'items'                   => $items,
                'customer_name'           => $customer['name'] ?? $order['customer_name'] ?? '',
                'customer_cpf_cnpj'       => $customer['document'] ?? $order['customer_document'] ?? '',
                'customer_ie'             => $customer['ie'] ?? $order['customer_ie'] ?? '',
                'customer_address'        => $customer['address'] ?? $order['customer_address'] ?? '',
                'customer_number'         => $customer['address_number'] ?? $order['customer_number'] ?? 'S/N',
                'customer_bairro'         => $customer['bairro'] ?? $customer['neighborhood'] ?? '',
                'customer_cep'            => $customer['cep'] ?? $customer['zipcode'] ?? '',
                'customer_municipio'      => $customer['city'] ?? $customer['municipio'] ?? '',
                'customer_cod_municipio'  => $customer['cod_municipio'] ?? '',
                'customer_uf'             => $customer['state'] ?? $customer['uf'] ?? $order['customer_state'] ?? 'RS',
                'valor_produtos'          => $order['total_amount'] ?? 0,
                'shipping_cost'           => $order['shipping_cost'] ?? $order['frete'] ?? 0,
                'installments'            => $installments,
            ]);

            $nfeService = new NfeService($this->db);
            $response = $nfeService->emit($orderId, $orderData);

            if ($response['success']) {
                $this->queueModel->markCompleted($queueId, $response['nfe_id'] ?? null);
                return [
                    'processed' => true,
                    'queue_id'  => $queueId,
                    'message'   => "NF-e emitida com sucesso para pedido #{$orderId}.",
                ];
            } else {
                $this->queueModel->markFailed($queueId, $response['message'] ?? 'Erro desconhecido');
                return [
                    'processed' => true,
                    'queue_id'  => $queueId,
                    'message'   => "Falha ao emitir NF-e para pedido #{$orderId}: " . ($response['message'] ?? ''),
                ];
            }

        } catch (\Throwable $e) {
            $this->queueModel->markFailed($queueId, $e->getMessage());
            return [
                'processed' => true,
                'queue_id'  => $queueId,
                'message'   => 'Exceção ao processar fila: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Processa até N itens da fila.
     * @param int $max
     * @return array resumo
     */
    public function processMultiple(int $max = 10): array
    {
        $results = ['total' => 0, 'success' => 0, 'failed' => 0, 'details' => []];

        for ($i = 0; $i < $max; $i++) {
            $r = $this->processNext();
            if (!$r['processed']) break;

            $results['total']++;
            $results['details'][] = $r;

            if (strpos($r['message'] ?? '', 'sucesso') !== false) {
                $results['success']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Retorna o model para operações diretas.
     * @return NfeQueue
     */
    public function getModel(): NfeQueue
    {
        return $this->queueModel;
    }
}
