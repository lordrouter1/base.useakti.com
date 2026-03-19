<?php
namespace Akti\Controllers;

use Akti\Models\NfeDocument;
use Akti\Models\NfeLog;
use Akti\Models\NfeCredential;
use Akti\Models\Order;
use Akti\Services\NfeService;
use Akti\Services\NfePdfGenerator;
use Akti\Core\ModuleBootloader;
use Akti\Utils\Input;
use Database;
use TenantManager;
use PDO;

/**
 * Controller: NfeDocumentController
 * Gerencia documentos NF-e: listagem, emissão, cancelamento, carta de correção, download.
 *
 * @package Akti\Controllers
 */
class NfeDocumentController
{
    private $db;
    private NfeDocument $docModel;
    private NfeLog $logModel;

    public function __construct()
    {
        if (!ModuleBootloader::isModuleEnabled('nfe')) {
            http_response_code(403);
            require 'app/views/layout/header.php';
            echo "<div class='container mt-5'><div class='alert alert-warning'><i class='fas fa-toggle-off me-2'></i>Módulo NF-e desativado para este tenant.</div></div>";
            require 'app/views/layout/footer.php';
            exit;
        }

        $database = new Database();
        $this->db = $database->getConnection();
        $this->docModel = new NfeDocument($this->db);
        $this->logModel = new NfeLog($this->db);
    }

    // ══════════════════════════════════════════════════════════════
    // Listagem
    // ══════════════════════════════════════════════════════════════

    /**
     * Painel de Notas Fiscais — listagem com filtros e cards de resumo.
     */
    public function index()
    {
        $page = Input::get('pg', 'int', 1);
        $perPage = 20;
        $filters = [];

        if (Input::hasGet('status'))  $filters['status']  = Input::get('status');
        if (Input::hasGet('month'))   $filters['month']   = Input::get('month', 'int');
        if (Input::hasGet('year'))    $filters['year']     = Input::get('year', 'int');
        if (Input::hasGet('search'))  $filters['search']   = Input::get('search');

        $result = $this->docModel->readPaginated($filters, $page, $perPage);
        $documents = $result['data'];
        $totalItems = $result['total'];
        $totalPages = ceil($totalItems / $perPage);

        // Cards de resumo
        $statusCounts = $this->docModel->countByStatus();
        $countThisMonth = $this->docModel->countThisMonth();
        $sumAuthorized = $this->docModel->sumAuthorizedThisMonth();

        // Verificar credenciais
        $credModel = new NfeCredential($this->db);
        $validation = $credModel->validateForEmission();

        // Paginação
        $ctPage = $page;
        $baseUrl = '?page=nfe_documents'
            . (!empty($filters['status']) ? '&status=' . urlencode($filters['status']) : '')
            . (!empty($filters['month']) ? '&month=' . $filters['month'] : '')
            . (!empty($filters['year']) ? '&year=' . $filters['year'] : '')
            . (!empty($filters['search']) ? '&search=' . urlencode($filters['search']) : '');

        require 'app/views/layout/header.php';
        require 'app/views/nfe/index.php';
        require 'app/views/layout/footer.php';
    }

    // ══════════════════════════════════════════════════════════════
    // Emissão
    // ══════════════════════════════════════════════════════════════

    /**
     * Emite NF-e para um pedido (AJAX).
     */
    public function emit()
    {
        header('Content-Type: application/json');

        $orderId = Input::post('order_id', 'int', 0);
        if ($orderId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID do pedido inválido.']);
            exit;
        }

        // Carregar dados do pedido
        $orderModel = new Order($this->db);
        $order = $orderModel->readOne($orderId);
        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Pedido não encontrado.']);
            exit;
        }

        // Carregar itens
        $items = $orderModel->getItems($orderId);

        // Montar dados completos para o serviço
        $orderData = array_merge($order, [
            'items'              => $items,
            'customer_name'      => $order['customer_name'] ?? '',
            'customer_cpf_cnpj'  => $order['customer_cpf'] ?? $order['customer_cnpj'] ?? '',
            'customer_ie'        => $order['customer_ie'] ?? '',
            'customer_address'   => $order['customer_address'] ?? '',
            'customer_uf'        => $order['customer_state'] ?? 'RS',
            'valor_produtos'     => $order['total_amount'] ?? 0,
            'payment_method'     => $order['payment_method'] ?? '',
            'observation'        => $order['notes'] ?? '',
        ]);

        $nfeService = new NfeService($this->db);
        $result = $nfeService->emit($orderId, $orderData);

