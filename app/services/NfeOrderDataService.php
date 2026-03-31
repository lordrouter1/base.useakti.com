<?php
namespace Akti\Services;

use Akti\Models\Order;
use Akti\Models\Product;
use Akti\Models\Customer;
use Akti\Models\Installment;
use PDO;

/**
 * Service: NfeOrderDataService
 * Monta os dados do pedido (itens, cliente, fiscais) necessários para emissão NF-e/NFC-e.
 * Elimina duplicação de código entre emit(), retry() e emitNfce().
 */
class NfeOrderDataService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Carrega e valida um pedido para emissão.
     *
     * @param int $orderId
     * @return array{order: array, items: array}
     * @throws \RuntimeException se o pedido não for encontrado ou não tiver itens
     */
    public function loadOrderWithItems(int $orderId): array
    {
        $orderModel = new Order($this->db);
        $order = $orderModel->readOne($orderId);

        if (!$order) {
            throw new \RuntimeException("Pedido #{$orderId} não encontrado.");
        }

        $items = $orderModel->getItems($orderId);
        if (empty($items)) {
            throw new \RuntimeException('Pedido sem itens. Não é possível emitir NF-e.');
        }

        return ['order' => $order, 'items' => $items];
    }

    /**
     * Enriquece itens do pedido com dados fiscais do cadastro de produtos.
     *
     * @param array $items Itens do pedido
     * @return array Itens enriquecidos
     */
    public function enrichItemsWithFiscalData(array $items): array
    {
        $productModel = new Product($this->db);

        foreach ($items as &$it) {
            if (empty($it['product_id'])) {
                continue;
            }

            $product = $productModel->readOne($it['product_id']);
            if (!$product) {
                continue;
            }

            $it['fiscal_ncm']                 = $product['fiscal_ncm'] ?? $product['ncm'] ?? '';
            $it['fiscal_cest']                = $product['fiscal_cest'] ?? $product['cest'] ?? '';
            $it['fiscal_cfop_interna']        = $product['fiscal_cfop_venda_interna'] ?? $product['cfop_venda_interna'] ?? '';
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
        unset($it);

        return $items;
    }

    /**
     * Carrega dados do cliente para emissão NF-e.
     *
     * @param int|null $customerId
     * @return array|null
     */
    public function loadCustomer(?int $customerId): ?array
    {
        if (empty($customerId)) {
            return null;
        }

        $customerModel = new Customer($this->db);
        return $customerModel->readOne($customerId);
    }

    /**
     * Carrega parcelas financeiras do pedido (silencia erros).
     *
     * @param int $orderId
     * @return array
     */
    public function loadInstallments(int $orderId): array
    {
        try {
            $installmentModel = new Installment($this->db);
            return $installmentModel->getByOrderId($orderId);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Monta o array completo de dados para emissão NF-e (modelo 55).
     *
     * @param int $orderId
     * @return array Dados prontos para NfeService::emit()
     * @throws \RuntimeException
     */
    public function buildNfeData(int $orderId): array
    {
        $loaded = $this->loadOrderWithItems($orderId);
        $order = $loaded['order'];
        $items = $this->enrichItemsWithFiscalData($loaded['items']);

        $customer = $this->loadCustomer($order['customer_id'] ?? null);
        $installments = $this->loadInstallments($orderId);

        return array_merge($order, [
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
    }

    /**
     * Monta o array completo de dados para emissão NFC-e (modelo 65).
     *
     * @param int $orderId
     * @return array Dados prontos para NfeService::emitNfce()
     * @throws \RuntimeException
     */
    public function buildNfceData(int $orderId): array
    {
        $loaded = $this->loadOrderWithItems($orderId);
        $order = $loaded['order'];
        $items = $this->enrichItemsWithFiscalData($loaded['items']);

        $customer = $this->loadCustomer($order['customer_id'] ?? null);

        return array_merge($order, [
            'items'              => $items,
            'customer_name'      => $customer['name'] ?? $order['customer_name'] ?? '',
            'customer_cpf_cnpj'  => $customer['document'] ?? $order['customer_document'] ?? '',
            'payment_method'     => $order['payment_method'] ?? 'dinheiro',
            'troco'              => $order['troco'] ?? 0,
            'valor_produtos'     => $order['total_amount'] ?? 0,
        ]);
    }
}
