<?php
/**
 * Mapa de rotas do sistema Akti.
 *
 * Cada chave é o valor de ?page= na URL. O array de configuração suporta:
 *
 *   'controller'     => (string)  Nome do controller (sem namespace, ex: 'ProductController')
 *   'default_action' => (string)  Método chamado quando action=index ou não há action. Default: 'index'
 *   'public'         => (bool)    Se true, não exige autenticação. Default: false
 *   'before_auth'    => (bool)    Se true, é processado ANTES do auth check (ex: login). Default: false
 *   'redirect'       => (string)  Se definido, redireciona para essa URL em vez de instanciar controller
 *   'view'           => (array)   Se definido, renderiza essas views diretamente (sem controller)
 *   'allow_unmapped' => (bool)    Se true, permite chamar qualquer método do controller (sem whitelist)
 *   'actions'        => (array)   Mapa de action => método ou config:
 *
 *       Formato simples (action chama método com mesmo nome):
 *           'store' => 'store'
 *
 *       Formato com rename (action chama método diferente):
 *           'createGradeType' => 'createGradeTypeAjax'
 *
 *       Formato com controller diferente:
 *           'generateCatalogLink' => ['controller' => 'CatalogController', 'method' => 'generate']
 *
 * ── Como adicionar uma nova rota ──
 * 1. Adicione a entrada neste arquivo
 * 2. Crie o controller em app/controllers/ com namespace Akti\Controllers
 * 3. Registre a página em app/config/menu.php (se aparecer no menu)
 * 4. Adicione a rota em tests/routes_test.php para cobertura de testes
 * 5. Execute: php vendor/bin/phpunit
 *
 * @see app/core/Router.php — Classe que consome este mapa
 * @see PROJECT_RULES.md — Módulo: Router
 */

