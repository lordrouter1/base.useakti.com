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
use Akti\Services\CustomerImportService;
use Akti\Services\CustomerExportService;
use Akti\Services\CustomerFormService;
use Akti\Services\ExternalApiService;
use Akti\Services\CustomerOrderHistoryService;
use Akti\Services\CustomerContactService;
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
    
    private Customer $customerModel;
    private CustomerContact $contactModel;
    private ImportBatch $importBatchModel;
    private ImportMappingProfile $mappingProfileModel;
    private Logger $logger;
    private \PDO $db;
    private CustomerImportService $importService;
    private CustomerExportService $exportService;
    private CustomerFormService $formService;
    private ExternalApiService $externalApiService;

    public function __construct(
        \PDO $db,
        Customer $customerModel,
        CustomerContact $contactModel,
        ImportBatch $importBatchModel,
        ImportMappingProfile $mappingProfileModel,
        Logger $logger,
        CustomerImportService $importService,
        CustomerExportService $exportService,
        CustomerFormService $formService,
        ExternalApiService $externalApiService
    ) {
        $this->db = $db;
        $this->customerModel = $customerModel;
        $this->contactModel = $contactModel;
        $this->importBatchModel = $importBatchModel;
        $this->mappingProfileModel = $mappingProfileModel;
        $this->logger = $logger;
        $this->importService = $importService;
        $this->exportService = $exportService;
        $this->formService = $formService;
        $this->externalApiService = $externalApiService;
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
        $data = $this->formService->captureFormData();

        // Validação server-side completa
        $v = $this->formService->validateCustomerData($data);

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
        $data['address'] = $this->formService->buildAddressJson($data);

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
        $filters = $this->captureFilters();

        $idsParam = Input::get('ids');
        $ids = null;
        if (!empty($idsParam)) {
            $ids = array_filter(array_map('intval', explode(',', $idsParam)));
        }

        $this->exportService->exportCsv($filters, $ids);
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

        $historyService = new CustomerOrderHistoryService($this->db);
        $result = $historyService->getOrderHistoryPaginated($customerId, $pageNum, $perPage);

        echo json_encode(array_merge(['success' => true], $result));
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

        $contactService = new CustomerContactService($this->db, $this->contactModel);
        $contacts = $contactService->listByCustomer($customerId);

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

        $contactService = new CustomerContactService($this->db, $this->contactModel);
        $result = $contactService->save([
            'contact_id'  => Input::post('contact_id', 'int'),
            'customer_id' => Input::post('customer_id', 'int'),
            'name'        => Input::post('name'),
            'role'        => Input::post('role'),
            'email'       => Input::post('email', 'email'),
            'phone'       => Input::post('phone', 'phone'),
            'is_primary'  => Input::post('is_primary', 'int', 0),
            'notes'       => Input::post('notes'),
        ]);

        if ($result['success']) {
            $action = (Input::post('contact_id', 'int')) ? 'atualizado' : 'criado';
            $this->logger->log('CUSTOMER_UPDATE', "Contato #{$result['id']} {$action} para cliente #" . Input::post('customer_id', 'int'));
        }

        echo json_encode($result);
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

        $contactService = new CustomerContactService($this->db, $this->contactModel);
        $result = $contactService->delete($contactId);

        if ($result['success']) {
            $this->logger->log('CUSTOMER_UPDATE', "Contato #{$contactId} removido");
        }

        echo json_encode($result);
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
    //  AJAX: Busca paginada de clientes (Select2 com scroll infinito)
    //  GET ?page=customers&action=searchAjax&q=termo&page=1&per_page=20
    // ═══════════════════════════════════════════════

    /**
     * Busca paginada para dropdowns com AJAX e scroll infinito (Select2).
     * Retorna JSON: { success, data, total, hasMore }
     */
    public function searchAjax(): void
    {
        header('Content-Type: application/json');

        $q       = Input::get('q') ?? '';
        $page    = Input::get('page', 'int', 1);
        $perPage = Input::get('per_page', 'int', 20);

        $result = $this->customerModel->searchPaginated($q, $page, $perPage);

        echo json_encode([
            'success' => true,
            'data'    => $result['data'],
            'total'   => $result['total'],
            'hasMore' => $result['hasMore'],
        ]);
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

        $result = $this->importService->parseFile($_FILES['import_file']);
        echo json_encode($result);
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

        $importMode = Input::post('import_mode', 'string', 'create');
        if (!in_array($importMode, ['create', 'update', 'create_or_update'])) {
            $importMode = 'create';
        }

        $userId = $_SESSION['user_id'] ?? 0;
        $tenantId = $_SESSION['tenant']['id'] ?? 0;

        $result = $this->importService->executeImport($mapping, $importMode, $userId, $tenantId);
        echo json_encode($result);
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

        $tenantId = $_SESSION['tenant']['id'] ?? 0;
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
        $tenantId = $_SESSION['tenant']['id'] ?? 0;

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

        $tenantId = $_SESSION['tenant']['id'] ?? 0;
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
            $tenantId = $_SESSION['tenant']['id'] ?? 0;
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

    /**
     * Download modelo de importação CSV.
     * Delegado ao CustomerImportService.
     */
    public function downloadImportTemplate() {
        $this->importService->generateTemplate();
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
        $historyService = new CustomerOrderHistoryService($this->db);
        return $historyService->getRecentOrders($customerId, $limit);
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
    //  Helpers de parse CSV / Excel — delegados ao CustomerImportService
    // ═══════════════════════════════════════════════

    private function parseCsvFile($filePath) {
        return $this->importService->parseCsvFile($filePath);
    }

    private function parseExcelFile($filePath) {
        return $this->importService->parseExcelFile($filePath);
    }

    private function normalizeDateForImport(string $dateStr): ?string {
        return $this->importService->normalizeDateForImport($dateStr);
    }

    private function normalizeUfForImport(string $state): string {
        return $this->importService->normalizeUfForImport($state);
    }
}
