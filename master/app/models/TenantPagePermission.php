<?php
/**
 * Model: TenantPagePermission
 * Gerencia permissões de páginas por tenant e por plano no painel Master.
 *
 * Lógica: Se não existem registros para o tenant → acesso total (retrocompatível).
 *         Se existem registros → whitelist (apenas as páginas listadas são permitidas).
 */

class TenantPagePermission
{
    private $db;

    /**
     * Lista completa de páginas controláveis (36 páginas).
     */
    public const CONTROLLABLE_PAGES = [
        // Comercial (9)
        'customers', 'orders', 'quotes', 'agenda', 'calendar',
        'price_tables', 'suppliers', 'tickets', 'whatsapp',
        // Catálogo (5)
        'products', 'categories', 'stock', 'supplies', 'supply_stock',
        // Produção (7)
        'pipeline', 'production_board', 'sectors', 'quality',
        'equipment', 'production_costs', 'shipments',
        // Fiscal (5)
        'financial', 'commissions', 'payment_gateways', 'nfe_documents', 'nfe_credentials',
        // Ferramentas (12)
        'reports', 'custom_reports', 'bi', 'site_builder', 'workflows',
        'email_marketing', 'attachments', 'audit', 'branches',
        'achievements', 'esg', 'ai_assistant',
        // Sistema (3)
        'settings', 'users', 'portal_admin',
    ];

    /**
     * Páginas sempre permitidas (não controláveis).
     */
    public const ALWAYS_ALLOWED = [
        'dashboard', 'home', 'profile', 'login', 'logout',
    ];

    /**
     * Agrupamento por categoria para exibição na UI.
     */
    public const PAGE_GROUPS = [
        'Comercial' => ['customers', 'orders', 'quotes', 'agenda', 'calendar', 'price_tables', 'suppliers', 'tickets', 'whatsapp'],
        'Catálogo' => ['products', 'categories', 'stock', 'supplies', 'supply_stock'],
        'Produção' => ['pipeline', 'production_board', 'sectors', 'quality', 'equipment', 'production_costs', 'shipments'],
        'Fiscal' => ['financial', 'commissions', 'payment_gateways', 'nfe_documents', 'nfe_credentials'],
        'Ferramentas' => ['reports', 'custom_reports', 'bi', 'site_builder', 'workflows', 'email_marketing', 'attachments', 'audit', 'branches', 'achievements', 'esg', 'ai_assistant'],
        'Sistema' => ['settings', 'users', 'portal_admin'],
    ];

    /**
     * Labels legíveis para cada página.
     */
    public const PAGE_LABELS = [
        'customers' => 'Clientes', 'orders' => 'Pedidos', 'quotes' => 'Orçamentos',
        'agenda' => 'Agenda de Contatos', 'calendar' => 'Calendário',
        'price_tables' => 'Tabelas de Preço', 'suppliers' => 'Fornecedores',
        'tickets' => 'Tickets / Suporte', 'whatsapp' => 'WhatsApp',
        'products' => 'Produtos', 'categories' => 'Categorias',
        'stock' => 'Controle de Estoque', 'supplies' => 'Insumos',
        'supply_stock' => 'Estoque de Insumos',
        'pipeline' => 'Linha de Produção', 'production_board' => 'Painel de Produção',
        'sectors' => 'Setores', 'quality' => 'Qualidade',
        'equipment' => 'Equipamentos', 'production_costs' => 'Custos de Produção',
        'shipments' => 'Entregas',
        'financial' => 'Financeiro', 'commissions' => 'Comissões',
        'payment_gateways' => 'Gateways de Pagamento',
        'nfe_documents' => 'Notas Fiscais (NF-e)', 'nfe_credentials' => 'Credenciais SEFAZ',
        'reports' => 'Relatórios', 'custom_reports' => 'Relatórios Customizados',
        'bi' => 'Business Intelligence', 'site_builder' => 'Site Builder',
        'workflows' => 'Automações', 'email_marketing' => 'E-mail Marketing',
        'attachments' => 'Anexos', 'audit' => 'Auditoria',
        'branches' => 'Filiais', 'achievements' => 'Gamificação',
        'esg' => 'ESG', 'ai_assistant' => 'Assistente IA',
        'settings' => 'Configurações', 'users' => 'Usuários',
        'portal_admin' => 'Portal do Cliente',
    ];

    public function __construct($db)
    {
        $this->db = $db;
    }

    // ── Permissões por Tenant ──

