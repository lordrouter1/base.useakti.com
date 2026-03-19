<?php
namespace Akti\Models;

use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use Akti\Models\ProductionSector;
use PDO;

class Pipeline {
    private $conn;

    // Definição das etapas do pipeline
    public static $stages = [
        'contato'    => ['label' => 'Contato',       'icon' => 'fas fa-phone',                'color' => '#9b59b6', 'order' => 1],
        'orcamento'  => ['label' => 'Orçamento',     'icon' => 'fas fa-file-invoice-dollar',   'color' => '#3498db', 'order' => 2],
        'venda'      => ['label' => 'Venda',         'icon' => 'fas fa-handshake',             'color' => '#2ecc71', 'order' => 3],
        'producao'   => ['label' => 'Produção',      'icon' => 'fas fa-industry',              'color' => '#e67e22', 'order' => 4],
        'preparacao' => ['label' => 'Preparação',    'icon' => 'fas fa-boxes-packing',         'color' => '#1abc9c', 'order' => 5],
        'envio'      => ['label' => 'Envio/Entrega', 'icon' => 'fas fa-truck',                 'color' => '#e74c3c', 'order' => 6],
        'financeiro' => ['label' => 'Financeiro',    'icon' => 'fas fa-coins',                 'color' => '#f39c12', 'order' => 7],
        'concluido'  => ['label' => 'Concluído',     'icon' => 'fas fa-check-double',          'color' => '#27ae60', 'order' => 8],
        'cancelado'  => ['label' => 'Cancelado',     'icon' => 'fas fa-ban',                   'color' => '#95a5a6', 'order' => 9],
    ];

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Busca todos os pedidos ativos no pipeline (não concluídos/cancelados) 
     * agrupados por etapa, com dados do cliente e responsável.
     * Para contatos agendados, usa scheduled_date para calcular horas na etapa.
     */
    public function getOrdersByStage() {
        $query = "SELECT o.*, c.name as customer_name, c.phone as customer_phone,
                         u.name as assigned_name,
                         CASE 
                            WHEN o.pipeline_stage = 'contato' AND o.scheduled_date IS NOT NULL AND o.scheduled_date > CURDATE()
                                THEN 0
                            WHEN o.pipeline_stage = 'contato' AND o.scheduled_date IS NOT NULL 
                                THEN TIMESTAMPDIFF(HOUR, o.scheduled_date, NOW())
                            ELSE TIMESTAMPDIFF(HOUR, o.pipeline_entered_at, NOW())
                         END as hours_in_stage,
                         o.quote_confirmed_at,
                         o.quote_confirmed_ip
                  FROM orders o
                  LEFT JOIN customers c ON o.customer_id = c.id
                  LEFT JOIN users u ON o.assigned_to = u.id
                  WHERE o.pipeline_stage NOT IN ('concluido','cancelado') AND o.status != 'cancelado'
                  ORDER BY o.priority DESC, o.pipeline_entered_at ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Agrupar por etapa
        $grouped = [];
        foreach (self::$stages as $key => $info) {
            $grouped[$key] = [];
        }
        foreach ($orders as $order) {
            $stage = $order['pipeline_stage'] ?? 'contato';
            if (isset($grouped[$stage])) {
                $grouped[$stage][] = $order;
            }
        }
        return $grouped;
    }

    /**
     * Busca metas de tempo por etapa
     */
    public function getStageGoals() {
        $query = "SELECT * FROM pipeline_stage_goals ORDER BY stage_order ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $goals = [];
        foreach ($rows as $row) {
            $goals[$row['stage']] = $row;
        }
        return $goals;
    }

