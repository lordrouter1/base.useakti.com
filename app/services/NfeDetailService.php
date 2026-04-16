<?php
namespace Akti\Services;

use PDO;

/**
 * Service: NfeDetailService
 *
 * Encapsula lógica de montagem de dados para detalhe de NF-e,
 * incluindo financeiro (parcelas) e cálculo IBPTax.
 *
 * @package Akti\Services
 */
class NfeDetailService
{
    private PDO $db;

    /**
     * Construtor da classe NfeDetailService.
     *
     * @param PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Carrega parcelas vinculadas a um pedido e calcula resumo financeiro.
     *
     * @param int $orderId
     * @return array ['installments' => [...], 'summary' => [...]]
     */
    public function loadInstallmentData(int $orderId): array
    {
        $installments = [];
        $summary = [
            'total'       => 0,
            'pagas'       => 0,
            'pendentes'   => 0,
            'valor_pago'  => 0.00,
            'valor_total' => 0.00,
            'faturadas'   => 0,
        ];

        try {
            $installmentModel = new \Akti\Models\Installment($this->db);
            $installments = $installmentModel->getByOrderId($orderId);

            foreach ($installments as $inst) {
                $summary['total']++;
                $summary['valor_total'] += (float) ($inst['amount'] ?? 0);

                if ($inst['status'] === 'pago') {
                    $summary['pagas']++;
                    $summary['valor_pago'] += (float) ($inst['paid_amount'] ?? $inst['amount'] ?? 0);
                } elseif (in_array($inst['status'], ['pendente', 'atrasado'])) {
                    $summary['pendentes']++;
                }

                if (!empty($inst['nfe_status']) && $inst['nfe_status'] === 'faturada') {
                    $summary['faturadas']++;
                }
            }
        } catch (\Throwable $e) {
            // Modelo não disponível — seguir sem parcelas
        }

        return [
            'installments' => $installments,
            'summary'      => $summary,
        ];
    }

    /**
     * Calcula valor de tributos aproximados via IBPTax para uma NF-e.
     *
     * @param int   $nfeId               ID da NF-e
     * @param float $existingValorTributos Valor já salvo no documento
     * @return array ['valor' => float, 'fonte' => string]
     */
    public function calculateIbptax(int $nfeId, float $existingValorTributos = 0.0): array
    {
        $valor = $existingValorTributos;
        $fonte = '';

        if ($valor > 0) {
            return ['valor' => $valor, 'fonte' => $fonte];
        }

        try {
            $ibptaxModel = new \Akti\Models\IbptaxModel($this->db);
            $stmt = $this->db->prepare(
                "SELECT ncm, v_prod AS valor_total, origem FROM nfe_document_items WHERE nfe_document_id = :nfe_id"
            );
            $stmt->execute([':nfe_id' => $nfeId]);
            $nfeItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $totalTrib = 0.00;
            foreach ($nfeItems as $item) {
                $calc = $ibptaxModel->calculateTaxApprox(
                    $item['ncm'] ?? '',
                    (float) ($item['valor_total'] ?? 0),
                    (string) ($item['origem'] ?? '0')
                );
                $totalTrib += $calc['vTotTrib'];
                if (empty($fonte) && !empty($calc['fonte'])) {
                    $fonte = $calc['fonte'];
                }
            }

            if ($totalTrib > 0) {
                $valor = $totalTrib;
            }
        } catch (\Throwable $e) {
            // IBPTax não disponível
        }

        return ['valor' => $valor, 'fonte' => $fonte];
    }
}