    /**
     * Retorna as páginas permitidas para um tenant (whitelist).
     * Array vazio = sem restrições (acesso total).
     */
    public function getPermissions(int $tenantClientId): array
    {
        $stmt = $this->db->prepare("
            SELECT page_key FROM tenant_page_permissions
            WHERE tenant_client_id = :tenant_client_id
            ORDER BY page_key
        ");
        $stmt->execute(['tenant_client_id' => $tenantClientId]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Verifica se o tenant tem restrições de página configuradas.
     */
    public function hasRestrictions(int $tenantClientId): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM tenant_page_permissions
            WHERE tenant_client_id = :tenant_client_id
        ");
        $stmt->execute(['tenant_client_id' => $tenantClientId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Define as permissões de páginas para um tenant (transação).
     * Remove todas as permissões anteriores e insere as novas.
     *
     * @param int $tenantClientId
     * @param array $pages Lista de page_keys permitidas
     * @param int $adminId ID do admin que está fazendo a alteração
     */
    public function setPermissions(int $tenantClientId, array $pages, int $adminId): void
    {
        $this->db->beginTransaction();
        try {
            // Limpar permissões existentes
            $stmt = $this->db->prepare("DELETE FROM tenant_page_permissions WHERE tenant_client_id = :id");
            $stmt->execute(['id' => $tenantClientId]);

            // Inserir novas permissões (apenas páginas válidas)
            if (!empty($pages)) {
                $stmt = $this->db->prepare("
                    INSERT INTO tenant_page_permissions (tenant_client_id, page_key, granted_by)
                    VALUES (:tenant_client_id, :page_key, :granted_by)
                ");
                foreach ($pages as $pageKey) {
                    if (in_array($pageKey, self::CONTROLLABLE_PAGES, true)) {
                        $stmt->execute([
                            'tenant_client_id' => $tenantClientId,
                            'page_key' => $pageKey,
                            'granted_by' => $adminId,
                        ]);
                    }
                }
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Remove todas as restrições de um tenant (volta para acesso total).
     */
    public function removeRestrictions(int $tenantClientId): void
    {
        $stmt = $this->db->prepare("DELETE FROM tenant_page_permissions WHERE tenant_client_id = :id");
        $stmt->execute(['id' => $tenantClientId]);
    }

    // ── Permissões por Plano (templates) ──

    /**
     * Retorna as páginas definidas para um plano.
     */
    public function getPlanPermissions(int $planId): array
    {
        $stmt = $this->db->prepare("
            SELECT page_key FROM plan_page_permissions
            WHERE plan_id = :plan_id
            ORDER BY page_key
        ");
        $stmt->execute(['plan_id' => $planId]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Verifica se o plano tem permissões definidas.
     */
    public function planHasPermissions(int $planId): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM plan_page_permissions WHERE plan_id = :plan_id
        ");
        $stmt->execute(['plan_id' => $planId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Define as permissões de páginas para um plano (transação).
     */
    public function setPlanPermissions(int $planId, array $pages): void
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("DELETE FROM plan_page_permissions WHERE plan_id = :id");
            $stmt->execute(['id' => $planId]);

            if (!empty($pages)) {
                $stmt = $this->db->prepare("
                    INSERT INTO plan_page_permissions (plan_id, page_key)
                    VALUES (:plan_id, :page_key)
                ");
                foreach ($pages as $pageKey) {
                    if (in_array($pageKey, self::CONTROLLABLE_PAGES, true)) {
                        $stmt->execute([
                            'plan_id' => $planId,
                            'page_key' => $pageKey,
                        ]);
                    }
                }
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Aplica as permissões de um plano a um tenant específico.
     */
    public function applyPlanPermissions(int $tenantClientId, int $planId, int $adminId): void
    {
        $planPages = $this->getPlanPermissions($planId);
        $this->setPermissions($tenantClientId, $planPages, $adminId);
    }

    /**
     * Sincroniza as permissões de um plano para todos os tenants que usam esse plano.
     */
    public function syncPlanToAllTenants(int $planId, int $adminId): int
    {
        $stmt = $this->db->prepare("
            SELECT id FROM tenant_clients WHERE plan_id = :plan_id AND is_active = 1
        ");
        $stmt->execute(['plan_id' => $planId]);
        $tenants = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $count = 0;
        foreach ($tenants as $tenantId) {
            $this->applyPlanPermissions((int)$tenantId, $planId, $adminId);
            $count++;
        }

        return $count;
    }
}
