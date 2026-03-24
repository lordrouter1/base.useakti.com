<?php
namespace Akti\Services;

use Akti\Models\Commission;
use Akti\Models\Logger;
use PDO;

/**
 * CommissionAutoService — Serviço de Comissão Automática
 *
 * Responsável por calcular e registrar comissões automaticamente quando:
 *   1. O pedido atinge a etapa configurada em 'pipeline_stage_comissao'
 *   2. O critério de liberação de pagamento é atendido (config 'criterio_liberacao_comissao'):
 *      - sem_confirmacao:  libera imediatamente (não checa pagamento)
 *      - primeira_parcela: liberada ao pagar a primeira parcela
 *      - pagamento_total:  liberada somente com pagamento total confirmado
 *   3. Existe um vendedor/comissionado vinculado ao pedido (seller_id)
 *
 * Se 'aprovacao_automatica' estiver ativa, a comissão já entra com status
 * 'aguardando_pagamento' (ao invés de 'calculada'), dispensando aprovação manual.
 *
 * Também registra uma transação financeira de saída (despesa de comissão).
 *
 * @package Akti\Services
 */
class CommissionAutoService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ═══════════════════════════════════════════════════
    // LEITURA DE CONFIGURAÇÕES
    // ═══════════════════════════════════════════════════

    /**
     * Retorna a etapa do pipeline configurada para gerar comissão.
     */
    public function getStageGatilho(): string
    {
        return $this->getConfigValue('pipeline_stage_comissao', 'concluido');
    }

    /**
     * Retorna o critério de liberação configurado.
     * @return string 'sem_confirmacao' | 'primeira_parcela' | 'pagamento_total'
     */
    public function getCriterioLiberacao(): string
    {
        return $this->getConfigValue('criterio_liberacao_comissao', 'pagamento_total');
    }

    /**
     * Retorna se a aprovação é automática.
     */
    public function isAprovacaoAutomatica(): bool
    {
        return (bool) (int) $this->getConfigValue('aprovacao_automatica', '0');
    }

    // ═══════════════════════════════════════════════════
    // CÁLCULO AUTOMÁTICO
    // ═══════════════════════════════════════════════════

    /**
     * Verifica se o pedido atende às condições para comissão automática
     * e, se atender, calcula e registra a comissão.
     *
     * Condições:
     *   - pipeline_stage = etapa configurada (pipeline_stage_comissao)
     *   - Critério de pagamento atendido (conforme criterio_liberacao_comissao)
     *   - seller_id IS NOT NULL
     *   - Comissão ainda não registrada para este pedido + vendedor
     *
     * @param int $orderId
     * @return array ['triggered' => bool, 'message' => string, 'comissao_id' => int|null]
     */
    public function tryAutoCommission(int $orderId): array
    {
        $stageGatilho = $this->getStageGatilho();
        $criterio = $this->getCriterioLiberacao();

        // Buscar dados do pedido
        $q = "SELECT o.id, o.seller_id, o.pipeline_stage, o.payment_status, 
                     o.total_amount, o.discount, o.status,
                     u.name as seller_name
              FROM orders o
              LEFT JOIN users u ON o.seller_id = u.id
              WHERE o.id = :id";
        $s = $this->db->prepare($q);
        $s->execute([':id' => $orderId]);
        $order = $s->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            return ['triggered' => false, 'message' => 'Pedido não encontrado.', 'comissao_id' => null];
        }

        // Verificar etapa do pipeline
        if (($order['pipeline_stage'] ?? '') !== $stageGatilho) {
            return ['triggered' => false, 'message' => "Pedido não está na etapa '{$stageGatilho}'.", 'comissao_id' => null];
        }

        // Verificar critério de pagamento
        $paymentOk = $this->checkCriterioPagamento($orderId, $order, $criterio);

        if (!$paymentOk) {
            $criterioLabels = [
                'sem_confirmacao'  => 'Liberação imediata',
                'primeira_parcela' => 'Primeira parcela paga',
                'pagamento_total'  => 'Pagamento total confirmado',
            ];
            return [
                'triggered' => false,
                'message'   => 'Critério de pagamento não atendido: ' . ($criterioLabels[$criterio] ?? $criterio) . '.',
                'comissao_id' => null,
            ];
        }

        $sellerId = (int) ($order['seller_id'] ?? 0);
        if ($sellerId <= 0) {
            return ['triggered' => false, 'message' => 'Nenhum vendedor/comissionado vinculado ao pedido.', 'comissao_id' => null];
        }

        // Verificar se já existe comissão registrada para este pedido + vendedor
        $commissionModel = new Commission($this->db);
        if ($commissionModel->existeComissao($orderId, $sellerId)) {
            return ['triggered' => false, 'message' => 'Comissão já registrada para este pedido e vendedor.', 'comissao_id' => null];
        }

        // Calcular comissão usando o CommissionEngine
        $engine = new CommissionEngine($this->db, $commissionModel);

        $valorVenda = (float) ($order['total_amount'] ?? 0);
        $desconto = (float) ($order['discount'] ?? 0);
        $valorLiquido = $valorVenda - $desconto;

        $context = [
            'user_id'      => $sellerId,
            'order_id'     => $orderId,
            'valor_venda'  => $valorLiquido > 0 ? $valorLiquido : $valorVenda,
            'margem_lucro' => 0,
            'observacao'   => 'Comissão automática — Pedido na etapa "' . $stageGatilho . '" com critério "' . $criterio . '" atendido.',
        ];

        $resultado = $engine->calcularERegistrar($context);

        if (!empty($resultado['error']) || !empty($resultado['warning'])) {
            $msg = $resultado['error'] ?? $resultado['warning'] ?? 'Erro desconhecido.';
            return ['triggered' => false, 'message' => $msg, 'comissao_id' => null];
        }

        $comissaoId = $resultado['comissao_id'] ?? null;
        $valorComissao = (float) ($resultado['valor_comissao'] ?? 0);

        // Nota: o CommissionEngine já define o status correto
        // ('aguardando_pagamento' se aprovação automática, 'calculada' se manual)

        // ⚠ A transação financeira (movimentação no caixa) NÃO é gerada aqui.
        // Ela só será registrada quando a comissão for efetivamente paga
        // (status → 'paga'), via CommissionService::pagarComissao().

        // Log
        try {
            $logger = new Logger($this->db);
            $logger->log('COMMISSION_AUTO', sprintf(
                'Comissão automática registrada: Pedido #%d | Vendedor ID: %d (%s) | Valor: R$ %s | Comissão ID: %d | Critério: %s',
                $orderId,
                $sellerId,
                $order['seller_name'] ?? 'N/A',
                number_format($valorComissao, 2, ',', '.'),
                $comissaoId ?? 0,
                $criterio
            ));
        } catch (\Throwable $e) {
            // Silencioso — não quebrar o fluxo
        }

        return [
            'triggered'    => true,
            'message'      => sprintf(
                'Comissão de R$ %s registrada para %s.',
                number_format($valorComissao, 2, ',', '.'),
                $order['seller_name'] ?? 'vendedor'
            ),
            'comissao_id'  => $comissaoId,
            'valor'        => $valorComissao,
        ];
    }

    // ═══════════════════════════════════════════════════
    // VERIFICAÇÃO DE CRITÉRIO DE PAGAMENTO
    // ═══════════════════════════════════════════════════

    /**
     * Verifica se o critério de pagamento para liberação da comissão foi atendido.
     *
     * @param int    $orderId
     * @param array  $order     Dados do pedido (com payment_status)
     * @param string $criterio  'sem_confirmacao' | 'primeira_parcela' | 'pagamento_total'
     * @return bool
     */
    private function checkCriterioPagamento(int $orderId, array $order, string $criterio): bool
    {
        switch ($criterio) {
            case 'sem_confirmacao':
                // Liberação imediata — não checa pagamento
                return true;

            case 'primeira_parcela':
                // Verifica se ao menos 1 parcela está paga e confirmada
                return $this->hasFirstInstallmentPaid($orderId);

            case 'pagamento_total':
            default:
                // Verifica se TODAS as parcelas estão pagas e confirmadas
                $paymentOk = (($order['payment_status'] ?? '') === 'pago');
                if (!$paymentOk) {
                    $paymentOk = $this->isFullyPaidAndConfirmed($orderId);
                }
                return $paymentOk;
        }
    }

    /**
     * Verifica se ao menos a primeira parcela está paga e confirmada.
     */
    private function hasFirstInstallmentPaid(int $orderId): bool
    {
        $q = "SELECT COUNT(*) FROM order_installments
              WHERE order_id = :oid AND installment_number > 0
                AND status = 'pago' AND is_confirmed = 1
              LIMIT 1";
        $s = $this->db->prepare($q);
        $s->execute([':oid' => $orderId]);
        return (int) $s->fetchColumn() > 0;
    }

    /**
     * Verifica diretamente nas parcelas se o pedido está totalmente pago e confirmado.
     */
    private function isFullyPaidAndConfirmed(int $orderId): bool
    {
        $q = "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pago' AND is_confirmed = 1 THEN 1 ELSE 0 END) as pagas
              FROM order_installments
              WHERE order_id = :oid AND installment_number > 0";
        $s = $this->db->prepare($q);
        $s->execute([':oid' => $orderId]);
        $row = $s->fetch(PDO::FETCH_ASSOC);

        if (!$row || (int) $row['total'] === 0) {
            return false;
        }

        return (int) $row['pagas'] >= (int) $row['total'];
    }

    // ═══════════════════════════════════════════════════
    // HELPER: Leitura de config
    // ═══════════════════════════════════════════════════

    /**
     * Lê um valor de configuração da tabela comissao_config.
     */
    private function getConfigValue(string $key, string $default = ''): string
    {
        $stmt = $this->db->prepare("SELECT config_value FROM comissao_config WHERE config_key = :k");
        $stmt->execute([':k' => $key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (string) $val : $default;
    }
}
