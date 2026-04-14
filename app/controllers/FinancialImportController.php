<?php
namespace Akti\Controllers;

use Akti\Models\Financial;
use Akti\Core\ModuleBootloader;
use Akti\Core\Log;
use Akti\Services\FinancialImportService;
use Akti\Utils\Input;

/**
 * FinancialImportController — Controller dedicado a importação financeira (OFX/CSV/Excel).
 *
 * Extraído do FinancialController (God Controller) na Fase 2
 * para responsabilidade única e manutenibilidade.
 *
 * Ações:
 *   - parseFile()          → AJAX: preview de arquivo de importação
 *   - importCsv()          → AJAX: importar CSV/Excel mapeado
 *   - importOfxSelected()  → AJAX: importar transações OFX selecionadas
 *   - importOfx()          → AJAX: importar OFX direto (modo legado)
 *
 * @package Akti\Controllers
 */
class FinancialImportController extends BaseController {
    private FinancialImportService $importService;

    public function __construct(\PDO $db, FinancialImportService $importService)
    {
        if (!ModuleBootloader::isModuleEnabled('financial')) {
            http_response_code(403);
            header('Content-Type: application/json');
            $this->json(['success' => false, 'message' => 'Módulo financeiro desativado.']);}

        $this->db = $db;
        $this->importService = $importService;
    }

    /**
     * Campos disponíveis para mapeamento de importação financeira.
     */
    public static function getFinancialImportFields(): array
    {
        return FinancialImportService::getFinancialImportFields();
    }

    // ═══════════════════════════════════════════
    // AJAX: Preview de arquivo de importação
    // ═══════════════════════════════════════════

    public function parseFile()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido.']);}

        if (empty($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['success' => false, 'message' => 'Nenhum arquivo enviado.']);}

        $file = $_FILES['import_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Validação MIME por magic bytes (SEC-006)
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowedMimes = ['text/plain', 'text/csv', 'application/csv', 'application/octet-stream',
                         'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        if (!in_array($mime, $allowedMimes)) {
            $this->json(['success' => false, 'message' => 'Tipo de arquivo não permitido.']);}

        if ($ext === 'ofx' || $ext === 'ofc') {
            $content = file_get_contents($file['tmp_name']);
            $result = $this->importService->parseOfx($content);

            if ($result['success']) {
                $this->importService->saveImportTmpFile($file['tmp_name'], $ext);
            }

            $this->json($result);

        } elseif (in_array($ext, ['csv', 'txt'])) {
            $this->importService->saveImportTmpFile($file['tmp_name'], $ext);
            $this->json($this->importService->parseCsv($file['tmp_name']));

        } elseif (in_array($ext, ['xls', 'xlsx'])) {
            $this->importService->saveImportTmpFile($file['tmp_name'], $ext);
            $this->json($this->importService->parseExcel($file['tmp_name']));

        } else {
            $this->json(['success' => false, 'message' => 'Formato não suportado. Envie OFX, CSV, TXT, XLS ou XLSX.']);
        }
        exit;
    }

    // ═══════════════════════════════════════════
    // AJAX: Importar CSV/Excel mapeado
    // ═══════════════════════════════════════════

    public function importCsv()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido.']);}

        // Determinar origem do arquivo
        $filePath = null;
        $ext = 'csv';

        if (!empty($_FILES['import_file']) && $_FILES['import_file']['error'] === UPLOAD_ERR_OK) {
            $filePath = $_FILES['import_file']['tmp_name'];
            $ext = strtolower(pathinfo($_FILES['import_file']['name'], PATHINFO_EXTENSION));
        } elseif (!empty($_SESSION['fin_import_tmp_file']) && file_exists($_SESSION['fin_import_tmp_file'])) {
            $filePath = $_SESSION['fin_import_tmp_file'];
            $ext = $_SESSION['fin_import_tmp_ext'] ?? 'csv';
        }

        if (!$filePath) {
            $this->json(['success' => false, 'message' => 'Nenhum arquivo enviado ou arquivo temporário expirado.']);}

        $mode = Input::post('import_mode', 'enum', 'registro', ['registro', 'contabilizar']);
        $userId = $_SESSION['user_id'] ?? null;

        // Parse mapping
        $mapping = [];
        if (!empty($_POST['mapping'])) {
            $mapping = is_string($_POST['mapping']) ? json_decode($_POST['mapping'], true) : $_POST['mapping'];
        }

