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
            'createGradeType'        => 'createGradeTypeAjax',
            'getGradeTypes'          => 'getGradeTypes',
            'generateCombinations'   => 'generateCombinationsAjax',
            'downloadImportTemplate' => 'downloadImportTemplate',
            'importProducts'         => 'importProducts',
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
            'store'  => 'store',
            'create' => 'create',
            'edit'   => 'edit',
            'update' => 'update',
            'delete' => 'delete',
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
            'productionBoard'      => 'productionBoard',
            'printProductionOrder' => 'printProductionOrder',
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
    // FISCAL / FINANCEIRO
    // ══════════════════════════════════════════════════════════════

    'financial' => [
        'controller'     => 'FinancialController',
        'default_action' => 'payments',
        'actions'        => [
            'payments'            => 'payments',
            'installments'        => 'installments',
            'generateInstallments' => 'generateInstallments',
            'payInstallment'      => 'payInstallment',
            'confirmPayment'      => 'confirmPayment',
            'cancelInstallment'   => 'cancelInstallment',
            'uploadAttachment'    => 'uploadAttachment',
            'removeAttachment'    => 'removeAttachment',
            'transactions'        => 'transactions',
            'addTransaction'      => 'addTransaction',
            'deleteTransaction'   => 'deleteTransaction',
            'importOfx'           => 'importOfx',
            'getSummaryJson'      => 'getSummaryJson',
            'getInstallmentsJson' => 'getInstallmentsJson',
        ],
    ],

    // ── Atalhos de menu fiscal ──
    'financial_payments' => [
        'controller'     => 'FinancialController',
        'default_action' => 'payments',
    ],

    'financial_transactions' => [
        'controller'     => 'FinancialController',
        'default_action' => 'transactions',
    ],

];
