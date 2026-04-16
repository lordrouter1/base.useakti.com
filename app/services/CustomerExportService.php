<?php
namespace Akti\Services;

use Akti\Models\Customer;
use Akti\Models\Logger;
use Akti\Utils\Input;
use PDO;

/**
 * CustomerExportService — Lógica de exportação de clientes.
 *
 * @see ROADMAP Fase 2 — Item 3.6 (Refatorar Controllers Monolíticos)
 */
class CustomerExportService
{
    /** @var Customer */
    private $customerModel;

    /** @var Logger */
    private $logger;

    /**
     * Construtor da classe CustomerExportService.
     *
     * @param Customer $customerModel Customer model
     * @param Logger $logger Logger
     */
    public function __construct(Customer $customerModel, Logger $logger)
    {
        $this->customerModel = $customerModel;
        $this->logger = $logger;
    }

    /**
     * Exporta clientes em formato CSV.
     *
     * @param array    $filters  Filtros de busca
     * @param int[]|null $ids    IDs específicos (se seleção)
     */
    public function exportCsv(array $filters, ?array $ids = null): void
    {
        if (!empty($ids)) {
            $filters['ids'] = $ids;
        }

        $customers = $this->customerModel->exportAll($filters);
        $count = count($customers);
        $userName = $_SESSION['user_name'] ?? 'Sistema';
        $this->logger->log('CUSTOMER_EXPORT', "Exportação de {$count} clientes por {$userName}");

        $filename = 'clientes_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Cabeçalho
        fputcsv($output, [
            'codigo', 'tipo_pessoa', 'nome', 'nome_fantasia', 'cpf_cnpj',
            'rg_ie', 'im', 'email', 'email_secundario', 'celular', 'telefone',
            'telefone_comercial', 'website', 'instagram',
            'data_nascimento', 'genero', 'nome_contato', 'cargo_contato',
            'cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'uf',
            'status', 'prazo_pagamento', 'limite_credito', 'desconto_padrao',
            'origem', 'tags', 'observacoes', 'cadastrado_em',
        ], ';');

        foreach ($customers as $c) {
            fputcsv($output, [
                $c['code'] ?? '',
                $c['person_type'] ?? 'PF',
                $c['name'] ?? '',
                $c['fantasy_name'] ?? '',
                $c['document'] ?? '',
                $c['rg_ie'] ?? '',
                $c['im'] ?? '',
                $c['email'] ?? '',
                $c['email_secondary'] ?? '',
                $c['cellphone'] ?? '',
                $c['phone'] ?? '',
                $c['phone_commercial'] ?? '',
                $c['website'] ?? '',
                $c['instagram'] ?? '',
                $c['birth_date'] ?? '',
                $c['gender'] ?? '',
                $c['contact_name'] ?? '',
                $c['contact_role'] ?? '',
                $c['zipcode'] ?? '',
                $c['address_street'] ?? '',
                $c['address_number'] ?? '',
                $c['address_complement'] ?? '',
                $c['address_neighborhood'] ?? '',
                $c['address_city'] ?? '',
                $c['address_state'] ?? '',
                $c['status'] ?? 'active',
                $c['payment_term'] ?? '',
                $c['credit_limit'] ?? '',
                $c['discount_default'] ?? '',
                $c['origin'] ?? '',
                $c['tags'] ?? '',
                $c['observations'] ?? '',
                $c['created_at'] ?? '',
            ], ';');
        }

        fclose($output);
        exit;
    }
}
