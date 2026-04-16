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
            'tickets' => [
                'label'      => 'Tickets / Suporte',
                'icon'       => 'fas fa-headset',
                'menu'       => true,
                'permission' => true,
            ],
            'whatsapp' => [
                'label'      => 'WhatsApp',
                'icon'       => 'fab fa-whatsapp',
                'menu'       => true,
                'permission' => true,
            ],
            'suporte' => [
                'label'      => 'Suporte Akti',
                'icon'       => 'fas fa-life-ring',
                'menu'       => true,
                'permission' => false,
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
            'supplies' => [
                'label'      => 'Insumos',
                'icon'       => 'fas fa-boxes-stacked',
                'menu'       => true,
                'permission' => true,
            ],
            'supply_stock' => [
                'label'      => 'Estoque de Insumos',
                'icon'       => 'fas fa-cubes',
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
            'equipment' => [
                'label'      => 'Equipamentos',
                'icon'       => 'fas fa-tools',
                'menu'       => true,
                'permission' => true,
            ],
            'production_costs' => [
                'label'      => 'Custos de Produção',
                'icon'       => 'fas fa-calculator',
                'menu'       => true,
                'permission' => true,
            ],
            'shipments' => [
                'label'      => 'Entregas',
                'icon'       => 'fas fa-shipping-fast',
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

    // ─── Grupo: Ferramentas & Relatórios ───
    'ferramentas' => [
        'label'    => 'Ferramentas',
        'icon'     => 'fas fa-toolbox',
        'menu'     => true,
        'children' => [
            'reports' => [
                'label'      => 'Relatórios',
                'icon'       => 'fas fa-chart-bar',
                'menu'       => true,
                'permission' => true,
            ],
            'custom_reports' => [
                'label'      => 'Relatórios Custom.',
                'icon'       => 'fas fa-chart-line',
                'menu'       => true,
                'permission' => true,
            ],
            'bi' => [
                'label'      => 'Business Intelligence',
                'icon'       => 'fas fa-chart-area',
                'menu'       => true,
                'permission' => true,
            ],
            'site_builder' => [
                'label'      => 'Site Builder',
                'icon'       => 'fas fa-palette',
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
            'branches' => [
                'label'      => 'Filiais',
                'icon'       => 'fas fa-building',
                'menu'       => true,
                'permission' => true,
            ],
            'achievements' => [
                'label'      => 'Gamificação',
                'icon'       => 'fas fa-trophy',
                'menu'       => true,
                'permission' => true,
            ],
            'esg' => [
                'label'      => 'ESG / Sustentabilidade',
                'icon'       => 'fas fa-leaf',
                'menu'       => true,
                'permission' => true,
            ],
            'ai_assistant' => [
                'label'      => 'Assistente IA',
                'icon'       => 'fas fa-robot',
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
