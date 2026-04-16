<?php

namespace Akti\Services\Contracts;

/**
 * Interface CheckoutServiceInterface.
 */
interface CheckoutServiceInterface
{
    /**
     * Gera conteúdo ou dados.
     *
     * @param array $params Parâmetros adicionais
     * @return array
     */
    public function generateToken(array $params): array;

    /**
     * Processa uma operação específica.
     *
     * @param string $token Token de autenticação/verificação
     * @param array $paymentData Payment data
     * @return array
     */
    public function processCheckout(string $token, array $paymentData): array;

    /**
     * Cancela operação.
     *
     * @param int $tokenId Token id
     * @return bool
     */
    public function cancelToken(int $tokenId): bool;

    /**
     * Mark installment paid from checkout.
     *
     * @param int $orderId ID do pedido
     * @param int|null $installmentId Installment id
     * @param float $amount Valor monetário
     * @param string $paymentMethod Payment method
     * @param string|null $externalId External id
     * @return void
     */
    public function markInstallmentPaidFromCheckout(
        int $orderId,
        ?int $installmentId,
        float $amount,
        string $paymentMethod,
        ?string $externalId = null
    ): void;

    /**
     * Expire old tokens.
     * @return int
     */
    public function expireOldTokens(): int;

    /**
     * Obtém dados específicos.
     *
     * @param string $token Token de autenticação/verificação
     * @return array|null
     */
    public function getTokenByToken(string $token): ?array;
}