        echo json_encode($result);
        exit;
    }

    // ══════════════════════════════════════════════════════════════
    // Cancelamento
    // ══════════════════════════════════════════════════════════════

    /**
     * Cancela uma NF-e (AJAX).
     */
    public function cancel()
    {
        header('Content-Type: application/json');

        $nfeId = Input::post('nfe_id', 'int', 0);
        $motivo = Input::post('motivo', 'string', '');

        if ($nfeId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID da NF-e inválido.']);
            exit;
        }

        if (mb_strlen($motivo) < 15) {
            echo json_encode(['success' => false, 'message' => 'Justificativa deve ter no mínimo 15 caracteres.']);
            exit;
        }

        $nfeService = new NfeService($this->db);
        $result = $nfeService->cancel($nfeId, $motivo);

        echo json_encode($result);
        exit;
    }

    // ══════════════════════════════════════════════════════════════
    // Carta de Correção
    // ══════════════════════════════════════════════════════════════

    /**
     * Envia Carta de Correção (AJAX).
     */
    public function correction()
    {
        header('Content-Type: application/json');

        $nfeId = Input::post('nfe_id', 'int', 0);
        $texto = Input::post('texto', 'string', '');

        if ($nfeId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID da NF-e inválido.']);
            exit;
        }

        if (mb_strlen($texto) < 15) {
            echo json_encode(['success' => false, 'message' => 'Texto da correção deve ter no mínimo 15 caracteres.']);
            exit;
        }

        $nfeService = new NfeService($this->db);
        $result = $nfeService->correction($nfeId, $texto);

        echo json_encode($result);
        exit;
    }

    // ══════════════════════════════════════════════════════════════
    // Download (XML / DANFE)
    // ══════════════════════════════════════════════════════════════

    /**
     * Download do XML ou DANFE.
     */
    public function download()
    {
        $nfeId = Input::get('id', 'int', 0);
        $type = Input::get('type', 'string', 'xml');

        $doc = $this->docModel->readOne($nfeId);
        if (!$doc) {
            $_SESSION['flash_error'] = 'NF-e não encontrada.';
            header('Location: ?page=nfe_documents');
            exit;
        }

        if ($type === 'danfe') {
            $this->downloadDanfe($doc);
        } elseif ($type === 'xml_cancel') {
            $this->downloadXml($doc['xml_cancelamento'] ?? '', "cancelamento_nfe_{$doc['numero']}.xml");
        } elseif ($type === 'xml_correcao') {
            $this->downloadXml($doc['xml_correcao'] ?? '', "correcao_nfe_{$doc['numero']}.xml");
        } else {
            $xml = $doc['xml_autorizado'] ?: $doc['xml_envio'];
            $this->downloadXml($xml ?? '', "nfe_{$doc['numero']}.xml");
        }
    }

    private function downloadXml(string $xml, string $filename): void
    {
        if (empty($xml)) {
            $_SESSION['flash_error'] = 'XML não disponível.';
            header('Location: ?page=nfe_documents');
            exit;
        }

        header('Content-Type: application/xml');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($xml));
        echo $xml;
        exit;
    }

    private function downloadDanfe(array $doc): void
    {
        $xml = $doc['xml_autorizado'] ?? '';
        if (empty($xml)) {
            $_SESSION['flash_error'] = 'XML autorizado não disponível para gerar DANFE.';
            header('Location: ?page=nfe_documents');
            exit;
        }

        $pdf = NfePdfGenerator::renderToString($xml);
        if ($pdf === null) {
            $_SESSION['flash_error'] = 'Não foi possível gerar o DANFE. Verifique se a biblioteca sped-da está instalada.';
            header('Location: ?page=nfe_documents');
            exit;
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="DANFE_' . $doc['numero'] . '.pdf"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }

    // ══════════════════════════════════════════════════════════════
    // Reenviar / Consultar Status
    // ══════════════════════════════════════════════════════════════

    /**
     * Consulta status da NF-e na SEFAZ (AJAX).
     */
    public function checkStatus()
    {
        header('Content-Type: application/json');

        $nfeId = Input::get('id', 'int', 0) ?: Input::post('nfe_id', 'int', 0);
        if ($nfeId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido.']);
            exit;
        }

        $nfeService = new NfeService($this->db);
        $result = $nfeService->checkStatus($nfeId);

        echo json_encode($result);
        exit;
    }

    // ══════════════════════════════════════════════════════════════
    // Detalhe
    // ══════════════════════════════════════════════════════════════

    /**
     * Exibe detalhe completo de uma NF-e (timeline + logs + XMLs).
     */
    public function detail()
    {
        $nfeId = Input::get('id', 'int', 0);
        $doc = $this->docModel->readOne($nfeId);

        if (!$doc) {
            $_SESSION['flash_error'] = 'NF-e não encontrada.';
            header('Location: ?page=nfe_documents');
            exit;
        }

        $logs = $this->logModel->getByDocument($nfeId);

        // Carregar pedido vinculado (se houver)
        $order = null;
        if ($doc['order_id']) {
            $orderModel = new Order($this->db);
            $order = $orderModel->readOne($doc['order_id']);
        }

        require 'app/views/layout/header.php';
        require 'app/views/nfe/detail.php';
        require 'app/views/layout/footer.php';
    }
}
