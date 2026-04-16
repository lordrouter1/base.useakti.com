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
use Akti\Core\Log;
use Akti\Services\NfceXmlBuilder;
use Akti\Services\NfceDanfeGenerator;
use Akti\Services\NfeContingencyService;
use Akti\Services\NfeSpedFiscalService;
use Akti\Services\NfeSintegraService;
use Akti\Services\NfeBackupService;
use Akti\Services\NfeOrderDataService;
use Akti\Services\NfeDashboardService;
use Akti\Services\NfeBatchDownloadService;
use Akti\Services\NfeDetailService;
use Akti\Services\NfeDownloadService;
use Akti\Services\NfeWebhookManagementService;
use Akti\Services\NfeFiscalReportService;
use Akti\Services\NfeBackupManagementService;
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
use TenantManager;

/**
 * Controller: NfeDocumentController
 * Gerencia documentos NF-e: listagem, emissão, cancelamento, carta de correção, download.
 *
 * @package Akti\Controllers
 */
class NfeDocumentController extends BaseController {
    private NfeDocument $docModel;
    private NfeLog $logModel;

    /**
     * Construtor da classe NfeDocumentController.
     *
     * @param \PDO $db Conexão PDO com o banco de dados
     * @param NfeDocument $docModel Doc model
     * @param NfeLog $logModel Log model
     */
    public function __construct(\PDO $db, NfeDocument $docModel, NfeLog $logModel)
    {
        if (!ModuleBootloader::isModuleEnabled('nfe')) {
            http_response_code(403);
            require 'app/views/layout/header.php';
            echo "<div class='container mt-5'><div class='alert alert-warning'><i class='fas fa-toggle-off me-2'></i>Módulo NF-e desativado para este tenant.</div></div>";
            require 'app/views/layout/footer.php';
            exit;
        }

        $this->db = $db;
        $this->docModel = $docModel;
        $this->logModel = $logModel;

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
            $this->json(['success' => false, 'message' => 'Sessão expirada.']);}
        $userModel = new User($this->db);
        // Verifica se o usuário tem permissão para a página — escrita requer a mesma permissão
        // O sistema usa permissão booleana por página; controle granular (view vs edit) pode ser
        // expandido futuramente. Aqui validamos que o user tem acesso ao módulo.
        if (!$userModel->checkPermission($_SESSION['user_id'], 'nfe_documents')) {
            http_response_code(403);
            $this->json(['success' => false, 'message' => 'Sem permissão para esta ação.']);}
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

        // ─── Integração Financeira: carregar parcelas via service ───
        $detailService = new NfeDetailService($this->db);
        $financialData = !empty($doc['order_id'])
            ? $detailService->loadInstallmentData((int) $doc['order_id'])
            : ['installments' => [], 'summary' => ['total' => 0, 'pagas' => 0, 'pendentes' => 0, 'valor_pago' => 0.00, 'valor_total' => 0.00, 'faturadas' => 0]];
        $installments = $financialData['installments'];
        $installmentSummary = $financialData['summary'];

