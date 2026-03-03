<?php

require_once 'app/models/Walkthrough.php';

class WalkthroughController {

    private $walkthroughModel;

    public function __construct() {
        $database = new Database();
        $db = $database->getConnection();
        $this->walkthroughModel = new Walkthrough($db);
    }

    /**
     * API: Verifica se o usuário precisa ver o walkthrough.
     * Retorna JSON: { "needs_walkthrough": true/false, "current_step": 0 }
     */
    public function checkStatus() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['needs_walkthrough' => false]);
            return;
        }

        $userId = (int) $_SESSION['user_id'];
        $needs = $this->walkthroughModel->needsWalkthrough($userId);
        $status = $this->walkthroughModel->getStatus($userId);

        echo json_encode([
            'needs_walkthrough' => $needs,
            'current_step' => $status ? (int) $status['current_step'] : 0
        ]);
    }

    /**
     * API: Marca o walkthrough como iniciado.
     */
    public function start() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false]);
            return;
        }

        $userId = (int) $_SESSION['user_id'];
        $result = $this->walkthroughModel->start($userId);
        echo json_encode(['success' => $result]);
    }

    /**
     * API: Marca o walkthrough como completo.
     */
    public function complete() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false]);
            return;
        }

        $userId = (int) $_SESSION['user_id'];
        $result = $this->walkthroughModel->complete($userId);
        echo json_encode(['success' => $result]);
    }

    /**
     * API: Marca o walkthrough como pulado.
     */
    public function skip() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false]);
            return;
        }

        $userId = (int) $_SESSION['user_id'];
        $result = $this->walkthroughModel->skip($userId);
        echo json_encode(['success' => $result]);
    }

    /**
     * API: Salva o passo atual do walkthrough.
     */
    public function saveStep() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false]);
            return;
        }

        $userId = (int) $_SESSION['user_id'];
        $step = isset($_POST['step']) ? (int) $_POST['step'] : 0;
        $result = $this->walkthroughModel->saveStep($userId, $step);
        echo json_encode(['success' => $result]);
    }

    /**
     * API: Reseta o walkthrough (para o admin permitir que o usuário veja de novo).
     */
    public function reset() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false]);
            return;
        }

        $userId = (int) $_SESSION['user_id'];
        $result = $this->walkthroughModel->reset($userId);
        echo json_encode(['success' => $result]);
    }

    /**
     * Página de manual/documentação embutida.
     */
    public function manual() {
        require 'app/views/layout/header.php';
        require 'app/views/walkthrough/manual.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Retorna os passos do walkthrough baseados no role e permissões do usuário.
     */
    public function getSteps() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['steps' => []]);
            return;
        }

        $role = $_SESSION['user_role'] ?? 'funcionario';
        $groupId = $_SESSION['group_id'] ?? null;
        $permissions = [];

        // Buscar permissões do grupo
        if ($groupId) {
            $database = new Database();
            $db = $database->getConnection();
            require_once 'app/models/UserGroup.php';
            $groupModel = new UserGroup($db);
            $permissions = $groupModel->getPermissions((int) $groupId);
        }

        $steps = $this->buildSteps($role, $permissions);
        echo json_encode(['steps' => $steps]);
    }

    /**
     * Monta os passos do walkthrough com base no perfil do usuário.
     * 
     * Propriedades de cada passo:
     *   - id:          Identificador único
     *   - type:        'modal' | 'highlight'
     *   - element:     Seletor CSS do elemento a destacar
     *   - title:       Título do passo
     *   - description: Descrição HTML
     *   - position:    Posição do popover (bottom, top, left, right, bottom-end)
     *   - page:        Se definido, navega para essa página antes de exibir
     *   - submenu:     Seletor CSS do .dropdown-toggle a abrir antes de buscar o elemento
     *   - icon:        Classe FA (apenas para modais)
     */
    private function buildSteps(string $role, array $permissions): array {
        $isAdmin = ($role === 'admin');
        $steps = [];

        // ── Boas-vindas (modal central) ──
        $steps[] = [
            'id' => 'welcome',
            'type' => 'modal',
            'title' => 'Bem-vindo ao Akti!',
            'description' => 'O <strong>Akti - Gestão em Produção</strong> é o seu sistema completo para gerenciar pedidos, clientes, produtos e toda a linha de produção.<br><br>Vamos fazer um <strong>tour rápido</strong> para você conhecer cada área. O tour vai <strong>navegar entre as páginas automaticamente</strong>!<br><br><small style="opacity:0.7"><i class="fas fa-keyboard me-1"></i>Atalhos: <kbd>→</kbd> Próximo · <kbd>←</kbd> Voltar · <kbd>Esc</kbd> Pular</small>',
            'icon' => 'fas fa-rocket',
            'position' => 'center'
        ];

        // ── Menu principal (navbar) ──
        $steps[] = [
            'id' => 'navbar',
            'type' => 'highlight',
            'element' => '#navbarNav .navbar-nav:first-of-type',
            'title' => '<i class="fas fa-compass me-1"></i> Menu Principal',
            'description' => 'Este é o <strong>menu de navegação</strong> do sistema. Os itens são organizados em <strong>grupos</strong> (Comercial, Catálogo, Produção).<br><br>O menu se adapta automaticamente às suas <strong>permissões</strong> — você só vê as áreas que pode acessar.',
            'position' => 'bottom',
            'page' => 'dashboard'
        ];

        // ── Dashboard ──
        $steps[] = [
            'id' => 'dashboard',
            'type' => 'highlight',
            'element' => 'main.main-bg',
            'title' => '<i class="fas fa-tachometer-alt me-1"></i> Dashboard — Visão Geral',
            'description' => 'O <strong>Dashboard</strong> é sua página inicial. Aqui você acompanha em tempo real:<br><br>• <strong>Resumo de pedidos</strong> por status<br>• <strong>Faturamento</strong> do período<br>• <strong>Pipeline</strong> com a distribuição de pedidos<br>• <strong>Alertas</strong> de prazos e pendências',
            'position' => 'bottom',
            'page' => 'dashboard'
        ];

        // ── Submenu Comercial — destacar o dropdown aberto ──
        $hasComercial = $isAdmin || in_array('orders', $permissions) || in_array('customers', $permissions);
        if ($hasComercial) {
            // Mostrar o submenu Comercial aberto, destacando os itens dentro
            $steps[] = [
                'id' => 'menu_comercial',
                'type' => 'highlight',
                'element' => '[data-wt-menu="comercial"]',
                'submenu' => '[data-wt-toggle="comercial"]',
                'title' => '<i class="fas fa-briefcase me-1"></i> Menu Comercial',
                'description' => 'O grupo <strong>Comercial</strong> contém os módulos de:<br><br>• <strong>Clientes</strong> — cadastro e gestão<br>• <strong>Pedidos</strong> — criação e acompanhamento<br>• <strong>Agenda de Contatos</strong> — follow-ups<br>• <strong>Tabelas de Preço</strong> — preços diferenciados<br><br>Vamos navegar por cada área!',
                'position' => 'bottom',
                'page' => 'dashboard'
            ];
        }

        // ── Pedidos (navega para a página) ──
        if ($isAdmin || in_array('orders', $permissions)) {
            $steps[] = [
                'id' => 'orders_page',
                'type' => 'highlight',
                'element' => 'main.main-bg',
                'title' => '<i class="fas fa-shopping-cart me-1"></i> Pedidos',
                'description' => 'Aqui você gerencia <strong>todos os pedidos</strong> do sistema:<br><br>• Crie novos pedidos com o botão <em>"Novo Pedido"</em><br>• Acompanhe o <strong>status</strong> de cada pedido<br>• Veja <strong>valores, prazos e prioridades</strong><br>• Cada pedido percorre as etapas: <em>Contato → Orçamento → Venda → Produção → Envio → Concluído</em>',
                'position' => 'top',
                'page' => 'orders'
            ];
        }

        // ── Clientes (navega para a página) ──
        if ($isAdmin || in_array('customers', $permissions)) {
            $steps[] = [
                'id' => 'customers_page',
                'type' => 'highlight',
                'element' => 'main.main-bg',
                'title' => '<i class="fas fa-users me-1"></i> Clientes',
                'description' => 'Gerencie sua <strong>base de clientes</strong> completa:<br><br>• Cadastre com <strong>nome, email, telefone, CPF/CNPJ</strong><br>• Vincule uma <strong>tabela de preço</strong> específica<br>• Acesse o <strong>histórico de pedidos</strong> do cliente<br>• Adicione <strong>foto</strong> e <strong>endereço completo</strong>',
                'position' => 'top',
                'page' => 'customers'
            ];
        }

        // ── Submenu Catálogo — destacar o dropdown aberto ──
        $hasCatalogo = $isAdmin || in_array('products', $permissions) || in_array('stock', $permissions);
        if ($hasCatalogo) {
            $steps[] = [
                'id' => 'menu_catalogo',
                'type' => 'highlight',
                'element' => '[data-wt-menu="catalogo"]',
                'submenu' => '[data-wt-toggle="catalogo"]',
                'title' => '<i class="fas fa-box-open me-1"></i> Menu Catálogo',
                'description' => 'O grupo <strong>Catálogo</strong> contém:<br><br>• <strong>Produtos</strong> — cadastro com fotos, grades e dados fiscais<br>• <strong>Categorias</strong> — organização em categorias e subcategorias<br>• <strong>Controle de Estoque</strong> — multi-armazém',
                'position' => 'bottom',
                'page' => 'dashboard'
            ];
        }

        // ── Produtos (navega para a página) ──
        if ($isAdmin || in_array('products', $permissions)) {
            $steps[] = [
                'id' => 'products_page',
                'type' => 'highlight',
                'element' => 'main.main-bg',
                'title' => '<i class="fas fa-box-open me-1"></i> Produtos',
                'description' => 'O catálogo completo dos seus <strong>produtos</strong>:<br><br>• Cadastre com <strong>fotos, preço, categorias</strong><br>• Configure <strong>grades</strong> (tamanho, cor, material)<br>• Adicione <strong>dados fiscais</strong> para NF-e<br>• Vincule <strong>setores de produção</strong><br>• Ative <strong>controle de estoque</strong> por produto',
                'position' => 'top',
                'page' => 'products'
            ];
        }

        // ── Estoque (navega para a página) ──
        if ($isAdmin || in_array('stock', $permissions)) {
            $steps[] = [
                'id' => 'stock_page',
                'type' => 'highlight',
                'element' => 'main.main-bg',
                'title' => '<i class="fas fa-warehouse me-1"></i> Estoque',
                'description' => 'Controle de <strong>estoque multi-armazém</strong>:<br><br>• Crie <strong>armazéns</strong> (Estoque Principal, Loja, Depósito)<br>• Registre <strong>entradas e saídas</strong><br>• Faça <strong>transferências</strong> entre armazéns<br>• Produtos com estoque podem <strong>pular a produção</strong>',
                'position' => 'top',
                'page' => 'stock'
            ];
        }

        // ── Submenu Produção — destacar o dropdown aberto ──
        $hasProducao = $isAdmin || in_array('pipeline', $permissions) || in_array('sectors', $permissions);
        if ($hasProducao) {
            $steps[] = [
                'id' => 'menu_producao',
                'type' => 'highlight',
                'element' => '[data-wt-menu="producao"]',
                'submenu' => '[data-wt-toggle="producao"]',
                'title' => '<i class="fas fa-industry me-1"></i> Menu Produção',
                'description' => 'O grupo <strong>Produção</strong> contém:<br><br>• <strong>Linha de Produção</strong> — pipeline Kanban<br>• <strong>Painel de Produção</strong> — visão detalhada<br>• <strong>Setores</strong> — organização da fábrica',
                'position' => 'bottom',
                'page' => 'dashboard'
            ];
        }

        // ── Pipeline (navega para a página) ──
        if ($isAdmin || in_array('pipeline', $permissions)) {
            $steps[] = [
                'id' => 'pipeline_page',
                'type' => 'highlight',
                'element' => 'main.main-bg',
                'title' => '<i class="fas fa-stream me-1"></i> Pipeline de Produção',
                'description' => 'O <strong>Pipeline</strong> é um quadro visual <strong>Kanban</strong> que mostra todos os pedidos organizados por etapa:<br><br>• Cada <strong>coluna</strong> é uma etapa do processo<br>• Cada <strong>card</strong> é um pedido<br>• <strong>Arraste os cards</strong> entre colunas para mover pedidos<br>• Pedidos atrasados ficam <strong>destacados em vermelho</strong>',
                'position' => 'top',
                'page' => 'pipeline'
            ];
        }

        // ── Setores (navega para a página) ──
        if ($isAdmin || in_array('sectors', $permissions)) {
            $steps[] = [
                'id' => 'sectors_page',
                'type' => 'highlight',
                'element' => 'main.main-bg',
                'title' => '<i class="fas fa-industry me-1"></i> Setores de Produção',
                'description' => 'Organize sua <strong>linha produtiva</strong> por setores:<br><br>• Crie setores como <em>Costura, Corte, Estamparia</em><br>• Vincule setores a <strong>produtos</strong> ou <strong>categorias</strong><br>• No pipeline, acompanhe o progresso <strong>setor a setor</strong>',
                'position' => 'top',
                'page' => 'sectors'
            ];
        }

        // ── Configurações (navega para a página) ──
        if ($isAdmin || in_array('settings', $permissions)) {
            $steps[] = [
                'id' => 'settings_page',
                'type' => 'highlight',
                'element' => 'main.main-bg',
                'title' => '<i class="fas fa-cog me-1"></i> Configurações',
                'description' => 'Personalize <strong>todo o sistema</strong>:<br><br>• <strong>Dados da empresa</strong> — nome, CNPJ, logo<br>• <strong>Tabelas de preço</strong> — preços diferenciados por grupo<br>• <strong>Pipeline</strong> — metas de tempo por etapa<br>• <strong>Dados fiscais</strong> — configurações para NF-e<br>• <strong>Preparação</strong> — checklist de conferência',
                'position' => 'top',
                'page' => 'settings'
            ];
        }

        // ── Usuários (admin) ──
        if ($isAdmin) {
            $steps[] = [
                'id' => 'users_page',
                'type' => 'highlight',
                'element' => 'main.main-bg',
                'title' => '<i class="fas fa-user-shield me-1"></i> Usuários e Permissões',
                'description' => 'Gerencie o <strong>acesso ao sistema</strong>:<br><br>• Cadastre <strong>usuários</strong> (Admin ou Funcionário)<br>• Crie <strong>grupos de permissão</strong><br>• Cada grupo define quais <strong>páginas</strong> podem ser acessadas<br>• Ex: Equipe de Produção vê só Dashboard, Pedidos e Pipeline',
                'position' => 'top',
                'page' => 'users'
            ];
        }

        // ── Perfil do usuário (volta ao dashboard) ──
        $steps[] = [
            'id' => 'profile',
            'type' => 'highlight',
            'element' => 'a[href="?page=profile"]',
            'title' => '<i class="fas fa-user-circle me-1"></i> Seu Perfil',
            'description' => 'Clique no seu <strong>nome de usuário</strong> para acessar seu perfil. Lá você pode:<br><br>• <strong>Alterar nome, email e senha</strong><br>• <strong>Personalizar</strong> suas preferências<br>• <strong>Visualizar</strong> suas informações de acesso',
            'position' => 'bottom-end',
            'page' => 'dashboard'
        ];

        // ── Botão de ajuda no rodapé ──
        $steps[] = [
            'id' => 'footer_help',
            'type' => 'highlight',
            'element' => '#wtFooterHelp',
            'title' => '<i class="fas fa-lightbulb me-1"></i> Botão de Tutorial',
            'description' => 'Este é o botão <strong>"Tutorial"</strong> no rodapé do sistema!<br><br>Clique nele <strong>a qualquer momento</strong> para refazer este tour e relembrar as funcionalidades de cada área.<br><br>Ao lado dele, você também encontra o link para o <strong>Manual do Sistema</strong> com instruções detalhadas.',
            'position' => 'top',
            'page' => 'dashboard'
        ];

        // ── Conclusão (modal) ──
        $steps[] = [
            'id' => 'finish',
            'type' => 'modal',
            'title' => 'Tour Concluído!',
            'description' => 'Parabéns! Agora você conhece todas as áreas do <strong>Akti</strong>.<br><br><i class="fas fa-sync-alt me-1 text-info"></i> <strong>Refazer o tour:</strong> Use o botão <em>"Tutorial"</em> no rodapé ou no menu do seu perfil.<br><br><i class="fas fa-book me-1 text-primary"></i> <strong>Manual completo:</strong> Clique em <em>"Manual"</em> no rodapé para ver instruções detalhadas de cada funcionalidade.',
            'icon' => 'fas fa-check-circle',
            'position' => 'center'
        ];

        return $steps;
    }
}
