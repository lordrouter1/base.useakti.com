<?php
namespace Akti\Services;

use Akti\Core\Log;

use Akti\Models\Financial;
use PDO;

/**
 * FinancialImportService — Camada de Serviço para Importação Financeira (OFX/CSV/Excel).
 *
 * Extrai toda a lógica de parsing e importação do Controller,
 * mantendo o Controller slim e focado em HTTP/resposta.
 *
 * @package Akti\Services
 */
class FinancialImportService
{
    private Financial $financial;
    private PDO $db;

    public function __construct(PDO $db, Financial $financial)
    {
        $this->db = $db;
        $this->financial = $financial;
    }

    // ═══════════════════════════════════════════
    // PARSE / PREVIEW
    // ═══════════════════════════════════════════

    /**
     * Parse de arquivo OFX/OFC — retorna transações para preview.
     * @param string $content Conteúdo do arquivo
     * @return array ['success' => bool, 'file_type' => string, 'rows' => array, ...]
     */
    public function parseOfx(string $content): array
    {
        $transactions = $this->financial->parseOfxTransactions($content);

        if (empty($transactions)) {
            return ['success' => false, 'message' => 'Nenhuma transação encontrada no arquivo OFX. Verifique o formato.'];
        }

        $rows = [];
        foreach ($transactions as $i => $tx) {
            $displayDate = $tx['date'] ?? '';
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $displayDate, $dm)) {
                $displayDate = $dm[3] . '/' . $dm[2] . '/' . $dm[1];
            }
            $rows[] = [
                'index'       => $i,
                'date'        => $displayDate,
                'description' => $tx['memo'] ?? '',
                'amount'      => (float) ($tx['amount'] ?? 0),
                'type'        => ((float) ($tx['amount'] ?? 0) >= 0) ? 'Crédito' : 'Débito',
                'fitid'       => $tx['fitid'] ?? '',
            ];
        }

        return [
            'success'    => true,
            'file_type'  => 'ofx',
            'rows'       => $rows,
            'total_rows' => count($rows),
            'columns'    => ['date', 'description', 'amount', 'type', 'fitid'],
        ];
    }

    /**
     * Parse de arquivo CSV/TXT — retorna dados estruturados.
     * @param string $filePath Caminho do arquivo
     * @return array
     */
    public function parseCsv(string $filePath): array
    {
        $rows = [];
        $headers = [];
        $handle = fopen($filePath, 'r');

        if (!$handle) {
            return ['success' => false, 'message' => 'Não foi possível abrir o arquivo.'];
        }

        // Detect BOM and skip it
        $bom = fread($handle, 3);
        if ($bom !== chr(0xEF) . chr(0xBB) . chr(0xBF)) {
            rewind($handle);
        }

        $separator = ',';
        $firstLine = fgets($handle);
        rewind($handle);
        // Skip BOM again
        $bom2 = fread($handle, 3);
        if ($bom2 !== chr(0xEF) . chr(0xBB) . chr(0xBF)) {
            rewind($handle);
        }

        // Auto-detect separator
        if (substr_count($firstLine, ';') > substr_count($firstLine, ',')) {
            $separator = ';';
        } elseif (substr_count($firstLine, "\t") > substr_count($firstLine, ',')) {
            $separator = "\t";
        }

        $lineNum = 0;
        while (($data = fgetcsv($handle, 0, $separator)) !== false) {
            if ($lineNum === 0) {
                $headers = array_map(function ($h) { return trim($h); }, $data);
            }
            $rows[] = $data;
            $lineNum++;
            if ($lineNum > 500) break;
        }
        fclose($handle);

        if (empty($rows)) {
            return ['success' => false, 'message' => 'Arquivo vazio ou não foi possível ler os dados.'];
        }

        return $this->buildCsvParseResponse($headers, $rows);
    }

    /**
     * Parse de arquivo Excel (XLS/XLSX) via PhpSpreadsheet.
     * @param string $filePath
     * @return array
     */
    public function parseExcel(string $filePath): array
    {
        if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
            return ['success' => false, 'message' => 'Biblioteca PhpSpreadsheet não está disponível para ler arquivos Excel.'];
        }

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = [];
            $headers = [];

            foreach ($worksheet->getRowIterator() as $rowIndex => $row) {
                $cells = [];
                foreach ($row->getCellIterator() as $cell) {
                    $cells[] = (string) $cell->getValue();
                }

                if ($rowIndex === 1) {
                    $headers = array_map(function ($h) { return trim((string) $h); }, $cells);
                }

                if (count(array_filter($cells, function ($v) { return trim((string) $v) !== ''; })) === 0) continue;

                $rows[] = $cells;
                if (count($rows) > 500) break;
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Erro ao ler arquivo Excel: ' . $e->getMessage()];
        }

        if (empty($rows)) {
            return ['success' => false, 'message' => 'Arquivo Excel vazio ou não foi possível ler os dados.'];
        }

        return $this->buildCsvParseResponse($headers, $rows);
    }

    /**
     * Salva o arquivo de importação em diretório temporário para reutilizar.
     * @param string $tmpName Caminho do arquivo temporário original
     * @param string $ext Extensão do arquivo
     */
    public function saveImportTmpFile(string $tmpName, string $ext): void
    {
        $tmpDir = sys_get_temp_dir() . '/akti_imports/';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }
        $tmpFileName = 'fin_import_' . session_id() . '_' . time() . '.' . $ext;
        $tmpPath = $tmpDir . $tmpFileName;
        copy($tmpName, $tmpPath);
        $_SESSION['fin_import_tmp_file'] = $tmpPath;
        $_SESSION['fin_import_tmp_ext'] = $ext;
    }

    // ═══════════════════════════════════════════
    // IMPORTAÇÃO
    // ═══════════════════════════════════════════

    /**
     * Importa transações OFX de linhas selecionadas.
     * Inclui controle de duplicidade via tabela ofx_imported_transactions.
     *
     * @param array $selectedIndexes Índices das transações selecionadas
     * @param string $mode 'registro' ou 'contabilizar'
     * @param int|null $userId
     * @return array Resultado da importação
     */
    public function importOfxSelected(array $selectedIndexes, string $mode, ?int $userId): array
    {
        $tmpPath = $_SESSION['fin_import_tmp_file'] ?? null;
        $tmpExt = $_SESSION['fin_import_tmp_ext'] ?? '';

        if (!$tmpPath || !file_exists($tmpPath)) {
            return ['success' => false, 'message' => 'Arquivo temporário não encontrado. Faça o upload novamente.'];
        }

        $content = file_get_contents($tmpPath);
        $transactions = $this->financial->parseOfxTransactions($content);

        if (empty($transactions)) {
            return ['success' => false, 'message' => 'Nenhuma transação encontrada no arquivo OFX.'];
        }

        $hasDuplicityControl = $this->hasOfxDuplicityTable();
        $bankAccount = $this->extractBankAccount($content);
        $result = ['imported' => 0, 'skipped' => 0, 'duplicates' => 0, 'errors' => []];

        foreach ($selectedIndexes as $idx) {
            if (!isset($transactions[$idx])) {
                $result['skipped']++;
                continue;
            }
            $tx = $transactions[$idx];

            try {
                $amount = abs((float) $tx['amount']);
                if ($amount <= 0) {
                    $result['skipped']++;
                    continue;
                }

                $fitid = $tx['fitid'] ?? '';

                // Verificar duplicidade via FITID
                if ($hasDuplicityControl && $fitid) {
                    if ($this->isOfxTransactionImported($fitid, $bankAccount)) {
                        $result['duplicates']++;
                        $result['skipped']++;
                        continue;
                    }
                }

                $isCredit = (float) $tx['amount'] > 0;

                $data = [
                    'type'             => ($mode === 'registro') ? 'registro' : ($isCredit ? 'entrada' : 'saida'),
                    'category'         => ($mode === 'registro') ? 'registro_ofx' : ($isCredit ? 'outra_entrada' : 'outra_saida'),
                    'description'      => $tx['memo'] ?: ($isCredit ? 'Crédito OFX' : 'Débito OFX'),
                    'amount'           => $amount,
                    'transaction_date' => $tx['date'],
                    'reference_type'   => 'ofx',
                    'payment_method'   => 'transferencia',
                    'is_confirmed'     => 1,
                    'user_id'          => $userId,
                    'notes'            => 'Importado via OFX (' . ($mode === 'registro' ? 'registro' : 'contabilizado') . ') — FITID: ' . $fitid,
                ];

                $this->financial->addTransaction($data);
                $transactionId = (int) $this->db->lastInsertId();

                // Registrar FITID na tabela de controle
                if ($hasDuplicityControl && $fitid) {
                    $this->registerOfxTransaction($fitid, $bankAccount, $tx['date'], $amount, $tx['memo'] ?? '', $transactionId);
                }

                $result['imported']++;
            } catch (\Exception $e) {
                $result['errors'][] = "Transação $idx: " . $e->getMessage();
            }
        }

        $modeLabel = ($mode === 'registro') ? 'apenas registro (não contabilizado)' : 'contabilizado no caixa';
        $result['success'] = true;

        $parts = [];
        $parts[] = $result['imported'] . ' transação(ões) importada(s) como ' . $modeLabel;
        if ($result['duplicates'] > 0) {
            $parts[] = $result['duplicates'] . ' duplicada(s) ignorada(s)';
        }
        if (!empty($result['errors'])) {
            $parts[] = count($result['errors']) . ' erro(s)';
        }

        $result['message'] = 'Importação concluída! ' . implode('. ', $parts) . '.';

        return $result;
    }

    /**
     * Importa transações CSV/Excel mapeado.
     * @param array $rows Linhas do arquivo
     * @param array $mapping Mapeamento de colunas
     * @param array $selectedRows Índices selecionados
     * @param string $mode 'registro' ou 'contabilizar'
     * @param int|null $userId
     * @return array Resultado
     */
    public function importCsvMapped(array $rows, array $mapping, array $selectedRows, string $mode, ?int $userId): array
    {
        return $this->financial->importCsvMapped($rows, $mapping, $selectedRows, $mode, $userId);
    }

    /**
     * Importa OFX diretamente (sem seleção de linhas — modo legacy).
     * @param string $filePath
     * @param string $mode
     * @param int|null $userId
     * @return array
     */
    public function importOfxDirect(string $filePath, string $mode, ?int $userId): array
    {
        return $this->financial->importOfx($filePath, $mode, $userId);
    }

    // ═══════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════

    /**
     * Campos disponíveis para mapeamento de importação financeira.
     * @return array
     */
    public static function getFinancialImportFields(): array
    {
        return [
            'date' => [
                'label'    => 'Data',
                'required' => true,
                'keywords' => ['data', 'date', 'vencimento', 'dt_lanc', 'dt_mov', 'dt_pag'],
            ],
            'description' => [
                'label'    => 'Descrição',
                'required' => true,
                'keywords' => ['descri', 'memo', 'histor', 'lanc', 'observ', 'detail'],
            ],
            'amount' => [
                'label'    => 'Valor',
                'required' => true,
                'keywords' => ['valor', 'amount', 'quantia', 'total', 'vlr', 'montante'],
            ],
            'type' => [
                'label'    => 'Tipo (Entrada/Saída)',
                'required' => false,
                'keywords' => ['tipo', 'type', 'natureza', 'd/c', 'dc', 'deb_cred'],
            ],
            'category' => [
                'label'    => 'Categoria',
                'required' => false,
                'keywords' => ['categoria', 'category', 'classificacao', 'grupo'],
            ],
            'payment_method' => [
                'label'    => 'Método de Pagamento',
                'required' => false,
                'keywords' => ['metodo', 'method', 'forma', 'pagamento', 'meio'],
            ],
            'notes' => [
                'label'    => 'Observações',
                'required' => false,
                'keywords' => ['obs', 'nota', 'notes', 'complemento', 'info'],
            ],
        ];
    }

    /**
     * Monta resposta estruturada do parse CSV/Excel.
     * @param array $headers
     * @param array $rows
     * @return array
     */
    private function buildCsvParseResponse(array $headers, array $rows): array
    {
        $columns = !empty($headers) ? $headers : array_map(function ($i) { return 'Coluna ' . ($i + 1); }, array_keys($rows[0] ?? []));

        $dataRows = array_slice($rows, 1);
        $totalRows = count($dataRows);

        $preview = [];
        for ($i = 0; $i < min(10, count($dataRows)); $i++) {
            $obj = [];
            foreach ($columns as $idx => $col) {
                $obj[$col] = isset($dataRows[$i][$idx]) ? $dataRows[$i][$idx] : '';
            }
            $preview[] = $obj;
        }

        // Auto-mapping heurístico
        $autoMapping = [];
        $financialFields = self::getFinancialImportFields();
        foreach ($columns as $col) {
            $lower = mb_strtolower(trim($col));
            $matched = '';
            foreach ($financialFields as $fieldKey => $fieldInfo) {
                $keywords = $fieldInfo['keywords'] ?? [];
                foreach ($keywords as $kw) {
                    if (mb_strpos($lower, $kw) !== false) {
                        $matched = $fieldKey;
                        break 2;
                    }
                }
            }
            if ($matched) {
                $autoMapping[$col] = $matched;
            }
        }

        return [
            'success'      => true,
            'file_type'    => 'csv',
            'columns'      => $columns,
            'headers'      => $headers,
            'rows'         => $rows,
            'preview'      => $preview,
            'auto_mapping' => $autoMapping,
            'total_rows'   => $totalRows,
        ];
    }

    // ═══════════════════════════════════════════
    // OFX DUPLICITY CONTROL
    // ═══════════════════════════════════════════

    /** @var bool|null Cache: tabela ofx_imported_transactions existe? */
    private static ?bool $ofxTableExists = null;

    /**
     * Verifica se a tabela ofx_imported_transactions existe.
     */
    private function hasOfxDuplicityTable(): bool
    {
        if (self::$ofxTableExists !== null) {
            return self::$ofxTableExists;
        }

        try {
            $stmt = $this->db->query("SHOW TABLES LIKE 'ofx_imported_transactions'");
            self::$ofxTableExists = ($stmt->rowCount() > 0);
        } catch (\PDOException $e) {
            self::$ofxTableExists = false;
        }

        return self::$ofxTableExists;
    }

    /**
     * Verifica se uma transação OFX já foi importada (por FITID + conta bancária).
     *
     * @param string $fitid
     * @param string|null $bankAccount
     * @return bool
     */
    private function isOfxTransactionImported(string $fitid, ?string $bankAccount): bool
    {
        try {
            if ($bankAccount) {
                $q = "SELECT COUNT(*) FROM ofx_imported_transactions WHERE fitid = :fitid AND bank_account = :acc";
                $s = $this->db->prepare($q);
                $s->execute([':fitid' => $fitid, ':acc' => $bankAccount]);
            } else {
                $q = "SELECT COUNT(*) FROM ofx_imported_transactions WHERE fitid = :fitid";
                $s = $this->db->prepare($q);
                $s->execute([':fitid' => $fitid]);
            }
            return (int) $s->fetchColumn() > 0;
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Registra transação OFX importada na tabela de controle de duplicidade.
     *
     * @param string $fitid
     * @param string|null $bankAccount
     * @param string $date
     * @param float $amount
     * @param string $description
     * @param int|null $transactionId
     */
    private function registerOfxTransaction(string $fitid, ?string $bankAccount, string $date, float $amount, string $description, ?int $transactionId): void
    {
        try {
            $q = "INSERT IGNORE INTO ofx_imported_transactions (fitid, bank_account, transaction_date, amount, description, financial_transaction_id)
                  VALUES (:fitid, :acc, :date, :amount, :desc, :tx_id)";
            $s = $this->db->prepare($q);
            $s->execute([
                ':fitid'  => $fitid,
                ':acc'    => $bankAccount,
                ':date'   => $date,
                ':amount' => $amount,
                ':desc'   => mb_substr($description, 0, 500),
                ':tx_id'  => $transactionId,
            ]);
        } catch (\PDOException $e) {
            Log::error('FinancialImport: Erro ao registrar FITID', ['exception' => $e->getMessage()]);
        }
    }

    /**
     * Extrai número da conta bancária do conteúdo OFX.
     *
     * @param string $content
     * @return string|null
     */
    private function extractBankAccount(string $content): ?string
    {
        // Tentar extrair ACCTID do OFX
        if (preg_match('/<ACCTID>(.*?)(?:<|\n)/i', $content, $m)) {
            $account = trim($m[1]);
            return !empty($account) ? $account : null;
        }
        return null;
    }
}
