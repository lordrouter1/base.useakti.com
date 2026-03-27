<?php
namespace Akti\Controllers;

use Akti\Models\NfeDocument;
use Akti\Models\NfeLog;
use Akti\Models\NfeCredential;
use Akti\Models\Order;
use Akti\Services\NfeService;
use Akti\Services\NfePdfGenerator;
use Akti\Services\NfeAuditService;
use Akti\Services\NfeQueueService;
use Akti\Services\NfeWebhookService;
use Akti\Services\NfeDistDFeService;
use Akti\Services\NfeManifestationService;
use Akti\Services\NfeDanfeCustomizer;
use Akti\Services\NfceXmlBuilder;
use Akti\Services\NfceDanfeGenerator;
use Akti\Services\NfeContingencyService;
use Akti\Services\NfeSpedFiscalService;
use Akti\Services\NfeSintegraService;
use Akti\Services\NfeBackupService;
use Akti\Models\NfeReceivedDocument;
use Akti\Models\NfeAuditLog;
use Akti\Models\NfeQueue;
use Akti\Models\NfeWebhook;
use Akti\Core\ModuleBootloader;
use Akti\Utils\Input;
use Akti\Utils\Validator;
use Akti\Middleware\RateLimitMiddleware;
use Akti\Models\User;
use Akti\Models\IbptaxModel;
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

        // Verificar permissão de visualização (nfe_documents)
        $this->checkPermission('nfe_documents');
    }

    /**
     * Verifica se o usuário tem permissão para acessar a página.
     * @param string $page Nome da página/módulo
     */
    private function checkPermission(string $page): void
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ?page=login');
            exit;
        }
        $userModel = new User($this->db);
        if (!$userModel->checkPermission($_SESSION['user_id'], $page)) {
            http_response_code(403);
            require 'app/views/layout/header.php';
            echo "<div class='container mt-5'><div class='alert alert-danger'><i class='fas fa-ban me-2'></i>Acesso Negado. Você não tem permissão para acessar o módulo de Notas Fiscais.</div></div>";
            require 'app/views/layout/footer.php';
            exit;
        }
    }

    /**
     * Verifica permissão de escrita (edit) para ações de emissão/cancelamento/correção.
     */
    private function checkWritePermission(): void
    {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Sessão expirada.']);
            exit;
        }
        $userModel = new User($this->db);
        // Verifica se o usuário tem permissão para a página — escrita requer a mesma permissão
        // O sistema usa permissão booleana por página; controle granular (view vs edit) pode ser
        // expandido futuramente. Aqui validamos que o user tem acesso ao módulo.
        if (!$userModel->checkPermission($_SESSION['user_id'], 'nfe_documents')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão para esta ação.']);
            exit;
        }
    }

    // ══════════════════════════════════════════════════════════════
    // Listagem
    // ══════════════════════════════════════════════════════════════

    /**
     * Verifica se a requisição é AJAX (fragmento para sidebar).
     */
    private function isAjaxFragment(): bool
    {
        return !empty($_GET['_ajax']) || (
            isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        );
    }

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
    // Detalhe da NF-e
    // ══════════════════════════════════════════════════════════════

    /**
     * Exibe detalhe completo de uma NF-e com dados financeiros e IBPTax.
     */
    public function detail()
    {
        $id = Input::get('id', 'int', 0);
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'ID da NF-e inválido.';
            header('Location: ?page=nfe_documents');
            exit;
        }

        $doc = $this->docModel->readOne($id);
        if (!$doc) {
            $_SESSION['flash_error'] = 'NF-e não encontrada.';
            header('Location: ?page=nfe_documents');
            exit;
        }

        // Logs SEFAZ
        $logs = $this->logModel->getByDocument($id);

        // Pedido vinculado
        $order = null;
        if (!empty($doc['order_id'])) {
            $orderModel = new Order($this->db);
            $order = $orderModel->readOne($doc['order_id']);
        }

        // ─── Integração Financeira: carregar parcelas do pedido ───
        $installments = [];
        $installmentSummary = [
            'total'         => 0,
            'pagas'         => 0,
            'pendentes'     => 0,
            'valor_pago'    => 0.00,
            'valor_total'   => 0.00,
            'faturadas'     => 0,
        ];
        if (!empty($doc['order_id'])) {
            try {
                $installmentModel = new \Akti\Models\Installment($this->db);
                $installments = $installmentModel->getByOrderId($doc['order_id']);
                foreach ($installments as $inst) {
                    $installmentSummary['total']++;
                    $installmentSummary['valor_total'] += (float) ($inst['amount'] ?? 0);
                    if ($inst['status'] === 'pago') {
                        $installmentSummary['pagas']++;
                        $installmentSummary['valor_pago'] += (float) ($inst['paid_amount'] ?? $inst['amount'] ?? 0);
                    } elseif (in_array($inst['status'], ['pendente', 'atrasado'])) {
                        $installmentSummary['pendentes']++;
                    }
                    // Parcela marcada como faturada (campo nfe_status ou billing_status)
                    if (!empty($inst['nfe_status']) && $inst['nfe_status'] === 'faturada') {
                        $installmentSummary['faturadas']++;
                    }
                }
            } catch (\Throwable $e) {
                // Modelo não disponível — seguir sem parcelas
            }
        }

        // ─── IBPTax: valor de tributos aproximados ───
        $valorTributosAprox = (float) ($doc['valor_tributos_aprox'] ?? 0);
        $ibptaxFonte = '';
        if ($valorTributosAprox <= 0 && !empty($doc['order_id'])) {
            // Tentar recalcular a partir dos itens do documento
            try {
                $ibptaxModel = new IbptaxModel($this->db);
                $stmtItems = $this->db->prepare(
                    "SELECT ncm, v_prod AS valor_total, origem FROM nfe_document_items WHERE nfe_document_id = :nfe_id"
                );
                $stmtItems->execute([':nfe_id' => $id]);
                $nfeItems = $stmtItems->fetchAll(\PDO::FETCH_ASSOC);

                $totalTrib = 0.00;
                foreach ($nfeItems as $item) {
                    $calc = $ibptaxModel->calculateTaxApprox(
                        $item['ncm'] ?? '',
                        (float) ($item['valor_total'] ?? 0),
                        (string) ($item['origem'] ?? '0')
                    );
                    $totalTrib += $calc['vTotTrib'];
                    if (empty($ibptaxFonte) && !empty($calc['fonte'])) {
                        $ibptaxFonte = $calc['fonte'];
                    }
                }
                if ($totalTrib > 0) {
                    $valorTributosAprox = $totalTrib;
                }
            } catch (\Throwable $e) {
                // IBPTax não disponível
            }
        }

        require 'app/views/layout/header.php';
        require 'app/views/nfe/detail.php';
        require 'app/views/layout/footer.php';
    }

    // ══════════════════════════════════════════════════════════════
    // Emissão de NF-e
    // ══════════════════════════════════════════════════════════════

    /**
     * Emite NF-e para um pedido (AJAX/JSON).
     * POST: order_id
     */
    public function emit()
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->checkWritePermission();

        // Rate limiting — proteção contra burst de emissões (FASE4-01)
        $rateCheck = RateLimitMiddleware::check('nfe_emit', 5);
        if (!$rateCheck['allowed']) {
            echo json_encode([
                'success' => false,
                'message' => "Aguarde {$rateCheck['retry_after']} segundo(s) entre emissões.",
            ]);
            exit;
        }

        $orderId = Input::post('order_id', 'int', 0);
        if ($orderId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID do pedido inválido.']);
            exit;
        }

        try {
            $orderModel = new Order($this->db);
            $order = $orderModel->readOne($orderId);
            if (!$order) {
                echo json_encode(['success' => false, 'message' => "Pedido #{$orderId} não encontrado."]);
                exit;
            }

            // Verificar se já existe NF-e autorizada para o pedido
            $existingNfe = $this->docModel->readByOrder($orderId);
            if ($existingNfe && $existingNfe['status'] === 'autorizada') {
                echo json_encode(['success' => false, 'message' => "Pedido #{$orderId} já possui NF-e autorizada (Nº {$existingNfe['numero']})."]);
                exit;
            }

            // Carregar itens do pedido
            $items = $orderModel->getItems($orderId);
            if (empty($items)) {
                echo json_encode(['success' => false, 'message' => 'Pedido sem itens. Não é possível emitir NF-e.']);
                exit;
            }

            // Enriquecer itens com dados fiscais dos produtos
            $productModel = new \Akti\Models\Product($this->db);
            foreach ($items as &$it) {
                if (!empty($it['product_id'])) {
                    $product = $productModel->readOne($it['product_id']);
                    if ($product) {
                        $it['fiscal_ncm']                 = $product['fiscal_ncm'] ?? $product['ncm'] ?? '';
                        $it['fiscal_cest']                = $product['fiscal_cest'] ?? $product['cest'] ?? '';
                        $it['fiscal_cfop_interna']        = $product['fiscal_cfop_venda_interna'] ?? $product['cfop_venda_interna'] ?? '';
                        $it['fiscal_cfop_interestadual']  = $product['fiscal_cfop_venda_interestadual'] ?? $product['cfop_venda_interestadual'] ?? '';
                        $it['fiscal_icms_cst']            = $product['fiscal_icms_cst'] ?? $product['icms_cst'] ?? '';
                        $it['fiscal_icms_aliquota']       = $product['fiscal_icms_aliquota'] ?? $product['icms_aliquota'] ?? 0;
                        $it['fiscal_pis_cst']             = $product['fiscal_pis_cst'] ?? $product['pis_cst'] ?? '';
                        $it['fiscal_cofins_cst']          = $product['fiscal_cofins_cst'] ?? $product['cofins_cst'] ?? '';
                        $it['fiscal_ipi_cst']             = $product['fiscal_ipi_cst'] ?? $product['ipi_cst'] ?? '';
                        $it['fiscal_ipi_aliquota']        = $product['fiscal_ipi_aliquota'] ?? $product['ipi_aliquota'] ?? 0;
                        $it['fiscal_origem']              = $product['fiscal_origem'] ?? $product['origem'] ?? 0;
                        $it['fiscal_ean']                 = $product['fiscal_ean'] ?? $product['ean'] ?? '';
                        $it['fiscal_unidade']             = $product['fiscal_unidade'] ?? $product['unidade'] ?? 'UN';
                    }
                }
            }
            unset($it);

            // Carregar dados do cliente
            $customer = null;
            if (!empty($order['customer_id'])) {
                $customerModel = new \Akti\Models\Customer($this->db);
                $customer = $customerModel->readOne($order['customer_id']);
            }

            // Parcelas financeiras
            $installments = [];
            try {
                $installmentModel = new \Akti\Models\Installment($this->db);
                $installments = $installmentModel->getByOrderId($orderId);
            } catch (\Throwable $e) {
                // sem parcelas
            }

            $orderData = array_merge($order, [
                'items'                   => $items,
                'customer_name'           => $customer['name'] ?? $order['customer_name'] ?? '',
                'customer_cpf_cnpj'       => $customer['document'] ?? $order['customer_document'] ?? '',
                'customer_ie'             => $customer['ie'] ?? $order['customer_ie'] ?? '',
                'customer_address'        => $customer['address'] ?? $order['customer_address'] ?? '',
                'customer_number'         => $customer['address_number'] ?? $order['customer_number'] ?? 'S/N',
                'customer_bairro'         => $customer['bairro'] ?? $customer['neighborhood'] ?? '',
                'customer_cep'            => $customer['cep'] ?? $customer['zipcode'] ?? '',
                'customer_municipio'      => $customer['city'] ?? $customer['municipio'] ?? '',
                'customer_cod_municipio'  => $customer['cod_municipio'] ?? '',
                'customer_uf'             => $customer['state'] ?? $customer['uf'] ?? $order['customer_state'] ?? 'RS',
                'valor_produtos'          => $order['total_amount'] ?? 0,
                'shipping_cost'           => $order['shipping_cost'] ?? $order['frete'] ?? 0,
                'installments'            => $installments,
            ]);

            $nfeService = new NfeService($this->db);
            $result = $nfeService->emit($orderId, $orderData);

            // Auditoria
            if ($result['success']) {
                $this->getAuditService()->logEmit(
                    $result['nfe_id'] ?? 0,
                    $orderId,
                    $result['chave'] ?? ''
                );

                // Webhook
                $this->dispatchWebhook('nfe.authorized', [
                    'nfe_id'   => $result['nfe_id'] ?? null,
                    'order_id' => $orderId,
                    'chave'    => $result['chave'] ?? '',
                ]);
            } else {
                // Webhook de erro/rejeição
                $this->dispatchWebhook('nfe.rejected', [
                    'nfe_id'   => $result['nfe_id'] ?? null,
                    'order_id' => $orderId,
                    'message'  => $result['message'] ?? '',
                ]);
            }

            echo json_encode($result);
        } catch (\Throwable $e) {
            error_log('[NfeDocumentController] emit error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        }
        exit;
    }

    // ══════════════════════════════════════════════════════════════
    // Cancelamento de NF-e
    // ══════════════════════════════════════════════════════════════

    /**
     * Cancela uma NF-e autorizada (AJAX/JSON).
     * POST: nfe_id, motivo
     */
    public function cancel()
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->checkWritePermission();

        $nfeId = Input::post('nfe_id', 'int', 0);
        $motivo = Input::post('motivo', 'string', '');

        if ($nfeId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID da NF-e inválido.']);
            exit;
        }
        if (strlen(trim($motivo)) < 15) {
            echo json_encode(['success' => false, 'message' => 'Justificativa deve ter no mínimo 15 caracteres.']);
            exit;
        }

        try {
            $nfeService = new NfeService($this->db);
            $result = $nfeService->cancel($nfeId, $motivo);

            // Auditoria
            if ($result['success']) {
                $this->getAuditService()->logCancel($nfeId, $motivo);

                // Webhook
                $doc = $this->docModel->readOne($nfeId);
                $this->dispatchWebhook('nfe.cancelled', [
                    'nfe_id'   => $nfeId,
                    'order_id' => $doc['order_id'] ?? null,
                    'chave'    => $doc['chave'] ?? '',
                    'motivo'   => $motivo,
                ]);
            }

            echo json_encode($result);
        } catch (\Throwable $e) {
            error_log('[NfeDocumentController] cancel error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        }
        exit;
    }

    // ══════════════════════════════════════════════════════════════
    // Carta de Correção (CC-e)
    // ══════════════════════════════════════════════════════════════

    /**
     * Envia Carta de Correção para NF-e autorizada (AJAX/JSON).
     * POST: nfe_id, texto
     */
    public function correction()
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->checkWritePermission();

        $nfeId = Input::post('nfe_id', 'int', 0);
        $texto = Input::post('texto', 'string', '');

        if ($nfeId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID da NF-e inválido.']);
            exit;
        }
        if (strlen(trim($texto)) < 15) {
            echo json_encode(['success' => false, 'message' => 'Texto da correção deve ter no mínimo 15 caracteres.']);
            exit;
        }

        try {
            $nfeService = new NfeService($this->db);
            $result = $nfeService->correction($nfeId, $texto);

            // Auditoria
            if ($result['success']) {
                $doc = $this->docModel->readOne($nfeId);
                $this->getAuditService()->logCorrection($nfeId, (int) ($doc['correcao_seq'] ?? 1), $texto);

                // Webhook
                $this->dispatchWebhook('nfe.corrected', [
                    'nfe_id'   => $nfeId,
                    'order_id' => $doc['order_id'] ?? null,
                    'chave'    => $doc['chave'] ?? '',
                    'seq'      => $doc['correcao_seq'] ?? 1,
                    'texto'    => $texto,
                ]);
            }

            echo json_encode($result);
        } catch (\Throwable $e) {
            error_log('[NfeDocumentController] correction error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        }
        exit;
    }

    // ══════════════════════════════════════════════════════════════
    // Download XML / DANFE
    // ══════════════════════════════════════════════════════════════

    /**
     * Download de XML ou DANFE de uma NF-e.
     * GET: id, type (xml|danfe|cancel_xml|cce_xml)
     */
    public function download()
    {
        $id = Input::get('id', 'int', 0);
        $type = Input::get('type', 'string', 'xml');

        if ($id <= 0) {
            $_SESSION['flash_error'] = 'ID da NF-e inválido.';
            header('Location: ?page=nfe_documents');
            exit;
        }

        $doc = $this->docModel->readOne($id);
        if (!$doc) {
            $_SESSION['flash_error'] = 'NF-e não encontrada.';
            header('Location: ?page=nfe_documents');
            exit;
        }

        $chave = $doc['chave'] ?? $doc['numero'];

        switch ($type) {
            case 'xml':
                $xml = $doc['xml_autorizado'] ?? $doc['xml_envio'] ?? '';
                if (empty($xml)) {
                    // Tentar ler do disco
                    if (!empty($doc['xml_path'])) {
                        $storage = new \Akti\Services\NfeStorageService();
                        $xml = $storage->readFile($doc['xml_path']);
                    }
                }
                if (empty($xml)) {
                    $_SESSION['flash_error'] = 'XML não disponível para esta NF-e.';
                    header('Location: ?page=nfe_documents&action=detail&id=' . $id);
                    exit;
                }

                $this->getAuditService()->logDownloadXml($id);

                header('Content-Type: application/xml; charset=utf-8');
                header('Content-Disposition: attachment; filename="NFe_' . $chave . '.xml"');
                header('Content-Length: ' . strlen($xml));
                echo $xml;
                exit;

            case 'danfe':
                $xmlAutorizado = $doc['xml_autorizado'] ?? '';
                if (empty($xmlAutorizado)) {
                    $_SESSION['flash_error'] = 'XML autorizado não disponível. DANFE não pode ser gerado.';
                    header('Location: ?page=nfe_documents&action=detail&id=' . $id);
                    exit;
                }

                // Gerar DANFE via customizer (com logo e rodapé personalizados)
                $customizer = new NfeDanfeCustomizer($this->db);
                $pdf = $customizer->generate($xmlAutorizado);

                if ($pdf === null) {
                    $_SESSION['flash_error'] = 'Não foi possível gerar o DANFE. Biblioteca sped-da pode não estar instalada.';
                    header('Location: ?page=nfe_documents&action=detail&id=' . $id);
                    exit;
                }

                $this->getAuditService()->logDownloadDanfe($id);

                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="DANFE_' . $chave . '.pdf"');
                header('Content-Length: ' . strlen($pdf));
                echo $pdf;
                exit;

            case 'cancel_xml':
                $xml = $doc['xml_cancelamento'] ?? '';
                if (empty($xml)) {
                    $_SESSION['flash_error'] = 'XML de cancelamento não disponível.';
                    header('Location: ?page=nfe_documents&action=detail&id=' . $id);
                    exit;
                }

                header('Content-Type: application/xml; charset=utf-8');
                header('Content-Disposition: attachment; filename="Cancel_' . $chave . '.xml"');
                header('Content-Length: ' . strlen($xml));
                echo $xml;
                exit;

            case 'cce_xml':
                $xml = $doc['xml_correcao'] ?? '';
                if (empty($xml)) {
                    $_SESSION['flash_error'] = 'XML de correção não disponível.';
                    header('Location: ?page=nfe_documents&action=detail&id=' . $id);
                    exit;
                }

                header('Content-Type: application/xml; charset=utf-8');
                header('Content-Disposition: attachment; filename="CCe_' . $chave . '.xml"');
                header('Content-Length: ' . strlen($xml));
                echo $xml;
                exit;

            default:
                $_SESSION['flash_error'] = 'Tipo de download inválido.';
                header('Location: ?page=nfe_documents&action=detail&id=' . $id);
                exit;
        }
    }

    // ══════════════════════════════════════════════════════════════
    // Consulta de Status na SEFAZ
    // ══════════════════════════════════════════════════════════════

    /**
     * Consulta o status de uma NF-e na SEFAZ (AJAX/JSON).
     * GET/POST: id
     */
    public function checkStatus()
    {
        header('Content-Type: application/json; charset=utf-8');

        $id = Input::get('id', 'int', 0);
        if ($id <= 0) {
            $id = Input::post('id', 'int', 0);
        }

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID da NF-e inválido.', 'details' => []]);
            exit;
        }

        try {
            $nfeService = new NfeService($this->db);
            $result = $nfeService->checkStatus($id);
            echo json_encode($result);
        } catch (\Throwable $e) {
            error_log('[NfeDocumentController] checkStatus error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage(), 'details' => []]);
        }
        exit;
    }

    // ══════════════════════════════════════════════════════════════
    // Dashboard Fiscal
    // ══════════════════════════════════════════════════════════════

    /**
     * Dashboard fiscal com KPIs, gráficos e alertas.
     */
    public function dashboard()
    {
        // Auditoria
        $this->getAuditService()->record('view', 'nfe_dashboard', null, 'Acessou Dashboard Fiscal');

        $reportModel = new \Akti\Models\NfeReportModel($this->db);

        $startDate = Input::get('start_date', 'string', date('Y-01-01'));
        $endDate   = Input::get('end_date', 'string', date('Y-m-d'));

        // KPIs acumulados (período selecionado)
        $kpis       = $reportModel->getFiscalKpis($startDate, $endDate);

        // KPIs do mês atual
        $kpisMonth  = $reportModel->getFiscalKpis(date('Y-m-01'), date('Y-m-d'));

        // Dados para gráficos
        $nfesByMonth = $reportModel->getNfesByMonth(12);
        $statusDist  = $reportModel->getStatusDistribution();

        // Tabelas top
        $topCfops      = $reportModel->getCfopSummary($startDate, $endDate);
        $topCustomers  = $reportModel->getTopCustomers(10);

        // Totais de impostos (12 meses)
        $totalTaxes = $reportModel->getTotalTaxes12Months();

        // Taxa de rejeição
        $totalEmitidas = (int)($kpisMonth['total_emitidas'] ?? 0);
        $rejeitadas    = (int)($kpisMonth['rejeitadas'] ?? 0);
        $taxaRejeicao  = $totalEmitidas > 0 ? round(($rejeitadas / $totalEmitidas) * 100, 1) : 0;

        // Labels e cores por status
        $statusLabels = \Akti\Models\NfeReportModel::getNfeStatusLabels();
        $statusColors = [
            'autorizada'  => '#28a745',
            'cancelada'   => '#343a40',
            'rejeitada'   => '#dc3545',
            'processando' => '#ffc107',
            'rascunho'    => '#6c757d',
            'inutilizada' => '#17a2b8',
            'corrigida'   => '#6f42c1',
            'denegada'    => '#e83e8c',
        ];

        // Alertas
        $alerts = [];
        try {
            $alerts = $reportModel->getFiscalAlerts();
        } catch (\Throwable $e) {}
        // Alerta de certificado (complementar)
        $credModel = new NfeCredential($this->db);
        $credentials = $credModel->get();
        if (!empty($credentials['certificate_expiry'])) {
            $expiryDate = new \DateTime($credentials['certificate_expiry']);
            $now = new \DateTime();
            $diff = $now->diff($expiryDate);
            if ($expiryDate < $now) {
                $alerts[] = ['severity' => 'danger', 'title' => 'Certificado Expirado', 'message' => 'Certificado digital EXPIRADO!'];
            } elseif ($diff->days <= 30) {
                $alerts[] = ['severity' => 'warning', 'title' => 'Certificado Expirando', 'message' => "Certificado expira em {$diff->days} dias."];
            }
        }

        // Contadores da fila e docs recebidos (Fase 5)
        $queueCounts = [];
        $receivedPendingCount = 0;
        try {
            $queueModel = new NfeQueue($this->db);
            $queueCounts = $queueModel->countByStatus();
        } catch (\Throwable $e) {}
        try {
            $receivedModel = new NfeReceivedDocument($this->db);
            $receivedCounts = $receivedModel->countByManifestationStatus();
            $receivedPendingCount = (int) ($receivedCounts['pendente'] ?? 0);
        } catch (\Throwable $e) {}

        // Renderizar resposta
        if ($this->isAjaxFragment()) {
            $isAjax = true;
            require 'app/views/nfe/dashboard.php';
            return;
        }

        require 'app/views/layout/header.php';
        require 'app/views/nfe/dashboard.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Renderiza o fragmento da dashboard para requisições AJAX (LEGACY - mantido para compat).
     */
    private function renderDashboardFragment(array $data)
    {
        extract($data);

        // Renderizar KPIs
        foreach ($kpis as $kpi) {
            echo "<div class='kpi-item'>";
            echo "<div class='kpi-title'>{$kpi['title']}</div>";
            echo "<div class='kpi-value'>{$kpi['value']}</div>";
            echo "</div>";
        }

        // Renderizar gráfico de NFes por mês
        echo "<div id='chartNfesByMonth'></div>";
        echo "<script>
            var ctx = document.getElementById('chartNfesByMonth').getContext('2d');
            var chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: " . json_encode(array_column($nfesByMonth, 'month')) . ",
                    datasets: [{
                        label: 'NFes Emitidas',
                        data: " . json_encode(array_column($nfesByMonth, 'total')) . ",
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.2)',
                        fill: true,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                }
            });
        </script>";

        // Renderizar tabela de top CFOPs
        echo "<h3>Top CFOPs</h3>";
        echo "<table class='table table-striped'>";
        echo "<thead><tr><th>CFOP</th><th>Total</th></tr></thead>";
        echo "<tbody>";
        foreach ($topCfops as $cfop) {
            echo "<tr><td>{$cfop['cfop']}</td><td>{$cfop['total']}</td></tr>";
        }
        echo "</tbody></table>";

        // Renderizar tabela de top clientes
        echo "<h3>Top Clientes</h3>";
        echo "<table class='table table-striped'>";
        echo "<thead><tr><th>Cliente</th><th>Total NFes</th></tr></thead>";
        echo "<tbody>";
        foreach ($topCustomers as $customer) {
            echo "<tr><td>{$customer['nome']}</td><td>{$customer['total_nfes']}</td></tr>";
        }
        echo "</tbody></table>";

        // Renderizar totais de impostos
        echo "<h3>Totais de Impostos (Últimos 12 Meses)</h3>";
        echo "<div id='chartTotalTaxes'></div>";
        echo "<script>
            var ctx = document.getElementById('chartTotalTaxes').getContext('2d');
            var chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: " . json_encode(array_column($totalTaxes, 'mes')) . ",
                    datasets: [{
                        label: 'Total de Impostos',
                        data: " . json_encode(array_column($totalTaxes, 'total')) . ",
                        backgroundColor: '#28a745',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                }
            });
        </script>";

        // Renderizar alertas
        if (!empty($alerts)) {
            echo "<div class='alert alert-warning'>";
            foreach ($alerts as $alert) {
                echo "<div><strong>{$alert['title']}:</strong> {$alert['message']}</div>";
            }
            echo "</div>";
        }
    }

    // ══════════════════════════════════════════════════════════════
    // FASE 4 — Relatório de Cartas de Correção (CC-e)
    // ══════════════════════════════════════════════════════════════

    /**
     * Exibe relatório de Cartas de Correção (CC-e) com filtro por período.
     * FASE4-02
     */
    public function correctionReport()
    {
        $this->getAuditService()->record('view', 'nfe_correction_report', null, 'Acessou Relatório de CC-e');

        $reportModel = new \Akti\Models\NfeReportModel($this->db);

        $startDate = Input::get('start_date', 'string', date('Y-m-01'));
        $endDate   = Input::get('end_date', 'string', date('Y-m-d'));

        // Histórico de CC-e
        $corrections = $reportModel->getCorrectionHistory($startDate, $endDate);

        // CC-e por mês (gráfico)
        $correctionsByMonth = $reportModel->getCorrectionsByMonth(12);

        // Totais
        $totalCorrections = count($corrections);
        $totalNfes = count(array_unique(array_column($corrections, 'nfe_document_id')));

        // AJAX fragment
        if ($this->isAjaxFragment()) {
            $isAjax = true;
            require 'app/views/nfe/correction_report.php';
            return;
        }

        require 'app/views/layout/header.php';
        require 'app/views/nfe/correction_report.php';
        require 'app/views/layout/footer.php';
    }

    // ══════════════════════════════════════════════════════════════
    // FASE 4 — Exportação de Relatórios em Excel
    // ══════════════════════════════════════════════════════════════

    /**
     * Exporta dados de relatório NF-e para Excel (.xlsx).
     * GET params: type (nfes|taxes|cfop|cancelled|corrections), start_date, end_date
     * FASE4-03
     */
    public function exportReport()
    {
        $this->checkWritePermission();

        $type      = Input::get('type', 'string', 'nfes');
        $startDate = Input::get('start_date', 'string', date('Y-01-01'));
        $endDate   = Input::get('end_date', 'string', date('Y-m-d'));

        $reportModel = new \Akti\Models\NfeReportModel($this->db);

        // Determinar dados e título
        switch ($type) {
            case 'nfes':
                $data  = $reportModel->getNfesByPeriod($startDate, $endDate);
                $title = "NFe_Emitidas_{$startDate}_a_{$endDate}";
                break;

            case 'taxes':
                $taxSummary = $reportModel->getTaxSummary($startDate, $endDate);
                $data  = $taxSummary['items'] ?? [];
                $title = "Resumo_Impostos_{$startDate}_a_{$endDate}";
                break;

            case 'cfop':
                $data  = $reportModel->getCfopSummary($startDate, $endDate);
                $title = "CFOPs_{$startDate}_a_{$endDate}";
                break;

            case 'cancelled':
                $data  = $reportModel->getCancelledNfes($startDate, $endDate);
                $title = "NFe_Canceladas_{$startDate}_a_{$endDate}";
                break;

            case 'corrections':
                $data  = $reportModel->getCorrectionHistory($startDate, $endDate);
                $title = "Cartas_Correcao_{$startDate}_a_{$endDate}";
                break;

            default:
                $_SESSION['flash_error'] = 'Tipo de relatório inválido.';
                header('Location: ?page=nfe_documents&action=dashboard');
                exit;
        }

        if (empty($data)) {
            $_SESSION['flash_error'] = 'Nenhum dado encontrado para exportar no período selecionado.';
            header('Location: ?page=nfe_documents&action=dashboard&start_date=' . urlencode($startDate) . '&end_date=' . urlencode($endDate));
            exit;
        }

        // Auditoria
        $this->getAuditService()->record('export_report', 'nfe_report', null, "Exportou relatório '{$type}' ({$startDate} a {$endDate})", [
            'type'       => $type,
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'rows'       => count($data),
        ]);

        // Exportar via NfeExportService
        try {
            $exportService = new \Akti\Services\NfeExportService();
            $exportService->exportToExcel($data, $title);
        } catch (\Throwable $e) {
            error_log('[NfeDocumentController] exportReport error: ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Erro ao exportar: ' . $e->getMessage();
            header('Location: ?page=nfe_documents&action=dashboard');
            exit;
        }
    }

    // ══════════════════════════════════════════════════════════════
    // FASE 5 — Emissão em Lote
    // ══════════════════════════════════════════════════════════════

    /**
     * Enfileira múltiplos pedidos para emissão em lote (AJAX).
     */
    public function batchEmit()
    {
        header('Content-Type: application/json');
        $this->checkWritePermission();

        $orderIdsRaw = Input::post('order_ids', 'string', '');
        if (empty($orderIdsRaw)) {
            echo json_encode(['success' => false, 'message' => 'Nenhum pedido selecionado.']);
            exit;
        }

        $orderIds = array_filter(array_map('intval', explode(',', $orderIdsRaw)));
        if (empty($orderIds)) {
            echo json_encode(['success' => false, 'message' => 'IDs de pedidos inválidos.']);
            exit;
        }

        $queueService = new NfeQueueService($this->db);
        $result = $queueService->enqueueBatch($orderIds);

        // Auditoria
        if ($result['success']) {
            $this->getAuditService()->logBatchEmit(count($orderIds), $result['batch_id']);

            // Webhook
            $this->dispatchWebhook('nfe.batch_enqueued', [
                'batch_id'  => $result['batch_id'],
                'order_ids' => $orderIds,
                'enqueued'  => $result['enqueued'],
            ]);
        }

        echo json_encode($result);
        exit;
    }

    // ══════════════════════════════════════════════════════════════
    // FASE 5 — Fila de Emissão Assíncrona
    // ══════════════════════════════════════════════════════════════

    /**
     * Listagem da fila de emissão.
     */
    public function queue()
    {
        $page = Input::get('pg', 'int', 1);
        $perPage = 20;
        $filters = [];

        if (Input::hasGet('status'))   $filters['status']   = Input::get('status');
        if (Input::hasGet('batch_id')) $filters['batch_id'] = Input::get('batch_id');

        $queueModel = new NfeQueue($this->db);
        $result = $queueModel->readPaginated($filters, $page, $perPage);
        $queueItems = $result['data'];
        $totalItems = $result['total'];
        $totalPages = ceil($totalItems / $perPage);
        $ctPage = $page;
        $statusCounts = $queueModel->countByStatus();

        // Tracking de lote — lista de batches para filtro
        $batches = $queueModel->listBatches(30);
        $batchFilter = $filters['batch_id'] ?? '';

        $baseUrl = '?page=nfe_documents&action=queue'
            . (!empty($filters['status']) ? '&status=' . urlencode($filters['status']) : '')
            . (!empty($filters['batch_id']) ? '&batch_id=' . urlencode($filters['batch_id']) : '');

        if ($this->isAjaxFragment()) {
            $isAjax = true;
            require 'app/views/nfe/queue.php';
            return;
        }

        require 'app/views/layout/header.php';
        require 'app/views/nfe/queue.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Processa próximo item da fila (AJAX — pode ser usado por cron).
     */
    public function processQueue()
    {
        header('Content-Type: application/json');
        $this->checkWritePermission();

        $max = Input::get('max', 'int', 5);
        $queueService = new NfeQueueService($this->db);
        $result = $queueService->processMultiple(min($max, 20));

        echo json_encode(['success' => true, 'result' => $result]);
        exit;
    }

    /**
     * Cancela item da fila (AJAX).
     */
    public function cancelQueue()
    {
        header('Content-Type: application/json');
        $this->checkWritePermission();

        $id = Input::post('id', 'int', 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido.']);
            exit;
        }

        $queueModel = new NfeQueue($this->db);
        $ok = $queueModel->cancel($id);

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Item removido da fila.' : 'Não foi possível cancelar (status não permite).',
        ]);
        exit;
    }

    // ══════════════════════════════════════════════════════════════
    // FASE 5 — Documentos Recebidos (DistDFe)
    // ══════════════════════════════════════════════════════════════

    /**
     * Listagem de documentos recebidos via DistDFe.
     */
    public function received()
    {
        $page = Input::get('pg', 'int', 1);
        $perPage = 20;
        $filters = [];

        if (Input::hasGet('status'))     $filters['status']     = Input::get('status');
        if (Input::hasGet('search'))     $filters['search']     = Input::get('search');
        if (Input::hasGet('date_start')) $filters['date_start'] = Input::get('date_start');
        if (Input::hasGet('date_end'))   $filters['date_end']   = Input::get('date_end');

        $receivedModel = new NfeReceivedDocument($this->db);
        $result = $receivedModel->readPaginated($filters, $page, $perPage);
        $receivedDocs = $result['data'];
        $totalItems = $result['total'];
        $totalPages = ceil($totalItems / $perPage);
        $ctPage = $page;
        $statusCounts = $receivedModel->countByManifestationStatus();

        $baseUrl = '?page=nfe_documents&action=received'
            . (!empty($filters['status']) ? '&status=' . urlencode($filters['status']) : '')
            . (!empty($filters['search']) ? '&search=' . urlencode($filters['search']) : '');

        // Verificar se DistDFe está disponível
        $distdfeService = new NfeDistDFeService($this->db);
        $distdfeAvailable = $distdfeService->isAvailable();

        if ($this->isAjaxFragment()) {
            $isAjax = true;
            require 'app/views/nfe/received.php';
            return;
        }

        require 'app/views/layout/header.php';
        require 'app/views/nfe/received.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Consulta DistDFe na SEFAZ (AJAX).
     */
    public function queryDistDFe()
    {
        header('Content-Type: application/json');
        $this->checkWritePermission();

        $distdfeService = new NfeDistDFeService($this->db);
        $result = $distdfeService->queryByNSU();

        // Auditoria
        $this->getAuditService()->logDistDFe($result['total'] ?? 0);

        echo json_encode($result);
        exit;
    }

    /**
     * Consulta DistDFe por chave de acesso (AJAX).
     */
    public function queryDistDFeByChave()
    {
        header('Content-Type: application/json');
        $this->checkWritePermission();

        $chave = Input::post('chave', 'string', '');
        $chave = preg_replace('/\D/', '', $chave);

        if (strlen($chave) !== 44) {
            echo json_encode(['success' => false, 'message' => 'Chave de acesso deve ter 44 dígitos.']);
            exit;
        }

        $distdfeService = new NfeDistDFeService($this->db);
        $result = $distdfeService->queryByChave($chave);

        echo json_encode($result);
        exit;
    }

    // ══════════════════════════════════════════════════════════════
    // FASE 5 — Manifestação do Destinatário
    // ══════════════════════════════════════════════════════════════

    /**
     * Envia manifestação do destinatário (AJAX).
     */
    public function manifest()
    {
        header('Content-Type: application/json');
        $this->checkWritePermission();

        $docId = Input::post('doc_id', 'int', 0);
        $type = Input::post('type', 'string', '');
        $justificativa = Input::post('justificativa', 'string', '');

        if ($docId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID do documento inválido.']);
            exit;
        }
        if (empty($type)) {
            echo json_encode(['success' => false, 'message' => 'Tipo de manifestação obrigatório.']);
            exit;
        }

        $manifestService = new NfeManifestationService($this->db);
        $result = $manifestService->manifest($docId, $type, $justificativa);

        // Auditoria
        if ($result['success']) {
            $receivedModel = new NfeReceivedDocument($this->db);
            $doc = $receivedModel->readOne($docId);
            $this->getAuditService()->logManifestation($docId, $type, $doc['chave'] ?? '');

            // Webhook
            $this->dispatchWebhook('nfe.manifestation', [
                'doc_id' => $docId,
                'type'   => $type,
                'chave'  => $doc['chave'] ?? '',
            ]);
        }

        echo json_encode($result);
        exit;
    }

    // ══════════════════════════════════════════════════════════════
    // FASE 5 — Auditoria de Acessos
    // ══════════════════════════════════════════════════════════════

    /**
     * Listagem de auditoria do módulo NF-e.
     */
    public function audit()
    {
        $page = Input::get('pg', 'int', 1);
        $perPage = 50;
        $filters = [];

        if (Input::hasGet('action_filter')) $filters['action']      = Input::get('action_filter');
        if (Input::hasGet('entity_type'))   $filters['entity_type'] = Input::get('entity_type');
        if (Input::hasGet('user_id'))       $filters['user_id']     = Input::get('user_id', 'int');
        if (Input::hasGet('date_start'))    $filters['date_start']  = Input::get('date_start');
        if (Input::hasGet('date_end'))      $filters['date_end']    = Input::get('date_end');
        if (Input::hasGet('search'))        $filters['search']      = Input::get('search');

        $auditModel = new NfeAuditLog($this->db);
        $result = $auditModel->readPaginated($filters, $page, $perPage);
        $auditLogs = $result['data'];
        $totalItems = $result['total'];
        $totalPages = ceil($totalItems / $perPage);
        $ctPage = $page;

        $distinctActions = $auditModel->getDistinctActions();
        $actionCounts = $auditModel->countByAction(
            $filters['date_start'] ?? null,
            $filters['date_end'] ?? null
        );

        $baseUrl = '?page=nfe_documents&action=audit'
            . (!empty($filters['action']) ? '&action_filter=' . urlencode($filters['action']) : '')
            . (!empty($filters['search']) ? '&search=' . urlencode($filters['search']) : '');

        if ($this->isAjaxFragment()) {
            $isAjax = true;
            require 'app/views/nfe/audit.php';
            return;
        }

        require 'app/views/layout/header.php';
        require 'app/views/nfe/audit.php';
        require 'app/views/layout/footer.php';
    }

    // ══════════════════════════════════════════════════════════════
    // FASE 5 — Webhooks
    // ══════════════════════════════════════════════════════════════

    /**
     * Listagem e gerenciamento de webhooks.
     */
    public function webhooks()
    {
        $webhookModel = new NfeWebhook($this->db);
        $webhooksList = $webhookModel->readAll();

        if ($this->isAjaxFragment()) {
            $isAjax = true;
            require 'app/views/nfe/webhooks.php';
            return;
        }

        require 'app/views/layout/header.php';
        require 'app/views/nfe/webhooks.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Cria ou atualiza um webhook (AJAX).
     */
    public function saveWebhook()
    {
        header('Content-Type: application/json');
        $this->checkWritePermission();

        $id       = Input::post('id', 'int', 0);
        $name     = Input::post('name', 'string', '');
        $url      = Input::post('url', 'string', '');
        $secret   = Input::post('secret', 'string', '');
        $eventsRaw = Input::post('events', 'string', '');
        $isActive = Input::post('is_active', 'int', 1);
        $retryCount = Input::post('retry_count', 'int', 3);
        $timeout  = Input::post('timeout_seconds', 'int', 10);

        if (empty($name) || empty($url)) {
            echo json_encode(['success' => false, 'message' => 'Nome e URL são obrigatórios.']);
            exit;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            echo json_encode(['success' => false, 'message' => 'URL inválida.']);
            exit;
        }

        $events = array_filter(array_map('trim', explode(',', $eventsRaw)));
        if (empty($events)) $events = ['*'];

        $data = [
            'name'            => $name,
            'url'             => $url,
            'secret'          => $secret,
            'events'          => $events,
            'is_active'       => $isActive,
            'retry_count'     => min(max($retryCount, 1), 10),
            'timeout_seconds' => min(max($timeout, 5), 30),
        ];

        $webhookModel = new NfeWebhook($this->db);

        if ($id > 0) {
            $ok = $webhookModel->update($id, $data);
            $msg = $ok ? 'Webhook atualizado.' : 'Erro ao atualizar.';
        } else {
            $newId = $webhookModel->create($data);
            $ok = $newId > 0;
            $msg = $ok ? 'Webhook criado com sucesso.' : 'Erro ao criar.';
        }

        $this->getAuditService()->record('webhook_config', 'nfe_webhook', $id ?: ($newId ?? null), $msg);

        echo json_encode(['success' => $ok, 'message' => $msg]);
        exit;
    }

    /**
     * Exclui um webhook (AJAX).
     */
    public function deleteWebhook()
    {
        header('Content-Type: application/json');
        $this->checkWritePermission();

        $id = Input::post('id', 'int', 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido.']);
            exit;
        }

        $webhookModel = new NfeWebhook($this->db);
        $ok = $webhookModel->delete($id);

        $this->getAuditService()->record('webhook_delete', 'nfe_webhook', $id, 'Webhook excluído');

        echo json_encode(['success' => $ok, 'message' => $ok ? 'Webhook excluído.' : 'Erro ao excluir.']);
        exit;
    }

    /**
     * Testa envio de webhook (AJAX).
     */
    public function testWebhook()
    {
        header('Content-Type: application/json');
        $this->checkWritePermission();

        $id = Input::post('id', 'int', 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido.']);
            exit;
        }

        $webhookModel = new NfeWebhook($this->db);
        $webhook = $webhookModel->readOne($id);
        if (!$webhook) {
            echo json_encode(['success' => false, 'message' => 'Webhook não encontrado.']);
            exit;
        }

        $whService = new NfeWebhookService($this->db);
        $result = $whService->dispatch('nfe.test', [
            'message' => 'Teste de webhook do sistema Akti.',
            'timestamp' => date('c'),
        ]);

        echo json_encode([
            'success' => $result['success'] > 0,
            'message' => "Enviado: {$result['dispatched']}, Sucesso: {$result['success']}, Falha: {$result['failed']}",
        ]);
        exit;
    }

    /**
     * Retorna logs de um webhook (AJAX/JSON).
     */
    public function webhookLogs()
    {
        header('Content-Type: application/json');

        $id = Input::get('id', 'int', 0);
        $page = Input::get('pg', 'int', 1);

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido.']);
            exit;
        }

        $webhookModel = new NfeWebhook($this->db);
        $result = $webhookModel->getLogs($id, $page, 20);

        echo json_encode(['success' => true, 'data' => $result['data'], 'total' => $result['total']]);
        exit;
    }

    // ══════════════════════════════════════════════════════════════
    // FASE 5 — Personalização DANFE
    // ══════════════════════════════════════════════════════════════

    /**
     * Configurações de personalização do DANFE.
     */
    public function danfeSettings()
    {
        $customizer = new NfeDanfeCustomizer($this->db);
        $danfeSettings = $customizer->getSettings();

        if ($this->isAjaxFragment()) {
            $isAjax = true;
            require 'app/views/nfe/danfe_settings.php';
            return;
        }

        require 'app/views/layout/header.php';
        require 'app/views/nfe/danfe_settings.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Salva configurações do DANFE (POST).
     */
    public function saveDanfeSettings()
    {
        $this->checkWritePermission();

        $customizer = new NfeDanfeCustomizer($this->db);

        // Upload de logo
        if (isset($_FILES['danfe_logo']) && $_FILES['danfe_logo']['error'] === UPLOAD_ERR_OK) {
            $result = $customizer->uploadLogo($_FILES['danfe_logo']);
            if (!$result['success']) {
                $_SESSION['flash_error'] = $result['message'];
                header('Location: ?page=nfe_documents&sec=danfe');
                exit;
            }
        }

        // Rodapé customizado
        $customFooter = Input::post('custom_footer', 'string', '');
        $customizer->saveSettings(['custom_footer' => $customFooter]);

        $this->getAuditService()->record('danfe_settings', 'nfe_credential', null, 'Configurações do DANFE atualizadas');

        $_SESSION['flash_success'] = 'Configurações do DANFE salvas com sucesso!';
        header('Location: ?page=nfe_documents&sec=danfe');
        exit;
    }

    // ══════════════════════════════════════════════════════════════
    // FASE 3 — Reenvio de NF-e Rejeitada
    // ══════════════════════════════════════════════════════════════

    /**
     * Reenvia uma NF-e rejeitada.
     * Gera novo XML com numeração atualizada e reenvia à SEFAZ.
     * POST: nfe_id
     */
    public function retry(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->checkWritePermission();

        $nfeId = Input::post('nfe_id', 'int', 0);
        if (!$nfeId) {
            echo json_encode(['success' => false, 'message' => 'ID da NF-e não informado.']);
            return;
        }

        $nfe = $this->docModel->readOne($nfeId);
        if (!$nfe || $nfe['status'] !== 'rejeitada') {
            echo json_encode(['success' => false, 'message' => 'NF-e não encontrada ou não está rejeitada.']);
            return;
        }

        $orderId = $nfe['order_id'] ?? 0;
        if (!$orderId) {
            echo json_encode(['success' => false, 'message' => 'NF-e sem pedido vinculado.']);
            return;
        }

        try {
            $orderModel = new Order($this->db);
            $order = $orderModel->readOne($orderId);
            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Pedido original não encontrado.']);
                return;
            }

            // Marcar o registro rejeitado antigo
            $this->docModel->update($nfeId, ['status' => 'cancelada_retry']);

            // Carregar itens do pedido
            $items = $orderModel->getItems($orderId);
            if (empty($items)) {
                echo json_encode(['success' => false, 'message' => 'Pedido sem itens.']);
                return;
            }

            // Enriquecer itens com dados fiscais
            $productModel = new \Akti\Models\Product($this->db);
            foreach ($items as &$it) {
                if (!empty($it['product_id'])) {
                    $product = $productModel->readOne($it['product_id']);
                    if ($product) {
                        $it['fiscal_ncm']                 = $product['fiscal_ncm'] ?? $product['ncm'] ?? '';
                        $it['fiscal_cest']                = $product['fiscal_cest'] ?? $product['cest'] ?? '';
                        $it['fiscal_cfop_interna']        = $product['fiscal_cfop_venda_interna'] ?? $product['cfop_venda_interna'] ?? '';
                        $it['fiscal_cfop_interestadual']  = $product['fiscal_cfop_venda_interestadual'] ?? $product['cfop_venda_interestadual'] ?? '';
                        $it['fiscal_icms_cst']            = $product['fiscal_icms_cst'] ?? $product['icms_cst'] ?? '';
                        $it['fiscal_icms_aliquota']       = $product['fiscal_icms_aliquota'] ?? $product['icms_aliquota'] ?? 0;
                        $it['fiscal_pis_cst']             = $product['fiscal_pis_cst'] ?? $product['pis_cst'] ?? '';
                        $it['fiscal_cofins_cst']          = $product['fiscal_cofins_cst'] ?? $product['cofins_cst'] ?? '';
                        $it['fiscal_ipi_cst']             = $product['fiscal_ipi_cst'] ?? $product['ipi_cst'] ?? '';
                        $it['fiscal_ipi_aliquota']        = $product['fiscal_ipi_aliquota'] ?? $product['ipi_aliquota'] ?? 0;
                        $it['fiscal_origem']              = $product['fiscal_origem'] ?? $product['origem'] ?? 0;
                        $it['fiscal_ean']                 = $product['fiscal_ean'] ?? $product['ean'] ?? '';
                        $it['fiscal_unidade']             = $product['fiscal_unidade'] ?? $product['unidade'] ?? 'UN';
                    }
                }
            }
            unset($it);

            // Carregar dados do cliente
            $customer = null;
            if (!empty($order['customer_id'])) {
                $customerModel = new \Akti\Models\Customer($this->db);
                $customer = $customerModel->readOne($order['customer_id']);
            }

            // Parcelas financeiras
            $installments = [];
            try {
                $installmentModel = new \Akti\Models\Installment($this->db);
                $installments = $installmentModel->getByOrderId($orderId);
            } catch (\Throwable $e) {
                // sem parcelas
            }

            $orderData = array_merge($order, [
                'items'                   => $items,
                'customer_name'           => $customer['name'] ?? $order['customer_name'] ?? '',
                'customer_cpf_cnpj'       => $customer['document'] ?? $order['customer_document'] ?? '',
                'customer_ie'             => $customer['ie'] ?? $order['customer_ie'] ?? '',
                'customer_address'        => $customer['address'] ?? $order['customer_address'] ?? '',
                'customer_number'         => $customer['address_number'] ?? $order['customer_number'] ?? 'S/N',
                'customer_bairro'         => $customer['bairro'] ?? $customer['neighborhood'] ?? '',
                'customer_cep'            => $customer['cep'] ?? $customer['zipcode'] ?? '',
                'customer_municipio'      => $customer['city'] ?? $customer['municipio'] ?? '',
                'customer_cod_municipio'  => $customer['cod_municipio'] ?? '',
                'customer_uf'             => $customer['state'] ?? $customer['uf'] ?? $order['customer_state'] ?? 'RS',
                'valor_produtos'          => $order['total_amount'] ?? 0,
                'shipping_cost'           => $order['shipping_cost'] ?? $order['frete'] ?? 0,
                'installments'            => $installments,
            ]);

            $nfeService = new NfeService($this->db);
            $result = $nfeService->emit($orderId, $orderData);

            // Auditoria
            if ($result['success']) {
                $this->getAuditService()->logEmit(
                    $result['nfe_id'] ?? 0,
                    $orderId,
                    $result['chave'] ?? ''
                );
                $this->getAuditService()->record('retry', 'nfe_document', $nfeId, "Reenvio de NF-e rejeitada #{$nfeId} → nova NF-e #{$result['nfe_id']}");

                $this->dispatchWebhook('nfe.authorized', [
                    'nfe_id'   => $result['nfe_id'] ?? null,
                    'order_id' => $orderId,
                    'chave'    => $result['chave'] ?? '',
                    'retry_of' => $nfeId,
                ]);
            } else {
                // Reverter status do registro antigo se falhar
                $this->docModel->update($nfeId, ['status' => 'rejeitada']);

                $this->dispatchWebhook('nfe.rejected', [
                    'nfe_id'   => $result['nfe_id'] ?? null,
                    'order_id' => $orderId,
                    'message'  => $result['message'] ?? '',
                    'retry_of' => $nfeId,
                ]);
            }

            echo json_encode($result);
        } catch (\Throwable $e) {
            $this->docModel->update($nfeId, ['status' => 'rejeitada']);
            error_log('[NfeDocumentController] retry error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro ao reenviar: ' . $e->getMessage()]);
        }
    }

    // ══════════════════════════════════════════════════════════════
    // Inutilização de Numeração
    // ══════════════════════════════════════════════════════════════

    /**
     * Inutiliza faixa de numeração na SEFAZ.
     * POST: num_inicial, num_final, modelo, serie, justificativa
     */
    public function inutilizar()
    {
        $this->checkWritePermission();

        $numInicial   = Input::post('num_inicial', 'int');
        $numFinal     = Input::post('num_final', 'int');
        $modelo       = Input::post('modelo', 'int', 55);
        $serie        = Input::post('serie', 'int', 1);
        $justificativa = Input::post('justificativa');

        header('Content-Type: application/json; charset=utf-8');

        // Validações
        if (!$numInicial || !$numFinal || $numInicial > $numFinal) {
            echo json_encode(['success' => false, 'message' => 'Números inválidos. O número inicial deve ser menor ou igual ao final.']);
            return;
        }
        if (strlen(trim($justificativa)) < 15) {
            echo json_encode(['success' => false, 'message' => 'Justificativa deve ter pelo menos 15 caracteres.']);
            return;
        }
        if (!in_array($modelo, [55, 65])) {
            echo json_encode(['success' => false, 'message' => 'Modelo inválido.']);
            return;
        }

        try {
            $nfeService = new NfeService($this->db);
            $result = $nfeService->inutilizar($numInicial, $numFinal, $justificativa, $modelo, $serie);

            if ($result['success'] ?? false) {
                // Registrar auditoria
                $this->getAuditService()->logInutilizar($numInicial, $numFinal, $justificativa);

                // Disparar webhook
                $this->dispatchWebhook('inutilizacao', [
                    'num_inicial' => $numInicial,
                    'num_final'   => $numFinal,
                    'modelo'      => $modelo,
                    'serie'       => $serie,
                ]);

                echo json_encode(['success' => true, 'message' => $result['message'] ?? "Numeração {$numInicial} a {$numFinal} inutilizada com sucesso."]);
            } else {
                echo json_encode(['success' => false, 'message' => $result['message'] ?? 'Erro ao inutilizar numeração na SEFAZ.']);
            }
        } catch (\Throwable $e) {
            error_log('[NfeDocumentController] Inutilizar error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro interno ao processar inutilização: ' . $e->getMessage()]);
        }
    }

    // ══════════════════════════════════════════════════════════════
    // FASE 5 — Emissão NFC-e (Modelo 65)
    // ══════════════════════════════════════════════════════════════

    /**
     * Emite NFC-e (modelo 65) para um pedido (AJAX/JSON).
     * POST: order_id
     * FASE5-01
     */
    public function emitNfce(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->checkWritePermission();

        // Rate limiting
        $rateCheck = RateLimitMiddleware::check('nfce_emit', 5);
        if (!$rateCheck['allowed']) {
            echo json_encode(['success' => false, 'message' => "Aguarde {$rateCheck['retry_after']} segundo(s) entre emissões."]);
            exit;
        }

        $orderId = Input::post('order_id', 'int', 0);
        if ($orderId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID do pedido inválido.']);
            exit;
        }

        try {
            $orderModel = new Order($this->db);
            $order = $orderModel->readOne($orderId);
            if (!$order) {
                echo json_encode(['success' => false, 'message' => "Pedido #{$orderId} não encontrado."]);
                exit;
            }

            // Carregar itens do pedido
            $items = $orderModel->getItems($orderId);
            if (empty($items)) {
                echo json_encode(['success' => false, 'message' => 'Pedido sem itens.']);
                exit;
            }

            // Enriquecer itens com dados fiscais
            $productModel = new \Akti\Models\Product($this->db);
            foreach ($items as &$it) {
                if (!empty($it['product_id'])) {
                    $product = $productModel->readOne($it['product_id']);
                    if ($product) {
                        $it['fiscal_ncm']            = $product['fiscal_ncm'] ?? $product['ncm'] ?? '';
                        $it['fiscal_cest']           = $product['fiscal_cest'] ?? $product['cest'] ?? '';
                        $it['fiscal_cfop_interna']   = $product['fiscal_cfop_venda_interna'] ?? $product['cfop_venda_interna'] ?? '';
                        $it['fiscal_icms_cst']       = $product['fiscal_icms_cst'] ?? $product['icms_cst'] ?? '';
                        $it['fiscal_icms_aliquota']  = $product['fiscal_icms_aliquota'] ?? $product['icms_aliquota'] ?? 0;
                        $it['fiscal_pis_cst']        = $product['fiscal_pis_cst'] ?? $product['pis_cst'] ?? '';
                        $it['fiscal_cofins_cst']     = $product['fiscal_cofins_cst'] ?? $product['cofins_cst'] ?? '';
                        $it['fiscal_ipi_cst']        = $product['fiscal_ipi_cst'] ?? $product['ipi_cst'] ?? '';
                        $it['fiscal_ipi_aliquota']   = $product['fiscal_ipi_aliquota'] ?? $product['ipi_aliquota'] ?? 0;
                        $it['fiscal_origem']         = $product['fiscal_origem'] ?? $product['origem'] ?? 0;
                        $it['fiscal_ean']            = $product['fiscal_ean'] ?? $product['ean'] ?? '';
                        $it['fiscal_unidade']        = $product['fiscal_unidade'] ?? $product['unidade'] ?? 'UN';
                    }
                }
            }
            unset($it);

            // Carregar dados do cliente
            $customer = null;
            if (!empty($order['customer_id'])) {
                $customerModel = new \Akti\Models\Customer($this->db);
                $customer = $customerModel->readOne($order['customer_id']);
            }

            $orderData = array_merge($order, [
                'items'              => $items,
                'customer_name'      => $customer['name'] ?? $order['customer_name'] ?? '',
                'customer_cpf_cnpj'  => $customer['document'] ?? $order['customer_document'] ?? '',
                'payment_method'     => $order['payment_method'] ?? 'dinheiro',
                'troco'              => $order['troco'] ?? 0,
                'valor_produtos'     => $order['total_amount'] ?? 0,
            ]);

            // Usar NfeService para emitir NFC-e
            $nfeService = new NfeService($this->db);
            $result = $nfeService->emitNfce($orderId, $orderData);

            // Auditoria
            if ($result['success']) {
                $this->getAuditService()->record('emit_nfce', 'nfe_document', $result['nfe_id'] ?? null,
                    "NFC-e emitida para pedido #{$orderId}", [
                        'order_id' => $orderId,
                        'chave'    => $result['chave'] ?? '',
                    ]);

                $this->dispatchWebhook('nfce.authorized', [
                    'nfe_id'   => $result['nfe_id'] ?? null,
                    'order_id' => $orderId,
                    'chave'    => $result['chave'] ?? '',
                ]);
            }

            echo json_encode($result);
        } catch (\Throwable $e) {
            error_log('[NfeDocumentController] emitNfce error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Download do DANFE NFC-e (cupom térmico) — PDF ou HTML.
     * GET: id, format (pdf|html)
     * FASE5-01
     */
    public function downloadDanfeNfce(): void
    {
        $id = Input::get('id', 'int', 0);
        $format = Input::get('format', 'string', 'html');

        if ($id <= 0) {
            $_SESSION['flash_error'] = 'ID da NFC-e inválido.';
            header('Location: ?page=nfe_documents');
            exit;
        }

        $doc = $this->docModel->readOne($id);
        if (!$doc || ($doc['modelo'] ?? 55) != 65) {
            $_SESSION['flash_error'] = 'NFC-e não encontrada.';
            header('Location: ?page=nfe_documents');
            exit;
        }

        $xmlAutorizado = $doc['xml_autorizado'] ?? '';
        if (empty($xmlAutorizado)) {
            $_SESSION['flash_error'] = 'XML autorizado não disponível para gerar DANFE NFC-e.';
            header('Location: ?page=nfe_documents&action=detail&id=' . $id);
            exit;
        }

        $generator = new NfceDanfeGenerator($this->db);
        $content = $generator->generate($xmlAutorizado, ['format' => $format]);

        if ($content === null) {
            $_SESSION['flash_error'] = 'Não foi possível gerar o DANFE NFC-e.';
            header('Location: ?page=nfe_documents&action=detail&id=' . $id);
            exit;
        }

        $chave = $doc['chave'] ?? $doc['numero'];

        if ($format === 'pdf') {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="DANFE_NFCe_' . $chave . '.pdf"');
        } else {
            header('Content-Type: text/html; charset=utf-8');
        }

        $this->getAuditService()->record('download_danfe_nfce', 'nfe_document', $id, "Download DANFE NFC-e #{$id}");

        echo $content;
        exit;
    }

    // ══════════════════════════════════════════════════════════════
    // FASE 5 — Contingência Automática
    // ══════════════════════════════════════════════════════════════

    /**
     * Retorna status atual da contingência (JSON).
     * FASE5-02
     */
    public function contingencyStatus(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $contingency = new NfeContingencyService($this->db);
        $status = $contingency->getStatus();

        echo json_encode(['success' => true, 'data' => $status]);
        exit;
    }

    /**
     * Ativa contingência manualmente (POST/JSON).
     * POST: justificativa, tp_emis (opcional)
     * FASE5-02
     */
    public function contingencyActivate(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->checkWritePermission();

        $justificativa = Input::post('justificativa', 'string', '');
        $tpEmis = Input::post('tp_emis', 'int', 0) ?: null;

        $contingency = new NfeContingencyService($this->db);
        $result = $contingency->activate($justificativa, $tpEmis);

        if ($result['success']) {
            $this->getAuditService()->record('contingency_activate', 'nfe_credential', null,
                'Contingência ativada: ' . ($result['message'] ?? ''));
        }

        echo json_encode($result);
        exit;
    }

    /**
     * Desativa contingência (POST/JSON).
     * FASE5-02
     */
    public function contingencyDeactivate(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->checkWritePermission();

        $contingency = new NfeContingencyService($this->db);
        $result = $contingency->deactivate();

        if ($result['success']) {
            $this->getAuditService()->record('contingency_deactivate', 'nfe_credential', null,
                'Contingência desativada. Pendentes: ' . ($result['pending'] ?? 0));
        }

        echo json_encode($result);
        exit;
    }

    /**
     * Sincroniza NF-e emitidas em contingência (POST/JSON).
     * FASE5-02
     */
    public function contingencySync(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->checkWritePermission();

        $contingency = new NfeContingencyService($this->db);
        $result = $contingency->syncPending();

        $this->getAuditService()->record('contingency_sync', 'nfe_credential', null,
            "Sincronização: {$result['synced']} ok, {$result['failed']} falha, {$result['remaining']} restantes");

        echo json_encode($result);
        exit;
    }

    /**
     * Retorna histórico de contingências (JSON).
     * FASE5-02
     */
    public function contingencyHistory(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $contingency = new NfeContingencyService($this->db);
        $history = $contingency->getHistory();

        echo json_encode(['success' => true, 'data' => $history]);
        exit;
    }

    // ══════════════════════════════════════════════════════════════
    // FASE 5 — Download XML em Lote (ZIP)
    // ══════════════════════════════════════════════════════════════

    /**
     * Download em lote de XMLs de NF-e selecionadas (ZIP).
     * POST: ids (string CSV de IDs) ou GET: start_date, end_date
     * FASE5-03
     */
    public function downloadBatch(): void
    {
        $this->checkWritePermission();

        $idsRaw = Input::post('ids', 'string', '') ?: Input::get('ids', 'string', '');
        $startDate = Input::get('start_date', 'string', '');
        $endDate = Input::get('end_date', 'string', '');

        $xmlFiles = [];

        if (!empty($idsRaw)) {
            // Download por IDs selecionados
            $ids = array_filter(array_map('intval', explode(',', $idsRaw)));
            if (empty($ids)) {
                $_SESSION['flash_error'] = 'Nenhuma NF-e selecionada.';
                header('Location: ?page=nfe_documents');
                exit;
            }

            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $this->db->prepare(
                "SELECT id, chave, numero, serie, modelo, status,
                        xml_autorizado, xml_cancelamento, xml_correcao, xml_path
                 FROM nfe_documents WHERE id IN ({$placeholders})"
            );
            $stmt->execute($ids);
            $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif (!empty($startDate) && !empty($endDate)) {
            // Download por período
            $stmt = $this->db->prepare(
                "SELECT id, chave, numero, serie, modelo, status,
                        xml_autorizado, xml_cancelamento, xml_correcao, xml_path
                 FROM nfe_documents
                 WHERE DATE(created_at) BETWEEN :start AND :end
                   AND status IN ('autorizada', 'cancelada', 'corrigida')
                 ORDER BY numero ASC"
            );
            $stmt->execute([':start' => $startDate, ':end' => $endDate]);
            $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $_SESSION['flash_error'] = 'Informe IDs ou período para download em lote.';
            header('Location: ?page=nfe_documents');
            exit;
        }

        if (empty($docs)) {
            $_SESSION['flash_error'] = 'Nenhum XML encontrado.';
            header('Location: ?page=nfe_documents');
            exit;
        }

        // Criar ZIP via streaming
        $storageService = new \Akti\Services\NfeStorageService();

        $zipFilename = 'XMLs_NFe_' . date('YmdHis') . '.zip';
        $tmpZip = tempnam(sys_get_temp_dir(), 'nfe_zip_');

        $zip = new \ZipArchive();
        if ($zip->open($tmpZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            $_SESSION['flash_error'] = 'Erro ao criar arquivo ZIP.';
            header('Location: ?page=nfe_documents');
            exit;
        }

        $addedCount = 0;
        foreach ($docs as $doc) {
            $chave = $doc['chave'] ?? $doc['numero'];
            $modelo = ($doc['modelo'] ?? 55) == 65 ? 'NFCe' : 'NFe';

            // XML autorizado
            $xml = $doc['xml_autorizado'] ?? '';
            if (empty($xml) && !empty($doc['xml_path'])) {
                $xml = $storageService->readFile($doc['xml_path']) ?? '';
            }
            if (!empty($xml)) {
                $zip->addFromString("{$modelo}_{$chave}_autorizado.xml", $xml);
                $addedCount++;
            }

            // XML cancelamento
            if (!empty($doc['xml_cancelamento'])) {
                $zip->addFromString("{$modelo}_{$chave}_cancelamento.xml", $doc['xml_cancelamento']);
                $addedCount++;
            }

            // XML CC-e
            if (!empty($doc['xml_correcao'])) {
                $zip->addFromString("{$modelo}_{$chave}_cce.xml", $doc['xml_correcao']);
                $addedCount++;
            }
        }

        $zip->close();

        if ($addedCount === 0) {
            @unlink($tmpZip);
            $_SESSION['flash_error'] = 'Nenhum XML disponível para download.';
            header('Location: ?page=nfe_documents');
            exit;
        }

        // Auditoria
        $this->getAuditService()->record('download_batch', 'nfe_document', null,
            "Download em lote: {$addedCount} XML(s) de " . count($docs) . ' NF-e(s)');

        // Enviar ZIP
        $zipSize = filesize($tmpZip);
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
        header('Content-Length: ' . $zipSize);
        header('Cache-Control: no-cache, must-revalidate');

        readfile($tmpZip);
        @unlink($tmpZip);
        exit;
    }

    // ══════════════════════════════════════════════════════════════
    // FASE 5 — Exportação SPED Fiscal (EFD)
    // ══════════════════════════════════════════════════════════════

    /**
     * Gera e faz download do arquivo SPED Fiscal (EFD ICMS/IPI).
     * GET: start_date, end_date
     * FASE5-04
     */
    public function exportSped(): void
    {
        $this->checkWritePermission();

        $startDate = Input::get('start_date', 'string', date('Y-m-01'));
        $endDate   = Input::get('end_date', 'string', date('Y-m-d'));

        try {
            $spedService = new NfeSpedFiscalService($this->db);
            $content = $spedService->generate($startDate, $endDate);

            if (empty($content)) {
                $_SESSION['flash_error'] = 'Nenhum dado para geração do SPED Fiscal no período.';
                header('Location: ?page=nfe_documents&action=dashboard');
                exit;
            }

            $this->getAuditService()->record('export_sped', 'nfe_report', null,
                "Exportação SPED Fiscal ({$startDate} a {$endDate})");

            $filename = 'SPED_Fiscal_' . str_replace('-', '', $startDate) . '_a_' . str_replace('-', '', $endDate) . '.txt';

            header('Content-Type: text/plain; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($content));

            echo $content;
        } catch (\Throwable $e) {
            error_log('[NfeDocumentController] exportSped error: ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Erro ao gerar SPED Fiscal: ' . $e->getMessage();
            header('Location: ?page=nfe_documents&action=dashboard');
        }
        exit;
    }

    // ══════════════════════════════════════════════════════════════
    // FASE 5 — Exportação SINTEGRA
    // ══════════════════════════════════════════════════════════════

    /**
     * Gera e faz download do arquivo SINTEGRA.
     * GET: start_date, end_date
     * FASE5-05
     */
    public function exportSintegra(): void
    {
        $this->checkWritePermission();

        $startDate = Input::get('start_date', 'string', date('Y-m-01'));
        $endDate   = Input::get('end_date', 'string', date('Y-m-d'));

        try {
            $sintegraService = new NfeSintegraService($this->db);
            $content = $sintegraService->generate($startDate, $endDate);

            if (empty($content)) {
                $_SESSION['flash_error'] = 'Nenhum dado para geração do SINTEGRA no período.';
                header('Location: ?page=nfe_documents&action=dashboard');
                exit;
            }

            $this->getAuditService()->record('export_sintegra', 'nfe_report', null,
                "Exportação SINTEGRA ({$startDate} a {$endDate})");

            $filename = 'SINTEGRA_' . str_replace('-', '', $startDate) . '_a_' . str_replace('-', '', $endDate) . '.txt';

            header('Content-Type: text/plain; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($content));

            echo $content;
        } catch (\Throwable $e) {
            error_log('[NfeDocumentController] exportSintegra error: ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Erro ao gerar SINTEGRA: ' . $e->getMessage();
            header('Location: ?page=nfe_documents&action=dashboard');
        }
        exit;
    }

    // ══════════════════════════════════════════════════════════════
    // FASE 5 — Livro de Registro de Saídas
    // ══════════════════════════════════════════════════════════════

    /**
     * Exibe o Livro de Registro de Saídas.
     * GET: start_date, end_date
     * FASE5-06
     */
    public function livroSaidas(): void
    {
        $this->getAuditService()->record('view', 'nfe_livro_saidas', null, 'Acessou Livro de Registro de Saídas');

        $reportModel = new \Akti\Models\NfeReportModel($this->db);

        $startDate = Input::get('start_date', 'string', date('Y-m-01'));
        $endDate   = Input::get('end_date', 'string', date('Y-m-d'));

        $livro = $reportModel->getLivroSaidas($startDate, $endDate);
        $items = $livro['items'] ?? [];
        $totalsByCfop = $livro['totals_by_cfop'] ?? [];
        $totalGeral = $livro['total_geral'] ?? [];
        $cfopDescriptions = \Akti\Models\NfeReportModel::getCfopDescriptions();

        if ($this->isAjaxFragment()) {
            $isAjax = true;
            require 'app/views/nfe/livro_saidas.php';
            return;
        }

        require 'app/views/layout/header.php';
        require 'app/views/nfe/livro_saidas.php';
        require 'app/views/layout/footer.php';
    }

    // ══════════════════════════════════════════════════════════════
    // FASE 5 — Livro de Registro de Entradas
    // ══════════════════════════════════════════════════════════════

    /**
     * Exibe o Livro de Registro de Entradas.
     * GET: start_date, end_date
     * FASE5-07
     */
    public function livroEntradas(): void
    {
        $this->getAuditService()->record('view', 'nfe_livro_entradas', null, 'Acessou Livro de Registro de Entradas');

        $reportModel = new \Akti\Models\NfeReportModel($this->db);

        $startDate = Input::get('start_date', 'string', date('Y-m-01'));
        $endDate   = Input::get('end_date', 'string', date('Y-m-d'));

        $livro = $reportModel->getLivroEntradas($startDate, $endDate);
        $items = $livro['items'] ?? [];
        $totalGeral = $livro['total_geral'] ?? [];

        if ($this->isAjaxFragment()) {
            $isAjax = true;
            require 'app/views/nfe/livro_entradas.php';
            return;
        }

        require 'app/views/layout/header.php';
        require 'app/views/nfe/livro_entradas.php';
        require 'app/views/layout/footer.php';
    }

    // ══════════════════════════════════════════════════════════════
    // FASE 5 — Backup Automático de XMLs
    // ══════════════════════════════════════════════════════════════

    /**
     * Executa backup de XMLs (POST/JSON).
     * POST: start_date, end_date, tipo (local|s3|ftp)
     * FASE5-08
     */
    public function backupXml(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->checkWritePermission();

        $startDate = Input::post('start_date', 'string', date('Y-m-01'));
        $endDate   = Input::post('end_date', 'string', date('Y-m-d'));
        $tipo      = Input::post('tipo', 'string', 'local');

        if (!in_array($tipo, ['local', 's3', 'ftp'])) {
            echo json_encode(['success' => false, 'message' => 'Tipo de backup inválido.']);
            exit;
        }

        try {
            $backupService = new NfeBackupService($this->db);
            $result = $backupService->execute($startDate, $endDate, $tipo);

            if ($result['success']) {
                $this->getAuditService()->record('backup_xml', 'nfe_backup', $result['backup_id'] ?? null,
                    "Backup XML: {$result['total']} arquivo(s), tipo={$tipo}");
            }

            echo json_encode($result);
        } catch (\Throwable $e) {
            error_log('[NfeDocumentController] backupXml error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Retorna histórico de backups (JSON).
     * FASE5-08
     */
    public function backupHistory(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $backupService = new NfeBackupService($this->db);
        $history = $backupService->getHistory();

        echo json_encode(['success' => true, 'data' => $history]);
        exit;
    }

    /**
     * Página de configuração de backup e relatórios fiscais.
     * FASE5-08
     */
    public function backupSettings(): void
    {
        $this->getAuditService()->record('view', 'nfe_backup_settings', null, 'Acessou configurações de backup');

        $backupService = new NfeBackupService($this->db);
        $backupHistory = $backupService->getHistory(20);

        // Carregar configurações
        $backupConfig = [];
        try {
            $stmt = $this->db->query("SELECT config_key, config_value FROM nfe_fiscal_config WHERE config_key LIKE 'backup_%'");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $backupConfig[$row['config_key']] = $row['config_value'];
            }
        } catch (\Throwable $e) {}

        if ($this->isAjaxFragment()) {
            $isAjax = true;
            require 'app/views/nfe/backup_settings.php';
            return;
        }

        require 'app/views/layout/header.php';
        require 'app/views/nfe/backup_settings.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Salva configurações de backup (POST).
     * FASE5-08
     */
    public function saveBackupSettings(): void
    {
        $this->checkWritePermission();

        $configs = [
            'backup_auto_enabled' => Input::post('backup_auto_enabled', 'int', 0),
            'backup_tipo'         => Input::post('backup_tipo', 'string', 'local'),
            'backup_s3_bucket'    => Input::post('backup_s3_bucket', 'string', ''),
            'backup_s3_region'    => Input::post('backup_s3_region', 'string', ''),
            'backup_s3_key'       => Input::post('backup_s3_key', 'string', ''),
            'backup_s3_secret'    => Input::post('backup_s3_secret', 'string', ''),
            'backup_ftp_host'     => Input::post('backup_ftp_host', 'string', ''),
            'backup_ftp_user'     => Input::post('backup_ftp_user', 'string', ''),
            'backup_ftp_password' => Input::post('backup_ftp_password', 'string', ''),
            'backup_ftp_path'     => Input::post('backup_ftp_path', 'string', '/backups/nfe/'),
            'backup_retention_days' => Input::post('backup_retention_days', 'int', 365),
        ];

        try {
            foreach ($configs as $key => $value) {
                $stmt = $this->db->prepare(
                    "INSERT INTO nfe_fiscal_config (config_key, config_value) VALUES (:key, :val)
                     ON DUPLICATE KEY UPDATE config_value = :val2, updated_at = NOW()"
                );
                $stmt->execute([':key' => $key, ':val' => (string) $value, ':val2' => (string) $value]);
            }

            $this->getAuditService()->record('backup_settings', 'nfe_backup', null, 'Configurações de backup atualizadas');
            $_SESSION['flash_success'] = 'Configurações de backup salvas!';
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Erro ao salvar configurações: ' . $e->getMessage();
        }

        header('Location: ?page=nfe_documents&action=backupSettings');
        exit;
    }

    // ══════════════════════════════════════════════════════════════
    // Helpers internos Fase 5
    // ══════════════════════════════════════════════════════════════

    /**
     * Retorna instância do serviço de auditoria (lazy).
     * @return NfeAuditService
     */
    private function getAuditService(): NfeAuditService
    {
        static $service = null;
        if ($service === null) {
            $service = new NfeAuditService($this->db);
        }
        return $service;
    }

    /**
     * Dispara webhooks para um evento NF-e.
     * @param string $event
     * @param array  $payload
     */
    private function dispatchWebhook(string $event, array $payload): void
    {
        try {
            $whService = new NfeWebhookService($this->db);
            $whService->dispatch($event, $payload);
        } catch (\Throwable $e) {
            error_log('[NfeDocumentController] Webhook dispatch error: ' . $e->getMessage());
        }
    }
}
