<?php
namespace Akti\Models;

/**
 * Model para o Site Builder.
 *
 * Gerencia páginas, seções, componentes e configurações de tema
 * da loja online do tenant.
 */
class SiteBuilder
{
    private $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    // ─── Pages ───────────────────────────────────────────────────

    /**
     * Lista todas as páginas do tenant.
     */
    public function getPages(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM sb_pages WHERE tenant_id = :tid ORDER BY sort_order, id'
        );
        $stmt->execute([':tid' => $tenantId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Obtém uma página específica.
     */
    public function getPage(int $id, int $tenantId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM sb_pages WHERE id = :id AND tenant_id = :tid'
        );
        $stmt->execute([':id' => $id, ':tid' => $tenantId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Cria uma nova página.
     */
    public function createPage(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO sb_pages (tenant_id, title, slug, type, meta_title, meta_description, is_active, sort_order)
             VALUES (:tid, :title, :slug, :type, :meta_title, :meta_desc, :active, :sort)'
        );
        $stmt->execute([
            ':tid'       => $data['tenant_id'],
            ':title'     => $data['title'],
            ':slug'      => $data['slug'],
            ':type'      => $data['type'] ?? 'custom',
            ':meta_title' => $data['meta_title'] ?? null,
            ':meta_desc' => $data['meta_description'] ?? null,
            ':active'    => $data['is_active'] ?? 1,
            ':sort'      => $data['sort_order'] ?? 0,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Atualiza uma página existente.
     */
    public function updatePage(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE sb_pages SET title = :title, slug = :slug, type = :type,
                    meta_title = :meta_title, meta_description = :meta_desc,
                    is_active = :active, sort_order = :sort
             WHERE id = :id AND tenant_id = :tid'
        );
        return $stmt->execute([
            ':id'        => $id,
            ':tid'       => $data['tenant_id'],
            ':title'     => $data['title'],
            ':slug'      => $data['slug'],
            ':type'      => $data['type'] ?? 'custom',
            ':meta_title' => $data['meta_title'] ?? null,
            ':meta_desc' => $data['meta_description'] ?? null,
            ':active'    => $data['is_active'] ?? 1,
            ':sort'      => $data['sort_order'] ?? 0,
        ]);
    }

    /**
     * Exclui uma página (cascade remove seções e componentes).
     */
    public function deletePage(int $id, int $tenantId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM sb_pages WHERE id = :id AND tenant_id = :tid'
        );
        return $stmt->execute([':id' => $id, ':tid' => $tenantId]);
    }

    // ─── Sections ────────────────────────────────────────────────

    /**
     * Lista seções de uma página (ordenadas).
     */
    public function getSections(int $pageId, int $tenantId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM sb_sections WHERE page_id = :pid AND tenant_id = :tid ORDER BY sort_order'
        );
        $stmt->execute([':pid' => $pageId, ':tid' => $tenantId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Obtém uma seção específica do tenant.
     */
    public function getSection(int $id, int $tenantId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM sb_sections WHERE id = :id AND tenant_id = :tid'
        );
        $stmt->execute([':id' => $id, ':tid' => $tenantId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Valida se a ordem informada contém exatamente as seções da página.
     */
    public function isValidSectionOrder(int $pageId, int $tenantId, array $order): bool
    {
        $sections = $this->getSections($pageId, $tenantId);
        $existingIds = array_map('intval', array_column($sections, 'id'));
        $incomingIds = array_map('intval', $order);

        sort($existingIds);
        sort($incomingIds);

        return $existingIds === $incomingIds;
    }

    /**
     * Salva seções em lote com transação.
     */
    public function saveSectionsBatch(int $tenantId, int $pageId, array $sections): bool
    {
        if (!$this->getPage($pageId, $tenantId)) {
            return false;
        }

        $existing = $this->getSections($pageId, $tenantId);
        $existingIds = array_flip(array_map('intval', array_column($existing, 'id')));

        try {
            $this->db->beginTransaction();

            foreach ($sections as $index => $section) {
                $sectionId = isset($section['id']) ? (int) $section['id'] : 0;
                $sectionData = [
                    'tenant_id'  => $tenantId,
                    'page_id'    => $pageId,
                    'type'       => $section['type'] ?? 'custom-html',
                    'settings'   => $section['settings'] ?? [],
                    'sort_order' => $index,
                    'is_visible' => $section['is_visible'] ?? 1,
                ];

                if ($sectionId > 0) {
                    if (!isset($existingIds[$sectionId])) {
                        $this->db->rollBack();
                        return false;
                    }
                    if (!$this->updateSection($sectionId, $sectionData)) {
                        $this->db->rollBack();
                        return false;
                    }
                    continue;
                }

                if ($this->createSection($sectionData) <= 0) {
                    $this->db->rollBack();
                    return false;
                }
            }

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }

    /**
     * Cria uma nova seção.
     */
    public function createSection(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO sb_sections (tenant_id, page_id, type, settings, sort_order, is_visible)
             VALUES (:tid, :pid, :type, :settings, :sort, :visible)'
        );
        $stmt->execute([
            ':tid'      => $data['tenant_id'],
            ':pid'      => $data['page_id'],
            ':type'     => $data['type'],
            ':settings' => json_encode($data['settings'] ?? []),
            ':sort'     => $data['sort_order'] ?? 0,
            ':visible'  => $data['is_visible'] ?? 1,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Atualiza uma seção existente.
     */
    public function updateSection(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE sb_sections SET type = :type, settings = :settings,
                    sort_order = :sort, is_visible = :visible
             WHERE id = :id AND tenant_id = :tid'
        );
        return $stmt->execute([
            ':id'       => $id,
            ':tid'      => $data['tenant_id'],
            ':type'     => $data['type'],
            ':settings' => json_encode($data['settings'] ?? []),
            ':sort'     => $data['sort_order'] ?? 0,
            ':visible'  => $data['is_visible'] ?? 1,
        ]);
    }

    /**
     * Exclui uma seção.
     */
    public function deleteSection(int $id, int $tenantId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM sb_sections WHERE id = :id AND tenant_id = :tid'
        );
        return $stmt->execute([':id' => $id, ':tid' => $tenantId]);
    }

    /**
     * Reordena seções de uma página.
     */
    public function reorderSections(int $pageId, int $tenantId, array $order): bool
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                'UPDATE sb_sections SET sort_order = :sort WHERE id = :id AND tenant_id = :tid AND page_id = :pid'
            );
            foreach ($order as $position => $sectionId) {
                $stmt->execute([
                    ':sort' => $position,
                    ':id'   => (int) $sectionId,
                    ':tid'  => $tenantId,
                    ':pid'  => $pageId,
                ]);
            }

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }

    // ─── Components ──────────────────────────────────────────────

    /**
     * Lista componentes de uma seção.
     */
    public function getComponents(int $sectionId, int $tenantId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM sb_components WHERE section_id = :sid AND tenant_id = :tid ORDER BY sort_order'
        );
        $stmt->execute([':sid' => $sectionId, ':tid' => $tenantId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Obtém um componente específico do tenant.
     */
    public function getComponent(int $id, int $tenantId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM sb_components WHERE id = :id AND tenant_id = :tid LIMIT 1'
        );
        $stmt->execute([':id' => $id, ':tid' => $tenantId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Cria um novo componente.
     */
    public function createComponent(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO sb_components (tenant_id, section_id, type, content, grid_col, grid_row, sort_order)
             VALUES (:tid, :sid, :type, :content, :col, :row, :sort)'
        );
        $stmt->execute([
            ':tid'     => $data['tenant_id'],
            ':sid'     => $data['section_id'],
            ':type'    => $data['type'],
            ':content' => json_encode($data['content'] ?? []),
            ':col'     => $data['grid_col'] ?? 12,
            ':row'     => $data['grid_row'] ?? 0,
            ':sort'    => $data['sort_order'] ?? 0,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Atualiza um componente existente.
     */
    public function updateComponent(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE sb_components SET type = :type, content = :content,
                    grid_col = :col, grid_row = :row, sort_order = :sort
             WHERE id = :id AND tenant_id = :tid'
        );
        return $stmt->execute([
            ':id'      => $id,
            ':tid'     => $data['tenant_id'],
            ':type'    => $data['type'],
            ':content' => json_encode($data['content'] ?? []),
            ':col'     => $data['grid_col'] ?? 12,
            ':row'     => $data['grid_row'] ?? 0,
            ':sort'    => $data['sort_order'] ?? 0,
        ]);
    }

    /**
     * Exclui um componente.
     */
    public function deleteComponent(int $id, int $tenantId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM sb_components WHERE id = :id AND tenant_id = :tid'
        );
        return $stmt->execute([':id' => $id, ':tid' => $tenantId]) && $stmt->rowCount() > 0;
    }

    // ─── Theme Settings ─────────────────────────────────────────

    /**
     * Obtém todas as configurações de tema do tenant.
     */
    public function getThemeSettings(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            'SELECT setting_key, setting_value, setting_group
             FROM sb_theme_settings WHERE tenant_id = :tid'
        );
        $stmt->execute([':tid' => $tenantId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }

    /**
     * Salva uma configuração de tema (insert ou update).
     */
    public function saveThemeSetting(int $tenantId, string $key, string $value, string $group = 'general'): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO sb_theme_settings (tenant_id, setting_key, setting_value, setting_group)
             VALUES (:tid, :key, :val, :grp)
             ON DUPLICATE KEY UPDATE setting_value = :val2, setting_group = :grp2'
        );
        return $stmt->execute([
            ':tid'  => $tenantId,
            ':key'  => $key,
            ':val'  => $value,
            ':grp'  => $group,
            ':val2' => $value,
            ':grp2' => $group,
        ]);
    }

    /**
     * Salva múltiplas configurações de tema de uma vez.
     */
    public function saveThemeSettings(int $tenantId, array $settings, string $group = 'general'): bool
    {
        try {
            $this->db->beginTransaction();
            foreach ($settings as $key => $value) {
                if (!$this->saveThemeSetting($tenantId, $key, (string) $value, $group)) {
                    $this->db->rollBack();
                    return false;
                }
            }
            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }

    // ─── Full Page Data ──────────────────────────────────────────

    /**
     * Carrega uma página com todas as suas seções e componentes.
     */
    public function getFullPage(int $pageId, int $tenantId): ?array
    {
        $page = $this->getPage($pageId, $tenantId);
        if (!$page) {
            return null;
        }

        $sections = $this->getSections($pageId, $tenantId);
        foreach ($sections as &$section) {
            $section['settings'] = json_decode($section['settings'] ?? '{}', true) ?: [];
            $section['components'] = $this->getComponents((int) $section['id'], $tenantId);
            foreach ($section['components'] as &$component) {
                $component['content'] = json_decode($component['content'] ?? '{}', true) ?: [];
            }
        }

        $page['sections'] = $sections;
        return $page;
    }
}
