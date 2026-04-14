<?php

namespace Akti\Controllers;

use Akti\Services\CustomerExportService;
use Akti\Utils\Input;

/**
 * CustomerExportController — Exportação de clientes (CSV).
 *
 * Extraído de CustomerController para separação de responsabilidades.
 *
 * @package Akti\Controllers
 */
class CustomerExportController extends BaseController {
    private CustomerExportService $exportService;

    public function __construct(\PDO $db, CustomerExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    public function export()
    {
        $filters = $this->captureFilters();

        $idsParam = Input::get('ids');
        $ids = null;
        if (!empty($idsParam)) {
            $ids = array_filter(array_map('intval', explode(',', $idsParam)));
        }

        $this->exportService->exportCsv($filters, $ids);
    }

    private function captureFilters(): array
    {
        return [
            'search'   => Input::get('search', 'string', ''),
            'status'   => Input::get('status', 'string', ''),
            'city'     => Input::get('city', 'string', ''),
            'state'    => Input::get('state', 'string', ''),
            'tag'      => Input::get('tag', 'string', ''),
            'type'     => Input::get('type', 'string', ''),
        ];
    }
}
