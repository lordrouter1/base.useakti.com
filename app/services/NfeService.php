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

    /**
     * Construtor da classe NfeService.
     *
     * @param PDO $db Conexão PDO com o banco de dados
     */
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

    /**
     * Verifica uma condição booleana.
     * @return bool
     */
    public function isLibraryAvailable(): bool
    {
        return $this->sefazClient->isLibraryAvailable();
    }

    /**
     * Test connection.
     * @return array
     */
    public function testConnection(): array
    {
        return $this->queryService->testConnection();
    }

    /**
     * Emite evento ou sinal.
     *
     * @param int $orderId ID do pedido
     * @param array $orderData Order data
     * @return array
     */
    public function emit(int $orderId, array $orderData): array
    {
        return $this->emissionService->emit($orderId, $orderData);
    }

    /**
     * Cancela operação.
     *
     * @param int $nfeId Nfe id
     * @param string $motivo Motivo
     * @return array
     */
    public function cancel(int $nfeId, string $motivo): array
    {
        return $this->cancellationService->cancel($nfeId, $motivo);
    }

    /**
     * Correction.
     *
     * @param int $nfeId Nfe id
     * @param string $texto Texto
     * @return array
     */
    public function correction(int $nfeId, string $texto): array
    {
        return $this->correctionService->correction($nfeId, $texto);
    }

    /**
     * Verifica condição ou estado.
     *
     * @param int $nfeId Nfe id
     * @return array
     */
    public function checkStatus(int $nfeId): array
    {
        return $this->queryService->checkStatus($nfeId);
    }

    /**
     * Obtém dados específicos.
     * @return array
     */
    public function getCredentials(): array
    {
        return $this->sefazClient->getCredentials();
    }

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
    public function inutilizar(int $numInicial, int $numFinal, string $justificativa, int $modelo = 55, int $serie = 1): array
    {
        return $this->emissionService->inutilizar($numInicial, $numFinal, $justificativa, $modelo, $serie);
    }
}
