<?php
namespace Akti\Models;

use Akti\Core\Log;

use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use PDO;

/**
 * Model para gerenciar etapas de preparo globais (configuráveis via Settings).
 * As etapas definidas aqui são usadas no checklist de preparação de pedidos.
 */
class PreparationStep {
    private $conn;

    public function __construct(\PDO $db) {
        $this->conn = $db;
    }

    /**
     * Verifica se a tabela existe (DDL movida para /sql).
     */
    public function createTableIfNotExists() {
        // DDL movida para sql/update_202603301000_extract_ddl_from_models.sql
        try {
            $this->conn->query("SELECT 1 FROM preparation_steps LIMIT 1");
        } catch (\Exception $e) {
            Log::warning('PreparationStep: Tabela preparation_steps não encontrada. Execute as migrations pendentes.');
        }
    }

    /**
     * Retorna todas as etapas (ativas e inativas), ordenadas
     */
    public function getAll() {
        $this->createTableIfNotExists();
        $this->seedDefaults();
        $stmt = $this->conn->query("SELECT * FROM preparation_steps ORDER BY sort_order ASC, id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna apenas as etapas ativas, ordenadas
     */
    public function getActive() {
        $this->createTableIfNotExists();
        $this->seedDefaults();
        $stmt = $this->conn->query("SELECT * FROM preparation_steps WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna as etapas ativas como array associativo [step_key => ['icon'=>..., 'label'=>..., 'desc'=>...]]
     * Compatível com o formato usado no detail.php e print_production_order.php
     */
    public function getActiveAsMap() {
        $steps = $this->getActive();
        $map = [];
        foreach ($steps as $s) {
            $map[$s['step_key']] = [
                'icon'  => $s['icon'],
                'label' => $s['label'],
                'desc'  => $s['description'],
            ];
        }
        return $map;
    }

    /**
     * Insere os padrões caso a tabela esteja vazia
     */
    private function seedDefaults() {
        $stmt = $this->conn->query("SELECT COUNT(*) FROM preparation_steps");
        $count = $stmt->fetchColumn();
        if ($count > 0) return;

        $defaults = [
            ['revisao_arquivos',  'Revisão dos Arquivos',       'Verificar se todos os arquivos de arte estão corretos',           'fas fa-file-check',    1],
            ['corte_acabamento',  'Corte e Acabamento',         'Realizar corte, dobra e acabamento dos materiais',                'fas fa-cut',           2],
            ['embalagem',         'Embalagem',                  'Embalar os produtos para envio/retirada',                         'fas fa-box',           3],
            ['conferencia_qtd',   'Conferência de Quantidade',  'Verificar se a quantidade confere com o pedido',                  'fas fa-list-check',    4],
            ['conferencia_qual',  'Conferência de Qualidade',   'Inspecionar qualidade final de todos os itens',                   'fas fa-search',        5],
            ['pronto_envio',      'Pronto para Envio',          'Confirmar que o pedido está 100% pronto para envio',              'fas fa-truck-loading', 6],
        ];

        $sql = "INSERT INTO preparation_steps (step_key, label, description, icon, sort_order) VALUES (:key, :label, :desc, :icon, :sort)";
        $ins = $this->conn->prepare($sql);
        foreach ($defaults as $d) {
            $ins->execute([':key' => $d[0], ':label' => $d[1], ':desc' => $d[2], ':icon' => $d[3], ':sort' => $d[4]]);
        }
    }

    /**
     * Adicionar uma nova etapa
     */
    public function add($key, $label, $description, $icon, $sortOrder = 0) {
        $this->createTableIfNotExists();
        $sql = "INSERT INTO preparation_steps (step_key, label, description, icon, sort_order) 
                VALUES (:key, :label, :desc, :icon, :sort)";
        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute([
            ':key'   => $key,
            ':label' => $label,
            ':desc'  => $description,
            ':icon'  => $icon,
            ':sort'  => $sortOrder,
        ]);
        if ($result) {
            EventDispatcher::dispatch('model.preparation_step.created', new Event('model.preparation_step.created', [
                'id' => $this->conn->lastInsertId(),
                'key' => $key,
                'label' => $label,
            ]));
        }
        return $result;
    }

    /**
     * Atualizar uma etapa existente
     */
    public function update($id, $label, $description, $icon, $sortOrder, $isActive) {
        $sql = "UPDATE preparation_steps 
                SET label = :label, description = :desc, icon = :icon, sort_order = :sort, is_active = :active 
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute([
            ':label'  => $label,
            ':desc'   => $description,
            ':icon'   => $icon,
            ':sort'   => $sortOrder,
            ':active' => $isActive ? 1 : 0,
            ':id'     => $id,
        ]);
        if ($result) {
            EventDispatcher::dispatch('model.preparation_step.updated', new Event('model.preparation_step.updated', [
                'id' => $id,
                'label' => $label,
            ]));
        }
        return $result;
    }

    /**
     * Excluir uma etapa
     */
    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM preparation_steps WHERE id = :id");
        $result = $stmt->execute([':id' => $id]);
        if ($result) {
            EventDispatcher::dispatch('model.preparation_step.deleted', new Event('model.preparation_step.deleted', ['id' => $id]));
        }
        return $result;
    }

    /**
     * Ativar/desativar uma etapa
     */
    public function toggleActive($id) {
        $stmt = $this->conn->prepare("UPDATE preparation_steps SET is_active = NOT is_active WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Buscar uma etapa por ID
     */
    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM preparation_steps WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
