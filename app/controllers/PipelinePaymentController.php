<?php

namespace Akti\Controllers;

use Akti\Models\Financial;
use Akti\Models\Logger;
use Akti\Services\PipelineAlertService;
use Akti\Services\PipelinePaymentService;
use Akti\Services\PipelineService;
use Akti\Utils\Input;
use Akti\Utils\Sanitizer;

/**
 * Class PipelinePaymentController.
 */
class PipelinePaymentController extends BaseController
{
    private PipelineAlertService $alertService;
    private PipelinePaymentService $paymentService;
    private PipelineService $pipelineService;

    /**
     * Construtor da classe PipelinePaymentController.
     *
     * @param \PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(\PDO $db)
    {
        parent::__construct($db);
        $this->alertService = new PipelineAlertService($db);
        $this->paymentService = new PipelinePaymentService($db);
        $this->pipelineService = new PipelineService($db);
    }

    /**
     * Conta registros com critérios opcionais.
     */
    public function countInstallments()
    {
        $orderId = Input::get('order_id', 'int');
        if (!$orderId) {
            $this->json(['success' => false, 'message' => 'ID do pedido não informado']);
        }
        $count = $this->alertService->countInstallments($orderId);
        $this->json(['success' => true, 'count' => $count]);
    }

    /**
     * Delete installments.
     */
    public function deleteInstallments()
    {
        $orderId = Input::post('order_id', 'int');
        if (!$orderId) {
            $this->json(['success' => false, 'message' => 'ID do pedido não informado']);
        }
        $result = $this->alertService->deleteInstallments($orderId);
        $this->json($result);
    }

    /**
     * Gera conteúdo ou dados.
     */
    public function generatePaymentLink()
    {
        $orderId = Input::post('order_id', 'int');
        if (!$orderId) {
            $this->json(['success' => false, 'message' => 'Pedido não informado.']);
        }

        $gatewaySlug = Input::post('gateway_slug', 'string', '');
        $method = Input::post('method', 'string', 'auto');

        $result = $this->paymentService->generatePaymentLink($orderId, $gatewaySlug, $method);
        $this->json($result);
    }

    /**
     * Gera conteúdo ou dados.
     */
    public function generateMercadoPagoLink()
    {
        $this->generatePaymentLink();
    }

    /**
     * Confirm down payment.
     */
    public function confirmDownPayment()
    {
        $orderId = Input::post('order_id', 'int');
        if (!$orderId) {
            $this->json(['success' => false, 'message' => 'ID do pedido não informado.']);
        }

        $installmentModel = new \Akti\Models\Installment($this->db);
        $installments = $installmentModel->getByOrderId($orderId);

        $downPaymentInstallment = null;
        foreach ($installments as $inst) {
            if ((int) $inst['installment_number'] === 0 && in_array($inst['status'], ['pendente', 'atrasado'], true)) {
                $downPaymentInstallment = $inst;
                break;
            }
        }

        if (!$downPaymentInstallment) {
            $this->json(['success' => false, 'message' => 'Nenhuma parcela de entrada pendente encontrada.']);
        }

        $result = $installmentModel->pay((int) $downPaymentInstallment['id'], [
            'paid_date'      => date('Y-m-d'),
            'paid_amount'    => (float) $downPaymentInstallment['amount'],
            'payment_method' => 'entrada',
            'notes'          => 'Entrada/sinal confirmada via detalhe do pedido',
            'user_id'        => $_SESSION['user_id'] ?? null,
        ], true);

        if ($result) {
            $installmentModel->updateOrderPaymentStatus($orderId);
            $logger = new Logger($this->db);
            $logger->log('DOWN_PAYMENT_CONFIRMED', "Confirmed down payment for order #$orderId");
            $this->json(['success' => true, 'message' => 'Entrada confirmada com sucesso.']);
        } else {
            $this->json(['success' => false, 'message' => 'Erro ao confirmar entrada.']);
        }
    }

    /**
     * Sincroniza dados.
     */
    public function syncInstallments()
    {
        $orderId       = Input::post('order_id', 'int');
        $paymentMethod = Input::post('payment_method');
        $numInst       = Input::post('installments', 'int') ?: 0;
        $downPayment   = Input::post('down_payment', 'float', 0);
        $discount      = Input::post('discount', 'float', 0);

        if (!$orderId) {
            $this->json(['success' => false, 'message' => 'ID do pedido não informado.']);
        }

        $dueDates = [];
        $rawDueDates = $_POST['due_dates'] ?? [];
        if (is_array($rawDueDates)) {
            foreach ($rawDueDates as $num => $dateVal) {
                $sanitizedDate = Sanitizer::date($dateVal);
                if ($sanitizedDate) {
                    $dueDates[(int)$num] = $sanitizedDate;
                }
            }
        }

        $result = $this->pipelineService->syncInstallments($orderId, $paymentMethod, $numInst, $downPayment, $discount, $dueDates);
        $this->json($result);
    }

    /**
     * Update installment due date.
     */
    public function updateInstallmentDueDate()
    {
        $installmentId = Input::post('installment_id', 'int');
        $dueDate       = Input::post('due_date', 'date');

        if (!$installmentId || !$dueDate) {
            $this->json(['success' => false, 'message' => 'Dados incompletos.']);
        }

        $financialModel = new Financial($this->db);
        $result = $financialModel->updateInstallmentDueDate($installmentId, $dueDate);

        if ($result) {
            $logger = new Logger($this->db);
            $logger->log('INSTALLMENT_DUE_DATE', "Updated due date of installment #$installmentId to $dueDate");
        }

        $this->json(['success' => (bool)$result]);
    }
}
