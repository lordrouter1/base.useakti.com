<?php
namespace Akti\Services;

use Akti\Models\Financial;
use Akti\Models\Installment;
use PDO;

/**
 * TransactionService — Camada de Serviço para Transações Financeiras.
 *
 * Orquestra operações de entradas, saídas, estornos e relatórios.
 * Os Controllers delegam a execução para este Service.
 *
 * @package Akti\Services
 */
class TransactionService
{
    private Financial $financial;
    private PDO $db;

    public function __construct(PDO $db, Financial $financial)
    {
        $this->db = $db;
        $this->financial = $financial;
    }

    /**
     * Adiciona uma transação financeira.
     * @param array $data
     * @return bool
     */
    public function addTransaction(array $data): bool
    {
        return $this->financial->addTransaction($data);
    }

    /**
     * Busca uma transação pelo ID.
     * @param int $id
     * @return array|false
     */
    public function getById(int $id)
    {
        return $this->financial->getTransactionById($id);
    }

    /**
     * Atualiza uma transação existente.
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        return $this->financial->updateTransaction($id, $data);
    }

    /**
     * Deleta transação.
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return $this->financial->deleteTransaction($id);
    }

    /**
     * Lista transações com paginação e filtros.
     * @param array $filters
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getPaginated(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        return $this->financial->getTransactionsPaginated($filters, $page, $perPage);
    }

    /**
     * Registra estorno na tabela de transações (chamado pelo InstallmentService).
     * @param array $parcelaAntes Dados da parcela antes do cancelamento
     * @param int|null $userId
     * @return bool
     */
    public function registerReversal(array $parcelaAntes, ?int $userId = null): bool
    {
        $valorEstorno = (float) ($parcelaAntes['paid_amount'] ?? $parcelaAntes['amount']);
        if ($valorEstorno <= 0) {
            return false;
        }

        return $this->financial->addTransaction([
            'type'             => 'registro',
            'category'         => 'estorno_pagamento',
            'description'      => "Estorno parcela {$parcelaAntes['installment_number']} - Pedido #{$parcelaAntes['order_id']}",
            'amount'           => $valorEstorno,
            'transaction_date' => date('Y-m-d'),
            'reference_type'   => 'installment',
            'reference_id'     => $parcelaAntes['id'] ?? null,
            'payment_method'   => $parcelaAntes['payment_method'] ?? null,
            'is_confirmed'     => 1,
            'user_id'          => $userId,
        ]);
    }

    /**
     * Registra transação de pagamento de parcela.
     * @param int $orderId
     * @param int $installmentId
     * @param array $data
     * @param bool $isConfirmed
     * @return bool
     */
    public function registerInstallmentPayment(int $orderId, int $installmentId, array $data, bool $isConfirmed): bool
    {
        return $this->financial->addTransaction([
            'type'             => 'entrada',
            'category'         => 'pagamento_pedido',
            'description'      => "Pagamento parcela - Pedido #{$orderId}",
            'amount'           => $data['paid_amount'],
            'transaction_date' => $data['paid_date'] ?? date('Y-m-d'),
            'reference_type'   => 'installment',
            'reference_id'     => $installmentId,
            'payment_method'   => $data['payment_method'] ?? null,
            'is_confirmed'     => $isConfirmed ? 1 : 0,
            'user_id'          => $data['user_id'] ?? null,
        ]);
    }

    /**
     * Retorna categorias de transação.
     * @return array
     */
    public function getCategories(): array
    {
        return Financial::getCategories();
    }
}