    /**
     * Atualiza a meta de horas de uma etapa
     */
    public function updateStageGoal($stage, $maxHours) {
        $query = "UPDATE pipeline_stage_goals SET max_hours = :max_hours WHERE stage = :stage";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':max_hours', $maxHours, PDO::PARAM_INT);
        $stmt->bindParam(':stage', $stage);
        return $stmt->execute();
    }

    /**
     * Move um pedido para a próxima etapa (ou uma etapa específica)
     */
    public function moveToStage($orderId, $newStage, $userId = null, $notes = '') {
        // Buscar etapa atual
        $query = "SELECT pipeline_stage FROM orders WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $orderId);
        $stmt->execute();
        $current = $stmt->fetch(PDO::FETCH_ASSOC);
        $fromStage = $current ? $current['pipeline_stage'] : null;

        // Atualizar pedido
        $query = "UPDATE orders SET pipeline_stage = :stage, pipeline_entered_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':stage', $newStage);
        $stmt->bindParam(':id', $orderId);
        $result = $stmt->execute();

        // Atualizar status compatível
        $statusMap = [
            'contato'    => 'orcamento',
            'orcamento'  => 'orcamento',
            'venda'      => 'aprovado',
            'producao'   => 'em_producao',
            'preparacao' => 'em_producao',
            'envio'      => 'em_producao',
            'financeiro' => 'em_producao',
            'concluido'  => 'concluido',
            'cancelado'  => 'cancelado',
        ];
        if (isset($statusMap[$newStage])) {
            $newStatus = $statusMap[$newStage];
            $q = "UPDATE orders SET status = :status WHERE id = :id";
            $s = $this->conn->prepare($q);
            $s->bindParam(':status', $newStatus);
            $s->bindParam(':id', $orderId);
            $s->execute();
        }

        // Registrar histórico
        if ($result) {
            $this->addHistory($orderId, $fromStage, $newStage, $userId, $notes);

            // Inicializar setores de produção quando entra na etapa "producao"
            if ($newStage === 'producao') {
                $this->initOrderProductionSectors($orderId);
            }

            EventDispatcher::dispatch('model.order.stage_changed', new Event('model.order.stage_changed', [
                'id' => $orderId,
                'from_stage' => $fromStage,
                'to_stage' => $newStage,
                'user_id' => $userId,
            ]));
        }

        return $result;
    }

    /**
     * Registra histórico de movimentação
     */
    public function addHistory($orderId, $fromStage, $toStage, $userId = null, $notes = '') {
        $query = "INSERT INTO pipeline_history (order_id, from_stage, to_stage, changed_by, notes) 
                  VALUES (:order_id, :from_stage, :to_stage, :changed_by, :notes)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $orderId);
        $stmt->bindParam(':from_stage', $fromStage);
        $stmt->bindParam(':to_stage', $toStage);
        $stmt->bindParam(':changed_by', $userId);
        $stmt->bindParam(':notes', $notes);
        return $stmt->execute();
    }

    /**
     * Busca histórico de um pedido com duração em cada etapa.
     * A duração é calculada apenas entre timestamps armazenados no banco (sem NOW())
     * para evitar problemas de timezone entre PHP e MySQL.
     * Para a etapa atual (última movimentação), a duração é calculada no PHP.
     */
    public function getHistory($orderId) {
        $query = "SELECT h.*, u.name as user_name,
                         (SELECT h2.created_at FROM pipeline_history h2 
                          WHERE h2.order_id = h.order_id AND h2.id > h.id
                          ORDER BY h2.id ASC LIMIT 1) as next_created_at
                  FROM pipeline_history h
                  LEFT JOIN users u ON h.changed_by = u.id 
                  WHERE h.order_id = :order_id 
                  ORDER BY h.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $orderId);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calcular duration_seconds no PHP para evitar problemas de timezone
        foreach ($rows as &$row) {
            if (!empty($row['next_created_at'])) {
                // Duração entre dois registros do banco (ambos no mesmo timezone)
                $start = strtotime($row['created_at']);
                $end   = strtotime($row['next_created_at']);
                $row['duration_seconds'] = max(0, $end - $start);
            } else {
                // Última movimentação (etapa atual): calcular com hora atual do PHP
                $start = strtotime($row['created_at']);
                $row['duration_seconds'] = max(0, time() - $start);
            }
            unset($row['next_created_at']);
        }
        unset($row);

        return $rows;
    }

    /**
     * Busca detalhes completos de um pedido para o pipeline
     */
    public function getOrderDetail($orderId) {
        $query = "SELECT o.*, c.name as customer_name, c.email as customer_email, 
                         c.phone as customer_phone, c.document as customer_document,
                         c.address as customer_address,
                         u.name as assigned_name,
                         CASE 
                            WHEN o.pipeline_stage = 'contato' AND o.scheduled_date IS NOT NULL AND o.scheduled_date > CURDATE()
                                THEN 0
                            WHEN o.pipeline_stage = 'contato' AND o.scheduled_date IS NOT NULL 
                                THEN TIMESTAMPDIFF(HOUR, o.scheduled_date, NOW())
                            ELSE TIMESTAMPDIFF(HOUR, o.pipeline_entered_at, NOW())
                         END as hours_in_stage
                  FROM orders o
                  LEFT JOIN customers c ON o.customer_id = c.id
                  LEFT JOIN users u ON o.assigned_to = u.id
                  WHERE o.id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $orderId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Atualiza dados extras do pedido (prioridade, responsável, notas, financeiro, envio)
     */
    public function updateOrderDetails($data) {
        $query = "UPDATE orders SET 
                    priority = :priority,
                    assigned_to = :assigned_to,
                    internal_notes = :internal_notes,
                    quote_notes = :quote_notes,
                    deadline = :deadline,
                    payment_status = :payment_status,
                    payment_method = :payment_method,
                    installments = :installments,
                    installment_value = :installment_value,
                    discount = :discount,
                    down_payment = :down_payment,
                    shipping_type = :shipping_type,
                    shipping_address = :shipping_address,
                    tracking_code = :tracking_code,
                    price_table_id = :price_table_id,
                    nf_number = :nf_number,
                    nf_series = :nf_series,
                    nf_status = :nf_status,
                    nf_access_key = :nf_access_key,
                    nf_notes = :nf_notes
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':priority', $data['priority']);
        $stmt->bindParam(':assigned_to', $data['assigned_to']);
        $stmt->bindParam(':internal_notes', $data['internal_notes']);
        $stmt->bindParam(':quote_notes', $data['quote_notes']);
        $stmt->bindParam(':deadline', $data['deadline']);
        $stmt->bindParam(':payment_status', $data['payment_status']);
        $stmt->bindParam(':payment_method', $data['payment_method']);
        $stmt->bindParam(':installments', $data['installments']);
        $stmt->bindParam(':installment_value', $data['installment_value']);
        $stmt->bindParam(':discount', $data['discount']);
        $stmt->bindParam(':down_payment', $data['down_payment']);
        $stmt->bindParam(':shipping_type', $data['shipping_type']);
        $stmt->bindParam(':shipping_address', $data['shipping_address']);
        $stmt->bindParam(':tracking_code', $data['tracking_code']);
        $stmt->bindParam(':price_table_id', $data['price_table_id']);
        $stmt->bindParam(':nf_number', $data['nf_number']);
        $stmt->bindParam(':nf_series', $data['nf_series']);
        $stmt->bindParam(':nf_status', $data['nf_status']);
        $stmt->bindParam(':nf_access_key', $data['nf_access_key']);
        $stmt->bindParam(':nf_notes', $data['nf_notes']);
        $stmt->bindParam(':id', $data['id']);
        return $stmt->execute();
    }

    /**
     * Conta pedidos atrasados (acima da meta de horas por etapa)
     * Para contatos agendados, respeita a scheduled_date
     */
    public function getDelayedOrders() {
        $goals = $this->getStageGoals();
        $query = "SELECT o.*, c.name as customer_name,
                         CASE 
                            WHEN o.pipeline_stage = 'contato' AND o.scheduled_date IS NOT NULL AND o.scheduled_date > CURDATE()
                                THEN 0
                            WHEN o.pipeline_stage = 'contato' AND o.scheduled_date IS NOT NULL 
                                THEN TIMESTAMPDIFF(HOUR, o.scheduled_date, NOW())
                            ELSE TIMESTAMPDIFF(HOUR, o.pipeline_entered_at, NOW())
                         END as hours_in_stage
                  FROM orders o
                  LEFT JOIN customers c ON o.customer_id = c.id
                  WHERE o.pipeline_stage NOT IN ('concluido','cancelado') AND o.status != 'cancelado'
                  ORDER BY hours_in_stage DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $delayed = [];
        foreach ($orders as $order) {
            $stage = $order['pipeline_stage'] ?? 'contato';
            $maxHours = isset($goals[$stage]) ? (int)$goals[$stage]['max_hours'] : 24;
            if ($maxHours > 0 && (int)$order['hours_in_stage'] > $maxHours) {
                $order['max_hours'] = $maxHours;
                $order['delay_hours'] = (int)$order['hours_in_stage'] - $maxHours;
                $delayed[] = $order;
            }
        }
        return $delayed;
    }

    /**
     * Busca pedidos concluídos (para histórico/relatório)
     */
    public function getCompletedOrders($limit = 50) {
        $query = "SELECT o.*, c.name as customer_name 
                  FROM orders o
                  LEFT JOIN customers c ON o.customer_id = c.id
                  WHERE o.pipeline_stage = 'concluido' OR o.status = 'concluido'
                  ORDER BY o.created_at DESC
                  LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Inicializa os setores de produção POR ITEM do pedido quando entra na etapa "producao".
     * Cada item tem seus próprios setores (fallback: produto > subcategoria > categoria).
     * Re-executa para itens novos que ainda não têm setores atribuídos.
     */
    public function initOrderProductionSectors($orderId) {
        // Buscar itens do pedido com dados do produto
        $stmt = $this->conn->prepare("SELECT oi.id as item_id, oi.product_id, p.category_id, p.subcategory_id 
            FROM order_items oi 
            JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = :oid");
        $stmt->execute([':oid' => $orderId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($items)) return;

        // Buscar quais itens já têm setores inicializados
        $existingStmt = $this->conn->prepare("SELECT DISTINCT order_item_id FROM order_production_sectors WHERE order_id = :oid");
        $existingStmt->execute([':oid' => $orderId]);
        $existingItemIds = $existingStmt->fetchAll(PDO::FETCH_COLUMN);

        $sectorModel = new ProductionSector($this->conn);

        $ins = $this->conn->prepare("INSERT INTO order_production_sectors (order_id, order_item_id, sector_id, status, sort_order) VALUES (:oid, :iid, :sid, 'pendente', :sort)");

        foreach ($items as $item) {
            // Pular itens que já possuem setores
            if (in_array($item['item_id'], $existingItemIds)) continue;

            $result = $sectorModel->getEffectiveSectors($item['product_id'], $item['subcategory_id'], $item['category_id']);
            if (!empty($result['sectors'])) {
                foreach ($result['sectors'] as $idx => $s) {
                    $ins->execute([
                        ':oid'  => $orderId,
                        ':iid'  => $item['item_id'],
                        ':sid'  => $s['sector_id'],
                        ':sort' => $s['sort_order']
                    ]);
                }
            }
        }
    }

    /**
     * Retorna os setores de produção de um pedido agrupados por item, com dados do setor e produto
     */
    public function getOrderProductionSectors($orderId) {
        $stmt = $this->conn->prepare("SELECT ops.*, 
                s.name as sector_name, s.icon, s.color,
                oi.product_id, oi.quantity, 
                p.name as product_name,
                u.name as completed_by_name
            FROM order_production_sectors ops
            JOIN production_sectors s ON ops.sector_id = s.id
            JOIN order_items oi ON ops.order_item_id = oi.id
            JOIN products p ON oi.product_id = p.id
            LEFT JOIN users u ON ops.completed_by = u.id
            WHERE ops.order_id = :oid
            ORDER BY ops.order_item_id ASC, ops.sort_order ASC");
        $stmt->execute([':oid' => $orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Concluir o setor atual de um item e avançar para o próximo.
     * O setor atual (pendente) é marcado como concluído.
     * O próximo setor permanece pendente até o usuário concluí-lo.
     */
    public function advanceItemSector($orderId, $orderItemId, $sectorId, $userId = null) {
        // Marcar setor atual como concluído (aceita pendente ou em_andamento para compatibilidade)
        $sql = "UPDATE order_production_sectors 
                SET status = 'concluido', started_at = IFNULL(started_at, NOW()), completed_at = NOW(), completed_by = :uid
                WHERE order_id = :oid AND order_item_id = :iid AND sector_id = :sid AND status IN ('pendente', 'em_andamento')";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':uid' => $userId, ':oid' => $orderId, ':iid' => $orderItemId, ':sid' => $sectorId]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Retroceder: reverte o último setor concluído de um item para pendente.
     * Se o sectorId informado já estiver concluído, reverte ele.
     * Caso contrário, encontra e reverte o último setor concluído (por sort_order)
     * para que o item volte ao setor anterior.
     */
    public function revertItemSector($orderId, $orderItemId, $sectorId, $userId = null) {
        // Buscar estado atual do setor informado
        $stmt = $this->conn->prepare("SELECT status, sort_order FROM order_production_sectors 
            WHERE order_id = :oid AND order_item_id = :iid AND sector_id = :sid");
        $stmt->execute([':oid' => $orderId, ':iid' => $orderItemId, ':sid' => $sectorId]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$current) return false;

        $targetSectorId = $sectorId;

        // Se o setor informado NÃO está concluído, buscar o último concluído deste item
        if ($current['status'] !== 'concluido') {
            $stmtLast = $this->conn->prepare("SELECT sector_id FROM order_production_sectors 
                WHERE order_id = :oid AND order_item_id = :iid AND status = 'concluido'
                ORDER BY sort_order DESC LIMIT 1");
            $stmtLast->execute([':oid' => $orderId, ':iid' => $orderItemId]);
            $lastConcluded = $stmtLast->fetch(PDO::FETCH_ASSOC);
            if (!$lastConcluded) return false; // Nenhum setor concluído para reverter
            $targetSectorId = $lastConcluded['sector_id'];
        }

        // Voltar o setor alvo para pendente
        $upd = $this->conn->prepare("UPDATE order_production_sectors 
            SET status = 'pendente', started_at = NULL, completed_at = NULL, completed_by = NULL
            WHERE order_id = :oid AND order_item_id = :iid AND sector_id = :sid");
        $upd->execute([':oid' => $orderId, ':iid' => $orderItemId, ':sid' => $targetSectorId]);

        return true;
    }

    /**
     * Retorna todos os itens de produção agrupados por setor, para o painel de produção.
     * Cada item aparece APENAS no setor em que se encontra atualmente:
     *   - O primeiro setor pendente (na ordem sort_order) é o setor atual
     *   - Se todos estão concluídos, aparece no último setor concluído
     * Filtra por setores permitidos se $allowedSectorIds não for vazio.
     * Status possíveis: pendente, concluido (sem em_andamento).
     */
    public function getProductionBoardData($allowedSectorIds = []) {
        // Buscar TODOS os registros de produção para pedidos em produção
        $sql = "SELECT ops.*, 
                s.name as sector_name, s.icon as sector_icon, s.color as sector_color, s.id as sector_id,
                oi.product_id, oi.quantity, oi.grade_combination_id, oi.grade_description,
                p.name as product_name,
                (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_main = 1 LIMIT 1) as product_image,
                o.id as order_id, o.created_at as order_created_at, o.priority, o.deadline,
                c.name as customer_name,
                u.name as completed_by_name,
                ua.name as assigned_name
            FROM order_production_sectors ops
            JOIN production_sectors s ON ops.sector_id = s.id
            JOIN order_items oi ON ops.order_item_id = oi.id
            JOIN products p ON oi.product_id = p.id
            JOIN orders o ON ops.order_id = o.id
            LEFT JOIN customers c ON o.customer_id = c.id
            LEFT JOIN users u ON ops.completed_by = u.id
            LEFT JOIN users ua ON o.assigned_to = ua.id
            WHERE o.pipeline_stage = 'producao' AND o.status != 'cancelado'
            ORDER BY ops.order_id ASC, ops.order_item_id ASC, ops.sort_order ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Agrupar todos os registros por (order_id, order_item_id)
        $itemGroups = [];
        foreach ($rows as $row) {
            $key = $row['order_id'] . '_' . $row['order_item_id'];
            $itemGroups[$key][] = $row;
        }

        // Para cada item, determinar em qual setor ele está ATUALMENTE
        $currentItems = []; // sector_id => [rows que estão nesse setor]
        foreach ($itemGroups as $key => $itemSectors) {
            $currentSectorRow = null;
            $hasPreviousConcluded = false;

            // 1. Pegar o primeiro setor pendente (próximo na fila)
            foreach ($itemSectors as $sec) {
                if ($sec['status'] === 'concluido') {
                    $hasPreviousConcluded = true;
                }
                if ($sec['status'] === 'pendente' && !$currentSectorRow) {
                    $currentSectorRow = $sec;
                    $currentSectorRow['has_previous_concluded'] = $hasPreviousConcluded;
                }
            }

            // 2. Se todos concluídos, mostrar no último setor concluído
            if (!$currentSectorRow) {
                $lastConcluido = null;
                foreach ($itemSectors as $sec) {
                    if ($sec['status'] === 'concluido') {
                        $lastConcluido = $sec;
                    }
                }
                if ($lastConcluido) {
                    $currentSectorRow = $lastConcluido;
                    $currentSectorRow['has_previous_concluded'] = false; // Já está concluído, retroceder é direto
                }
            }

            if ($currentSectorRow) {
                $sid = $currentSectorRow['sector_id'];
                if (!isset($currentItems[$sid])) {
                    $currentItems[$sid] = [];
                }
                $currentItems[$sid][] = $currentSectorRow;
            }
        }

        // Montar array de setores agrupados
        $sectors = [];
        foreach ($currentItems as $sid => $items) {
            // Filtro de permissão
            if (!empty($allowedSectorIds) && !in_array((int)$sid, $allowedSectorIds)) continue;

            $first = $items[0];
            if (!isset($sectors[$sid])) {
                $sectors[$sid] = [
                    'id'    => $sid,
                    'name'  => $first['sector_name'],
                    'icon'  => $first['sector_icon'],
                    'color' => $first['sector_color'],
                    'items' => [],
                    'counts' => ['pendente' => 0, 'concluido' => 0],
                ];
            }
            foreach ($items as $item) {
                $sectors[$sid]['items'][] = $item;
                $st = $item['status'];
                if (isset($sectors[$sid]['counts'][$st])) {
                    $sectors[$sid]['counts'][$st]++;
                }
            }
        }

        // Se o usuário tem permissão a setores sem itens, adicionar vazios
        if (!empty($allowedSectorIds)) {
            $placeholders = implode(',', array_fill(0, count($allowedSectorIds), '?'));
            $stmtSec = $this->conn->prepare("SELECT id, name, icon, color FROM production_sectors WHERE id IN ($placeholders) AND is_active = 1 ORDER BY id ASC");
            $stmtSec->execute($allowedSectorIds);
            $allSectors = $stmtSec->fetchAll(PDO::FETCH_ASSOC);
            foreach ($allSectors as $s) {
                if (!isset($sectors[$s['id']])) {
                    $sectors[$s['id']] = [
                        'id'    => $s['id'],
                        'name'  => $s['name'],
                        'icon'  => $s['icon'],
                        'color' => $s['color'],
                        'items' => [],
                        'counts' => ['pendente' => 0, 'concluido' => 0],
                    ];
                }
            }
        } else {
            // Admin: incluir todos os setores ativos
            $stmtAll = $this->conn->prepare("SELECT id, name, icon, color FROM production_sectors WHERE is_active = 1 ORDER BY id ASC");
            $stmtAll->execute();
            $allSectors = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
            foreach ($allSectors as $s) {
                if (!isset($sectors[$s['id']])) {
                    $sectors[$s['id']] = [
                        'id'    => $s['id'],
                        'name'  => $s['name'],
                        'icon'  => $s['icon'],
                        'color' => $s['color'],
                        'items' => [],
                        'counts' => ['pendente' => 0, 'concluido' => 0],
                    ];
                }
            }
        }

        return $sectors;
    }

    /**
     * Mover um setor de produção de um item para um status específico (fallback genérico)
     */
    public function moveOrderSector($orderId, $orderItemId, $sectorId, $newStatus, $userId = null) {
        $sql = "UPDATE order_production_sectors SET status = :status";

        if ($newStatus === 'concluido') {
            $sql .= ", started_at = IFNULL(started_at, NOW()), completed_at = NOW(), completed_by = :uid";
        } elseif ($newStatus === 'pendente') {
            $sql .= ", started_at = NULL, completed_at = NULL, completed_by = NULL";
        }

        $sql .= " WHERE order_id = :oid AND order_item_id = :iid AND sector_id = :sid";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':status', $newStatus);
        $stmt->bindParam(':oid', $orderId);
        $stmt->bindParam(':iid', $orderItemId);
        $stmt->bindParam(':sid', $sectorId);
        if ($newStatus === 'concluido') {
            $stmt->bindParam(':uid', $userId);
        }
        return $stmt->execute();
    }

    /**
     * Estatísticas do pipeline para o dashboard
     */
    public function getStats() {
        // Total por etapa
        $query = "SELECT pipeline_stage, COUNT(*) as total 
                  FROM orders 
                  WHERE status != 'cancelado'
                  GROUP BY pipeline_stage";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $byStage = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Total geral ativo
        $query2 = "SELECT COUNT(*) FROM orders WHERE pipeline_stage NOT IN ('concluido','cancelado') AND status != 'cancelado'";
        $stmt2 = $this->conn->prepare($query2);
        $stmt2->execute();
        $totalActive = $stmt2->fetchColumn();

        // Total atrasados
        $delayed = $this->getDelayedOrders();
        $totalDelayed = count($delayed);

        // Total concluídos no mês
        $query3 = "SELECT COUNT(*) FROM orders WHERE pipeline_stage = 'concluido' 
                   AND MONTH(pipeline_entered_at) = MONTH(NOW()) AND YEAR(pipeline_entered_at) = YEAR(NOW())";
        $stmt3 = $this->conn->prepare($query3);
        $stmt3->execute();
        $completedMonth = $stmt3->fetchColumn();

        // Valor total ativo
        $query4 = "SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE pipeline_stage NOT IN ('concluido','cancelado') AND status != 'cancelado'";
        $stmt4 = $this->conn->prepare($query4);
        $stmt4->execute();
        $totalValue = $stmt4->fetchColumn();

        return [
            'by_stage' => $byStage,
            'total_active' => $totalActive,
            'total_delayed' => $totalDelayed,
            'completed_month' => $completedMonth,
            'total_value' => $totalValue,
            'delayed_orders' => $delayed,
        ];
    }
}
