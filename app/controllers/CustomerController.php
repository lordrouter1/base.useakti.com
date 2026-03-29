<?php
namespace Akti\Controllers;

use Akti\Models\Customer;
use Akti\Models\CustomerContact;
use Akti\Models\ImportBatch;
use Akti\Models\ImportMappingProfile;
use Akti\Models\PriceTable;
use Akti\Models\Logger;
use Akti\Models\User;
use Akti\Utils\Input;
use Akti\Utils\Validator;
use Database;
use PDO;
use TenantManager;

/**
 * Controller: CustomerController
 *
 * CRUD completo de clientes com ~40 campos, validação server-side,
 * soft delete, auditoria, AJAX endpoints, exportação CSV e proxy APIs.
 *
 * Fase 2 do Roadmap de Refatoração do Cadastro de Clientes.
 *
 * @see docs/cadastro/ROADMAP_CADASTRO_CLIENTES.md
 */
class CustomerController {
    
    private $customerModel;
    private $contactModel;
    private $importBatchModel;
    private $mappingProfileModel;
    private $logger;
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->customerModel = new Customer($this->db);
        $this->contactModel  = new CustomerContact($this->db);
        $this->importBatchModel = new ImportBatch($this->db);
        $this->mappingProfileModel = new ImportMappingProfile($this->db);
        $this->logger = new Logger($this->db);
    }

    // ═══════════════════════════════════════════════
    //  CRUD — Listagem (index)
    // ═══════════════════════════════════════════════

    public function index() {
        $totalItems = (int) $this->customerModel->countAll();

        // Verificar limite de clientes do tenant
        $maxCustomers = TenantManager::getTenantLimit('max_customers');
        $currentCustomers = $totalItems;
        $limitReached = ($maxCustomers !== null && $currentCustomers >= $maxCustomers);
        $limitInfo = $limitReached ? ['current' => $currentCustomers, 'max' => $maxCustomers] : null;

        // Campos disponíveis para mapeamento de importação (Fase 4 — atualizado com todos os novos campos)
        $importFields = [
            'name'                 => ['label' => 'Nome / Razão Social', 'required' => true],
            'person_type'          => ['label' => 'Tipo Pessoa (PF/PJ)', 'required' => false],
            'fantasy_name'         => ['label' => 'Nome Fantasia', 'required' => false],
            'document'             => ['label' => 'CPF / CNPJ', 'required' => false],
            'rg_ie'                => ['label' => 'RG / Inscrição Estadual', 'required' => false],
            'im'                   => ['label' => 'Inscrição Municipal', 'required' => false],
            'birth_date'           => ['label' => 'Data Nascimento/Fundação', 'required' => false],
            'gender'               => ['label' => 'Gênero', 'required' => false],
            'email'                => ['label' => 'E-mail', 'required' => false],
            'email_secondary'      => ['label' => 'E-mail Secundário', 'required' => false],
            'phone'                => ['label' => 'Telefone', 'required' => false],
            'cellphone'            => ['label' => 'Celular / WhatsApp', 'required' => false],
            'phone_commercial'     => ['label' => 'Telefone Comercial', 'required' => false],
            'website'              => ['label' => 'Website', 'required' => false],
            'instagram'            => ['label' => 'Instagram', 'required' => false],
            'contact_name'         => ['label' => 'Nome do Contato (PJ)', 'required' => false],
            'contact_role'         => ['label' => 'Cargo do Contato', 'required' => false],
            'zipcode'              => ['label' => 'CEP', 'required' => false],
            'address_street'       => ['label' => 'Logradouro', 'required' => false],
            'address_type'         => ['label' => 'Tipo Logradouro', 'required' => false],
            'address_name'         => ['label' => 'Nome do Logradouro', 'required' => false],
            'address_number'       => ['label' => 'Número', 'required' => false],
            'address_complement'   => ['label' => 'Complemento', 'required' => false],
            'address_neighborhood' => ['label' => 'Bairro', 'required' => false],
            'address_city'         => ['label' => 'Cidade', 'required' => false],
            'address_state'        => ['label' => 'Estado (UF)', 'required' => false],
            'neighborhood'         => ['label' => 'Bairro (legado)', 'required' => false],
            'complement'           => ['label' => 'Complemento (legado)', 'required' => false],
            'origin'               => ['label' => 'Origem', 'required' => false],
            'tags'                 => ['label' => 'Tags', 'required' => false],
            'observations'         => ['label' => 'Observações', 'required' => false],
            'status'               => ['label' => 'Status (active/inactive)', 'required' => false],
            'payment_term'         => ['label' => 'Prazo de Pagamento', 'required' => false],
            'credit_limit'         => ['label' => 'Limite de Crédito', 'required' => false],
            'discount_default'     => ['label' => 'Desconto Padrão (%)', 'required' => false],
        ];

        // Dados para filtros avançados
        $states = $this->customerModel->getDistinctStates();
        $cities = $this->customerModel->getDistinctCities();

        require 'app/views/layout/header.php';
        require 'app/views/customers/index.php';
        require 'app/views/layout/footer.php';
    }

    // ═══════════════════════════════════════════════
    //  CRUD — Formulário de Criação
    // ═══════════════════════════════════════════════

    public function create() {
        $priceTableModel = new PriceTable($this->db);
        $priceTables = $priceTableModel->readAll();

        // Carregar lista de vendedores (usuários)
        $userModel = new User($this->db);
        $sellers = $userModel->readAll();

        require 'app/views/layout/header.php';
        require 'app/views/customers/create.php';
        require 'app/views/layout/footer.php';
    }

    // ═══════════════════════════════════════════════
    //  CRUD — Processar Criação (POST) — Fase 2
    // ═══════════════════════════════════════════════

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=customers');
            exit;
        }

        // Verificar limite de clientes do tenant
        $maxCustomers = TenantManager::getTenantLimit('max_customers');
        if ($maxCustomers !== null) {
            $currentCustomers = (int) $this->customerModel->countAll();
            if ($currentCustomers >= $maxCustomers) {
                $_SESSION['errors'] = ['limit' => 'Limite de clientes atingido para o seu plano.'];
                header('Location: ?page=customers&action=create');
                exit;
            }
        }

        // Capturar e sanitizar TODOS os campos
        $data = $this->captureFormData();

        // Validação server-side completa
        $v = $this->validateCustomerData($data);

        // Verificar duplicidade de documento
        if (!empty($data['document'])) {
            $duplicate = $this->customerModel->checkDuplicate($data['document']);
            if ($duplicate) {
                $v->addError('document', "Já existe um cliente com este documento: {$duplicate['name']} ({$duplicate['code']}).");
            }
        }

        if ($v->fails()) {
            $_SESSION['errors'] = $v->errors();
            $_SESSION['old'] = $_POST;
            header('Location: ?page=customers&action=create');
            exit;
        }

        // Processar upload de foto
        $data['photo'] = $this->handlePhotoUpload();

        // Adicionar auditoria
        $data['created_by'] = $_SESSION['user_id'] ?? null;

        // Manter campo address JSON para retrocompatibilidade
        $data['address'] = json_encode([
            'zipcode'        => $data['zipcode'] ?? '',
            'address_type'   => '',
            'address_name'   => $data['address_street'] ?? '',
            'address_number' => $data['address_number'] ?? '',
            'neighborhood'   => $data['address_neighborhood'] ?? '',
            'complement'     => $data['address_complement'] ?? '',
        ]);

        // Criar o cliente
        $newId = $this->customerModel->create($data);

        // Log de auditoria
        $code = $data['code'] ?? 'CLI-?????';
        $userName = $_SESSION['user_name'] ?? 'Sistema';
        $this->logger->log('CUSTOMER_CREATE', "Cliente {$code} '{$data['name']}' criado por {$userName}");

        $_SESSION['success'] = 'Cliente cadastrado com sucesso!';
        header('Location: ?page=customers&status=success');
        exit;
    }

    // ═══════════════════════════════════════════════
    //  CRUD — Formulário de Edição
    // ═══════════════════════════════════════════════

    public function edit() {
        $id = Input::get('id', 'int');
        if (!$id) {
            header('Location: ?page=customers');
            exit;
        }
        
        $customer = $this->customerModel->readOne($id);
        if (!$customer) {
            header('Location: ?page=customers');
            exit;
        }

        // Decode address JSON for the form (retrocompatibilidade)
        $customer['address_data'] = json_decode($customer['address'] ?? '{}', true) ?: [];
        
        $priceTableModel = new PriceTable($this->db);
        $priceTables = $priceTableModel->readAll();

        // Carregar lista de vendedores (usuários)
        $userModel = new User($this->db);
        $sellers = $userModel->readAll();

        // Carregar contatos adicionais
        $contacts = $this->contactModel->readByCustomer($id);

        require 'app/views/layout/header.php';
        require 'app/views/customers/edit.php';
        require 'app/views/layout/footer.php';
    }

    // ═══════════════════════════════════════════════
    //  CRUD — Processar Edição (POST) — Fase 2
    // ═══════════════════════════════════════════════

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=customers');
            exit;
        }

        $id = Input::post('id', 'int');
        if (!$id) {
            header('Location: ?page=customers');
            exit;
        }

        // Verificar se o cliente existe
        $existing = $this->customerModel->readOne($id);
        if (!$existing) {
            $_SESSION['errors'] = ['id' => 'Cliente não encontrado.'];
            header('Location: ?page=customers');
            exit;
        }

        // Capturar e sanitizar TODOS os campos
        $data = $this->captureFormData();
        $data['id'] = $id;

        // Validação server-side completa
        $v = $this->validateCustomerData($data, $id);

        // Verificar duplicidade de documento (excluindo o próprio)
        if (!empty($data['document'])) {
            $duplicate = $this->customerModel->checkDuplicate($data['document'], $id);
            if ($duplicate) {
                $v->addError('document', "Já existe outro cliente com este documento: {$duplicate['name']} ({$duplicate['code']}).");
            }
        }

        if ($v->fails()) {
            $_SESSION['errors'] = $v->errors();
            $_SESSION['old'] = $_POST;
            header('Location: ?page=customers&action=edit&id=' . $id);
            exit;
        }

        // Processar upload de foto (manter existente se nenhuma nova)
        $newPhoto = $this->handlePhotoUpload();
        if ($newPhoto) {
            $data['photo'] = $newPhoto;
        }
        // Se não houver nova foto, NÃO incluir 'photo' no data para não sobrescrever

        // Adicionar auditoria
        $data['updated_by'] = $_SESSION['user_id'] ?? null;

        // Manter campo address JSON para retrocompatibilidade
        $data['address'] = json_encode([
            'zipcode'        => $data['zipcode'] ?? '',
            'address_type'   => '',
            'address_name'   => $data['address_street'] ?? '',
            'address_number' => $data['address_number'] ?? '',
            'neighborhood'   => $data['address_neighborhood'] ?? '',
            'complement'     => $data['address_complement'] ?? '',
        ]);

        // Atualizar o cliente
        $this->customerModel->update($data);

        // Log de auditoria
        $code = $existing['code'] ?? 'CLI-?????';
        $userName = $_SESSION['user_name'] ?? 'Sistema';
        $this->logger->log('CUSTOMER_UPDATE', "Cliente {$code} '{$data['name']}' atualizado por {$userName}");

        $_SESSION['success'] = 'Cliente atualizado com sucesso!';
        header('Location: ?page=customers&status=success');
        exit;
    }

    // ═══════════════════════════════════════════════
    //  CRUD — Exclusão (POST + Soft Delete) — Fase 2
    // ═══════════════════════════════════════════════

    public function delete() {
        // Aceitar tanto POST (novo) quanto GET (retrocompatibilidade)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = Input::post('id', 'int');
        } else {
            // Retrocompatibilidade: ainda aceita GET mas faz soft delete
            $id = Input::get('id', 'int');
        }

        if (!$id) {
            if ($this->isAjax()) {
                $this->jsonResponse(['success' => false, 'message' => 'ID não informado.']);
            }
            header('Location: ?page=customers');
            exit;
        }

        $customer = $this->customerModel->readOne($id);
        if (!$customer) {
            if ($this->isAjax()) {
                $this->jsonResponse(['success' => false, 'message' => 'Cliente não encontrado.']);
            }
            header('Location: ?page=customers');
            exit;
        }

        // Soft delete (não remove do banco)
        $this->customerModel->softDelete($id);

        // Log de auditoria
        $code = $customer['code'] ?? 'CLI-?????';
        $userName = $_SESSION['user_name'] ?? 'Sistema';
        $this->logger->log('CUSTOMER_DELETE', "Cliente {$code} '{$customer['name']}' excluído (soft) por {$userName}");

        if ($this->isAjax()) {
            $this->jsonResponse(['success' => true, 'message' => 'Cliente excluído com sucesso.']);
        }

        $_SESSION['success'] = 'Cliente excluído com sucesso!';
        header('Location: ?page=customers&status=success');
        exit;
    }

    // ═══════════════════════════════════════════════
    //  Action: Restaurar cliente (POST) — Fase 2
    // ═══════════════════════════════════════════════

    public function restore() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Método não permitido.']);
        }

        $id = Input::post('id', 'int');
        if (!$id) {
            $this->jsonResponse(['success' => false, 'message' => 'ID não informado.']);
        }

        $this->customerModel->restore($id);

        $customer = $this->customerModel->readOne($id);
        $code = $customer['code'] ?? 'CLI-?????';
        $userName = $_SESSION['user_name'] ?? 'Sistema';
        $this->logger->log('CUSTOMER_RESTORE', "Cliente {$code} '{$customer['name']}' restaurado por {$userName}");

        $this->jsonResponse(['success' => true, 'message' => 'Cliente restaurado com sucesso.']);
    }

    // ═══════════════════════════════════════════════
    //  Action: Atualizar Status (POST/AJAX) — Fase 2
    // ═══════════════════════════════════════════════

    public function updateStatus() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Método não permitido.']);
        }

        $id     = Input::post('id', 'int');
        $status = Input::post('status');

        if (!$id || !$status) {
            $this->jsonResponse(['success' => false, 'message' => 'ID e status são obrigatórios.']);
        }

        $allowed = ['active', 'inactive', 'blocked'];
        if (!in_array($status, $allowed, true)) {
            $this->jsonResponse(['success' => false, 'message' => 'Status inválido.']);
        }

        $result = $this->customerModel->updateStatus($id, $status);

        if ($result) {
            $customer = $this->customerModel->readOne($id);
            $code = $customer['code'] ?? 'CLI-?????';
            $userName = $_SESSION['user_name'] ?? 'Sistema';
            $this->logger->log('CUSTOMER_STATUS', "Cliente {$code} status alterado para {$status} por {$userName}");
        }

        $this->jsonResponse(['success' => $result, 'message' => $result ? 'Status atualizado.' : 'Erro ao atualizar.']);
    }

    // ═══════════════════════════════════════════════
    //  Action: Ficha completa do cliente — Fase 2
    // ═══════════════════════════════════════════════

    public function view() {
        $id = Input::get('id', 'int');
        if (!$id) {
            header('Location: ?page=customers');
            exit;
        }

        $customer = $this->customerModel->readOne($id);
        if (!$customer) {
            header('Location: ?page=customers');
            exit;
        }

        // Decode address JSON (retrocompatibilidade)
        $customer['address_data'] = json_decode($customer['address'] ?? '{}', true) ?: [];

        // Contatos adicionais
        $contacts = $this->contactModel->readByCustomer($id);

        // Estatísticas (total pedidos, valor total, último pedido, ticket médio)
        $stats = $this->customerModel->getCustomerStats($id);

        // Últimos 5 pedidos do cliente
        $recentOrders = $this->getRecentOrders($id, 5);

        // Tabela de preço vinculada
        $priceTable = null;
        if (!empty($customer['price_table_id'])) {
            $ptModel = new PriceTable($this->db);
            $priceTable = $ptModel->readOne($customer['price_table_id']);
        }

        // Nome do vendedor
        $sellerName = null;
        if (!empty($customer['seller_id'])) {
            $userModel = new User($this->db);
            $seller = $userModel->readOne($customer['seller_id']);
            $sellerName = $seller['name'] ?? null;
        }

        require 'app/views/layout/header.php';
        require 'app/views/customers/view.php';
        require 'app/views/layout/footer.php';
    }

    // ═══════════════════════════════════════════════
    //  Action: Verificar duplicidade (AJAX) — Fase 2
    // ═══════════════════════════════════════════════

    public function checkDuplicate() {
        header('Content-Type: application/json');

        $document  = Input::get('document');
        $excludeId = Input::get('exclude_id', 'int');

        if (empty($document)) {
            echo json_encode(['exists' => false]);
            exit;
        }

        $document = preg_replace('/\D/', '', $document);
        $result = $this->customerModel->checkDuplicate($document, $excludeId ?: null);

        if ($result) {
            echo json_encode([
                'exists'   => true,
                'customer' => [
                    'id'       => $result['id'],
                    'code'     => $result['code'] ?? '',
                    'name'     => $result['name'],
                    'document' => $result['document'],
                ],
            ]);
        } else {
            echo json_encode(['exists' => false]);
        }
        exit;
    }

    // ═══════════════════════════════════════════════
    //  Action: Proxy ViaCEP (AJAX) — Fase 2
    // ═══════════════════════════════════════════════

    public function searchCep() {
        header('Content-Type: application/json');

        $cep = Input::get('cep');
        $cep = preg_replace('/\D/', '', $cep ?? '');

        if (strlen($cep) !== 8) {
            echo json_encode(['success' => false, 'message' => 'CEP deve ter 8 dígitos.']);
            exit;
        }

        // Verificar cache na sessão (1 hora)
        $cacheKey = 'cep_cache_' . $cep;
        if (isset($_SESSION[$cacheKey]) && $_SESSION[$cacheKey]['expires'] > time()) {
            echo json_encode(['success' => true, 'data' => $_SESSION[$cacheKey]['data'], 'cached' => true]);
            exit;
        }

        // Chamar ViaCEP
        $url = "https://viacep.com.br/ws/{$cep}/json/";
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'header'  => "User-Agent: Akti/1.0\r\n",
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            echo json_encode(['success' => false, 'message' => 'Não foi possível consultar o CEP.']);
            exit;
        }

        $viaCep = json_decode($response, true);

        if (!$viaCep || isset($viaCep['erro'])) {
            echo json_encode(['success' => false, 'message' => 'CEP não encontrado.']);
            exit;
        }

        $data = [
            'zipcode'              => preg_replace('/\D/', '', $viaCep['cep'] ?? ''),
            'address_street'       => $viaCep['logradouro'] ?? '',
            'address_neighborhood' => $viaCep['bairro'] ?? '',
            'address_city'         => $viaCep['localidade'] ?? '',
            'address_state'        => $viaCep['uf'] ?? '',
            'address_ibge'         => $viaCep['ibge'] ?? '',
        ];

        // Cachear por 1 hora
        $_SESSION[$cacheKey] = ['data' => $data, 'expires' => time() + 3600];

        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    // ═══════════════════════════════════════════════
    //  Action: Proxy BrasilAPI CNPJ (AJAX) — Fase 2
    // ═══════════════════════════════════════════════

    public function searchCnpj() {
        header('Content-Type: application/json');

        $cnpj = Input::get('cnpj');
        $cnpj = preg_replace('/\D/', '', $cnpj ?? '');

        if (strlen($cnpj) !== 14) {
            echo json_encode(['success' => false, 'message' => 'CNPJ deve ter 14 dígitos.']);
            exit;
        }

        // Validar CNPJ
        if (!Validator::isValidCnpj($cnpj)) {
            echo json_encode(['success' => false, 'message' => 'CNPJ inválido.']);
            exit;
        }

        // Verificar cache na sessão (1 hora)
        $cacheKey = 'cnpj_cache_' . $cnpj;
        if (isset($_SESSION[$cacheKey]) && $_SESSION[$cacheKey]['expires'] > time()) {
            echo json_encode(['success' => true, 'data' => $_SESSION[$cacheKey]['data'], 'cached' => true]);
            exit;
        }

        // Chamar BrasilAPI
        $url = "https://brasilapi.com.br/api/cnpj/v1/{$cnpj}";
        $context = stream_context_create([
            'http' => [
                'timeout' => 15,
                'header'  => "User-Agent: Akti/1.0\r\n",
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            echo json_encode(['success' => false, 'message' => 'Não foi possível consultar o CNPJ.']);
            exit;
        }

        $apiData = json_decode($response, true);

        if (!$apiData || isset($apiData['message'])) {
            echo json_encode(['success' => false, 'message' => $apiData['message'] ?? 'CNPJ não encontrado.']);
            exit;
        }

        $data = [
            'name'                 => $apiData['razao_social'] ?? '',
            'fantasy_name'         => $apiData['nome_fantasia'] ?? '',
            'document'             => $cnpj,
            'email'                => strtolower($apiData['email'] ?? ''),
            'phone'                => preg_replace('/\D/', '', $apiData['ddd_telefone_1'] ?? ''),
            'zipcode'              => preg_replace('/\D/', '', $apiData['cep'] ?? ''),
            'address_street'       => $apiData['logradouro'] ?? '',
            'address_number'       => $apiData['numero'] ?? '',
            'address_complement'   => $apiData['complemento'] ?? '',
            'address_neighborhood' => $apiData['bairro'] ?? '',
            'address_city'         => $apiData['municipio'] ?? '',
            'address_state'        => $apiData['uf'] ?? '',
        ];

        // Cachear por 1 hora
        $_SESSION[$cacheKey] = ['data' => $data, 'expires' => time() + 3600];

        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    // ═══════════════════════════════════════════════
    //  Action: Exportação CSV — Fase 2
    // ═══════════════════════════════════════════════

    public function export() {
        $format = Input::get('format', 'string', 'csv');
        $filters = $this->captureFilters();

        // Se recebeu IDs específicos (exportação de selecionados), filtrar apenas esses
        $idsParam = Input::get('ids');
        if (!empty($idsParam)) {
            $ids = array_filter(array_map('intval', explode(',', $idsParam)));
            if (!empty($ids)) {
                $filters['ids'] = $ids;
            }
        }

        $customers = $this->customerModel->exportAll($filters);

        // Log de auditoria
        $count = count($customers);
        $userName = $_SESSION['user_name'] ?? 'Sistema';
        $this->logger->log('CUSTOMER_EXPORT', "Exportação de {$count} clientes por {$userName}");

        // Gerar CSV
        $filename = 'clientes_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        // BOM UTF-8
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Cabeçalho (Rec 7 — compatibilidade bilateral com importação)
        fputcsv($output, [
            'codigo', 'tipo_pessoa', 'nome', 'nome_fantasia', 'cpf_cnpj',
            'rg_ie', 'im', 'email', 'email_secundario', 'celular', 'telefone',
            'telefone_comercial', 'website', 'instagram',
            'data_nascimento', 'genero', 'nome_contato', 'cargo_contato',
            'cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'uf',
            'status', 'prazo_pagamento', 'limite_credito', 'desconto_padrao',
            'origem', 'tags', 'observacoes', 'cadastrado_em',
        ], ';');

        // Dados
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

    // ═══════════════════════════════════════════════
    //  Action: Listar Tags Existentes (AJAX) — Fase 4
    // ═══════════════════════════════════════════════

    /**
     * Retorna todas as tags distintas já utilizadas em clientes.
     * Endpoint: GET ?page=customers&action=getTags
     * Usado pelo componente de autocomplete de tags (customer-tags.js).
     */
    public function getTags()
    {
        header('Content-Type: application/json');

        $tags = $this->customerModel->getAllTags();

        echo json_encode([
            'success' => true,
            'tags'    => $tags,
        ]);
        exit;
    }

    // ═══════════════════════════════════════════════
    //  Action: Histórico de Pedidos do Cliente (AJAX) — Fase 4
    // ═══════════════════════════════════════════════

    /**
     * Retorna pedidos paginados de um cliente.
     * Endpoint: GET ?page=customers&action=getOrderHistory&id=X&page_num=1&per_page=10
     * Usado na tab "Histórico" da ficha do cliente (view.php).
     */
    public function getOrderHistory()
    {
        header('Content-Type: application/json');

        $customerId = Input::get('id', 'int');
        $pageNum    = Input::get('page_num', 'int') ?: 1;
        $perPage    = Input::get('per_page', 'int') ?: 10;

        if (!$customerId) {
            echo json_encode(['success' => false, 'message' => 'ID do cliente não informado.']);
            exit;
        }

        $offset = ($pageNum - 1) * $perPage;

        // Contar total de pedidos
        $countQuery = "SELECT COUNT(*) as total FROM orders WHERE customer_id = :cid";
        $countStmt = $this->db->prepare($countQuery);
        $countStmt->bindValue(':cid', $customerId, PDO::PARAM_INT);
        $countStmt->execute();
        $total = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Buscar pedidos paginados
        $query = "SELECT o.id, o.total_amount, o.status, o.created_at
                  FROM orders o
                  WHERE o.customer_id = :cid
                  ORDER BY o.created_at DESC
                  LIMIT :lim OFFSET :off";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':cid', $customerId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Formatar dados
        $formatted = [];
        foreach ($orders as $order) {
            $formatted[] = [
                'id'           => (int) $order['id'],
                'total_amount' => number_format($order['total_amount'] ?? 0, 2, ',', '.'),
                'status'       => $order['status'] ?? '',
                'created_at'   => !empty($order['created_at']) ? date('d/m/Y', strtotime($order['created_at'])) : '—',
            ];
        }

        echo json_encode([
            'success'    => true,
            'orders'     => $formatted,
            'total'      => $total,
            'page'       => $pageNum,
            'per_page'   => $perPage,
            'total_pages' => (int) ceil($total / $perPage),
        ]);
        exit;
    }

    // ═══════════════════════════════════════════════
    //  Action: Ações em lote (POST/AJAX) — Fase 2
    // ═══════════════════════════════════════════════

    public function bulkAction() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
            exit;
        }

        $action = Input::post('bulk_action');
        $ids = array_map('intval', Input::postArray('ids'));

        if (empty($ids)) {
            echo json_encode(['success' => false, 'message' => 'Nenhum cliente selecionado.']);
            exit;
        }

        $userName = $_SESSION['user_name'] ?? 'Sistema';
        $count = count($ids);

        switch ($action) {
            case 'activate':
                $affected = $this->customerModel->bulkUpdateStatus($ids, 'active');
                $this->logger->log('CUSTOMER_STATUS', "Status de {$affected} clientes alterado para 'active' por {$userName}");
                echo json_encode(['success' => true, 'message' => "{$affected} clientes ativados.", 'affected' => $affected]);
                break;

            case 'inactivate':
                $affected = $this->customerModel->bulkUpdateStatus($ids, 'inactive');
                $this->logger->log('CUSTOMER_STATUS', "Status de {$affected} clientes alterado para 'inactive' por {$userName}");
                echo json_encode(['success' => true, 'message' => "{$affected} clientes inativados.", 'affected' => $affected]);
                break;

            case 'block':
                $affected = $this->customerModel->bulkUpdateStatus($ids, 'blocked');
                $this->logger->log('CUSTOMER_STATUS', "Status de {$affected} clientes alterado para 'blocked' por {$userName}");
                echo json_encode(['success' => true, 'message' => "{$affected} clientes bloqueados.", 'affected' => $affected]);
                break;

            case 'delete':
                $affected = $this->customerModel->bulkDelete($ids);
                $this->logger->log('CUSTOMER_DELETE', "Exclusão em lote de {$affected} clientes por {$userName}");
                echo json_encode(['success' => true, 'message' => "{$affected} clientes excluídos.", 'affected' => $affected]);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Ação inválida.']);
                break;
        }
        exit;
    }

    // ═══════════════════════════════════════════════
    //  Contatos — CRUD AJAX — Fase 2
    // ═══════════════════════════════════════════════

    /**
     * GET: Lista contatos de um cliente (AJAX/JSON)
     */
    public function getContacts() {
        header('Content-Type: application/json');

        $customerId = Input::get('customer_id', 'int');
        if (!$customerId) {
            echo json_encode(['success' => false, 'message' => 'ID do cliente é obrigatório.']);
            exit;
        }

        $contacts = $this->contactModel->readByCustomer($customerId);

        echo json_encode(['success' => true, 'data' => $contacts]);
        exit;
    }

    /**
     * POST: Cria ou atualiza um contato (AJAX/JSON)
     */
    public function saveContact() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
            exit;
        }

        $contactId   = Input::post('contact_id', 'int');
        $customerId  = Input::post('customer_id', 'int');
        $name        = Input::post('name');
        $role        = Input::post('role');
        $email       = Input::post('email', 'email');
        $phone       = Input::post('phone', 'phone');
        $isPrimary   = Input::post('is_primary', 'int', 0);
        $notes       = Input::post('notes');

        if (!$customerId || !$name) {
            echo json_encode(['success' => false, 'message' => 'Cliente e nome do contato são obrigatórios.']);
            exit;
        }

        $data = [
            'customer_id' => $customerId,
            'name'        => $name,
            'role'        => $role,
            'email'       => $email,
            'phone'       => preg_replace('/\D/', '', $phone ?? ''),
            'is_primary'  => $isPrimary,
            'notes'       => $notes,
        ];

        if ($contactId) {
            // Atualizar
            $data['id'] = $contactId;
            $this->contactModel->update($data);
            $this->logger->log('CUSTOMER_UPDATE', "Contato #{$contactId} atualizado para cliente #{$customerId}");
            echo json_encode(['success' => true, 'message' => 'Contato atualizado.', 'id' => $contactId]);
        } else {
            // Criar
            $newId = $this->contactModel->create($data);
            $this->logger->log('CUSTOMER_UPDATE', "Contato #{$newId} criado para cliente #{$customerId}");
            echo json_encode(['success' => true, 'message' => 'Contato adicionado.', 'id' => $newId]);
        }
        exit;
    }

    /**
     * POST: Remove um contato (AJAX/JSON)
     */
    public function deleteContact() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
            exit;
        }

        $contactId = Input::post('contact_id', 'int');
        if (!$contactId) {
            echo json_encode(['success' => false, 'message' => 'ID do contato é obrigatório.']);
            exit;
        }

        $this->contactModel->delete($contactId);
        $this->logger->log('CUSTOMER_UPDATE', "Contato #{$contactId} removido");

        echo json_encode(['success' => true, 'message' => 'Contato removido.']);
        exit;
    }

    // ═══════════════════════════════════════════════
    //  AJAX: Lista de clientes com filtro e paginação
    //  (atualizado com filtros avançados — Fase 2)
    // ═══════════════════════════════════════════════

    public function getCustomersList() {
        header('Content-Type: application/json');

        $search  = Input::get('search');
        $page    = max(1, Input::get('pg', 'int', 1));
        $perPage = Input::get('per_page', 'int', 20);

        // Filtros avançados
        $filters = $this->captureFilters();

        $result     = $this->customerModel->readPaginatedFiltered($page, $perPage, $search ?: null, $filters);
        $total      = $result['total'];
        $totalPages = max(1, (int) ceil($total / $perPage));
        $items      = $result['data'];

        echo json_encode([
            'success'     => true,
            'items'       => $items,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => $totalPages,
        ]);
        exit;
    }

    // ═══════════════════════════════════════════════
    //  AJAX: Busca clientes para Select2
    // ═══════════════════════════════════════════════

    public function searchSelect2()
    {
        header('Content-Type: application/json');

        $q     = isset($_GET['q']) ? trim($_GET['q']) : '';
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;

        $results = $this->customerModel->searchForSelect2($q, $limit);

        echo json_encode(['data' => $results]);
        exit;
    }

    // ═══════════════════════════════════════════════
    //  IMPORTAÇÃO: Parse do arquivo (Step 1 → Step 2)
    // ═══════════════════════════════════════════════

    public function parseImportFile() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['import_file'])) {
            echo json_encode(['success' => false, 'message' => 'Nenhum arquivo enviado.']);
            exit;
        }

        $file = $_FILES['import_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Erro no upload do arquivo.']);
            exit;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'xls', 'xlsx', 'txt'])) {
            echo json_encode(['success' => false, 'message' => 'Formato não suportado. Use CSV, XLS ou XLSX.']);
            exit;
        }

        $rows = [];
        if (in_array($ext, ['csv', 'txt'])) {
            $rows = $this->parseCsvFile($file['tmp_name']);
        } elseif (in_array($ext, ['xls', 'xlsx'])) {
            if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
                $rows = $this->parseExcelFile($file['tmp_name']);
            } else {
                $rows = $this->parseCsvFile($file['tmp_name']);
            }
        }

        if (empty($rows)) {
            echo json_encode(['success' => false, 'message' => 'Arquivo vazio ou não foi possível ler os dados.']);
            exit;
        }

        // Salvar temporariamente
        $tmpDir = sys_get_temp_dir() . '/akti_imports/';
        if (!is_dir($tmpDir)) mkdir($tmpDir, 0755, true);
        $tmpName = 'cust_import_' . session_id() . '_' . time() . '.' . $ext;
        $tmpPath = $tmpDir . $tmpName;
        move_uploaded_file($file['tmp_name'], $tmpPath);
        $_SESSION['cust_import_tmp_file'] = $tmpPath;
        $_SESSION['cust_import_tmp_ext'] = $ext;

        $columns = !empty($rows) ? array_keys($rows[0]) : [];
        $preview = array_slice($rows, 0, 10);
        $totalRows = count($rows);

        // Auto-mapeamento por nome de coluna (Fase 4 — expandido com todos os novos campos)
        $colMap = [
            // Nome
            'nome' => 'name', 'name' => 'name', 'razao_social' => 'name', 'razao social' => 'name', 'cliente' => 'name',
            // Tipo pessoa
            'tipo' => 'person_type', 'tipo_pessoa' => 'person_type', 'type' => 'person_type', 'person_type' => 'person_type', 'tipo pessoa' => 'person_type',
            // Fantasia
            'fantasia' => 'fantasy_name', 'nome_fantasia' => 'fantasy_name', 'fantasy' => 'fantasy_name', 'nome fantasia' => 'fantasy_name', 'fantasy_name' => 'fantasy_name',
            // Documento
            'cpf' => 'document', 'cnpj' => 'document', 'cpf/cnpj' => 'document', 'cpf_cnpj' => 'document', 'documento' => 'document', 'document' => 'document',
            // RG/IE
            'rg' => 'rg_ie', 'ie' => 'rg_ie', 'inscricao_estadual' => 'rg_ie', 'inscricao estadual' => 'rg_ie', 'rg_ie' => 'rg_ie',
            // IM
            'im' => 'im', 'inscricao_municipal' => 'im', 'inscricao municipal' => 'im',
            // E-mail
            'email' => 'email', 'e-mail' => 'email', 'e_mail' => 'email',
            'email_secundario' => 'email_secondary', 'email secundario' => 'email_secondary', 'email_secondary' => 'email_secondary',
            // Telefones
            'telefone' => 'phone', 'phone' => 'phone', 'fone' => 'phone', 'tel' => 'phone',
            'celular' => 'cellphone', 'whatsapp' => 'cellphone', 'mobile' => 'cellphone', 'cellphone' => 'cellphone', 'cel' => 'cellphone',
            'telefone_comercial' => 'phone_commercial', 'tel_comercial' => 'phone_commercial', 'phone_commercial' => 'phone_commercial',
            // Web/Social
            'website' => 'website', 'site' => 'website', 'url' => 'website',
            'instagram' => 'instagram', 'insta' => 'instagram',
            // Contato PJ
            'nome_contato' => 'contact_name', 'contato' => 'contact_name', 'contact_name' => 'contact_name',
            'cargo' => 'contact_role', 'funcao' => 'contact_role', 'contact_role' => 'contact_role',
            // Endereço
            'cep' => 'zipcode', 'zip' => 'zipcode', 'zipcode' => 'zipcode', 'zip_code' => 'zipcode',
            'tipo_logradouro' => 'address_type', 'tipo logradouro' => 'address_type',
            'logradouro' => 'address_street', 'endereco' => 'address_street', 'rua' => 'address_street', 'address' => 'address_street', 'address_street' => 'address_street',
            'numero' => 'address_number', 'num' => 'address_number', 'nro' => 'address_number', 'address_number' => 'address_number',
            'bairro' => 'address_neighborhood', 'neighborhood' => 'address_neighborhood', 'address_neighborhood' => 'address_neighborhood',
            'complemento' => 'address_complement', 'complement' => 'address_complement', 'comp' => 'address_complement', 'address_complement' => 'address_complement',
            'cidade' => 'address_city', 'city' => 'address_city', 'municipio' => 'address_city', 'address_city' => 'address_city',
            'estado' => 'address_state', 'uf' => 'address_state', 'state' => 'address_state', 'address_state' => 'address_state',
            // Data
            'nascimento' => 'birth_date', 'fundacao' => 'birth_date', 'birth' => 'birth_date', 'birth_date' => 'birth_date', 'data_nascimento' => 'birth_date',
            // Gênero
            'genero' => 'gender', 'sexo' => 'gender', 'gender' => 'gender',
            // Outros
            'origem' => 'origin', 'canal' => 'origin', 'origin' => 'origin',
            'tags' => 'tags', 'etiquetas' => 'tags', 'classificacao' => 'tags',
            'obs' => 'observations', 'observacao' => 'observations', 'observacoes' => 'observations', 'notes' => 'observations', 'observations' => 'observations',
            // Status
            'status' => 'status', 'situacao' => 'status', 'ativo' => 'status',
            // Dados comerciais
            'prazo_pagamento' => 'payment_term', 'prazo' => 'payment_term', 'payment_term' => 'payment_term', 'condicao_pgto' => 'payment_term',
            'limite_credito' => 'credit_limit', 'credito' => 'credit_limit', 'credit_limit' => 'credit_limit',
            'desconto' => 'discount_default', 'desconto_padrao' => 'discount_default', 'discount' => 'discount_default', 'discount_default' => 'discount_default',
            // Compatibilidade bilateral (Rec 7)
            'codigo' => '_skip', 'cadastrado_em' => '_skip',
        ];

        $autoMapping = [];
        foreach ($columns as $col) {
            $normalized = mb_strtolower(trim($col));
            $normalized = str_replace([' ', '-', 'ç', 'ã', 'á', 'é', 'ó', 'ú', 'ê', 'í'], ['_', '_', 'c', 'a', 'a', 'e', 'o', 'u', 'e', 'i'], $normalized);
            if (isset($colMap[$normalized])) {
                $autoMapping[$col] = $colMap[$normalized];
            }
            // Fallback: nome original direto
            if (!isset($autoMapping[$col]) && isset($colMap[mb_strtolower(trim($col))])) {
                $autoMapping[$col] = $colMap[mb_strtolower(trim($col))];
            }
        }

        echo json_encode([
            'success'      => true,
            'columns'      => $columns,
            'preview'      => $preview,
            'total_rows'   => $totalRows,
            'auto_mapping' => $autoMapping,
        ]);
        exit;
    }

    // ═══════════════════════════════════════════════
    //  IMPORTAÇÃO: Executar import com mapeamento
    //  Rec 1: Progresso em tempo real (session-based)
    //  Rec 2: Modo atualização/merge
    //  Rec 3: Rastreamento de lote para desfazer
    //  Rec 6: Processamento chunked para grandes volumes
    // ═══════════════════════════════════════════════

    public function importCustomersMapped() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método inválido.']);
            exit;
        }

        $mapping = json_decode(Input::post('mapping'), true);
        if (empty($mapping)) {
            echo json_encode(['success' => false, 'message' => 'Nenhum mapeamento de colunas definido.']);
            exit;
        }

        $mappedFields = array_values($mapping);
        if (!in_array('name', $mappedFields)) {
            echo json_encode(['success' => false, 'message' => 'O campo "Nome / Razão Social" é obrigatório no mapeamento.']);
            exit;
        }

        // ── Modo de importação: create | update | create_or_update ──
        $importMode = Input::post('import_mode', 'string', 'create');
        if (!in_array($importMode, ['create', 'update', 'create_or_update'])) {
            $importMode = 'create';
        }

        // Para modos update/create_or_update, precisa de campo documento mapeado
        if (in_array($importMode, ['update', 'create_or_update']) && !in_array('document', $mappedFields)) {
            echo json_encode(['success' => false, 'message' => 'Para modo de atualização, o campo "CPF/CNPJ" deve ser mapeado.']);
            exit;
        }

        // ── Verificação de limite do plano ──
        $maxCustomers = \TenantManager::getTenantLimit('max_customers');
        $currentCustomers = (int) $this->customerModel->countAll();
        $availableSlots = ($maxCustomers !== null) ? max(0, $maxCustomers - $currentCustomers) : PHP_INT_MAX;

        if ($importMode === 'create' && $maxCustomers !== null && $availableSlots <= 0) {
            echo json_encode(['success' => false, 'message' => 'Limite de clientes do plano atingido. Não é possível importar.']);
            exit;
        }

        // Recuperar arquivo temporário
        $tmpPath = $_SESSION['cust_import_tmp_file'] ?? null;
        $ext = $_SESSION['cust_import_tmp_ext'] ?? 'csv';
        if (!$tmpPath || !file_exists($tmpPath)) {
            echo json_encode(['success' => false, 'message' => 'Arquivo temporário não encontrado. Faça o upload novamente.']);
            exit;
        }

        // Ler arquivo
        $rows = [];
        if (in_array($ext, ['csv', 'txt'])) {
            $rows = $this->parseCsvFile($tmpPath);
        } else {
            if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
                $rows = $this->parseExcelFile($tmpPath);
            } else {
                $rows = $this->parseCsvFile($tmpPath);
            }
        }

        if (empty($rows)) {
            echo json_encode(['success' => false, 'message' => 'Arquivo vazio ou não foi possível reler os dados.']);
            exit;
        }

        $totalRows = count($rows);

        // ── Criar lote de importação (Rec 3 — rastreamento) ──
        $batchId = $this->importBatchModel->create([
            'tenant_id'   => $_SESSION['tenant_id'] ?? 0,
            'entity_type' => 'customers',
            'file_name'   => basename($tmpPath),
            'total_rows'  => $totalRows,
            'import_mode' => $importMode,
            'mapping_json'=> json_encode($mapping),
            'created_by'  => $_SESSION['user_id'] ?? null,
        ]);

        // ── Progresso via session (Rec 1) ──
        $_SESSION['import_progress'] = [
            'batch_id'  => $batchId,
            'total'     => $totalRows,
            'processed' => 0,
            'imported'  => 0,
            'updated'   => 0,
            'skipped'   => 0,
            'errors'    => 0,
            'status'    => 'processing',
        ];

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $warnings = [];
        $progressUpdateInterval = max(1, (int) floor($totalRows / 50)); // atualizar progresso a cada ~2%

        foreach ($rows as $lineNum => $row) {
            $lineDisplay = $lineNum + 2;

            // ── Verificação de limite por registro (apenas modo create) ──
            if ($importMode !== 'update' && $maxCustomers !== null && ($imported + $currentCustomers) >= $maxCustomers) {
                $remaining = $totalRows - $lineNum;
                $errors[] = ['line' => $lineDisplay, 'message' => "Limite do plano atingido. {$remaining} registro(s) restante(s) não importado(s)."];
                $skipped += $remaining;
                break;
            }

            // Aplicar mapeamento
            $mapped = [];
            foreach ($mapping as $fileCol => $sysField) {
                if (!empty($sysField) && $sysField !== '_skip' && isset($row[$fileCol])) {
                    $mapped[$sysField] = trim($row[$fileCol]);
                }
            }

            // Validar obrigatórios
            if (empty($mapped['name'])) {
                $errors[] = ['line' => $lineDisplay, 'message' => 'Nome do cliente é obrigatório.'];
                $skipped++;
                continue;
            }

            // ══════════════════════════════════════════
            // NORMALIZAÇÃO INTELIGENTE DE DADOS
            // ══════════════════════════════════════════

            // ── 1. Detecção automática CPF/CNPJ → person_type ──
            if (!empty($mapped['document'])) {
                $docDigits = preg_replace('/\D/', '', $mapped['document']);

                // ── 0. Recuperar zeros à esquerda perdidos pelo CSV/Excel ──
                $docLen = strlen($docDigits);
                if ($docLen > 0 && $docLen < 11) {
                    // Tentar CPF (pad 11), senão tentar CNPJ (pad 14)
                    $paddedCpf = str_pad($docDigits, 11, '0', STR_PAD_LEFT);
                    if (Validator::isValidCpf($paddedCpf)) {
                        $docDigits = $paddedCpf;
                        $mapped['document'] = $paddedCpf;
                    } else {
                        $paddedCnpj = str_pad($docDigits, 14, '0', STR_PAD_LEFT);
                        if (Validator::isValidCnpj($paddedCnpj)) {
                            $docDigits = $paddedCnpj;
                            $mapped['document'] = $paddedCnpj;
                        }
                    }
                } elseif ($docLen > 11 && $docLen < 14) {
                    // Pode ser CNPJ sem zeros à esquerda — tentar preencher para 14
                    $padded = str_pad($docDigits, 14, '0', STR_PAD_LEFT);
                    if (Validator::isValidCnpj($padded)) {
                        $docDigits = $padded;
                        $mapped['document'] = $padded;
                    }
                }

                if (empty($mapped['person_type'])) {
                    if (strlen($docDigits) === 14) {
                        $mapped['person_type'] = 'PJ';
                    } elseif (strlen($docDigits) === 11) {
                        $mapped['person_type'] = 'PF';
                    } elseif (strlen($docDigits) > 11) {
                        $mapped['person_type'] = 'PJ';
                    } else {
                        $mapped['person_type'] = 'PF';
                    }
                }

                // ── 2. Validação de CPF/CNPJ (warning, não bloqueia) ──
                $personType = strtoupper(trim($mapped['person_type'] ?? 'PF'));
                if ($personType === 'PJ') {
                    if (strlen($docDigits) === 14 && !Validator::isValidCnpj($docDigits)) {
                        $warnings[] = ['line' => $lineDisplay, 'message' => 'CNPJ "' . $mapped['document'] . '" possui dígitos verificadores inválidos.'];
                    } elseif (strlen($docDigits) !== 14 && strlen($docDigits) > 0) {
                        $warnings[] = ['line' => $lineDisplay, 'message' => 'Documento "' . $mapped['document'] . '" não possui 14 dígitos para CNPJ.'];
                    }
                } else {
                    if (strlen($docDigits) === 11 && !Validator::isValidCpf($docDigits)) {
                        $warnings[] = ['line' => $lineDisplay, 'message' => 'CPF "' . $mapped['document'] . '" possui dígitos verificadores inválidos.'];
                    } elseif (strlen($docDigits) !== 11 && strlen($docDigits) > 0) {
                        $warnings[] = ['line' => $lineDisplay, 'message' => 'Documento "' . $mapped['document'] . '" não possui 11 dígitos para CPF.'];
                    }
                }

                // ── 3. Detecção de duplicados (comportamento depende do modo) ──
                if (strlen($docDigits) > 0) {
                    $existing = $this->customerModel->findByDocument($docDigits);
                    if ($existing) {
                        if ($importMode === 'create') {
                            $warnings[] = ['line' => $lineDisplay, 'message' => 'Documento já cadastrado — Cliente: "' . $existing['name'] . '" (Cód: ' . ($existing['code'] ?? 'N/A') . '). Registro importado mesmo assim.'];
                        }
                        // Para update/create_or_update, a existência é tratada no bloco de persistência abaixo
                    }
                }
            }

            // ── 4. Normalização de person_type ──
            if (!empty($mapped['person_type'])) {
                $pt = strtoupper(trim($mapped['person_type']));
                $ptMap = [
                    'PF' => 'PF', 'FISICA' => 'PF', 'FÍSICA' => 'PF', 'PESSOA FISICA' => 'PF', 'PESSOA FÍSICA' => 'PF', 'F' => 'PF', 'CPF' => 'PF',
                    'PJ' => 'PJ', 'JURIDICA' => 'PJ', 'JURÍDICA' => 'PJ', 'PESSOA JURIDICA' => 'PJ', 'PESSOA JURÍDICA' => 'PJ', 'J' => 'PJ', 'CNPJ' => 'PJ',
                ];
                $mapped['person_type'] = $ptMap[$pt] ?? 'PF';
            }

            // ── 5. Normalização de data (birth_date) ──
            if (!empty($mapped['birth_date'])) {
                $mapped['birth_date'] = $this->normalizeDateForImport($mapped['birth_date']);
            }

            // ── 6. Normalização de gênero ──
            if (!empty($mapped['gender'])) {
                $g = strtoupper(trim($mapped['gender']));
                $gMap = [
                    'M' => 'M', 'MASCULINO' => 'M', 'MASC' => 'M', 'MALE' => 'M', 'H' => 'M', 'HOMEM' => 'M',
                    'F' => 'F', 'FEMININO' => 'F', 'FEM' => 'F', 'FEMALE' => 'F', 'MULHER' => 'F',
                    'O' => 'O', 'OUTRO' => 'O', 'OTHER' => 'O', 'NB' => 'O', 'NAO BINARIO' => 'O', 'NÃO BINÁRIO' => 'O',
                ];
                $mapped['gender'] = $gMap[$g] ?? null;
            }

            // ── 7. Normalização de UF ──
            if (!empty($mapped['address_state'])) {
                $mapped['address_state'] = $this->normalizeUfForImport($mapped['address_state']);
            }

            // ── 8. Validação de e-mail ──
            if (!empty($mapped['email']) && !filter_var($mapped['email'], FILTER_VALIDATE_EMAIL)) {
                $warnings[] = ['line' => $lineDisplay, 'message' => 'E-mail "' . $mapped['email'] . '" possui formato inválido.'];
            }
            if (!empty($mapped['email'])) {
                $mapped['email'] = strtolower(trim($mapped['email']));
            }
            if (!empty($mapped['email_secondary'])) {
                $mapped['email_secondary'] = strtolower(trim($mapped['email_secondary']));
                if (!filter_var($mapped['email_secondary'], FILTER_VALIDATE_EMAIL)) {
                    $warnings[] = ['line' => $lineDisplay, 'message' => 'E-mail secundário "' . $mapped['email_secondary'] . '" possui formato inválido.'];
                }
            }

            // ── 9. Normalização de status ──
            if (!empty($mapped['status'])) {
                $st = strtolower(trim($mapped['status']));
                $stMap = [
                    'ativo' => 'active', 'active' => 'active', 'a' => 'active', '1' => 'active', 'sim' => 'active', 'yes' => 'active',
                    'inativo' => 'inactive', 'inactive' => 'inactive', 'i' => 'inactive', '0' => 'inactive', 'nao' => 'inactive', 'não' => 'inactive', 'no' => 'inactive',
                    'bloqueado' => 'blocked', 'blocked' => 'blocked', 'b' => 'blocked',
                ];
                $mapped['status'] = $stMap[$st] ?? 'active';
            }

            // ── 10. Preencher created_by com usuário logado ──
            $mapped['created_by'] = $_SESSION['user_id'] ?? null;

            // ── 11. Sanitização de telefones (remover caracteres não numéricos exceto +) ──
            foreach (['phone', 'cellphone', 'phone_commercial'] as $phoneField) {
                if (!empty($mapped[$phoneField])) {
                    $mapped[$phoneField] = preg_replace('/[^\d+() -]/', '', $mapped[$phoneField]);
                }
            }

            // ── 12. Normalização de CEP ──
            if (!empty($mapped['zipcode'])) {
                $mapped['zipcode'] = preg_replace('/\D/', '', $mapped['zipcode']);
                if (strlen($mapped['zipcode']) === 8) {
                    $mapped['zipcode'] = substr($mapped['zipcode'], 0, 5) . '-' . substr($mapped['zipcode'], 5, 3);
                }
            }

            // ── 13. Normalização de valores monetários ──
            if (!empty($mapped['credit_limit'])) {
                $mapped['credit_limit'] = str_replace(['R$', ' ', '.'], ['', '', ''], $mapped['credit_limit']);
                $mapped['credit_limit'] = str_replace(',', '.', $mapped['credit_limit']);
            }
            if (!empty($mapped['discount_default'])) {
                $mapped['discount_default'] = str_replace(['%', ' '], '', $mapped['discount_default']);
                $mapped['discount_default'] = str_replace(',', '.', $mapped['discount_default']);
            }

            // ══════════════════════════════════════════
            // PERSISTÊNCIA — com suporte a modo create/update/merge
            // ══════════════════════════════════════════

            try {
                $existingCustomer = null;
                if (!empty($mapped['document'])) {
                    $docDigits = preg_replace('/\D/', '', $mapped['document']);
                    if (strlen($docDigits) > 0) {
                        $existingCustomer = $this->customerModel->findByDocument($docDigits);
                    }
                }

                if ($importMode === 'update') {
                    // Apenas atualizar existentes, ignorar novos
                    if ($existingCustomer) {
                        $mapped['id'] = $existingCustomer['id'];
                        $result = $this->customerModel->updateFromImport($mapped);
                        if ($result) {
                            $updated++;
                            $this->importBatchModel->addItem($batchId, $existingCustomer['id'], 'updated', json_encode($row), $lineDisplay);
                            $this->logger->log('IMPORT_CUSTOMER_UPDATE', "Cliente atualizado ID: {$existingCustomer['id']} Nome: {$mapped['name']}");
                        } else {
                            $errors[] = ['line' => $lineDisplay, 'message' => 'Erro ao atualizar "' . $mapped['name'] . '" no banco de dados.'];
                        }
                    } else {
                        $skipped++;
                        $warnings[] = ['line' => $lineDisplay, 'message' => 'Cliente com documento "' . ($mapped['document'] ?? 'N/A') . '" não encontrado para atualização. Ignorado.'];
                    }
                } elseif ($importMode === 'create_or_update') {
                    // Atualizar se existir, criar se não existir
                    if ($existingCustomer) {
                        $mapped['id'] = $existingCustomer['id'];
                        $result = $this->customerModel->updateFromImport($mapped);
                        if ($result) {
                            $updated++;
                            $this->importBatchModel->addItem($batchId, $existingCustomer['id'], 'updated', json_encode($row), $lineDisplay);
                            $this->logger->log('IMPORT_CUSTOMER_UPDATE', "Cliente atualizado ID: {$existingCustomer['id']} Nome: {$mapped['name']}");
                        } else {
                            $errors[] = ['line' => $lineDisplay, 'message' => 'Erro ao atualizar "' . $mapped['name'] . '" no banco de dados.'];
                        }
                    } else {
                        $mapped['import_batch_id'] = $batchId;
                        $customerId = $this->customerModel->importFromMapped($mapped);
                        if ($customerId) {
                            $imported++;
                            $this->importBatchModel->addItem($batchId, $customerId, 'created', json_encode($row), $lineDisplay);
                            $this->logger->log('IMPORT_CUSTOMER', "Cliente importado ID: {$customerId} Nome: {$mapped['name']}");
                        } else {
                            $errors[] = ['line' => $lineDisplay, 'message' => 'Erro ao salvar "' . $mapped['name'] . '" no banco de dados.'];
                        }
                    }
                } else {
                    // Modo padrão: create
                    $mapped['import_batch_id'] = $batchId;
                    $customerId = $this->customerModel->importFromMapped($mapped);
                    if ($customerId) {
                        $imported++;
                        $this->importBatchModel->addItem($batchId, $customerId, 'created', json_encode($row), $lineDisplay);
                        $this->logger->log('IMPORT_CUSTOMER', "Cliente importado ID: {$customerId} Nome: {$mapped['name']}");
                    } else {
                        $errors[] = ['line' => $lineDisplay, 'message' => 'Erro ao salvar "' . $mapped['name'] . '" no banco de dados.'];
                    }
                }
            } catch (\Exception $e) {
                $errors[] = ['line' => $lineDisplay, 'message' => 'Erro: ' . $e->getMessage()];
            }

            // ── Atualizar progresso na session (Rec 1) ──
            if (($lineNum + 1) % $progressUpdateInterval === 0 || ($lineNum + 1) === $totalRows) {
                $_SESSION['import_progress'] = [
                    'batch_id'  => $batchId,
                    'total'     => $totalRows,
                    'processed' => $lineNum + 1,
                    'imported'  => $imported,
                    'updated'   => $updated,
                    'skipped'   => $skipped,
                    'errors'    => count($errors),
                    'status'    => 'processing',
                ];
                // Atualizar progresso no banco
                $this->importBatchModel->updateProgress($batchId, $lineNum + 1, $imported, $skipped, count($errors), count($warnings));
            }
        }

        // ── Finalizar lote ──
        $batchStatus = count($errors) > 0 ? 'completed_with_errors' : 'completed';
        $this->importBatchModel->finalize($batchId, $batchStatus, $imported, $updated, $skipped, json_encode($errors), json_encode($warnings));

        // Limpar arquivo temporário
        if (file_exists($tmpPath)) {
            unlink($tmpPath);
        }
        unset($_SESSION['cust_import_tmp_file'], $_SESSION['cust_import_tmp_ext']);

        // Atualizar progresso final
        $_SESSION['import_progress'] = [
            'batch_id'  => $batchId,
            'total'     => $totalRows,
            'processed' => $totalRows,
            'imported'  => $imported,
            'updated'   => $updated,
            'skipped'   => $skipped,
            'errors'    => count($errors),
            'status'    => 'completed',
        ];

        // Log de auditoria
        $userName = $_SESSION['user_name'] ?? 'Sistema';
        $modeLabel = ['create' => 'criação', 'update' => 'atualização', 'create_or_update' => 'criação/atualização'][$importMode] ?? 'criação';
        $this->logger->log('CUSTOMER_IMPORT', "Importação ({$modeLabel}) de {$imported} cliente(s) criado(s), {$updated} atualizado(s), {$skipped} ignorado(s) por {$userName}");

        echo json_encode([
            'success'  => true,
            'imported' => $imported,
            'updated'  => $updated,
            'skipped'  => $skipped,
            'errors'   => $errors,
            'warnings' => $warnings,
            'batch_id' => $batchId,
            'mode'     => $importMode,
        ]);
        exit;
    }

    // ═══════════════════════════════════════════════
    //  IMPORTAÇÃO: Progresso em tempo real (Rec 1)
    // ═══════════════════════════════════════════════

    public function getImportProgress() {
        header('Content-Type: application/json');

        $progress = $_SESSION['import_progress'] ?? null;
        if (!$progress) {
            echo json_encode(['success' => false, 'message' => 'Nenhuma importação em andamento.']);
            exit;
        }

        echo json_encode(['success' => true, 'progress' => $progress]);
        exit;
    }

    // ═══════════════════════════════════════════════
    //  IMPORTAÇÃO: Desfazer importação (Rec 3)
    // ═══════════════════════════════════════════════

    public function undoImport() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método inválido.']);
            exit;
        }

        $batchId = (int) Input::post('batch_id', 'int', 0);
        if ($batchId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID do lote inválido.']);
            exit;
        }

        $batch = $this->importBatchModel->findById($batchId);
        if (!$batch) {
            echo json_encode(['success' => false, 'message' => 'Lote de importação não encontrado.']);
            exit;
        }

        if ($batch['status'] === 'undone') {
            echo json_encode(['success' => false, 'message' => 'Esta importação já foi desfeita.']);
            exit;
        }

        // Obter itens criados neste lote
        $createdItems = $this->importBatchModel->getCreatedItems($batchId);
        $deletedCount = 0;

        foreach ($createdItems as $item) {
            try {
                $result = $this->customerModel->softDelete((int) $item['entity_id']);
                if ($result) {
                    $deletedCount++;
                }
            } catch (\Exception $e) {
                // Continuar mesmo se falhar em um registro
            }
        }

        // Marcar lote como desfeito
        $userId = $_SESSION['user_id'] ?? 0;
        $this->importBatchModel->markUndone($batchId, (int) $userId);

        // Log
        $userName = $_SESSION['user_name'] ?? 'Sistema';
        $this->logger->log('CUSTOMER_IMPORT_UNDO', "Importação (lote #{$batchId}) desfeita por {$userName}. {$deletedCount} cliente(s) removido(s).");

        echo json_encode([
            'success' => true,
            'message' => "{$deletedCount} cliente(s) removido(s) com sucesso.",
            'deleted' => $deletedCount,
        ]);
        exit;
    }

    // ═══════════════════════════════════════════════
    //  IMPORTAÇÃO: Histórico de importações
    // ═══════════════════════════════════════════════

    public function getImportHistory() {
        header('Content-Type: application/json');

        $tenantId = $_SESSION['tenant_id'] ?? 0;
        $batches = $this->importBatchModel->listByTenant($tenantId);

        echo json_encode([
            'success'  => true,
            'batches'  => $batches,
        ]);
        exit;
    }

    // ═══════════════════════════════════════════════
    //  IMPORTAÇÃO: Detalhes de um lote
    // ═══════════════════════════════════════════════

    public function getImportDetails() {
        header('Content-Type: application/json');

        $batchId  = (int) ($_GET['batch_id'] ?? 0);
        $tenantId = $_SESSION['tenant_id'] ?? 0;

        if ($batchId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Lote inválido.']);
            exit;
        }

        $batch = $this->importBatchModel->findById($batchId);
        if (!$batch || (int) $batch['tenant_id'] !== (int) $tenantId) {
            echo json_encode(['success' => false, 'message' => 'Lote não encontrado.']);
            exit;
        }

        $items = $this->importBatchModel->getItemsWithEntity($batchId, $batch['entity_type'] ?? 'customers');

        $created = [];
        $updated = [];
        foreach ($items as $item) {
            $entry = [
                'id'       => $item['entity_id'],
                'name'     => $item['entity_name'] ?? '—',
                'email'    => $item['entity_email'] ?? '',
                'document' => $item['entity_document'] ?? '',
                'line'     => $item['line_number'],
            ];
            if ($item['action'] === 'created') {
                $created[] = $entry;
            } elseif ($item['action'] === 'updated') {
                $updated[] = $entry;
            }
        }

        $errors   = !empty($batch['errors_json'])   ? json_decode($batch['errors_json'], true)   : [];
        $warnings = !empty($batch['warnings_json']) ? json_decode($batch['warnings_json'], true) : [];

        echo json_encode([
            'success'  => true,
            'batch'    => [
                'id'             => $batch['id'],
                'file_name'      => $batch['file_name'],
                'import_mode'    => $batch['import_mode'],
                'status'         => $batch['status'],
                'total_rows'     => $batch['total_rows'],
                'imported_count' => $batch['imported_count'],
                'updated_count'  => $batch['updated_count'],
                'skipped_count'  => $batch['skipped_count'],
                'error_count'    => $batch['error_count'],
                'warning_count'  => $batch['warning_count'],
                'created_at'     => $batch['created_at'],
            ],
            'created'  => $created,
            'updated'  => $updated,
            'errors'   => $errors,
            'warnings' => $warnings,
        ]);
        exit;
    }

    // ═══════════════════════════════════════════════
    //  IMPORTAÇÃO: Perfis de mapeamento (Rec 4)
    // ═══════════════════════════════════════════════

    public function getMappingProfiles() {
        header('Content-Type: application/json');

        $tenantId = $_SESSION['tenant_id'] ?? 0;
        $profiles = $this->mappingProfileModel->listByTenant($tenantId, 'customers');

        echo json_encode([
            'success'  => true,
            'profiles' => $profiles,
        ]);
        exit;
    }

    public function saveMappingProfile() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método inválido.']);
            exit;
        }

        $name = trim(Input::post('profile_name', 'string', ''));
        $mappingJson = Input::post('mapping');
        $isDefault = (int) Input::post('is_default', 'int', 0);
        $profileId = (int) Input::post('profile_id', 'int', 0);

        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Nome do perfil é obrigatório.']);
            exit;
        }

        $mapping = json_decode($mappingJson, true);
        if (empty($mapping)) {
            echo json_encode(['success' => false, 'message' => 'Mapeamento inválido.']);
            exit;
        }

        try {
            $tenantId = $_SESSION['tenant_id'] ?? 0;
            $importMode = Input::post('import_mode', 'string', 'create');

            if ($profileId > 0) {
                // Atualizar existente
                $result = $this->mappingProfileModel->update($profileId, [
                    'name'        => $name,
                    'mapping_json' => $mappingJson,
                    'import_mode' => $importMode,
                    'is_default'  => $isDefault,
                    'tenant_id'   => $tenantId,
                    'entity_type' => 'customers',
                ]);
                $msg = 'Perfil atualizado com sucesso.';
            } else {
                // Criar novo
                $profileId = $this->mappingProfileModel->create([
                    'tenant_id'   => $tenantId,
                    'entity_type' => 'customers',
                    'name'        => $name,
                    'mapping_json' => $mappingJson,
                    'import_mode' => $importMode,
                    'is_default'  => $isDefault,
                    'created_by'  => $_SESSION['user_id'] ?? null,
                ]);
                $msg = 'Perfil salvo com sucesso.';
            }

            echo json_encode(['success' => true, 'message' => $msg, 'profile_id' => $profileId]);
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            if (strpos($errorMsg, 'Duplicate entry') !== false) {
                echo json_encode(['success' => false, 'message' => 'Já existe um perfil com este nome.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao salvar perfil: ' . $errorMsg]);
            }
        }
        exit;
    }

    public function deleteMappingProfile() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método inválido.']);
            exit;
        }

        $profileId = (int) Input::post('profile_id', 'int', 0);
        if ($profileId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID do perfil inválido.']);
            exit;
        }

        $result = $this->mappingProfileModel->delete($profileId);
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Perfil excluído com sucesso.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao excluir perfil.']);
        }
        exit;
    }

    // ═══════════════════════════════════════════════
    //  Download modelo de importação CSV
    // ═══════════════════════════════════════════════

    public function downloadImportTemplate() {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="modelo_importacao_clientes.csv"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

        fputcsv($output, [
            'nome', 'tipo_pessoa', 'nome_fantasia', 'cpf_cnpj', 'rg_ie', 'im',
            'data_nascimento', 'genero', 'email', 'email_secundario',
            'telefone', 'celular', 'telefone_comercial', 'website', 'instagram',
            'nome_contato', 'cargo_contato',
            'cep', 'logradouro', 'tipo_logradouro', 'nome_logradouro', 'numero', 'bairro', 'complemento',
            'cidade', 'uf', 'origem', 'tags', 'observacoes',
            'status', 'prazo_pagamento', 'limite_credito', 'desconto_padrao'
        ], ';');
        fputcsv($output, [
            'Maria Silva', 'PF', '', '529.982.247-25', '12.345.678-9', '',
            '15/03/1990', 'F', 'maria@email.com', '',
            '(11) 3333-4444', '(11) 99999-0000', '', 'https://maria.com.br', 'mariasilva',
            '', '',
            '01001-000', 'Praça da Sé', 'Praça', 'da Sé', '100', 'Sé', 'Sala 5',
            'São Paulo', 'SP', 'Site', 'VIP,Varejo', 'Cliente desde 2020',
            'active', '30 dias', '5000.00', '5'
        ], ';');
        fputcsv($output, [
            'Empresa ABC Ltda', 'PJ', 'ABC', '11.222.333/0001-81', '123.456.789.001', '12345',
            '10/01/2005', '', 'contato@abc.com.br', 'financeiro@abc.com.br',
            '(21) 3333-4444', '(21) 98888-7777', '(21) 3333-5555', 'https://abc.com.br', 'empresa_abc',
            'João Gerente', 'Gerente de Compras',
            '20040-020', 'Av. Brasil', 'Avenida', 'Brasil', '500', 'Centro', '',
            'Rio de Janeiro', 'RJ', 'Indicação', 'Atacado,Indústria', '',
            'active', '60 dias', '25000.00', '10'
        ], ';');

        fclose($output);
        exit;
    }

    // ═══════════════════════════════════════════════
    //  MÉTODOS PRIVADOS — Helpers
    // ═══════════════════════════════════════════════

    /**
     * Captura e sanitiza todos os campos do formulário de cliente.
     * Aplicando sanitizações específicas por campo conforme checklist.
     *
     * @return array Dados sanitizados
     */
    private function captureFormData(): array
    {
        // Campos básicos de identificação
        $personType  = Input::post('person_type', 'string', 'PF');
        $name        = Input::post('name');
        $fantasyName = Input::post('fantasy_name');
        $document    = Input::post('document');
        $rgIe        = Input::post('rg_ie');
        $im          = Input::post('im');
        $birthDate   = Input::post('birth_date');
        $gender      = Input::post('gender');

        // Converter data de nascimento de DD/MM/AAAA para Y-m-d
        if ($birthDate) {
            $birthDate = trim($birthDate);
            // Aceitar formato DD/MM/AAAA
            if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $birthDate, $m)) {
                $birthDate = $m[3] . '-' . $m[2] . '-' . $m[1];
            }
            // Aceitar formato DD-MM-AAAA
            elseif (preg_match('#^(\d{2})-(\d{2})-(\d{4})$#', $birthDate, $m)) {
                $birthDate = $m[3] . '-' . $m[2] . '-' . $m[1];
            }
            // Se já estiver em Y-m-d, manter
        }

        // Campos de contato
        $email          = Input::post('email', 'email');
        $emailSecondary = Input::post('email_secondary', 'email');
        $phone          = Input::post('phone', 'phone');
        $cellphone      = Input::post('cellphone', 'phone');
        $phoneComm      = Input::post('phone_commercial', 'phone');
        $website        = Input::post('website');
        $instagram      = Input::post('instagram');
        $contactName    = Input::post('contact_name');
        $contactRole    = Input::post('contact_role');

        // Campos de endereço
        $zipcode       = Input::post('zipcode');
        $street        = Input::post('address_street');
        $number        = Input::post('address_number');
        $complement    = Input::post('address_complement');
        $neighborhood  = Input::post('address_neighborhood');
        $city          = Input::post('address_city');
        $state         = Input::post('address_state');
        $country       = Input::post('address_country', 'string', 'Brasil');
        $ibge          = Input::post('address_ibge');

        // Campos comerciais
        $priceTableId    = Input::post('price_table_id', 'int');
        $paymentTerm     = Input::post('payment_term');
        $creditLimit     = Input::post('credit_limit');
        $discountDefault = Input::post('discount_default');
        $sellerId        = Input::post('seller_id', 'int');
        $origin          = Input::post('origin');
        $tags            = Input::post('tags');
        $observations    = Input::post('observations');
        $status          = Input::post('status', 'string', 'active');

        // Sanitizações específicas
        $name     = trim(preg_replace('/\s+/', ' ', $name ?? ''));
        $document = preg_replace('/\D/', '', $document ?? '');
        $phone    = preg_replace('/\D/', '', $phone ?? '');
        $cellphone = preg_replace('/\D/', '', $cellphone ?? '');
        $phoneComm = preg_replace('/\D/', '', $phoneComm ?? '');
        $zipcode  = preg_replace('/\D/', '', $zipcode ?? '');
        $email    = $email ? trim(strtolower($email)) : null;
        $emailSecondary = $emailSecondary ? trim(strtolower($emailSecondary)) : null;

        // Instagram: remover @ inicial
        if ($instagram && strpos($instagram, '@') === 0) {
            $instagram = substr($instagram, 1);
        }

        // Website: adicionar https:// se não tiver protocolo
        if ($website && !preg_match('#^https?://#i', $website)) {
            $website = 'https://' . $website;
        }

        // Credit limit: converter para float (aceitar formato BR)
        if ($creditLimit !== null && $creditLimit !== '') {
            $creditLimit = str_replace(['R$', ' ', '.'], '', $creditLimit);
            $creditLimit = str_replace(',', '.', $creditLimit);
            $creditLimit = is_numeric($creditLimit) ? (float) $creditLimit : null;
        }

        // Discount: converter para float
        if ($discountDefault !== null && $discountDefault !== '') {
            $discountDefault = str_replace(['%', ' '], '', $discountDefault);
            $discountDefault = str_replace(',', '.', $discountDefault);
            $discountDefault = is_numeric($discountDefault) ? (float) $discountDefault : null;
        }

        return [
            'person_type'          => $personType,
            'name'                 => $name,
            'fantasy_name'         => $fantasyName ?: null,
            'document'             => $document ?: null,
            'rg_ie'                => $rgIe ?: null,
            'im'                   => $im ?: null,
            'birth_date'           => $birthDate ?: null,
            'gender'               => $gender ?: null,
            'email'                => $email ?: null,
            'email_secondary'      => $emailSecondary ?: null,
            'phone'                => $phone ?: null,
            'cellphone'            => $cellphone ?: null,
            'phone_commercial'     => $phoneComm ?: null,
            'website'              => $website ?: null,
            'instagram'            => $instagram ?: null,
            'contact_name'         => $contactName ?: null,
            'contact_role'         => $contactRole ?: null,
            'zipcode'              => $zipcode ?: null,
            'address_street'       => $street ?: null,
            'address_number'       => $number ?: null,
            'address_complement'   => $complement ?: null,
            'address_neighborhood' => $neighborhood ?: null,
            'address_city'         => $city ?: null,
            'address_state'        => $state ?: null,
            'address_country'      => $country ?: 'Brasil',
            'address_ibge'         => $ibge ?: null,
            'price_table_id'       => $priceTableId ?: null,
            'payment_term'         => $paymentTerm ?: null,
            'credit_limit'         => $creditLimit,
            'discount_default'     => $discountDefault,
            'seller_id'            => $sellerId ?: null,
            'origin'               => $origin ?: null,
            'tags'                 => $tags ?: null,
            'observations'         => $observations ?: null,
            'status'               => $status ?: 'active',
        ];
    }

    /**
     * Validação server-side completa dos dados do cliente.
     *
     * @param array    $data      Dados sanitizados
     * @param int|null $excludeId ID a excluir na validação de unicidade (edição)
     * @return Validator
     */
    private function validateCustomerData(array $data, ?int $excludeId = null): Validator
    {
        $v = new Validator();

        // Obrigatórios
        $v->required('person_type', $data['person_type'], 'Tipo de Pessoa')
          ->inList('person_type', $data['person_type'], ['PF', 'PJ'], 'Tipo de Pessoa')
          ->required('name', $data['name'], 'Nome / Razão Social')
          ->minLength('name', $data['name'], 3, 'Nome / Razão Social')
          ->maxLength('name', $data['name'], 191, 'Nome / Razão Social');

        // Documento (CPF/CNPJ) — validação conforme tipo
        if (!empty($data['document'])) {
            $v->document('document', $data['document'], $data['person_type'] ?? 'PF', 'CPF/CNPJ');
        }

        // Fantasy name
        $v->maxLength('fantasy_name', $data['fantasy_name'], 191, 'Nome Fantasia');

        // RG/IE e IM
        $v->maxLength('rg_ie', $data['rg_ie'], 30, 'RG / Inscrição Estadual')
          ->maxLength('im', $data['im'], 30, 'Inscrição Municipal');

        // Data de nascimento
        if (!empty($data['birth_date'])) {
            $v->date('birth_date', $data['birth_date'], 'Data de Nascimento')
              ->dateNotFuture('birth_date', $data['birth_date'], 'Data de Nascimento');
        }

        // Gênero
        if (!empty($data['gender'])) {
            $v->inList('gender', $data['gender'], ['M', 'F', 'O'], 'Gênero');
        }

        // E-mails
        if (!empty($data['email'])) {
            $v->email('email', $data['email'], 'E-mail')
              ->maxLength('email', $data['email'], 191, 'E-mail');
        }
        if (!empty($data['email_secondary'])) {
            $v->email('email_secondary', $data['email_secondary'], 'E-mail Secundário')
              ->maxLength('email_secondary', $data['email_secondary'], 191, 'E-mail Secundário');
        }

        // Telefones
        $v->maxLength('phone', $data['phone'], 20, 'Telefone')
          ->maxLength('cellphone', $data['cellphone'], 20, 'Celular')
          ->maxLength('phone_commercial', $data['phone_commercial'], 20, 'Telefone Comercial');

        // Website
        if (!empty($data['website'])) {
            $v->url('website', $data['website'], 'Website')
              ->maxLength('website', $data['website'], 255, 'Website');
        }

        // Instagram
        $v->maxLength('instagram', $data['instagram'], 50, 'Instagram');

        // Contato PJ
        $v->maxLength('contact_name', $data['contact_name'], 100, 'Nome do Contato')
          ->maxLength('contact_role', $data['contact_role'], 80, 'Cargo do Contato');

        // Endereço
        $v->maxLength('address_street', $data['address_street'], 200, 'Logradouro')
          ->maxLength('address_number', $data['address_number'], 20, 'Número')
          ->maxLength('address_complement', $data['address_complement'], 100, 'Complemento')
          ->maxLength('address_neighborhood', $data['address_neighborhood'], 100, 'Bairro')
          ->maxLength('address_city', $data['address_city'], 100, 'Cidade');

        // UF
        if (!empty($data['address_state'])) {
            $ufs = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
            $v->inList('address_state', strtoupper($data['address_state']), $ufs, 'UF');
        }

        // Comercial
        $v->maxLength('payment_term', $data['payment_term'], 50, 'Condição de Pagamento');

        if ($data['credit_limit'] !== null && $data['credit_limit'] !== '') {
            $v->decimal('credit_limit', $data['credit_limit'], 'Limite de Crédito');
        }
        if ($data['discount_default'] !== null && $data['discount_default'] !== '') {
            $v->decimal('discount_default', $data['discount_default'], 'Desconto Padrão')
              ->between('discount_default', $data['discount_default'], 0, 100, 'Desconto Padrão');
        }

        // Origin e Tags
        $v->maxLength('origin', $data['origin'], 50, 'Origem')
          ->maxLength('tags', $data['tags'], 500, 'Tags');

        // Status
        $v->inList('status', $data['status'], ['active', 'inactive', 'blocked'], 'Status');

        return $v;
    }

    /**
     * Captura filtros avançados do GET para listagem.
     *
     * @return array Filtros sanitizados
     */
    private function captureFilters(): array
    {
        $filters = [];

        $status     = Input::get('status');
        $personType = Input::get('person_type');
        $state      = Input::get('state');
        $city       = Input::get('city');
        $sellerId   = Input::get('seller_id', 'int');
        $from       = Input::get('from');
        $to         = Input::get('to');
        $tags       = Input::get('tags');
        $search     = Input::get('search');

        if ($status)     $filters['status']      = $status;
        if ($personType) $filters['person_type']  = $personType;
        if ($state)      $filters['state']        = $state;
        if ($city)       $filters['city']         = $city;
        if ($sellerId)   $filters['seller_id']    = $sellerId;
        if ($from)       $filters['from']         = $from;
        if ($to)         $filters['to']           = $to;
        if ($tags)       $filters['tags']         = $tags;
        if ($search)     $filters['search']       = $search;

        return $filters;
    }

    /**
     * Busca os últimos pedidos de um cliente (para a ficha do cliente).
     *
     * @param int $customerId ID do cliente
     * @param int $limit      Número máximo de pedidos
     * @return array Lista de pedidos recentes
     */
    private function getRecentOrders(int $customerId, int $limit = 5): array
    {
        $query = "SELECT o.id, o.total_amount, o.status, o.created_at
                  FROM orders o
                  WHERE o.customer_id = :cid
                  ORDER BY o.created_at DESC
                  LIMIT :lim";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':cid', $customerId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Processa upload de foto do cliente.
     *
     * @return string|null Caminho da foto salva ou null
     */
    private function handlePhotoUpload(): ?string
    {
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $maxSize = 5 * 1024 * 1024;
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
            $fileType = mime_content_type($_FILES['photo']['tmp_name']);
            
            if ($_FILES['photo']['size'] > $maxSize || !in_array($fileType, $allowedTypes)) {
                return null;
            }

            $uploadDir = TenantManager::getTenantUploadBase() . 'customers/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileExtension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid() . '.' . $fileExtension;
            $targetFile = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetFile)) {
                return $targetFile;
            }
        }
        return null;
    }

    /**
     * Verifica se a requisição é AJAX.
     *
     * @return bool
     */
    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Envia resposta JSON e encerra.
     *
     * @param array $data
     */
    private function jsonResponse(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    // ═══════════════════════════════════════════════
    //  Helpers de parse CSV / Excel
    // ═══════════════════════════════════════════════

    private function parseCsvFile($filePath) {
        $rows = [];
        $handle = fopen($filePath, 'r');
        if (!$handle) return $rows;

        // Detect BOM
        $bom = fread($handle, 3);
        if ($bom !== chr(0xEF) . chr(0xBB) . chr(0xBF)) {
            rewind($handle);
        }

        // Detect separator
        $firstLine = fgets($handle);
        rewind($handle);
        $bom = fread($handle, 3);
        if ($bom !== chr(0xEF) . chr(0xBB) . chr(0xBF)) {
            rewind($handle);
        }

        $separator = (substr_count($firstLine, ';') >= substr_count($firstLine, ',')) ? ';' : ',';

        $header = fgetcsv($handle, 0, $separator);
        if (!$header) {
            fclose($handle);
            return $rows;
        }

        // Normalize header keys
        $header = array_map(function ($h) {
            return trim(mb_strtolower($h));
        }, $header);

        while (($line = fgetcsv($handle, 0, $separator)) !== false) {
            $lineCount = count($line);
            $headerCount = count($header);
            if ($lineCount === $headerCount) {
                $rows[] = array_combine($header, $line);
            } elseif ($lineCount < $headerCount) {
                // Linha com menos colunas: preencher com vazio
                $line = array_pad($line, $headerCount, '');
                $rows[] = array_combine($header, $line);
            } elseif ($lineCount > $headerCount) {
                // Linha com mais colunas: truncar excedente
                $rows[] = array_combine($header, array_slice($line, 0, $headerCount));
            }
        }

        fclose($handle);
        return $rows;
    }

    /**
     * Faz parse de arquivo Excel (.xlsx) e retorna array de linhas.
     *
     * @param string $filePath Caminho do arquivo
     * @return array Linhas parseadas como arrays associativos
     */
    private function parseExcelFile($filePath) {
        $rows = [];

        // Se PhpSpreadsheet estiver disponível
        if (class_exists('\\PhpOffice\\PhpSpreadsheet\\IOFactory')) {
            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
                $worksheet = $spreadsheet->getActiveSheet();
                $data = $worksheet->toArray();

                if (empty($data)) return $rows;

                $header = array_map(function ($h) {
                    return trim(mb_strtolower($h ?? ''));
                }, array_shift($data));

                foreach ($data as $line) {
                    $lineCount = count($line);
                    $headerCount = count($header);
                    if ($lineCount === $headerCount) {
                        $rows[] = array_combine($header, $line);
                    } elseif ($lineCount < $headerCount) {
                        $line = array_pad($line, $headerCount, '');
                        $rows[] = array_combine($header, $line);
                    } elseif ($lineCount > $headerCount) {
                        $rows[] = array_combine($header, array_slice($line, 0, $headerCount));
                    }
                }
            } catch (\Exception $e) {
                // Falha ao ler o Excel, retorna vazio
            }
        }

        return $rows;
    }

    // ═══════════════════════════════════════════════
    //  IMPORTAÇÃO: Helpers de normalização
    // ═══════════════════════════════════════════════

    /**
     * Normaliza uma data para o formato Y-m-d aceito pelo banco.
     * Aceita: dd/mm/yyyy, dd-mm-yyyy, dd.mm.yyyy, yyyy-mm-dd, yyyy/mm/dd, mm/dd/yyyy
     *
     * @param string $dateStr Data em formato variável
     * @return string|null Data normalizada (Y-m-d) ou null se inválida
     */
    private function normalizeDateForImport(string $dateStr): ?string
    {
        $dateStr = trim($dateStr);
        if ($dateStr === '' || $dateStr === '0') return null;

        // Já está no formato correto Y-m-d
        $dt = \DateTime::createFromFormat('Y-m-d', $dateStr);
        if ($dt && $dt->format('Y-m-d') === $dateStr) {
            return $dateStr;
        }

        // dd/mm/yyyy ou dd-mm-yyyy ou dd.mm.yyyy
        $formats = ['d/m/Y', 'd-m-Y', 'd.m.Y', 'm/d/Y', 'Y/m/d', 'd/m/y', 'd-m-y'];
        foreach ($formats as $fmt) {
            $dt = \DateTime::createFromFormat($fmt, $dateStr);
            if ($dt) {
                // Validar se a data faz sentido (ex: dia não pode ser >31)
                $year = (int) $dt->format('Y');
                if ($year > 1900 && $year <= (int) date('Y')) {
                    return $dt->format('Y-m-d');
                }
            }
        }

        // Tentar parse genérico
        try {
            $ts = strtotime($dateStr);
            if ($ts !== false && $ts > strtotime('1900-01-01') && $ts <= time()) {
                return date('Y-m-d', $ts);
            }
        } catch (\Exception $e) {
            // Ignorar
        }

        return null;
    }

    /**
     * Normaliza o nome de um estado brasileiro para a sigla UF de 2 letras.
     *
     * @param string $state Nome ou sigla do estado
     * @return string Sigla UF normalizada ou valor original se não encontrado
     */
    private function normalizeUfForImport(string $state): string
    {
        $state = trim($state);
        if ($state === '') return '';

        // Já é UF de 2 letras
        $upper = strtoupper($state);
        $validUfs = [
            'AC','AL','AP','AM','BA','CE','DF','ES','GO','MA',
            'MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN',
            'RS','RO','RR','SC','SP','SE','TO'
        ];
        if (in_array($upper, $validUfs)) {
            return $upper;
        }

        // Mapa de nomes → UF
        $map = [
            'acre' => 'AC', 'alagoas' => 'AL', 'amapa' => 'AP', 'amapá' => 'AP',
            'amazonas' => 'AM', 'bahia' => 'BA', 'ceara' => 'CE', 'ceará' => 'CE',
            'distrito federal' => 'DF', 'espirito santo' => 'ES', 'espírito santo' => 'ES',
            'goias' => 'GO', 'goiás' => 'GO', 'maranhao' => 'MA', 'maranhão' => 'MA',
            'mato grosso' => 'MT', 'mato grosso do sul' => 'MS',
            'minas gerais' => 'MG', 'minas' => 'MG',
            'para' => 'PA', 'pará' => 'PA', 'paraiba' => 'PB', 'paraíba' => 'PB',
            'parana' => 'PR', 'paraná' => 'PR',
            'pernambuco' => 'PE', 'piaui' => 'PI', 'piauí' => 'PI',
            'rio de janeiro' => 'RJ', 'rio grande do norte' => 'RN', 'rio grande do sul' => 'RS',
            'rondonia' => 'RO', 'rondônia' => 'RO', 'roraima' => 'RR',
            'santa catarina' => 'SC', 'sao paulo' => 'SP', 'são paulo' => 'SP',
            'sergipe' => 'SE', 'tocantins' => 'TO',
        ];

        $normalized = mb_strtolower($state, 'UTF-8');
        return $map[$normalized] ?? $upper;
    }
}
