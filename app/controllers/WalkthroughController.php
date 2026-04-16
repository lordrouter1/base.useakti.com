<?php
namespace Akti\Controllers;

use Akti\Models\Walkthrough;
use Akti\Models\UserGroup;
use Akti\Utils\Input;

/**
 * Class WalkthroughController.
 */
class WalkthroughController extends BaseController {

    private Walkthrough $walkthroughModel;
    /**
     * Construtor da classe WalkthroughController.
     *
     * @param \PDO $db Conexão PDO com o banco de dados
     * @param Walkthrough $walkthroughModel Walkthrough model
     */
    public function __construct(\PDO $db, Walkthrough $walkthroughModel) {
        $this->db = $db;
        $this->walkthroughModel = $walkthroughModel;
    }

    /**
     * API: Verifica se o usuário precisa ver o walkthrough.
     * Retorna JSON: { "needs_walkthrough": true/false, "current_step": 0 }
     */
    public function checkStatus() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            $this->json(['needs_walkthrough' => false]);}

        $userId = (int) $_SESSION['user_id'];
        $needs = $this->walkthroughModel->needsWalkthrough($userId);
        $status = $this->walkthroughModel->getStatus($userId);

        $this->json([
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
            $this->json(['success' => false]);}

        $userId = (int) $_SESSION['user_id'];
        $result = $this->walkthroughModel->start($userId);
        $this->json(['success' => $result]);
    }

    /**
     * API: Marca o walkthrough como completo.
     */
    public function complete() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            $this->json(['success' => false]);}

        $userId = (int) $_SESSION['user_id'];
        $result = $this->walkthroughModel->complete($userId);
        $this->json(['success' => $result]);
    }

    /**
     * API: Marca o walkthrough como pulado.
     */
    public function skip() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            $this->json(['success' => false]);}

        $userId = (int) $_SESSION['user_id'];
        $result = $this->walkthroughModel->skip($userId);
        $this->json(['success' => $result]);
    }

    /**
     * API: Salva o passo atual do walkthrough.
     */
    public function saveStep() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            $this->json(['success' => false]);}

        $userId = (int) $_SESSION['user_id'];
        $step = Input::post('step', 'int', 0);
        $result = $this->walkthroughModel->saveStep($userId, $step);
        $this->json(['success' => $result]);
    }

    /**
     * API: Reseta o walkthrough (para o admin permitir que o usuário veja de novo).
     */
    public function reset() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            $this->json(['success' => false]);}

        $userId = (int) $_SESSION['user_id'];
        $result = $this->walkthroughModel->reset($userId);
        $this->json(['success' => $result]);
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
            $this->json(['steps' => []]);}

        $role = $_SESSION['user_role'] ?? 'funcionario';
        $groupId = $_SESSION['group_id'] ?? null;
        $permissions = [];

        // Buscar permissões do grupo
        if ($groupId) {
            $groupModel = new UserGroup($this->db);
            $permissions = $groupModel->getPermissions((int) $groupId);
        }

        $steps = $this->buildSteps($role, $permissions);
        $this->json(['steps' => $steps]);
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
            'description' => 'Este é o <strong>menu de navegação</strong> do sistema. Os itens são organizados em <strong>grupos</strong> (Comercial, Catálogo, Produção, Fiscal).<br><br>Para voltar à <strong>página inicial</strong>, basta clicar na <strong>logo</strong> do Akti no canto superior esquerdo.<br><br>O menu se adapta automaticamente às suas <strong>permissões</strong>.',
            'position' => 'bottom',
            'page' => 'home'
        ];

        // ── Página Inicial (Home unificada) ──
        $steps[] = [
            'id' => 'home_page',
            'type' => 'highlight',
            'element' => '#home-cards-summary',
            'title' => '<i class="fas fa-home me-1"></i> Página Inicial — Resumo',
            'description' => 'A <strong>Página Inicial</strong> reúne tudo em um só lugar:<br><br>• <strong>Cards de resumo</strong> — pedidos ativos, criados hoje, atrasados e concluídos no mês<br>• <strong>Pipeline visual</strong> — distribuição dos pedidos por etapa<br>• <strong>Resumo financeiro</strong> — recebido, a receber, atrasos<br>• <strong>Atalhos rápidos</strong> no topo — criar pedido, cliente, acessar pipeline e pagamentos<br><br>Clique nos cards para ir direto à área correspondente.',
            'position' => 'bottom-end',
            'page' => 'home'
        ];

        // ── Atalhos rápidos ──
        $steps[] = [
            'id' => 'home_shortcuts',
            'type' => 'highlight',
            'element' => '#home-shortcuts',
            'title' => '<i class="fas fa-bolt me-1"></i> Atalhos Rápidos',
            'description' => 'No topo da página inicial ficam os <strong>atalhos rápidos</strong> para as ações mais usadas:<br><br>• <strong>Novo Pedido</strong> — cria um pedido direto<br>• <strong>Novo Cliente</strong> — cadastra um cliente<br>• <strong>Pipeline</strong> — abre o quadro Kanban<br>• <strong>Pagamentos</strong> — acessa as parcelas',
            'position' => 'bottom-end',
            'page' => 'home'
        ];

        // ── Submenu Comercial ──
        $hasComercial = $isAdmin || in_array('orders', $permissions) || in_array('customers', $permissions);
        if ($hasComercial) {
            $steps[] = [
                'id' => 'menu_comercial',
                'type' => 'highlight',
                'element' => '[data-wt-menu="comercial"]',
                'submenu' => '[data-wt-toggle="comercial"]',
                'title' => '<i class="fas fa-briefcase me-1"></i> Menu Comercial',
                'description' => 'O grupo <strong>Comercial</strong> contém os módulos de:<br><br>• <strong>Clientes</strong> — cadastro completo com foto, endereço, CPF/CNPJ e tabela de preço vinculada<br>• <strong>Pedidos</strong> — criação e acompanhamento de vendas e orçamentos<br>• <strong>Agenda de Contatos</strong> — follow-ups agendados com clientes<br>• <strong>Tabelas de Preço</strong> — preços diferenciados por grupo de cliente<br><br>Vamos navegar por cada área!',
                'position' => 'bottom',
                'page' => 'home'
            ];
        }

        // ── Pedidos ──
        if ($isAdmin || in_array('orders', $permissions)) {
            $steps[] = [
                'id' => 'orders_page',
                'type' => 'highlight',
                'element' => 'main.main-bg',
                'title' => '<i class="fas fa-shopping-cart me-1"></i> Pedidos',
                'description' => 'Aqui você gerencia <strong>todos os pedidos</strong> do sistema:<br><br>• Crie novos pedidos com o botão <em>"Novo Pedido"</em><br>• Acompanhe <strong>status, valores, prazos e prioridades</strong><br>• Adicione <strong>produtos com grades</strong> (tamanho, cor, material)<br>• Configure <strong>frete, desconto e forma de pagamento</strong><br>• Gere <strong>links públicos</strong> para o cliente visualizar orçamentos<br>• Cada pedido percorre: <em>Contato → Orçamento → Venda → Produção → Preparação → Envio → Financeiro → Concluído</em>',
                'position' => 'bottom-end',
                'page' => 'orders'
            ];
        }

        // ── Clientes ──
        if ($isAdmin || in_array('customers', $permissions)) {
            $steps[] = [
                'id' => 'customers_page',
                'type' => 'highlight',
                'element' => 'main.main-bg',
                'title' => '<i class="fas fa-users me-1"></i> Clientes',
                'description' => 'Gerencie sua <strong>base de clientes</strong> completa:<br><br>• Cadastre com <strong>nome, email, telefone, CPF/CNPJ</strong><br>• Vincule uma <strong>tabela de preço</strong> específica<br>• Acesse o <strong>histórico de pedidos</strong> do cliente<br>• Adicione <strong>foto</strong> e <strong>endereço completo</strong> (usado em boletos e notas)',
                'position' => 'bottom-end',
                'page' => 'customers'
            ];
        }

        // ── Submenu Catálogo ──
        $hasCatalogo = $isAdmin || in_array('products', $permissions) || in_array('stock', $permissions);
        if ($hasCatalogo) {
            $steps[] = [
                'id' => 'menu_catalogo',
                'type' => 'highlight',
                'element' => '[data-wt-menu="catalogo"]',
                'submenu' => '[data-wt-toggle="catalogo"]',
                'title' => '<i class="fas fa-box-open me-1"></i> Menu Catálogo',
                'description' => 'O grupo <strong>Catálogo</strong> contém:<br><br>• <strong>Produtos</strong> — cadastro com fotos, grades, dados fiscais e setores<br>• <strong>Categorias</strong> — organização em categorias e subcategorias com grades herdáveis<br>• <strong>Controle de Estoque</strong> — multi-armazém com entradas, saídas e transferências',
                'position' => 'bottom',
                'page' => 'home'
            ];
        }

        // ── Produtos ──
        if ($isAdmin || in_array('products', $permissions)) {
            $steps[] = [
                'id' => 'products_page',
                'type' => 'highlight',
                'element' => 'main.main-bg',
                'title' => '<i class="fas fa-box-open me-1"></i> Produtos',
                'description' => 'O catálogo completo dos seus <strong>produtos</strong>:<br><br>• Cadastre com <strong>fotos múltiplas</strong> (JPG, PNG, WebP, GIF)<br>• Configure <strong>grades</strong> (tamanho, cor, material) com combinações automáticas<br>• Adicione <strong>dados fiscais</strong> (NCM, CFOP, CSTs, alíquotas) para NF-e<br>• Vincule <strong>setores de produção</strong> ao produto<br>• Ative <strong>controle de estoque</strong> por produto<br>• Importe produtos em lote via <strong>planilha CSV</strong>',
                'position' => 'bottom-end',
                'page' => 'products'
            ];
        }

        // ── Estoque ──
        if ($isAdmin || in_array('stock', $permissions)) {
            $steps[] = [
                'id' => 'stock_page',
                'type' => 'highlight',
                'element' => 'main.main-bg',
                'title' => '<i class="fas fa-warehouse me-1"></i> Estoque',
                'description' => 'Controle de <strong>estoque multi-armazém</strong>:<br><br>• Crie <strong>armazéns</strong> (Estoque Principal, Loja, Depósito)<br>• Registre <strong>entradas e saídas</strong> com observações<br>• Faça <strong>transferências</strong> entre armazéns<br>• Configure <strong>estoque mínimo</strong> e receba alertas<br>• Defina <strong>localização física</strong> dos itens (ex: A1-P3)<br>• Produtos com estoque podem <strong>pular a produção</strong> no pipeline',
                'position' => 'bottom-end',
                'page' => 'stock'
            ];
        }

        // ── Submenu Produção ──
        $hasProducao = $isAdmin || in_array('pipeline', $permissions) || in_array('sectors', $permissions);
        if ($hasProducao) {
            $steps[] = [
                'id' => 'menu_producao',
                'type' => 'highlight',
                'element' => '[data-wt-menu="producao"]',
                'submenu' => '[data-wt-toggle="producao"]',
                'title' => '<i class="fas fa-industry me-1"></i> Menu Produção',
                'description' => 'O grupo <strong>Produção</strong> contém:<br><br>• <strong>Linha de Produção</strong> — pipeline Kanban com arrastar e soltar<br>• <strong>Painel de Produção</strong> — visão detalhada para o chão de fábrica<br>• <strong>Setores</strong> — organização da fábrica (Costura, Corte, Estamparia, etc.)',
                'position' => 'bottom',
                'page' => 'home'
            ];
        }

        // ── Pipeline ──
        if ($isAdmin || in_array('pipeline', $permissions)) {
            $steps[] = [
                'id' => 'pipeline_page',
                'type' => 'highlight',
                'element' => 'main.main-bg',
                'title' => '<i class="fas fa-stream me-1"></i> Pipeline de Produção',
                'description' => 'O <strong>Pipeline</strong> é um quadro visual <strong>Kanban</strong>:<br><br>• Cada <strong>coluna</strong> = uma etapa do processo<br>• Cada <strong>card</strong> = um pedido com info resumida<br>• <strong>Arraste os cards</strong> entre colunas para mover pedidos<br>• Pedidos atrasados ficam <strong>destacados em vermelho</strong><br>• Clique no card para ver <strong>detalhes completos</strong> do pedido<br>• Metas de tempo por etapa são configuráveis',
                'position' => 'bottom-end',
                'page' => 'pipeline'
            ];
        }

        // ── Setores ──
        if ($isAdmin || in_array('sectors', $permissions)) {
            $steps[] = [
                'id' => 'sectors_page',
                'type' => 'highlight',
                'element' => 'main.main-bg',
                'title' => '<i class="fas fa-industry me-1"></i> Setores de Produção',
                'description' => 'Organize sua <strong>linha produtiva</strong> por setores:<br><br>• Crie setores como <em>Costura, Corte, Estamparia, Embalagem</em><br>• Vincule setores a <strong>produtos</strong>, <strong>categorias</strong> ou <strong>subcategorias</strong><br>• No pipeline, acompanhe o progresso <strong>setor a setor</strong><br>• Defina a <strong>ordem</strong> dos setores na linha de produção',
                'position' => 'bottom-end',
                'page' => 'sectors'
            ];
        }

        // ── Submenu Fiscal ──
        $hasFinancial = $isAdmin || in_array('financial', $permissions);
        if ($hasFinancial) {
            $steps[] = [
                'id' => 'menu_fiscal',
                'type' => 'highlight',
                'element' => '[data-wt-menu="fiscal"]',
                'submenu' => '[data-wt-toggle="fiscal"]',
                'title' => '<i class="fas fa-coins me-1"></i> Menu Fiscal',
                'description' => 'O grupo <strong>Fiscal</strong> contém o módulo financeiro completo:<br><br>• <strong>Pagamentos</strong> — controle de parcelas, boletos, comprovantes e confirmações<br>• <strong>Entradas / Saídas</strong> — livro caixa com registro de todas as transações<br><br>Estornos ficam registrados para auditoria mas <strong>não afetam os cálculos</strong> de saldo.',
                'position' => 'bottom',
                'page' => 'home'
            ];
        }

        // ── Pagamentos (parcelas) ──
        if ($hasFinancial) {
            $steps[] = [
                'id' => 'financial_payments',
                'type' => 'highlight',
                'element' => 'main.main-bg',
                'title' => '<i class="fas fa-file-invoice-dollar me-1"></i> Pagamentos / Parcelas',
                'description' => 'Gerencie <strong>todas as parcelas</strong> de todos os pedidos:<br><br>• <strong>Registrar pagamento</strong> — com forma de pagamento pré-selecionada<br>• <strong>Confirmar / Estornar</strong> — aprovação manual de pagamentos<br>• <strong>Anexar comprovante</strong> — upload de foto ou PDF<br>• <strong>Reimprimir boleto</strong> — boleto CNAB 400/FEBRABAN com código de barras<br>• Filtre por <strong>status, mês e ano</strong><br>• Parcelas ordenadas por <strong>vencimento</strong>, atrasadas em destaque<br><br><small class="text-muted">Ao clicar em "Ver parcelas" de um pedido, a listagem é apenas visualização.</small>',
                'position' => 'bottom-end',
                'page' => 'financial_payments'
            ];
        }

        // ── Entradas e Saídas ──
        if ($hasFinancial) {
            $steps[] = [
                'id' => 'financial_transactions',
                'type' => 'highlight',
                'element' => 'main.main-bg',
                'title' => '<i class="fas fa-exchange-alt me-1"></i> Entradas e Saídas',
                'description' => 'O <strong>livro caixa</strong> do sistema registra todas as movimentações:<br><br>• <strong>Entradas</strong> — pagamentos de pedidos, serviços avulsos, venda direta<br>• <strong>Saídas</strong> — materiais, aluguel, salários, impostos, fornecedores<br>• <strong>Estornos</strong> — ficam visíveis no registro para auditoria, mas <strong>não contam</strong> nos totais<br>• Adicione transações <strong>manualmente</strong> com "Nova Transação"<br>• Filtre por <strong>tipo, mês, ano e categoria</strong><br><br>Os totais de <strong>Entradas, Saídas e Saldo</strong> são exibidos no topo.',
                'position' => 'bottom-end',
                'page' => 'financial_transactions'
            ];
        }

        // ── Configurações ──
        if ($isAdmin || in_array('settings', $permissions)) {
            $steps[] = [
                'id' => 'settings_page',
                'type' => 'highlight',
                'element' => 'main.main-bg',
                'title' => '<i class="fas fa-cog me-1"></i> Configurações',
                'description' => 'Personalize <strong>todo o sistema</strong>:<br><br>• <strong>Dados da empresa</strong> — nome, CNPJ, logo, endereço e telefone<br>• <strong>Tabelas de preço</strong> — preços diferenciados por grupo de cliente<br>• <strong>Pipeline</strong> — metas de tempo por etapa<br>• <strong>Dados fiscais</strong> — configurações para emissão de NF-e<br>• <strong>Boleto/Bancário</strong> — dados bancários para geração de boletos FEBRABAN<br>• <strong>Preparação</strong> — checklist de conferência antes do envio',
                'position' => 'bottom-end',
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
                'description' => 'Gerencie o <strong>acesso ao sistema</strong>:<br><br>• Cadastre <strong>usuários</strong> (Admin ou Funcionário)<br>• Crie <strong>grupos de permissão</strong> (ex: Produção, Vendas, Estoque)<br>• Cada grupo define quais <strong>páginas e módulos</strong> podem ser acessados<br>• Atribua um grupo a cada funcionário para controlar o acesso',
                'position' => 'bottom-end',
                'page' => 'users'
            ];
        }

        // ── Perfil ──
        $steps[] = [
            'id' => 'profile',
            'type' => 'highlight',
            'element' => 'a[href="?page=profile"]',
            'title' => '<i class="fas fa-user-circle me-1"></i> Seu Perfil',
            'description' => 'Clique no seu <strong>nome de usuário</strong> para acessar seu perfil:<br><br>• <strong>Alterar nome, email e senha</strong><br>• <strong>Personalizar</strong> suas preferências<br>• <strong>Visualizar</strong> informações de acesso e grupo',
            'position' => 'bottom-end',
            'page' => 'home'
        ];

        // ── Botão de ajuda no rodapé ──
        $steps[] = [
            'id' => 'footer_help',
            'type' => 'highlight',
            'element' => '#wtFooterHelp',
            'title' => '<i class="fas fa-lightbulb me-1"></i> Botão de Tutorial',
            'description' => 'Este é o botão <strong>"Tutorial"</strong> no rodapé!<br><br>Clique nele <strong>a qualquer momento</strong> para refazer este tour.<br><br>Ao lado, você encontra o link para o <strong>Manual do Sistema</strong> com instruções completas de cada funcionalidade.',
            'position' => 'top',
            'page' => 'home'
        ];

        // ── Conclusão (modal) ──
        $steps[] = [
            'id' => 'finish',
            'type' => 'modal',
            'title' => 'Tour Concluído!',
            'description' => 'Parabéns! Agora você conhece todas as áreas do <strong>Akti</strong>.<br><br><i class="fas fa-sync-alt me-1 text-info"></i> <strong>Refazer o tour:</strong> Use o botão <em>"Tutorial"</em> no rodapé.<br><br><i class="fas fa-book me-1 text-primary"></i> <strong>Manual completo:</strong> Clique em <em>"Manual"</em> no rodapé para ver instruções detalhadas de cada funcionalidade.',
            'icon' => 'fas fa-check-circle',
            'position' => 'center'
        ];

        return $steps;
    }
}
