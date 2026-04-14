<?php

namespace Akti\Controllers;

use Akti\Services\ProductImportService;
use Akti\Utils\Input;

/**
 * ProductImportController — Importação de produtos (CSV/Excel).
 *
 * Extraído de ProductController para separação de responsabilidades.
 *
 * @package Akti\Controllers
 */
class ProductImportController extends BaseController
{
    private ProductImportService $importService;

    public function __construct(\PDO $db, ProductImportService $importService)
    {
        $this->importService = $importService;
    }

    public function parseImportFile()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['import_file'])) {
            $this->json(['success' => false, 'message' => 'Nenhum arquivo enviado.']);
        }

        $result = $this->importService->parseImportFile($_FILES['import_file']);
        $this->json($result);
    }

    public function importProductsMapped()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido.']);
        }

        $mapping = json_decode(Input::post('mapping'), true);
        if (empty($mapping)) {
            $this->json(['success' => false, 'message' => 'Nenhum mapeamento de colunas definido.']);
        }

        $result = $this->importService->importProductsMapped($mapping);
        $this->json($result);
    }

    public function downloadImportTemplate()
    {
        $this->importService->generateImportTemplate();
        exit;
    }

    public function importProducts()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['import_file'])) {
            $this->json(['success' => false, 'message' => 'Nenhum arquivo enviado.']);
        }

        $result = $this->importService->importProductsDirect($_FILES['import_file']);
        $this->json($result);
    }
}