        // Parse selected rows
        $selectedRows = [];
        if (!empty($_POST['selected_rows'])) {
            $selectedRows = is_string($_POST['selected_rows']) ? json_decode($_POST['selected_rows'], true) : $_POST['selected_rows'];
        }

        $selectedRows = array_map('intval', $selectedRows);

        // Re-parse o arquivo para obter as linhas
        $rows = [];
        if (in_array($ext, ['xls', 'xlsx']) && class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
            $parseResult = $this->importService->parseExcel($filePath);
            $rows = $parseResult['rows'] ?? [];
        } else {
            $parseResult = $this->importService->parseCsv($filePath);
            $rows = $parseResult['rows'] ?? [];
        }

        if (empty($rows) || empty($selectedRows)) {
            $this->json(['success' => false, 'message' => 'Nenhuma linha selecionada para importação.']);}

        // Resolve column-name→field mapping to column-index→field mapping
        $headers = array_map(function ($h) { return trim($h); }, $rows[0] ?? []);
        $resolvedMapping = [];

        $isNameBased = false;
        foreach ($mapping as $key => $val) {
            if (!is_numeric($key)) {
                $isNameBased = true;
                break;
            }
        }

        if ($isNameBased) {
            foreach ($mapping as $colName => $fieldKey) {
                $colIdx = array_search($colName, $headers);
                if ($colIdx !== false && $fieldKey !== '_skip') {
                    $resolvedMapping[$fieldKey] = $colIdx;
                }
            }
        } else {
            $resolvedMapping = $mapping;
        }

        try {
            $result = $this->importService->importCsvMapped($rows, $resolvedMapping, $selectedRows, $mode, $userId);
        } catch (\Exception $e) {
            Log::error('FinancialImportController: importCsv', ['exception' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => 'Erro interno na importação. Tente novamente.']);}

        $modeLabel = $mode === 'registro' ? 'apenas registro (não contabilizado)' : 'contabilizado no caixa';
        $this->json([
            'success'  => true,
            'imported' => $result['imported'],
            'skipped'  => $result['skipped'],
            'errors'   => $result['errors'],
            'message'  => sprintf(
                'Importação concluída! %d transação(ões) importada(s) como %s.%s',
                $result['imported'],
                $modeLabel,
                !empty($result['errors']) ? ' Erros: ' . count($result['errors']) : ''
            )
        ]);}

    // ═══════════════════════════════════════════
    // AJAX: Importar transações OFX selecionadas
    // ═══════════════════════════════════════════

    public function importOfxSelected()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido.']);}

        $mode = Input::post('import_mode', 'enum', 'registro', ['registro', 'contabilizar']);
        $userId = $_SESSION['user_id'] ?? null;

        $selectedIndexes = [];
        if (!empty($_POST['selected_rows'])) {
            $raw = is_string($_POST['selected_rows']) ? json_decode($_POST['selected_rows'], true) : $_POST['selected_rows'];
            $selectedIndexes = array_map('intval', $raw ?: []);
        }

        $result = $this->importService->importOfxSelected($selectedIndexes, $mode, $userId);
        $this->json($result);}

    // ═══════════════════════════════════════════
    // AJAX: Importar OFX direto (modo legado)
    // ═══════════════════════════════════════════

    public function importOfx()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido.']);}

        if (empty($_FILES['ofx_file']) || $_FILES['ofx_file']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['success' => false, 'message' => 'Nenhum arquivo OFX enviado.']);}

        $file = $_FILES['ofx_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['ofx', 'ofc'])) {
            $this->json(['success' => false, 'message' => 'Formato não suportado. Envie um arquivo .OFX']);}

        $mode = Input::post('import_mode', 'enum', 'registro', ['registro', 'contabilizar']);
        $userId = $_SESSION['user_id'] ?? null;

        try {
            $result = $this->importService->importOfxDirect($file['tmp_name'], $mode, $userId);

            $modeLabel = $mode === 'registro' ? 'apenas registro (não contabilizado)' : 'contabilizado no caixa';
            $this->json([
                'success'  => true,
                'imported' => $result['imported'],
                'skipped'  => $result['skipped'],
                'errors'   => $result['errors'],
                'message'  => sprintf(
                    'Importação concluída! %d transação(ões) importada(s) como %s.%s',
                    $result['imported'],
                    $modeLabel,
                    !empty($result['errors']) ? ' Erros: ' . count($result['errors']) : ''
                )
            ]);
        } catch (\Exception $e) {
            Log::error('FinancialImportController: validateRows', ['exception' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => 'Erro interno na importação. Tente novamente.']);
        }
        exit;
    }
}
