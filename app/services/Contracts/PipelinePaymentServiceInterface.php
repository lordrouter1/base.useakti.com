<?php

namespace Akti\Services\Contracts;

interface PipelinePaymentServiceInterface
{
    public function generatePaymentLink(int $orderId, string $gatewaySlug = '', string $method = 'auto'): array;

    public function generateCheckoutLink(
        int $orderId,
        ?int $installmentId = null,
        string $gatewaySlug = '',
        array $allowedMethods = []
    ): array;
}
