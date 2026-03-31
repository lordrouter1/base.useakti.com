<?php
namespace Akti\Services;

use Akti\Models\Product;
use Akti\Models\Category;
use Akti\Models\Subcategory;
use Akti\Models\Logger;
use PDO;
use TenantManager;

/**
 * Service responsável por toda lógica de importação de produtos.
 * Extraído do ProductController (Fase 2 — Refatoração de Controllers Monolíticos).
 */
class ProductImportService
{
    private $db;
    private $productModel;
    private $categoryModel;
    private $subcategoryModel;
    private $logger;

    public function __construct(PDO $db, Product $productModel, Category $categoryModel, Subcategory $subcategoryModel, Logger $logger)
    {
        $this->db = $db;
        $this->productModel = $productModel;
        $this->categoryModel = $categoryModel;
        $this->subcategoryModel = $subcategoryModel;
        $this->logger = $logger;
    }

    /**
     * Analisa arquivo de importação (CSV/XLS/XLSX) e retorna preview sem efetuar importação.
     *
     * @param array $file $_FILES['import_file']
     * @return array Resultado com columns, preview, total_rows, auto_mapping
     */
    public function parseImportFile(array $file): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Erro no upload do arquivo.'];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'xls', 'xlsx'])) {
            return ['success' => false, 'message' => 'Formato não suportado. Use CSV, XLS ou XLSX.'];
        }

        $rows = $this->readFileRows($file['tmp_name'], $ext);

        if (empty($rows)) {
            return ['success' => false, 'message' => 'Arquivo vazio ou não foi possível ler os dados.'];
        }

        // Salvar arquivo temporário
        $tmpDir = sys_get_temp_dir() . '/akti_imports/';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }
        $tmpName = 'import_' . session_id() . '_' . time() . '.' . $ext;
        $tmpPath = $tmpDir . $tmpName;
        move_uploaded_file($file['tmp_name'], $tmpPath);
        $_SESSION['import_tmp_file'] = $tmpPath;
        $_SESSION['import_tmp_ext'] = $ext;

        $columns = !empty($rows) ? array_keys($rows[0]) : [];
        $preview = array_slice($rows, 0, 10);
        $totalRows = count($rows);
        $autoMapping = $this->autoMapColumns($columns);

        return [
            'success'      => true,
            'columns'      => $columns,
            'preview'      => $preview,
            'total_rows'   => $totalRows,
            'auto_mapping' => $autoMapping,
        ];
    }

    /**
     * Importa produtos usando mapeamento de colunas definido pelo usuário.
     *
     * @param array $mapping Mapeamento file_column => system_field
     * @return array Resultado com imported, errors
     */
    public function importProductsMapped(array $mapping): array
    {
        $mappedFields = array_values($mapping);
        if (!in_array('name', $mappedFields)) {
            return ['success' => false, 'message' => 'O campo "Nome do Produto" é obrigatório no mapeamento.'];
        }
        if (!in_array('price', $mappedFields)) {
            return ['success' => false, 'message' => 'O campo "Preço de Venda" é obrigatório no mapeamento.'];
        }

        $tmpPath = $_SESSION['import_tmp_file'] ?? null;
        $ext = $_SESSION['import_tmp_ext'] ?? 'csv';
        if (!$tmpPath || !file_exists($tmpPath)) {
            return ['success' => false, 'message' => 'Arquivo temporário não encontrado. Faça o upload novamente.'];
        }

        $rows = $this->readFileRows($tmpPath, $ext);
        if (empty($rows)) {
            return ['success' => false, 'message' => 'Arquivo vazio ou não foi possível reler os dados.'];
        }

        $maxProducts = TenantManager::getTenantLimit('max_products');
        $currentProducts = $this->productModel->countAll();

        $imported = 0;
        $errors = [];
        $categoryCache = [];
        $subcategoryCache = [];

        foreach ($rows as $lineNum => $row) {
            $lineDisplay = $lineNum + 2;

            $mapped = [];
            foreach ($mapping as $fileCol => $sysField) {
                if (!empty($sysField) && $sysField !== '_skip' && isset($row[$fileCol])) {
                    $mapped[$sysField] = trim($row[$fileCol]);
                }
            }

            if (empty($mapped['name'])) {
                $errors[] = ['line' => $lineDisplay, 'message' => 'Nome do produto é obrigatório.'];
                continue;
            }

            $price = isset($mapped['price']) ? str_replace(',', '.', $mapped['price']) : '';
            if ($price === '' || !is_numeric($price) || floatval($price) < 0) {
                $errors[] = ['line' => $lineDisplay, 'message' => 'Preço inválido ou não informado para "' . $mapped['name'] . '".'];
                continue;
            }

            $categoryId = $this->resolveCategory($mapped['category'] ?? '', $categoryCache);
            $subcategoryId = $this->resolveSubcategory($mapped['subcategory'] ?? '', $categoryId, $subcategoryCache);

            if ($maxProducts !== null && ($currentProducts + $imported) >= $maxProducts) {
                $errors[] = ['line' => $lineDisplay, 'message' => 'Limite de produtos atingido para este cliente.'];
                continue;
            }

            $data = [
                'name'           => $mapped['name'],
                'sku'            => $mapped['sku'] ?? '',
                'description'    => $mapped['description'] ?? '',
                'category_id'    => $categoryId,
                'subcategory_id' => $subcategoryId,
                'price'          => floatval($price),
            ];

            if (!empty($mapped['ncm'])) {
                $data['fiscal_ncm'] = $mapped['ncm'];
            }

            try {
                $productId = $this->productModel->create($data);
                if ($productId) {
                    $imported++;
                    $this->logger->log('IMPORT_PRODUCT', 'Imported product ID: ' . $productId . ' Name: ' . $data['name']);
                } else {
                    $errors[] = ['line' => $lineDisplay, 'message' => 'Erro ao salvar "' . $mapped['name'] . '" no banco de dados.'];
                }
            } catch (\Exception $e) {
                $errors[] = ['line' => $lineDisplay, 'message' => 'Erro: ' . $e->getMessage()];
            }
        }

        // Limpar arquivo temporário
        if (file_exists($tmpPath)) {
            unlink($tmpPath);
        }
        unset($_SESSION['import_tmp_file'], $_SESSION['import_tmp_ext']);

        return [
            'success'  => true,
            'imported' => $imported,
            'errors'   => $errors,
        ];
    }

    /**
     * Importa produtos diretamente (mapeamento automático por header).
     *
     * @param array $file $_FILES['import_file']
     * @return array Resultado com imported, errors
     */
    public function importProductsDirect(array $file): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Erro no upload do arquivo.'];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'xls', 'xlsx'])) {
            return ['success' => false, 'message' => 'Formato não suportado. Use CSV, XLS ou XLSX.'];
        }

        $rows = $this->readFileRows($file['tmp_name'], $ext);
        if (empty($rows)) {
            return ['success' => false, 'message' => 'Arquivo vazio ou não foi possível ler os dados.'];
        }

        $maxProducts = TenantManager::getTenantLimit('max_products');
        $currentProducts = $this->productModel->countAll();
        if ($maxProducts !== null && $currentProducts >= $maxProducts) {
            return ['success' => false, 'message' => 'Limite de produtos do cliente atingido.'];
        }

        $imported = 0;
        $errors = [];
        $categoryCache = [];
        $subcategoryCache = [];

        $colMap = $this->getColumnMap();

        foreach ($rows as $lineNum => $row) {
            $lineDisplay = $lineNum + 2;

            $mapped = [];
            foreach ($row as $key => $value) {
                $normalizedKey = $this->normalizeColumnName($key);
                if (isset($colMap[$normalizedKey])) {
                    $mapped[$colMap[$normalizedKey]] = trim($value);
                }
            }

            if (empty($mapped['name'])) {
                $errors[] = ['line' => $lineDisplay, 'message' => 'Nome do produto é obrigatório.'];
                continue;
            }

            $price = isset($mapped['price']) ? str_replace(',', '.', $mapped['price']) : '';
            if ($price === '' || !is_numeric($price) || floatval($price) < 0) {
                $errors[] = ['line' => $lineDisplay, 'message' => 'Preço inválido ou não informado para "' . $mapped['name'] . '".'];
                continue;
            }

            $categoryId = $this->resolveCategory($mapped['category'] ?? '', $categoryCache);
            $subcategoryId = $this->resolveSubcategory($mapped['subcategory'] ?? '', $categoryId, $subcategoryCache);

            if ($maxProducts !== null && ($currentProducts + $imported) >= $maxProducts) {
                $errors[] = ['line' => $lineDisplay, 'message' => 'Limite de produtos atingido para este cliente.'];
                continue;
            }

            $data = [
                'name'           => $mapped['name'],
                'description'    => $mapped['description'] ?? '',
                'category_id'    => $categoryId,
                'subcategory_id' => $subcategoryId,
                'price'          => floatval($price),
            ];

            if (!empty($mapped['fiscal_ncm'])) {
                $data['fiscal_ncm'] = $mapped['fiscal_ncm'];
            }

            try {
                $productId = $this->productModel->create($data);
                if ($productId) {
                    $imported++;
                    $this->logger->log('IMPORT_PRODUCT', 'Imported product ID: ' . $productId . ' Name: ' . $data['name']);
                } else {
                    $errors[] = ['line' => $lineDisplay, 'message' => 'Erro ao salvar "' . $mapped['name'] . '" no banco de dados.'];
                }
            } catch (\Exception $e) {
                $errors[] = ['line' => $lineDisplay, 'message' => 'Erro: ' . $e->getMessage()];
            }
        }

        return [
            'success'  => true,
            'imported' => $imported,
            'errors'   => $errors,
        ];
    }

    /**
     * Gera CSV de template para importação.
     * Retorna o conteúdo CSV.
     */
    public function generateImportTemplate(): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="modelo_importacao_produtos.csv"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($output, [
            'nome', 'preco', 'preco_custo', 'estoque', 'categoria', 'subcategoria',
            'descricao', 'formato', 'material', 'ncm'
        ], ';');

        fputcsv($output, [
            'Cartão de Visita', '49.90', '15.00', '100', 'Impressos', 'Cartões',
            'Cartão couché 300g, 4x4 cores', '9x5cm', 'Couché 300g', '49019900'
        ], ';');
        fputcsv($output, [
            'Banner Lona', '89.90', '30.00', '50', 'Grandes Formatos', '',
            'Impressão digital em lona 440g', '1x0.5m', 'Lona 440g', ''
        ], ';');

        fclose($output);
    }

    // ──────────────────────────────────────────────────────────
    // Métodos privados auxiliares
    // ──────────────────────────────────────────────────────────

    /**
     * Lê as linhas de um arquivo CSV ou Excel e retorna array associativo.
     */
    private function readFileRows(string $filePath, string $ext): array
    {
        if ($ext === 'csv') {
            return $this->parseCsvFile($filePath);
        }

        if (in_array($ext, ['xls', 'xlsx']) && class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
            return $this->parseExcelFile($filePath);
        }

        // Fallback: tentar ler como CSV
        return $this->parseCsvFile($filePath);
    }

    private function parseCsvFile(string $filePath): array
    {
        $rows = [];
        $handle = fopen($filePath, 'r');
        if (!$handle) return $rows;

        $bom = fread($handle, 3);
        if ($bom !== chr(0xEF) . chr(0xBB) . chr(0xBF)) {
            rewind($handle);
        }

        $firstLine = fgets($handle);
        rewind($handle);
        $bom = fread($handle, 3);
        if ($bom !== chr(0xEF) . chr(0xBB) . chr(0xBF)) {
            rewind($handle);
        }

        $separator = (substr_count($firstLine, ';') >= substr_count($firstLine, ',')) ? ';' : ',';

        $headers = fgetcsv($handle, 0, $separator);
        if (!$headers) { fclose($handle); return $rows; }

        $headers = array_map(function($h) {
            return strtolower(trim(str_replace(['"', "'"], '', $h)));
        }, $headers);

        while (($line = fgetcsv($handle, 0, $separator)) !== false) {
            if (count(array_filter($line, function($v) { return trim($v) !== ''; })) === 0) continue;
            $row = [];
            foreach ($headers as $i => $header) {
                $row[$header] = isset($line[$i]) ? $line[$i] : '';
            }
            $rows[] = $row;
        }

        fclose($handle);
        return $rows;
    }

    private function parseExcelFile(string $filePath): array
    {
        if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
            return [];
        }

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = [];
            $headers = [];

            foreach ($worksheet->getRowIterator() as $rowIndex => $row) {
                $cells = [];
                foreach ($row->getCellIterator() as $cell) {
                    $cells[] = $cell->getValue();
                }

                if ($rowIndex === 1) {
                    $headers = array_map(function($h) {
                        return strtolower(trim((string)$h));
                    }, $cells);
                    continue;
                }

                if (count(array_filter($cells, function($v) { return trim((string)$v) !== ''; })) === 0) continue;

                $rowData = [];
                foreach ($headers as $i => $header) {
                    $rowData[$header] = isset($cells[$i]) ? (string)$cells[$i] : '';
                }
                $rows[] = $rowData;
            }

            return $rows;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Retorna mapa de colunas para mapeamento automático.
     */
    private function getColumnMap(): array
    {
        return [
            'nome' => 'name', 'name' => 'name', 'produto' => 'name',
            'preco' => 'price', 'preco' => 'price', 'price' => 'price', 'valor' => 'price',
            'preco_custo' => 'cost_price', 'preco_custo' => 'cost_price', 'custo' => 'cost_price', 'cost_price' => 'cost_price',
            'estoque' => 'stock_quantity_legacy', 'stock' => 'stock_quantity_legacy', 'quantidade' => 'stock_quantity_legacy', 'stock_quantity' => 'stock_quantity_legacy',
            'categoria' => 'category', 'category' => 'category',
            'subcategoria' => 'subcategory', 'subcategory' => 'subcategory',
            'descricao' => 'description', 'descricao' => 'description', 'description' => 'description',
            'formato' => 'format', 'format' => 'format', 'dimensoes' => 'format',
            'material' => 'material', 'papel' => 'material',
            'ncm' => 'fiscal_ncm', 'fiscal_ncm' => 'fiscal_ncm',
        ];
    }

    /**
     * Gera mapeamento automático de colunas.
     */
    private function autoMapColumns(array $columns): array
    {
        $colMap = [
            'nome' => 'name', 'name' => 'name', 'produto' => 'name',
            'preco' => 'price', 'preco' => 'price', 'price' => 'price', 'valor' => 'price',
            'preco_custo' => 'cost_price', 'preco_custo' => 'cost_price', 'custo' => 'cost_price', 'cost_price' => 'cost_price',
            'sku' => 'sku', 'codigo' => 'sku', 'codigo' => 'sku', 'code' => 'sku',
            'categoria' => 'category', 'category' => 'category',
            'subcategoria' => 'subcategory', 'subcategory' => 'subcategory',
            'descricao' => 'description', 'descricao' => 'description', 'description' => 'description',
            'formato' => 'format', 'format' => 'format', 'dimensoes' => 'format',
            'material' => 'material', 'papel' => 'material',
            'ncm' => 'ncm', 'fiscal_ncm' => 'ncm',
        ];

        $autoMapping = [];
        foreach ($columns as $col) {
            $normalized = $this->normalizeColumnName($col);
            if (isset($colMap[$normalized])) {
                $autoMapping[$col] = $colMap[$normalized];
            }
        }
        return $autoMapping;
    }

    /**
     * Normaliza nome de coluna para mapeamento.
     */
    private function normalizeColumnName(string $name): string
    {
        $name = strtolower(trim($name));
        return str_replace([' ', '-', 'ç', 'ã', 'á', 'é', 'ó', 'ú'], ['_', '_', 'c', 'a', 'a', 'e', 'o', 'u'], $name);
    }

    /**
     * Resolve (ou cria) uma categoria pelo nome.
     */
    private function resolveCategory(string $catName, array &$cache): ?int
    {
        if (empty($catName)) return null;

        if (isset($cache[$catName])) {
            return $cache[$catName];
        }

        $stmt = $this->categoryModel->readAll();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($categories as $cat) {
            if (mb_strtolower($cat['name']) === mb_strtolower($catName)) {
                $cache[$catName] = $cat['id'];
                return $cat['id'];
            }
        }

        $this->categoryModel->name = $catName;
        if ($this->categoryModel->create()) {
            $cache[$catName] = $this->categoryModel->id;
            return $this->categoryModel->id;
        }

        return null;
    }

    /**
     * Resolve (ou cria) uma subcategoria pelo nome e categoria.
     */
    private function resolveSubcategory(string $subName, ?int $categoryId, array &$cache): ?int
    {
        if (empty($subName) || !$categoryId) return null;

        $subKey = $categoryId . '_' . $subName;
        if (isset($cache[$subKey])) {
            return $cache[$subKey];
        }

        $subs = $this->categoryModel->getSubcategories($categoryId);
        if (is_array($subs)) {
            foreach ($subs as $sub) {
                if (mb_strtolower($sub['name']) === mb_strtolower($subName)) {
                    $cache[$subKey] = $sub['id'];
                    return $sub['id'];
                }
            }
        }

        $this->subcategoryModel->name = $subName;
        $this->subcategoryModel->category_id = $categoryId;
        if ($this->subcategoryModel->create()) {
            $cache[$subKey] = $this->subcategoryModel->id;
            return $this->subcategoryModel->id;
        }

        return null;
    }
}
