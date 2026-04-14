<?php

namespace Akti\Services\Contracts;

interface CheckoutServiceInterface
{
    public function generateToken(array $params): array;

    public function processCheckout(string $token, array $paymentData): array;

    public function cancelToken(int $tokenId): bool;

    public function markInstallmentPaidFromCheckout(
        int $orderId,
        ?int $installmentId,
        float $amount,
        string $paymentMethod,
        ?string $externalId = null
    ): void;

    public function expireOldTokens(): int;

    public function getTokenByToken(string $token): ?array;
}
