<?php

namespace Akti\Services\Contracts;

interface NfeServiceInterface
{
    public function isLibraryAvailable(): bool;

    public function testConnection(): array;

    public function emit(int $orderId, array $orderData): array;

    public function cancel(int $nfeId, string $motivo): array;

    public function correction(int $nfeId, string $texto): array;

    public function checkStatus(int $nfeId): array;

    public function getCredentials(): array;

    public function inutilizar(int $numInicial, int $numFinal, string $justificativa, int $modelo = 55, int $serie = 1): array;
}
