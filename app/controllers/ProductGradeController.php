<?php

namespace Akti\Controllers;

use Akti\Services\ProductGradeService;
use Akti\Utils\Input;

/**
 * ProductGradeController — Gerenciamento de grades de produtos.
 *
 * Extraído de ProductController para separação de responsabilidades.
 *
 * @package Akti\Controllers
 */
class ProductGradeController extends BaseController
{
    private ProductGradeService $gradeService;

    public function __construct(\PDO $db, ProductGradeService $gradeService)
    {
        $this->gradeService = $gradeService;
    }

    public function createGradeTypeAjax()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Input::hasPost('name')) {
            $name = Input::post('name');
            $description = Input::post('description');
            $icon = Input::post('icon', 'string', 'fas fa-th');
            $result = $this->gradeService->createGradeType($name, $description ?: null, $icon);
            $this->json($result);
        }
        $this->json(['success' => false]);
    }

    public function getGradeTypes()
    {
        $types = $this->gradeService->getAllGradeTypes();
        $this->json($types);
    }

    public function generateCombinationsAjax()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $gradesData = Input::postArray('grades');
            $combinations = $this->gradeService->generateCombinations($gradesData);
            $this->json(['success' => true, 'combinations' => $combinations]);
        }
        $this->json(['success' => false]);
    }
}