return [

    // ══════════════════════════════════════════════════════════════
    // PÁGINAS PÚBLICAS (sem autenticação)
    // ══════════════════════════════════════════════════════════════

    'catalog' => [
        'controller'     => 'CatalogController',
        'default_action' => 'index',
        'public'         => true,
        'before_auth'    => true,
        'actions'        => [
            'addToCart'      => 'addToCart',
            'removeFromCart' => 'removeFromCart',
            'updateCartItem' => 'updateCartItem',
            'getCart'        => 'getCart',
            'confirmQuote'   => 'confirmQuote',
            'revokeQuote'    => 'revokeQuote',
            'getProducts'    => 'getProducts',
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // PORTAL DO CLIENTE (público, autenticação própria)
    // ══════════════════════════════════════════════════════════════

    'portal' => [
        'controller'     => 'PortalController',
        'default_action' => 'index',
        'public'         => true,
        'before_auth'    => true,
        'actions'        => [
            // Auth
            'login'             => 'login',
            'loginMagic'        => 'loginMagic',
            'requestMagicLink'  => 'requestMagicLink',
            'setupPassword'     => 'setupPassword',
            'logout'            => 'logout',
            'register'          => 'register',
            'forgotPassword'    => 'forgotPassword',
            'resetPassword'     => 'resetPassword',

            // 2FA
            'verify2fa'         => 'verify2fa',
            'resend2fa'         => 'resend2fa',
            'toggle2fa'         => 'toggle2fa',

            // Dashboard
            'dashboard'         => 'dashboard',

            // Pedidos
            'orders'            => 'orders',
            'orderDetail'       => 'orderDetail',
            'approveOrder'      => 'approveOrder',
            'rejectOrder'       => 'rejectOrder',
            'cancelApproval'    => 'cancelApproval',

            // Novo Pedido (orçamento)
            'newOrder'          => 'newOrder',
            'getProducts'       => 'getProducts',
            'addToCart'         => 'addToCart',
            'removeFromCart'    => 'removeFromCart',
            'updateCartItem'    => 'updateCartItem',
            'getCart'           => 'getCart',
            'submitOrder'       => 'submitOrder',

            // Financeiro
            'installments'      => 'installments',
            'installmentDetail' => 'installmentDetail',

            // Tracking
            'tracking'          => 'tracking',

            // Mensagens
            'messages'          => 'messages',
            'sendMessage'       => 'sendMessage',

            // Documentos
            'documents'         => 'documents',
            'downloadDocument'  => 'downloadDocument',

            // Perfil
            'profile'           => 'profile',
            'updateProfile'     => 'updateProfile',
            'uploadAvatar'      => 'uploadAvatar',
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // ADMIN DO PORTAL (painel admin — gerência de acessos e configs)
    // ══════════════════════════════════════════════════════════════

    'portal_admin' => [
        'controller'     => 'PortalAdminController',
        'default_action' => 'index',
        'actions'        => [
            'create'        => 'create',
            'store'         => 'store',
            'edit'          => 'edit',
            'update'        => 'update',
            'delete'        => 'delete',
            'toggleAccess'  => 'toggleAccess',
            'resetPassword' => 'resetPassword',
            'sendMagicLink' => 'sendMagicLink',
            'forceLogout'   => 'forceLogout',
            'config'        => 'config',
            'saveConfig'    => 'saveConfig',
            'metrics'       => 'metrics',
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // AUTENTICAÇÃO (before_auth — processado antes do auth check)
    // ══════════════════════════════════════════════════════════════

    'login' => [
        'controller'     => 'UserController',
        'default_action' => 'login',
        'before_auth'    => true,
        'actions'        => [
            'logout' => 'logout',
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // PÁGINAS AUTENTICADAS — GERAIS
    // ══════════════════════════════════════════════════════════════

    'home' => [
        'view' => [
            'app/views/layout/header.php',
            'app/views/home/index.php',
            'app/views/layout/footer.php',
        ],
    ],

    'dashboard' => [
        'redirect' => '?',
    ],

    'profile' => [
        'controller'     => 'UserController',
        'default_action' => 'profile',
        'actions'        => [
            'update' => 'updateProfile',
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // PRODUTOS
    // ══════════════════════════════════════════════════════════════

    'products' => [
        'controller'     => 'ProductController',
        'default_action' => 'index',
        'actions'        => [
            'store'                  => 'store',
            'create'                 => 'create',
            'edit'                   => 'edit',
            'update'                 => 'update',
            'delete'                 => 'delete',
            'deleteImage'            => 'deleteImage',
            'getSubcategories'       => 'getSubcategories',
            'createCategoryAjax'     => 'createCategoryAjax',
            'createGradeType'        => 'createGradeTypeAjax',
            'getGradeTypes'          => 'getGradeTypes',
            'generateCombinations'   => 'generateCombinationsAjax',
            'downloadImportTemplate' => 'downloadImportTemplate',
            'importProducts'         => 'importProducts',
            'getProductsList'        => 'getProductsList',
            'searchSelect2'          => 'searchSelect2',
            'parseImportFile'        => 'parseImportFile',
            'importProductsMapped'   => 'importProductsMapped',
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // CATEGORIAS E SUBCATEGORIAS
    // ══════════════════════════════════════════════════════════════

    'categories' => [
        'controller'     => 'CategoryController',
        'default_action' => 'index',
        'actions'        => [
            'store'                         => 'store',
            'update'                        => 'update',
            'delete'                        => 'delete',
            'storeSub'                      => 'storeSub',
            'updateSub'                     => 'updateSub',
            'deleteSub'                     => 'deleteSub',
            'getInheritedGrades'            => 'getInheritedGradesAjax',
            'toggleCategoryCombination'     => 'toggleCategoryCombinationAjax',
            'toggleSubcategoryCombination'  => 'toggleSubcategoryCombinationAjax',
            'getProductsForExport'          => 'getProductsForExport',
            'exportToProducts'              => 'exportToProducts',
            'getInheritedSectors'           => 'getInheritedSectorsAjax',
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // SETORES DE PRODUÇÃO
    // ══════════════════════════════════════════════════════════════

    'sectors' => [
        'controller'     => 'SectorController',
        'default_action' => 'index',
        'actions'        => [
            'store'  => 'store',
            'update' => 'update',
            'delete' => 'delete',
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // CLIENTES
    // ══════════════════════════════════════════════════════════════

    'customers' => [
        'controller'     => 'CustomerController',
        'default_action' => 'index',
        'actions'        => [
            // CRUD básico
            'store'                   => 'store',
            'create'                  => 'create',
            'edit'                    => 'edit',
            'update'                  => 'update',
            'delete'                  => 'delete',

            // Ficha detalhada (Fase 2)
            'view'                    => 'view',

            // AJAX — Listagem e busca
            'getCustomersList'        => 'getCustomersList',
            'searchSelect2'           => 'searchSelect2',

            // AJAX — Verificações e APIs externas (Fase 2)
            'checkDuplicate'          => 'checkDuplicate',
            'searchCep'               => 'searchCep',
            'searchCnpj'              => 'searchCnpj',

            // Exportação (Fase 2)
            'export'                  => 'export',

            // Ações em lote (Fase 2)
            'bulkAction'              => 'bulkAction',
            'updateStatus'            => 'updateStatus',
            'restore'                 => 'restore',

            // Contatos CRUD AJAX (Fase 2)
            'getContacts'             => 'getContacts',
            'saveContact'             => 'saveContact',
            'deleteContact'           => 'deleteContact',

            // Importação
            'parseImportFile'         => 'parseImportFile',
            'importCustomersMapped'   => 'importCustomersMapped',
            'downloadImportTemplate'  => 'downloadImportTemplate',
            'getImportProgress'       => 'getImportProgress',
            'undoImport'              => 'undoImport',
            'getImportHistory'        => 'getImportHistory',
            'getImportDetails'        => 'getImportDetails',
            'getMappingProfiles'      => 'getMappingProfiles',
            'saveMappingProfile'      => 'saveMappingProfile',
            'deleteMappingProfile'    => 'deleteMappingProfile',

            // Tags e Histórico (Fase 4)
            'getTags'                 => 'getTags',
            'getOrderHistory'         => 'getOrderHistory',
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // PEDIDOS
    // ══════════════════════════════════════════════════════════════

    'orders' => [
        'controller'     => 'OrderController',
        'default_action' => 'index',
        'actions'        => [
            'store'      => 'store',
            'create'     => 'create',
            'edit'       => 'edit',
            'update'     => 'update',
            'delete'     => 'delete',
            'addItem'    => 'addItem',
            'updateItem' => 'updateItem',
            'deleteItem' => 'deleteItem',
            'updateItemDiscount' => 'updateItemDiscount',
            'updateItemQty'      => 'updateItemQty',
            'printQuote' => 'printQuote',
            'printOrder' => 'printOrder',
            'agenda'     => 'agenda',
            'report'     => 'report',
        ],
    ],

    // ── Agenda de Contatos (atalho de menu → usa OrderController) ──
    'agenda' => [
        'controller'     => 'OrderController',
        'default_action' => 'agenda',
    ],

    // ══════════════════════════════════════════════════════════════
    // LINHA DE PRODUÇÃO (PIPELINE)
    // ══════════════════════════════════════════════════════════════

    'pipeline' => [
        'controller'     => 'PipelineController',
        'default_action' => 'index',
        'actions'        => [
            'move'                 => 'move',
            'moveAjax'             => 'moveAjax',
            'detail'               => 'detail',
            'updateDetails'        => 'updateDetails',
            'settings'             => 'settings',
            'saveSettings'         => 'saveSettings',
            'alerts'               => 'alerts',
            'getPricesByTable'     => 'getPricesByTable',
            'addExtraCost'         => 'addExtraCost',
            'deleteExtraCost'      => 'deleteExtraCost',
            'moveSector'           => 'moveSector',
            'getItemLogs'          => 'getItemLogs',
            'addItemLog'           => 'addItemLog',
            'deleteItemLog'        => 'deleteItemLog',
            'togglePreparation'    => 'togglePreparation',
            'checkOrderStock'      => 'checkOrderStock',
            'countInstallments'    => 'countInstallments',
            'deleteInstallments'   => 'deleteInstallments',
            'generateMercadoPagoLink' => 'generateMercadoPagoLink',
            'generatePaymentLink'     => 'generatePaymentLink',
            'productionBoard'      => 'productionBoard',
            'printProductionOrder' => 'printProductionOrder',
            'printThermalReceipt'  => 'printThermalReceipt',
            'syncInstallments'     => 'syncInstallments',
            'updateInstallmentDueDate' => 'updateInstallmentDueDate',
            // Actions que usam CatalogController em vez de PipelineController
            'generateCatalogLink'    => ['controller' => 'CatalogController', 'method' => 'generate'],
            'deactivateCatalogLink'  => ['controller' => 'CatalogController', 'method' => 'deactivate'],
            'getCatalogLink'         => ['controller' => 'CatalogController', 'method' => 'getLink'],
        ],
    ],

    // ── Painel de Produção (atalho de menu → usa PipelineController) ──
    'production_board' => [
        'controller'     => 'PipelineController',
        'default_action' => 'productionBoard',
        'actions'        => [
            'moveSector'    => 'moveSector',
            'getItemLogs'   => 'getItemLogs',
            'addItemLog'    => 'addItemLog',
            'deleteItemLog' => 'deleteItemLog',
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // TABELAS DE PREÇO (atalho de menu)
    // ══════════════════════════════════════════════════════════════

    'price_tables' => [
        'controller'     => 'SettingsController',
        'default_action' => 'priceTablesIndex',
        'actions'        => [
            'createPriceTable' => 'createPriceTable',
            'updatePriceTable' => 'updatePriceTable',
            'deletePriceTable' => 'deletePriceTable',
            'editPriceTable'   => 'editPriceTable',
            'savePriceItem'    => 'savePriceItem',
            'deletePriceItem'  => 'deletePriceItem',
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // GATEWAYS DE PAGAMENTO
    // ══════════════════════════════════════════════════════════════

    'payment_gateways' => [
        'controller'     => 'PaymentGatewayController',
        'default_action' => 'index',
        'actions'        => [
            'edit'           => 'edit',
            'update'         => 'update',
            'testConnection' => 'testConnection',
            'createCharge'   => 'createCharge',
            'chargeStatus'   => 'chargeStatus',
            'transactions'   => 'transactions',
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // CONFIGURAÇÕES DO SISTEMA
    // ══════════════════════════════════════════════════════════════

    'settings' => [
        'controller'     => 'SettingsController',
        'default_action' => 'index',
        'actions'        => [
            'saveCompany'           => 'saveCompany',
            'createPriceTable'      => 'createPriceTable',
            'updatePriceTable'      => 'updatePriceTable',
            'deletePriceTable'      => 'deletePriceTable',
            'editPriceTable'        => 'editPriceTable',
            'savePriceItem'         => 'savePriceItem',
            'deletePriceItem'       => 'deletePriceItem',
            'getPricesForCustomer'  => 'getPricesForCustomer',
            'addPreparationStep'    => 'addPreparationStep',
            'updatePreparationStep' => 'updatePreparationStep',
            'deletePreparationStep' => 'deletePreparationStep',
            'togglePreparationStep' => 'togglePreparationStep',
            'saveBankSettings'      => 'saveBankSettings',
            'saveFiscalSettings'    => 'saveFiscalSettings',
            'saveSecuritySettings'  => 'saveSecuritySettings',
            'saveDashboardWidgets'  => 'saveDashboardWidgets',
            'resetDashboardWidgets' => 'resetDashboardWidgets',
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // GESTÃO DE USUÁRIOS (ADMIN)
    // ══════════════════════════════════════════════════════════════

    'users' => [
        'controller'     => 'UserController',
        'default_action' => 'index',
        'actions'        => [
            'create'      => 'create',
            'store'       => 'store',
            'edit'        => 'edit',
            'update'      => 'update',
            'delete'      => 'delete',
            'groups'      => 'groups',
            'createGroup' => 'createGroup',
            'updateGroup' => 'updateGroup',
            'deleteGroup' => 'deleteGroup',
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // CONTROLE DE ESTOQUE
    // ══════════════════════════════════════════════════════════════

    'stock' => [
        'controller'     => 'StockController',
        'default_action' => 'index',
        'actions'        => [
            'warehouses'           => 'warehouses',
            'storeWarehouse'       => 'storeWarehouse',
            'updateWarehouse'      => 'updateWarehouse',
            'deleteWarehouse'      => 'deleteWarehouse',
            'entry'                => 'entry',
            'storeMovement'        => 'storeMovement',
            'movements'            => 'movements',
            'getStockItems'        => 'getStockItems',
            'getMovements'         => 'getMovements',
            'getMovement'          => 'getMovement',
            'updateMovement'       => 'updateMovement',
            'deleteMovement'       => 'deleteMovement',
            'getProductCombinations' => 'getProductCombinations',
            'updateItemMeta'       => 'updateItemMeta',
            'getProductStock'      => 'getProductStock',
            'setDefault'           => 'setDefault',
            'getDefaultWarehouse'  => 'getDefaultWarehouse',
            'checkOrderStock'      => 'checkOrderStock',
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // WALKTHROUGH (TUTORIAL)
    // ══════════════════════════════════════════════════════════════

    'walkthrough' => [
        'controller'     => 'WalkthroughController',
        'default_action' => 'checkStatus',
        'actions'        => [
            'checkStatus' => 'checkStatus',
            'start'       => 'start',
            'complete'    => 'complete',
            'skip'        => 'skip',
            'saveStep'    => 'saveStep',
            'reset'       => 'reset',
            'getSteps'    => 'getSteps',
            'manual'      => 'manual',
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // RELATÓRIOS FINANCEIROS
    // ══════════════════════════════════════════════════════════════

    'reports' => [
        'controller'     => 'ReportController',
        'default_action' => 'index',
        'actions'        => [
            'exportPdf'   => 'exportPdf',
            'exportExcel' => 'exportExcel',
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // FISCAL / FINANCEIRO
    //
    // Fase 2: Controller principal mantém dashboard + payments (views).
    // Ações de parcelas delegam para InstallmentController.
    // Ações de transações delegam para TransactionController.
    // Ações de importação delegam para FinancialImportController.
    // ══════════════════════════════════════════════════════════════

    'financial' => [
        'controller'     => 'FinancialController',
        'default_action' => 'payments',
        'actions'        => [
            // ── Dashboard e Pagamentos (FinancialController) ──
            'payments'              => 'payments',
            'getSummaryJson'        => 'getSummaryJson',

            // ── Relatórios e Exportação (FinancialController) ──
            'getDre'                => 'getDre',
            'getCashflow'           => 'getCashflow',
            'exportTransactionsCsv' => 'exportTransactionsCsv',
            'exportDreCsv'          => 'exportDreCsv',
            'exportCashflowCsv'     => 'exportCashflowCsv',
            'getAuditLog'           => 'getAuditLog',
            'exportAuditCsv'        => 'exportAuditCsv',

            // ── Parcelas → InstallmentController ──
            'installments'              => ['controller' => 'InstallmentController', 'method' => 'installments'],
            'generateInstallments'      => ['controller' => 'InstallmentController', 'method' => 'generate'],
            'payInstallment'            => ['controller' => 'InstallmentController', 'method' => 'pay'],
            'confirmPayment'            => ['controller' => 'InstallmentController', 'method' => 'confirm'],
            'cancelInstallment'         => ['controller' => 'InstallmentController', 'method' => 'cancel'],
            'uploadAttachment'          => ['controller' => 'InstallmentController', 'method' => 'uploadAttachment'],
            'removeAttachment'          => ['controller' => 'InstallmentController', 'method' => 'removeAttachment'],
            'mergeInstallments'         => ['controller' => 'InstallmentController', 'method' => 'merge'],
            'splitInstallment'          => ['controller' => 'InstallmentController', 'method' => 'split'],
            'getInstallmentsPaginated'  => ['controller' => 'InstallmentController', 'method' => 'getPaginated'],
            'getInstallmentsJson'       => ['controller' => 'InstallmentController', 'method' => 'getJson'],

            // ── Transações → TransactionController ──
            'transactions'              => ['controller' => 'TransactionController', 'method' => 'index'],
            'addTransaction'            => ['controller' => 'TransactionController', 'method' => 'add'],
            'deleteTransaction'         => ['controller' => 'TransactionController', 'method' => 'delete'],
            'getTransaction'            => ['controller' => 'TransactionController', 'method' => 'get'],
            'updateTransaction'         => ['controller' => 'TransactionController', 'method' => 'update'],
            'getTransactionsPaginated'  => ['controller' => 'TransactionController', 'method' => 'getPaginated'],

            // ── Importação → FinancialImportController ──
            'parseImportFile'           => ['controller' => 'FinancialImportController', 'method' => 'parseFile'],
            'importCsv'                 => ['controller' => 'FinancialImportController', 'method' => 'importCsv'],
            'importOfxSelected'         => ['controller' => 'FinancialImportController', 'method' => 'importOfxSelected'],
            'importOfx'                 => ['controller' => 'FinancialImportController', 'method' => 'importOfx'],

            // ── Recorrências → RecurringTransactionController ──
            'recurringList'         => ['controller' => 'RecurringTransactionController', 'method' => 'list'],
            'recurringStore'        => ['controller' => 'RecurringTransactionController', 'method' => 'store'],
            'recurringUpdate'       => ['controller' => 'RecurringTransactionController', 'method' => 'update'],
            'recurringDelete'       => ['controller' => 'RecurringTransactionController', 'method' => 'delete'],
            'recurringToggle'       => ['controller' => 'RecurringTransactionController', 'method' => 'toggle'],
            'recurringProcess'      => ['controller' => 'RecurringTransactionController', 'method' => 'process'],
            'recurringGet'          => ['controller' => 'RecurringTransactionController', 'method' => 'get'],
        ],
    ],

    // ── Atalhos de menu fiscal (redirecionam para página unificada) ──
    'financial_payments' => [
        'redirect' => '?page=financial&action=payments',
    ],

    'financial_transactions' => [
        'redirect' => '?page=financial&action=payments&section=transactions',
    ],

    // ══════════════════════════════════════════════════════════════
    // NF-e — NOTA FISCAL ELETRÔNICA
    // ══════════════════════════════════════════════════════════════

    'nfe_credentials' => [
        'controller'     => 'NfeCredentialController',
        'default_action' => 'index',
        'actions'        => [
            'store'          => 'store',
            'update'         => 'update',
            'testConnection' => 'testConnection',
            'importIbptax'   => 'importIbptax',
            'ibptaxStats'    => 'ibptaxStats',
        ],
    ],

    'nfe_documents' => [
        'controller'     => 'NfeDocumentController',
        'default_action' => 'index',
        'actions'        => [
            'emit'               => 'emit',
            'cancel'             => 'cancel',
            'correction'         => 'correction',
            'download'           => 'download',
            'checkStatus'        => 'checkStatus',
            'detail'             => 'detail',
            'dashboard'          => 'dashboard',
            // Fase 3 — Reenvio de NF-e rejeitada
            'retry'              => 'retry',
            // Fase 5 — Emissão em lote e fila
            'batchEmit'          => 'batchEmit',
            'queue'              => 'queue',
            'processQueue'       => 'processQueue',
            'cancelQueue'        => 'cancelQueue',
            // Fase 5 — Documentos recebidos (DistDFe)
            'received'           => 'received',
            'queryDistDFe'       => 'queryDistDFe',
            'queryDistDFeByChave' => 'queryDistDFeByChave',
            // Fase 5 — Manifestação
            'manifest'           => 'manifest',
            // Fase 5 — Auditoria
            'audit'              => 'audit',
            // Fase 5 — Webhooks
            'webhooks'           => 'webhooks',
            'saveWebhook'        => 'saveWebhook',
            'deleteWebhook'      => 'deleteWebhook',
            'testWebhook'        => 'testWebhook',
            'webhookLogs'        => 'webhookLogs',
            // Fase 5 — DANFE
            'danfeSettings'      => 'danfeSettings',
            'saveDanfeSettings'  => 'saveDanfeSettings',
            // Fase 6 — Inutilização de numeração
            'inutilizar'         => 'inutilizar',
            // Fase 4 — Relatório de CC-e e Exportação
            'correctionReport'   => 'correctionReport',
            'exportReport'       => 'exportReport',
            // Fase 5 — NFC-e (Modelo 65)
            'emitNfce'           => 'emitNfce',
            'downloadDanfeNfce'  => 'downloadDanfeNfce',
            // Fase 5 — Contingência Automática
            'contingencyStatus'    => 'contingencyStatus',
            'contingencyActivate'  => 'contingencyActivate',
            'contingencyDeactivate'=> 'contingencyDeactivate',
            'contingencySync'      => 'contingencySync',
            'contingencyHistory'   => 'contingencyHistory',
            // Fase 5 — Download XML em Lote (ZIP)
            'downloadBatch'      => 'downloadBatch',
            // Fase 5 — Exportação SPED Fiscal e SINTEGRA
            'exportSped'         => 'exportSped',
            'exportSintegra'     => 'exportSintegra',
            // Fase 5 — Livros de Registro
            'livroSaidas'        => 'livroSaidas',
            'livroEntradas'      => 'livroEntradas',
            // Fase 5 — Backup de XMLs
            'backupXml'          => 'backupXml',
            'backupHistory'      => 'backupHistory',
            'backupSettings'     => 'backupSettings',
            'saveBackupSettings' => 'saveBackupSettings',
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // API — Geração de token JWT para consumo da API Node.js
    // ══════════════════════════════════════════════════════════════

    'api' => [
        'controller'     => 'ApiController',
        'default_action' => 'token',
        'actions'        => [
            'token' => 'token',
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // MÓDULO DE COMISSÕES
    // ══════════════════════════════════════════════════════════════

    'commissions' => [
        'controller'     => 'CommissionController',
        'default_action' => 'index',
        'actions'        => [
            // Dashboard
            'index'              => 'index',
            // Cadastros
            'formas'             => 'formas',
            'storeForma'         => 'storeForma',
            'updateForma'        => 'updateForma',
            'deleteForma'        => 'deleteForma',
            'getFaixas'          => 'getFaixas',
            // Grupos
            'grupos'             => 'grupos',
            'linkGrupo'          => 'linkGrupo',
            'unlinkGrupo'        => 'unlinkGrupo',
            // Usuários
            'usuarios'           => 'usuarios',
            'linkUsuario'        => 'linkUsuario',
            'unlinkUsuario'      => 'unlinkUsuario',
            // Produtos
            'produtos'           => 'produtos',
            'saveProdutoRegra'   => 'saveProdutoRegra',
            'deleteProdutoRegra' => 'deleteProdutoRegra',
            // Simulador
            'simulador'          => 'simulador',
            'simularCalculo'     => 'simularCalculo',
            // Cálculo real
            'calcular'           => 'calcular',
            // Histórico
            'historico'          => 'historico',
            'getHistoricoPaginated' => 'getHistoricoPaginated',
            // Ações de status
            'aprovar'            => 'aprovar',
            'pagar'              => 'pagar',
            'cancelar'           => 'cancelar',
            'aprovarLote'        => 'aprovarLote',
            'pagarLote'          => 'pagarLote',
            // Aprovação/Pagamento por Vendedor (modal)
            'getVendedoresPendentes' => 'getVendedoresPendentes',
            'getComissoesVendedor'   => 'getComissoesVendedor',
            // Configurações
            'configuracoes'      => 'configuracoes',
            'saveConfig'         => 'saveConfig',
        ],
    ],

    // ── Atalhos de menu comissões (redirecionam para página unificada) ──
    'commissions_formas' => [
        'redirect' => '?page=commissions&action=formas',
    ],

    'commissions_historico' => [
        'redirect' => '?page=commissions&action=historico',
    ],

];