        // ─── IBPTax: valor de tributos aproximados via service ───
        $ibptaxResult = $detailService->calculateIbptax($id, (float) ($doc['valor_tributos_aprox'] ?? 0));
        $valorTributosAprox = $ibptaxResult['valor'];
        $ibptaxFonte = $ibptaxResult['fonte'];

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
            $this->json(['success' => false, 'message' => "Aguarde {$rateCheck['retry_after']} segundo(s) entre emissões."]);}

        $orderId = Input::post('order_id', 'int', 0);
        if ($orderId <= 0) {
            $this->json(['success' => false, 'message' => 'ID do pedido inválido.']);}

        try {
            // Verificar se já existe NF-e autorizada
            $existingNfe = $this->docModel->readByOrder($orderId);
            if ($existingNfe && $existingNfe['status'] === 'autorizada') {
                $this->json(['success' => false, 'message' => "Pedido #{$orderId} já possui NF-e autorizada (Nº {$existingNfe['numero']})."]);}

            // Montar dados via service
            $orderDataService = new NfeOrderDataService($this->db);
            $orderData = $orderDataService->buildNfeData($orderId);

            $nfeService = new NfeService($this->db);
            $result = $nfeService->emit($orderId, $orderData);

            // Auditoria + Webhook
            if ($result['success']) {
                $this->getAuditService()->logEmit($result['nfe_id'] ?? 0, $orderId, $result['chave'] ?? '');
                $this->dispatchWebhook('nfe.authorized', [
                    'nfe_id' => $result['nfe_id'] ?? null, 'order_id' => $orderId, 'chave' => $result['chave'] ?? '',
                ]);
            } else {
                $this->dispatchWebhook('nfe.rejected', [
                    'nfe_id' => $result['nfe_id'] ?? null, 'order_id' => $orderId, 'message' => $result['message'] ?? '',
                ]);
            }

            $this->json($result);
        } catch (\Throwable $e) {
            Log::error('NfeDocumentController: emit', ['exception' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => 'Erro interno ao emitir NF-e. Tente novamente.']);
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
            $this->json(['success' => false, 'message' => 'ID da NF-e inválido.']);}
        if (strlen(trim($motivo)) < 15) {
            $this->json(['success' => false, 'message' => 'Justificativa deve ter no mínimo 15 caracteres.']);}

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

            $this->json($result);
        } catch (\Throwable $e) {
            Log::error('NfeDocumentController: cancel', ['exception' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => 'Erro interno ao cancelar NF-e. Tente novamente.']);
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
            $this->json(['success' => false, 'message' => 'ID da NF-e inválido.']);}
        if (strlen(trim($texto)) < 15) {
            $this->json(['success' => false, 'message' => 'Texto da correção deve ter no mínimo 15 caracteres.']);}

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

            $this->json($result);
        } catch (\Throwable $e) {
            Log::error('NfeDocumentController: correction', ['exception' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => 'Erro interno ao registrar correção. Tente novamente.']);
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
        $downloadService = new NfeDownloadService($this->db);

        switch ($type) {
            case 'xml':
                $xml = $downloadService->getAuthorizedXml($doc);
                if (empty($xml)) {
                    $_SESSION['flash_error'] = 'XML não disponível para esta NF-e.';
                    header('Location: ?page=nfe_documents&action=detail&id=' . $id);
                    exit;
                }
                $this->getAuditService()->logDownloadXml($id);
                $downloadService->sendXmlDownload($xml, 'NFe', $chave);

            case 'danfe':
                $xmlAutorizado = $doc['xml_autorizado'] ?? '';
                $pdf = $downloadService->generateDanfe($xmlAutorizado);
                if ($pdf === null) {
                    $_SESSION['flash_error'] = 'Não foi possível gerar o DANFE. XML autorizado não disponível ou biblioteca sped-da pode não estar instalada.';
                    header('Location: ?page=nfe_documents&action=detail&id=' . $id);
                    exit;
                }
                $this->getAuditService()->logDownloadDanfe($id);
                $downloadService->sendDanfeDownload($pdf, $chave);

            case 'cancel_xml':
                $xml = $downloadService->getCancelXml($doc);
                if (empty($xml)) {
                    $_SESSION['flash_error'] = 'XML de cancelamento não disponível.';
                    header('Location: ?page=nfe_documents&action=detail&id=' . $id);
                    exit;
                }
                $downloadService->sendXmlDownload($xml, 'Cancel', $chave);

            case 'cce_xml':
                $xml = $downloadService->getCceXml($doc);
                if (empty($xml)) {
                    $_SESSION['flash_error'] = 'XML de correção não disponível.';
                    header('Location: ?page=nfe_documents&action=detail&id=' . $id);
                    exit;
                }
                $downloadService->sendXmlDownload($xml, 'CCe', $chave);

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
            $this->json(['success' => false, 'message' => 'ID da NF-e inválido.', 'details' => []]);}

        try {
            $nfeService = new NfeService($this->db);
            $result = $nfeService->checkStatus($id);
            $this->json($result);
        } catch (\Throwable $e) {
            Log::error('NfeDocumentController: checkStatus', ['exception' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => 'Erro ao consultar status. Tente novamente.', 'details' => []]);
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

        $startDate = Input::get('start_date', 'string', date('Y-01-01'));
        $endDate   = Input::get('end_date', 'string', date('Y-m-d'));

        // Carregar dados via service
        $dashboardService = new NfeDashboardService($this->db);
        $data = $dashboardService->loadDashboardData($startDate, $endDate);
        extract($data);

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

        $startDate = Input::get('start_date', 'string', date('Y-m-01'));
        $endDate   = Input::get('end_date', 'string', date('Y-m-d'));

        $fiscalService = new NfeFiscalReportService($this->db);
        $data = $fiscalService->getCorrectionReportData($startDate, $endDate);
        extract($data);

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

        $fiscalService = new NfeFiscalReportService($this->db);
        $result = $fiscalService->getExportData($type, $startDate, $endDate);

        if (isset($result['error'])) {
            $_SESSION['flash_error'] = $result['error'];
            header('Location: ?page=nfe_documents&action=dashboard&start_date=' . urlencode($startDate) . '&end_date=' . urlencode($endDate));
            exit;
        }

        $data  = $result['data'];
        $title = $result['title'];

        // Auditoria
        $this->getAuditService()->record('export_report', 'nfe_report', null, "Exportou relatório '{$type}' ({$startDate} a {$endDate})", [
            'type'       => $type,
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'rows'       => count($data),
        ]);

        try {
            $fiscalService->exportToExcel($data, $title);
        } catch (\Throwable $e) {
            Log::error('NfeDocumentController: exportReport', ['exception' => $e->getMessage()]);
            $_SESSION['flash_error'] = 'Erro interno ao exportar relatório. Tente novamente.';
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
            $this->json(['success' => false, 'message' => 'Nenhum pedido selecionado.']);}

        $orderIds = array_filter(array_map('intval', explode(',', $orderIdsRaw)));
        if (empty($orderIds)) {
            $this->json(['success' => false, 'message' => 'IDs de pedidos inválidos.']);}

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

        $this->json($result);}

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

        $this->json(['success' => true, 'result' => $result]);}

    /**
     * Cancela item da fila (AJAX).
     */
    public function cancelQueue()
    {
        header('Content-Type: application/json');
        $this->checkWritePermission();

        $id = Input::post('id', 'int', 0);
        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'ID inválido.']);}

        $queueModel = new NfeQueue($this->db);
        $ok = $queueModel->cancel($id);

        $this->json([
            'success' => $ok,
            'message' => $ok ? 'Item removido da fila.' : 'Não foi possível cancelar (status não permite).',
        ]);}

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

        $this->json($result);}

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
            $this->json(['success' => false, 'message' => 'Chave de acesso deve ter 44 dígitos.']);}

        $distdfeService = new NfeDistDFeService($this->db);
        $result = $distdfeService->queryByChave($chave);

        $this->json($result);}

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
            $this->json(['success' => false, 'message' => 'ID do documento inválido.']);}
        if (empty($type)) {
            $this->json(['success' => false, 'message' => 'Tipo de manifestação obrigatório.']);}

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

        $this->json($result);}

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
        $whMgmt = new NfeWebhookManagementService($this->db);
        $webhooksList = $whMgmt->listAll();

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

        $whMgmt = new NfeWebhookManagementService($this->db);
        $result = $whMgmt->save([
            'id'              => Input::post('id', 'int', 0),
            'name'            => Input::post('name', 'string', ''),
            'url'             => Input::post('url', 'string', ''),
            'secret'          => Input::post('secret', 'string', ''),
            'events'          => Input::post('events', 'string', ''),
            'is_active'       => Input::post('is_active', 'int', 1),
            'retry_count'     => Input::post('retry_count', 'int', 3),
            'timeout_seconds' => Input::post('timeout_seconds', 'int', 10),
        ]);

        $this->getAuditService()->record('webhook_config', 'nfe_webhook', $result['id'] ?? null, $result['message']);

        $this->json($result);}

    /**
     * Exclui um webhook (AJAX).
     */
    public function deleteWebhook()
    {
        header('Content-Type: application/json');
        $this->checkWritePermission();

        $id = Input::post('id', 'int', 0);

        $whMgmt = new NfeWebhookManagementService($this->db);
        $result = $whMgmt->delete($id);

        if ($result['success']) {
            $this->getAuditService()->record('webhook_delete', 'nfe_webhook', $id, 'Webhook excluído');
        }

        $this->json($result);}

    /**
     * Testa envio de webhook (AJAX).
     */
    public function testWebhook()
    {
        header('Content-Type: application/json');
        $this->checkWritePermission();

        $id = Input::post('id', 'int', 0);

        $whMgmt = new NfeWebhookManagementService($this->db);
        $result = $whMgmt->test($id);

        $this->json($result);}

    /**
     * Retorna logs de um webhook (AJAX/JSON).
     */
    public function webhookLogs()
    {
        header('Content-Type: application/json');

        $id   = Input::get('id', 'int', 0);
        $page = Input::get('pg', 'int', 1);

        $whMgmt = new NfeWebhookManagementService($this->db);
        $result = $whMgmt->getLogs($id, $page, 20);

        $this->json($result);}

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
            $this->json(['success' => false, 'message' => 'ID da NF-e não informado.']);}

        $nfe = $this->docModel->readOne($nfeId);
        if (!$nfe || $nfe['status'] !== 'rejeitada') {
            $this->json(['success' => false, 'message' => 'NF-e não encontrada ou não está rejeitada.']);}

        $orderId = $nfe['order_id'] ?? 0;
        if (!$orderId) {
            $this->json(['success' => false, 'message' => 'NF-e sem pedido vinculado.']);}

        try {
            // Marcar o registro rejeitado antigo
            $this->docModel->update($nfeId, ['status' => 'cancelada_retry']);

            // Montar dados via service
            $orderDataService = new NfeOrderDataService($this->db);
            $orderData = $orderDataService->buildNfeData($orderId);

            $nfeService = new NfeService($this->db);
            $result = $nfeService->emit($orderId, $orderData);

            // Auditoria + Webhook
            if ($result['success']) {
                $this->getAuditService()->logEmit($result['nfe_id'] ?? 0, $orderId, $result['chave'] ?? '');
                $this->getAuditService()->record('retry', 'nfe_document', $nfeId, "Reenvio de NF-e rejeitada #{$nfeId} → nova NF-e #{$result['nfe_id']}");
                $this->dispatchWebhook('nfe.authorized', [
                    'nfe_id' => $result['nfe_id'] ?? null, 'order_id' => $orderId, 'chave' => $result['chave'] ?? '', 'retry_of' => $nfeId,
                ]);
            } else {
                // Reverter status do registro antigo se falhar
                $this->docModel->update($nfeId, ['status' => 'rejeitada']);
                $this->dispatchWebhook('nfe.rejected', [
                    'nfe_id' => $result['nfe_id'] ?? null, 'order_id' => $orderId, 'message' => $result['message'] ?? '', 'retry_of' => $nfeId,
                ]);
            }

            $this->json($result);
        } catch (\Throwable $e) {
            $this->docModel->update($nfeId, ['status' => 'rejeitada']);
            Log::error('NfeDocumentController: retry', ['exception' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => 'Erro interno ao reenviar NF-e. Tente novamente.']);
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
            $this->json(['success' => false, 'message' => 'Números inválidos. O número inicial deve ser menor ou igual ao final.']);}
        if (strlen(trim($justificativa)) < 15) {
            $this->json(['success' => false, 'message' => 'Justificativa deve ter pelo menos 15 caracteres.']);}
        if (!in_array($modelo, [55, 65])) {
            $this->json(['success' => false, 'message' => 'Modelo inválido.']);}

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

                $this->json(['success' => true, 'message' => $result['message'] ?? "Numeração {$numInicial} a {$numFinal} inutilizada com sucesso."]);
            } else {
                $this->json(['success' => false, 'message' => $result['message'] ?? 'Erro ao inutilizar numeração na SEFAZ.']);
            }
        } catch (\Throwable $e) {
            Log::error('NfeDocumentController: Inutilizar', ['exception' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => 'Erro interno ao processar inutilização. Tente novamente.']);
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
            $this->json(['success' => false, 'message' => "Aguarde {$rateCheck['retry_after']} segundo(s) entre emissões."]);}

        $orderId = Input::post('order_id', 'int', 0);
        if ($orderId <= 0) {
            $this->json(['success' => false, 'message' => 'ID do pedido inválido.']);}

        try {
            // Montar dados via service
            $orderDataService = new NfeOrderDataService($this->db);
            $orderData = $orderDataService->buildNfceData($orderId);

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
                    'nfe_id' => $result['nfe_id'] ?? null, 'order_id' => $orderId, 'chave' => $result['chave'] ?? '',
                ]);
            }

            $this->json($result);
        } catch (\Throwable $e) {
            Log::error('NfeDocumentController: emitNfce', ['exception' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => 'Erro interno ao emitir NFC-e. Tente novamente.']);
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

        $this->json(['success' => true, 'data' => $status]);}

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

        $this->json($result);}

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

        $this->json($result);}

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

        $this->json($result);}

    /**
     * Retorna histórico de contingências (JSON).
     * FASE5-02
     */
    public function contingencyHistory(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $contingency = new NfeContingencyService($this->db);
        $history = $contingency->getHistory();

        $this->json(['success' => true, 'data' => $history]);}

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

        $idsRaw    = Input::post('ids', 'string', '') ?: Input::get('ids', 'string', '');
        $startDate = Input::get('start_date', 'string', '');
        $endDate   = Input::get('end_date', 'string', '');

        $batchService = new NfeBatchDownloadService($this->db);

        if (!empty($idsRaw)) {
            $ids = array_filter(array_map('intval', explode(',', $idsRaw)));
            if (empty($ids)) {
                $_SESSION['flash_error'] = 'Nenhuma NF-e selecionada.';
                header('Location: ?page=nfe_documents');
                exit;
            }
            $docs = $batchService->fetchByIds($ids);
        } elseif (!empty($startDate) && !empty($endDate)) {
            $docs = $batchService->fetchByPeriod($startDate, $endDate);
        } else {
            $_SESSION['flash_error'] = 'Informe IDs ou período para download em lote.';
            header('Location: ?page=nfe_documents');
            exit;
        }

        $result = $batchService->buildZip($docs);

        if (!$result['success']) {
            $_SESSION['flash_error'] = $result['message'];
            header('Location: ?page=nfe_documents');
            exit;
        }

        // Auditoria
        $this->getAuditService()->record('download_batch', 'nfe_document', null,
            "Download em lote: {$result['addedCount']} XML(s) de {$result['docCount']} NF-e(s)");

        $batchService->sendZip($result['tmpZip'], $result['zipFilename']);
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
            $fiscalService = new NfeFiscalReportService($this->db);
            $content = $fiscalService->generateSped($startDate, $endDate);

            if ($content === null) {
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
            Log::error('NfeDocumentController: exportSped', ['exception' => $e->getMessage()]);
            $_SESSION['flash_error'] = 'Erro interno ao gerar SPED Fiscal. Tente novamente.';
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
            $fiscalService = new NfeFiscalReportService($this->db);
            $content = $fiscalService->generateSintegra($startDate, $endDate);

            if ($content === null) {
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
            Log::error('NfeDocumentController: exportSintegra', ['exception' => $e->getMessage()]);
            $_SESSION['flash_error'] = 'Erro interno ao gerar SINTEGRA. Tente novamente.';
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

        $startDate = Input::get('start_date', 'string', date('Y-m-01'));
        $endDate   = Input::get('end_date', 'string', date('Y-m-d'));

        $fiscalService = new NfeFiscalReportService($this->db);
        $data = $fiscalService->getLivroSaidasData($startDate, $endDate);
        extract($data);

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

        $startDate = Input::get('start_date', 'string', date('Y-m-01'));
        $endDate   = Input::get('end_date', 'string', date('Y-m-d'));

        $fiscalService = new NfeFiscalReportService($this->db);
        $data = $fiscalService->getLivroEntradasData($startDate, $endDate);
        extract($data);

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

        try {
            $backupMgmt = new NfeBackupManagementService($this->db);
            $result = $backupMgmt->executeBackup($startDate, $endDate, $tipo);

            if ($result['success']) {
                $this->getAuditService()->record('backup_xml', 'nfe_backup', $result['backup_id'] ?? null,
                    "Backup XML: {$result['total']} arquivo(s), tipo={$tipo}");
            }

            $this->json($result);
        } catch (\Throwable $e) {
            Log::error('NfeDocumentController: backupXml', ['exception' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => 'Erro interno ao gerar backup XML. Tente novamente.']);
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

        $backupMgmt = new NfeBackupManagementService($this->db);
        $history = $backupMgmt->getHistory();

        $this->json(['success' => true, 'data' => $history]);}

    /**
     * Página de configuração de backup e relatórios fiscais.
     * FASE5-08
     */
    public function backupSettings(): void
    {
        $this->getAuditService()->record('view', 'nfe_backup_settings', null, 'Acessou configurações de backup');

        $backupMgmt = new NfeBackupManagementService($this->db);
        $backupHistory = $backupMgmt->getHistory(20);
        $backupConfig  = $backupMgmt->loadConfig();

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
            $backupMgmt = new NfeBackupManagementService($this->db);
            $backupMgmt->saveConfig($configs);

            $this->getAuditService()->record('backup_settings', 'nfe_backup', null, 'Configurações de backup atualizadas');
            $_SESSION['flash_success'] = 'Configurações de backup salvas!';
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Erro interno ao salvar configurações. Tente novamente.';
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
            Log::error('NfeDocumentController: Webhook dispatch', ['exception' => $e->getMessage()]);
        }
    }
}
