<?php

namespace Akti\Services\Contracts;

/**
 * Interface PipelinePaymentServiceInterface.
 */
interface PipelinePaymentServiceInterface
{
    /**
     * Gera conteúdo ou dados.
     *
     * @param int $orderId ID do pedido
     * @param string $gatewaySlug Gateway slug
     * @param string $method Method
     * @return array
     */
    public function generatePaymentLink(int $orderId, string $gatewaySlug = '', string $method = 'auto'): array;

    /**
     * Gera conteúdo ou dados.
     *
     * @param int $orderId ID do pedido
     * @param int|null $installmentId Installment id
     * @param string $gatewaySlug Gateway slug
     * @param array $allowedMethods Allowed methods
     * @return array
     */
    public function generateCheckoutLink(
        int $orderId,
        ?int $installmentId = null,
        string $gatewaySlug = '',
        array $allowedMethods = []
    ): array;
}
