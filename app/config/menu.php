<?php
/**
 * Registro centralizado de todas as páginas/módulos do sistema.
 * 
 * Este array é a ÚNICA fonte de verdade para:
 *   - Itens do menu principal (header.php)
 *   - Permissões de grupos (groups.php)
 * 
 * ESTRUTURA:
 * 
 *   Item simples (link direto):
 *   'page_key' => [
 *       'label'      => 'Nome exibido',
 *       'icon'       => 'Classe Font Awesome',
 *       'menu'       => true/false  — exibe no menu principal
 *       'permission' => true/false  — aparece na lista de permissões de grupo
 *   ]
 * 
 *   Grupo/Submenu (dropdown):
 *   'grupo' => [
 *       'label'    => 'Nome do grupo',
 *       'icon'     => 'Classe Font Awesome',
 *       'menu'     => true,
 *       'children' => [
 *           'page_key' => [ ... mesmo formato de item simples ... ],
 *       ],
 *   ]
 * 
 * Páginas com 'permission' => false são acessíveis por todos (home, dashboard, profile).
 * Páginas com 'menu' => false não aparecem na navbar (ex: profile, users).
 */

return [

    // ─── Dashboard (visão geral — acesso via logo, sem item no menu) ───
    'dashboard' => [
        'label'      => 'Dashboard',
        'icon'       => 'fas fa-tachometer-alt',
        'menu'       => false,
        'permission' => false,
    ],

    // ─── Grupo: Comercial ───
    'comercial' => [
        'label'    => 'Comercial',
        'icon'     => 'fas fa-briefcase',
        'menu'     => true,
        'children' => [
            'customers' => [
                'label'      => 'Clientes',
                'icon'       => 'fas fa-users',
                'menu'       => true,
                'permission' => true,
            ],
            'orders' => [
                'label'      => 'Pedidos',
                'icon'       => 'fas fa-shopping-cart',
                'menu'       => true,
                'permission' => true,
            ],
            'quotes' => [
                'label'      => 'Orçamentos',
                'icon'       => 'fas fa-file-alt',
                'menu'       => true,
                'permission' => true,
            ],
            'agenda' => [
                'label'      => 'Agenda de Contatos',
                'icon'       => 'fas fa-calendar-alt',
                'menu'       => true,
                'permission' => true,
            ],
            'calendar' => [
                'label'      => 'Calendário',
                'icon'       => 'fas fa-calendar',
                'menu'       => true,
                'permission' => true,
            ],
            'price_tables' => [
                'label'      => 'Tabelas de Preço',
                'icon'       => 'fas fa-tags',
                'menu'       => true,
                'permission' => true,
            ],
            'suppliers' => [
                'label'      => 'Fornecedores',
                'icon'       => 'fas fa-truck',
                'menu'       => true,
                'permission' => true,
            ],
        ],
    ],

    // ─── Grupo: Catálogo ───
    'catalogo' => [
        'label'    => 'Catálogo',
        'icon'     => 'fas fa-box-open',
        'menu'     => true,
        'children' => [
            'products' => [
                'label'      => 'Produtos',
                'icon'       => 'fas fa-box-open',
                'menu'       => true,
                'permission' => true,
            ],
            'categories' => [
                'label'      => 'Categorias',
                'icon'       => 'fas fa-folder-open',
                'menu'       => true,
                'permission' => true,
            ],
            'stock' => [
                'label'      => 'Controle de Estoque',
                'icon'       => 'fas fa-warehouse',
                'menu'       => true,
                'permission' => true,
            ],
        ],
    ],

    // ─── Grupo: Produção ───
    'producao' => [
        'label'    => 'Produção',
        'icon'     => 'fas fa-industry',
        'menu'     => true,
        'children' => [
            'pipeline' => [
                'label'      => 'Linha de Produção',
                'icon'       => 'fas fa-stream',
                'menu'       => true,
                'permission' => true,
            ],
            'production_board' => [
                'label'      => 'Painel de Produção',
                'icon'       => 'fas fa-tasks',
                'menu'       => true,
                'permission' => true,
            ],
            'sectors' => [
                'label'      => 'Setores',
                'icon'       => 'fas fa-industry',
                'menu'       => true,
                'permission' => true,
            ],
            'quality' => [
                'label'      => 'Qualidade',
                'icon'       => 'fas fa-clipboard-check',
                'menu'       => true,
                'permission' => true,
            ],
        ],
    ],

    // ─── Grupo: Fiscal ───
    'fiscal' => [
        'label'    => 'Fiscal',
        'icon'     => 'fas fa-coins',
        'menu'     => true,
        'children' => [
            'financial' => [
                'label'      => 'Financeiro',
                'icon'       => 'fas fa-file-invoice-dollar',
                'menu'       => true,
                'permission' => true,
            ],
            'commissions' => [
                'label'      => 'Comissões',
                'icon'       => 'fas fa-hand-holding-usd',
                'menu'       => true,
                'permission' => true,
            ],
            'payment_gateways' => [
                'label'      => 'Gateways de Pagamento',
                'icon'       => 'fas fa-credit-card',
                'menu'       => true,
                'permission' => true,
            ],
            'nfe_documents' => [
                'label'      => 'Notas Fiscais (NF-e)',
                'icon'       => 'fas fa-file-invoice',
                'menu'       => true,
                'permission' => true,
                'module'     => 'nfe',
            ],
            'nfe_credentials' => [
                'label'      => 'Credenciais SEFAZ',
                'icon'       => 'fas fa-certificate',
                'menu'       => false,
                'permission' => true,
                'module'     => 'nfe',
                'permission_alias' => null,
            ],
        ],
    ],

    // ─── Site Builder (editor visual da loja online) ───
    /*'site_builder' => [
        'label'      => 'Site Builder',
        'icon'       => 'fas fa-paint-brush',
        'menu'       => true,
        'permission' => true,
    ],*/

    // ─── Relatórios (item direto no menu principal) ───
    'reports' => [
        'label'      => 'Relatórios',
        'icon'       => 'fas fa-chart-bar',
        'menu'       => true,
        'permission' => true,
    ],

    // ─── Grupo: Ferramentas ───
    'ferramentas' => [
        'label'    => 'Ferramentas',
        'icon'     => 'fas fa-toolbox',
        'menu'     => true,
        'children' => [
            'custom_reports' => [
                'label'      => 'Relatórios Custom.',
                'icon'       => 'fas fa-chart-line',
                'menu'       => true,
                'permission' => true,
            ],
            'workflows' => [
                'label'      => 'Automações',
                'icon'       => 'fas fa-cogs',
                'menu'       => true,
                'permission' => true,
            ],
            'email_marketing' => [
                'label'      => 'E-mail Marketing',
                'icon'       => 'fas fa-envelope',
                'menu'       => true,
                'permission' => true,
            ],
            'attachments' => [
                'label'      => 'Anexos',
                'icon'       => 'fas fa-paperclip',
                'menu'       => true,
                'permission' => true,
            ],
            'audit' => [
                'label'      => 'Auditoria',
                'icon'       => 'fas fa-history',
                'menu'       => true,
                'permission' => true,
            ],
        ],
    ],

    // ─── Itens ocultos do menu principal (ficam no menu direito) ───
    'financial' => [
        'label'      => 'Fiscal (Financeiro)',
        'icon'       => 'fas fa-coins',
        'menu'       => false,
        'permission' => true,
    ],
    'settings' => [
        'label'      => 'Configurações',
        'icon'       => 'fas fa-building',
        'menu'       => false,
        'permission' => true,
    ],
    'users' => [
        'label'      => 'Gestão de Usuários',
        'icon'       => 'fas fa-users-cog',
        'menu'       => false,
        'permission' => true,
    ],
    'portal_admin' => [
        'label'      => 'Admin do Portal',
        'icon'       => 'fas fa-globe',
        'menu'       => false,
        'permission' => true,
    ],
    'profile' => [
        'label'      => 'Meu Perfil',
        'icon'       => 'fas fa-user-circle',
        'menu'       => false,
        'permission' => false,
    ],
];
