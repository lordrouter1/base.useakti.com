<?php
namespace Akti\Controllers;

use Akti\Models\Financial;
use Akti\Core\ModuleBootloader;
use Akti\Services\FinancialImportService;
use Akti\Utils\Input;
use Database;
use PDO;

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
class FinancialImportController
{
    private PDO $db;
    private FinancialImportService $importService;

    public function __construct()
    {
        if (!ModuleBootloader::isModuleEnabled('financial')) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Módulo financeiro desativado.']);
            exit;
        }

        $database = new Database();
        $this->db = $database->getConnection();

        $financial = new Financial($this->db);
        $this->importService = new FinancialImportService($this->db, $financial);
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
            echo json_encode(['success' => false, 'message' => 'Método inválido.']);
            exit;
        }

        if (empty($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Nenhum arquivo enviado.']);
            exit;
        }

        $file = $_FILES['import_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($ext === 'ofx' || $ext === 'ofc') {
            $content = file_get_contents($file['tmp_name']);
            $result = $this->importService->parseOfx($content);

            if ($result['success']) {
                $this->importService->saveImportTmpFile($file['tmp_name'], $ext);
            }

            echo json_encode($result);

        } elseif (in_array($ext, ['csv', 'txt'])) {
            $this->importService->saveImportTmpFile($file['tmp_name'], $ext);
            echo json_encode($this->importService->parseCsv($file['tmp_name']));

        } elseif (in_array($ext, ['xls', 'xlsx'])) {
            $this->importService->saveImportTmpFile($file['tmp_name'], $ext);
            echo json_encode($this->importService->parseExcel($file['tmp_name']));

        } else {
            echo json_encode(['success' => false, 'message' => 'Formato não suportado. Envie OFX, CSV, TXT, XLS ou XLSX.']);
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
            echo json_encode(['success' => false, 'message' => 'Método inválido.']);
            exit;
        }

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
            echo json_encode(['success' => false, 'message' => 'Nenhum arquivo enviado ou arquivo temporário expirado.']);
            exit;
        }

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
            echo json_encode(['success' => false, 'message' => 'Nenhuma linha selecionada para importação.']);
            exit;
        }

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
            echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
            exit;
        }

        $modeLabel = $mode === 'registro' ? 'apenas registro (não contabilizado)' : 'contabilizado no caixa';
        echo json_encode([
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
        exit;
    }

    // ═══════════════════════════════════════════
    // AJAX: Importar transações OFX selecionadas
    // ═══════════════════════════════════════════

    public function importOfxSelected()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método inválido.']);
            exit;
        }

        $mode = Input::post('import_mode', 'enum', 'registro', ['registro', 'contabilizar']);
        $userId = $_SESSION['user_id'] ?? null;

        $selectedIndexes = [];
        if (!empty($_POST['selected_rows'])) {
            $raw = is_string($_POST['selected_rows']) ? json_decode($_POST['selected_rows'], true) : $_POST['selected_rows'];
            $selectedIndexes = array_map('intval', $raw ?: []);
        }

        $result = $this->importService->importOfxSelected($selectedIndexes, $mode, $userId);
        echo json_encode($result);
        exit;
    }

    // ═══════════════════════════════════════════
    // AJAX: Importar OFX direto (modo legado)
    // ═══════════════════════════════════════════

    public function importOfx()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método inválido.']);
            exit;
        }

        if (empty($_FILES['ofx_file']) || $_FILES['ofx_file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Nenhum arquivo OFX enviado.']);
            exit;
        }

        $file = $_FILES['ofx_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['ofx', 'ofc'])) {
            echo json_encode(['success' => false, 'message' => 'Formato não suportado. Envie um arquivo .OFX']);
            exit;
        }

        $mode = Input::post('import_mode', 'enum', 'registro', ['registro', 'contabilizar']);
        $userId = $_SESSION['user_id'] ?? null;

        try {
            $result = $this->importService->importOfxDirect($file['tmp_name'], $mode, $userId);

            $modeLabel = $mode === 'registro' ? 'apenas registro (não contabilizado)' : 'contabilizado no caixa';
            echo json_encode([
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
            echo json_encode(['success' => false, 'message' => 'Erro na importação: ' . $e->getMessage()]);
        }
        exit;
    }
}
