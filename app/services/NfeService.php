<?php
namespace Akti\Services;

use Akti\Models\NfeDocument;
use Akti\Models\NfeLog;
use Akti\Services\Contracts\NfeServiceInterface;
use PDO;

/**
 * NfeService — Facade para operações NF-e.
 *
 * Delega para sub-services especializados:
 *   - NfeSefazClient       — Inicialização SEFAZ
 *   - NfeEmissionService   — Emissão e inutilização
 *   - NfeCancellationService — Cancelamento
 *   - NfeCorrectionService — Carta de Correção
 *   - NfeQueryService      — Consultas SEFAZ
 *
 * @package Akti\Services
 */
class NfeService implements NfeServiceInterface
{
    private NfeSefazClient $sefazClient;
    private NfeEmissionService $emissionService;
    private NfeCancellationService $cancellationService;
    private NfeCorrectionService $correctionService;
    private NfeQueryService $queryService;

    public function __construct(PDO $db)
    {
        $docModel  = new NfeDocument($db);
        $logModel  = new NfeLog($db);

        $this->sefazClient         = new NfeSefazClient($db);
        $this->emissionService     = new NfeEmissionService($db, $this->sefazClient, $docModel, $logModel);
        $this->cancellationService = new NfeCancellationService($this->sefazClient, $docModel, $logModel);
        $this->correctionService   = new NfeCorrectionService($db, $this->sefazClient, $docModel, $logModel);
        $this->queryService        = new NfeQueryService($this->sefazClient, $docModel, $logModel);
    }

    public function isLibraryAvailable(): bool
    {
        return $this->sefazClient->isLibraryAvailable();
    }

    public function testConnection(): array
    {
        return $this->queryService->testConnection();
    }

    public function emit(int $orderId, array $orderData): array
    {
        return $this->emissionService->emit($orderId, $orderData);
    }

    public function cancel(int $nfeId, string $motivo): array
    {
        return $this->cancellationService->cancel($nfeId, $motivo);
    }

    public function correction(int $nfeId, string $texto): array
    {
        return $this->correctionService->correction($nfeId, $texto);
    }

    public function checkStatus(int $nfeId): array
    {
        return $this->queryService->checkStatus($nfeId);
    }

    public function getCredentials(): array
    {
        return $this->sefazClient->getCredentials();
    }

    public function inutilizar(int $numInicial, int $numFinal, string $justificativa, int $modelo = 55, int $serie = 1): array
    {
        return $this->emissionService->inutilizar($numInicial, $numFinal, $justificativa, $modelo, $serie);
    }
}
