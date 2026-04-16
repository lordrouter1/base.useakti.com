<?php

namespace Akti\Services\Contracts;

/**
 * Interface NfeServiceInterface.
 */
interface NfeServiceInterface
{
    /**
     * Verifica uma condição booleana.
     * @return bool
     */
    public function isLibraryAvailable(): bool;

    /**
     * Test connection.
     * @return array
     */
    public function testConnection(): array;

    /**
     * Emite evento ou sinal.
     *
     * @param int $orderId ID do pedido
     * @param array $orderData Order data
     * @return array
     */
    public function emit(int $orderId, array $orderData): array;

    /**
     * Cancela operação.
     *
     * @param int $nfeId Nfe id
     * @param string $motivo Motivo
     * @return array
     */
    public function cancel(int $nfeId, string $motivo): array;

    /**
     * Correction.
     *
     * @param int $nfeId Nfe id
     * @param string $texto Texto
     * @return array
     */
    public function correction(int $nfeId, string $texto): array;

    /**
     * Verifica condição ou estado.
     *
     * @param int $nfeId Nfe id
     * @return array
     */
    public function checkStatus(int $nfeId): array;

    /**
     * Obtém dados específicos.
     * @return array
     */
    public function getCredentials(): array;

    /**
     * Inutilizar.
     *
     * @param int $numInicial Num inicial
     * @param int $numFinal Num final
     * @param string $justificativa Justificativa
     * @param int $modelo Modelo
     * @param int $serie Serie
     * @return array
     */
    public function inutilizar(int $numInicial, int $numFinal, string $justificativa, int $modelo = 55, int $serie = 1): array;
}
