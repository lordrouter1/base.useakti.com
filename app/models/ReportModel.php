<?php
namespace Akti\Models;

use PDO;

/**
 * Model: ReportModel
 * Gera relatórios financeiros a partir das tabelas existentes do sistema.
 * Entradas: Conexão PDO ($db), períodos (start/end) via parâmetros.
 * Saídas: Arrays de dados para relatórios (pedidos, faturamento, DRE, parcelas).
 * Não deve conter HTML, echo, print ou acesso direto a $_POST/$_GET.
 */
class ReportModel
{
    private $conn;

    /**
     * Construtor do model
     * @param PDO $db Conexão PDO
     */
    public function __construct($db)
    {
        $this->conn = $db;
    }

    // ═══════════════════════════════════════════
    // PEDIDOS POR PERÍODO
    // ═══════════════════════════════════════════

    /**
     * Retorna pedidos dentro de um período com cliente, total, status e data.
     *
     * @param string $start Data inicial (Y-m-d)
     * @param string $end   Data final (Y-m-d)
     * @return array Lista de pedidos
     */
    public function getOrdersByPeriod(string $start, string $end): array
    {
        $sql = "SELECT o.id,
                       c.name AS customer_name,
                       (o.total_amount - COALESCE(o.discount, 0)) AS total,
                       o.status,
                       o.payment_status,
                       o.pipeline_stage,
                       DATE_FORMAT(o.created_at, '%d/%m/%Y') AS created_at_fmt,
                       o.created_at
                FROM orders o
                LEFT JOIN customers c ON o.customer_id = c.id
                WHERE DATE(o.created_at) BETWEEN :start AND :end
                  AND o.status != 'cancelado'
                ORDER BY o.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':start', $start);
        $stmt->bindParam(':end', $end);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ═══════════════════════════════════════════
    // FATURAMENTO POR CLIENTE
    // ═══════════════════════════════════════════

    /**
     * Retorna faturamento agrupado por cliente com quantidade de pedidos e soma.
     *
     * @param string $start Data inicial (Y-m-d)
     * @param string $end   Data final (Y-m-d)
     * @return array Lista de faturamento por cliente
     */
    public function getRevenueByCustomer(string $start, string $end): array
    {
        $sql = "SELECT c.id AS customer_id,
                       c.name AS customer_name,
                       COUNT(o.id) AS total_orders,
                       COALESCE(SUM(o.total_amount - COALESCE(o.discount, 0)), 0) AS total_revenue
                FROM orders o
                LEFT JOIN customers c ON o.customer_id = c.id
                WHERE DATE(o.created_at) BETWEEN :start AND :end
                  AND o.status != 'cancelado'
                GROUP BY c.id, c.name
                ORDER BY total_revenue DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':start', $start);
        $stmt->bindParam(':end', $end);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ═══════════════════════════════════════════
    // DRE — DEMONSTRATIVO DE RESULTADO
    // ═══════════════════════════════════════════

    /**
     * Retorna entradas e saídas agrupadas por categoria com saldo líquido.
     *
     * @param string $start Data inicial (Y-m-d)
     * @param string $end   Data final (Y-m-d)
     * @return array ['entries' => [...], 'exits' => [...], 'totals' => [...]]
     */
    public function getIncomeStatement(string $start, string $end): array
    {
        // Entradas (parcelas pagas + transações de entrada confirmadas)
        $sqlEntries = "SELECT 'pagamento_pedido' AS category,
                              'Pagamento de Pedido' AS category_label,
                              COALESCE(SUM(oi.paid_amount), 0) AS total
                       FROM order_installments oi
                       WHERE oi.status = 'pago'
                         AND DATE(oi.paid_date) BETWEEN :start AND :end

                       UNION ALL

                       SELECT ft.category,
                              ft.category AS category_label,
                              COALESCE(SUM(ft.amount), 0) AS total
                       FROM financial_transactions ft
                       WHERE ft.type = 'entrada'
                         AND ft.is_confirmed = 1
                         AND ft.category NOT IN ('estorno_pagamento', 'registro_ofx')
                         AND DATE(ft.transaction_date) BETWEEN :start2 AND :end2
                       GROUP BY ft.category";

        $stmtE = $this->conn->prepare($sqlEntries);
        $stmtE->bindParam(':start', $start);
        $stmtE->bindParam(':end', $end);
        $stmtE->bindParam(':start2', $start);
        $stmtE->bindParam(':end2', $end);
        $stmtE->execute();
        $entries = $stmtE->fetchAll(PDO::FETCH_ASSOC);

        // Saídas (transações de saída confirmadas)
        $sqlExits = "SELECT ft.category,
                            ft.category AS category_label,
                            COALESCE(SUM(ft.amount), 0) AS total
                     FROM financial_transactions ft
                     WHERE ft.type = 'saida'
                       AND ft.is_confirmed = 1
                       AND ft.category NOT IN ('estorno_pagamento', 'registro_ofx')
                       AND DATE(ft.transaction_date) BETWEEN :start AND :end
                     GROUP BY ft.category";

        $stmtS = $this->conn->prepare($sqlExits);
        $stmtS->bindParam(':start', $start);
        $stmtS->bindParam(':end', $end);
        $stmtS->execute();
        $exits = $stmtS->fetchAll(PDO::FETCH_ASSOC);

        // Calcular totais
        $totalEntries = array_sum(array_column($entries, 'total'));
        $totalExits   = array_sum(array_column($exits, 'total'));

        return [
            'entries' => $entries,
            'exits'   => $exits,
            'totals'  => [
                'total_entries' => $totalEntries,
                'total_exits'   => $totalExits,
                'net_balance'   => $totalEntries - $totalExits,
            ],
        ];
    }

    // ═══════════════════════════════════════════
    // PARCELAS PENDENTES / ATRASADAS
    // ═══════════════════════════════════════════

    /**
     * Retorna parcelas pendentes ou atrasadas com dias de atraso, ordenadas por vencimento.
     *
     * @return array Lista de parcelas abertas
     */
    public function getOpenInstallments(): array
    {
        $sql = "SELECT oi.id AS installment_id,
                       oi.order_id,
                       c.name AS customer_name,
                       oi.installment_number,
                       oi.amount,
                       DATE_FORMAT(oi.due_date, '%d/%m/%Y') AS due_date_fmt,
                       oi.due_date,
                       oi.status,
                       CASE
                           WHEN oi.due_date < CURDATE() THEN DATEDIFF(CURDATE(), oi.due_date)
                           ELSE 0
                       END AS days_overdue
                FROM order_installments oi
                JOIN orders o ON oi.order_id = o.id
                LEFT JOIN customers c ON o.customer_id = c.id
                WHERE oi.status IN ('pendente', 'atrasado')
                  AND o.status != 'cancelado'
                ORDER BY oi.due_date ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ═══════════════════════════════════════════
    // AGENDAMENTOS DE CONTATO (ORÇAMENTO)
    // ═══════════════════════════════════════════

    /**
     * Retorna contatos agendados dentro de um período, com cliente e prioridade.
     *
     * @param string $start Data inicial (Y-m-d)
     * @param string $end   Data final (Y-m-d)
     * @return array Lista de agendamentos
     */
    public function getScheduledContacts(string $start, string $end): array
    {
        $sql = "SELECT o.id,
                       c.name AS customer_name,
                       c.phone AS customer_phone,
                       c.email AS customer_email,
                       o.scheduled_date,
                       DATE_FORMAT(o.scheduled_date, '%d/%m/%Y') AS scheduled_date_fmt,
                       o.priority,
                       o.internal_notes AS notes,
                       (o.total_amount - COALESCE(o.discount, 0)) AS total,
                       DATE_FORMAT(o.created_at, '%d/%m/%Y') AS created_at_fmt
                FROM orders o
                LEFT JOIN customers c ON o.customer_id = c.id
                WHERE o.scheduled_date IS NOT NULL
                  AND o.scheduled_date BETWEEN :start AND :end
                  AND o.pipeline_stage = 'contato'
                  AND o.status != 'cancelado'
                ORDER BY o.scheduled_date ASC, o.priority DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':start', $start);
        $stmt->bindParam(':end', $end);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // ═══════════════════════════════════════════
    // LABELS LEGÍVEIS PARA CATEGORIAS
    // ═══════════════════════════════════════════

    /**
     * Mapa de categorias de transação para labels legíveis (pt-BR).
     */
    public static function getCategoryLabels(): array
    {
        return [
            'pagamento_pedido'  => 'Pagamento de Pedido',
            'servico_avulso'    => 'Serviço Avulso',
            'outra_entrada'     => 'Outra Entrada',
            'material'          => 'Compra de Material',
            'salario'           => 'Salários',
            'aluguel'           => 'Aluguel',
            'energia'           => 'Energia/Água',
            'internet'          => 'Internet/Telefone',
            'manutencao'        => 'Manutenção',
        ];
    }

    /**
     * Retorna label legível de uma categoria.
     */
    public static function getCategoryLabel(string $category): string
    {
        $labels = self::getCategoryLabels();
        return $labels[$category] ?? ucfirst(str_replace('_', ' ', $category));
    }

    /**
     * Mapa de status de pedido para labels legíveis (pt-BR).
     */
    public static function getStatusLabels(): array
    {
        return [
            'pendente'   => 'Pendente',
            'pago'       => 'Pago',
            'parcial'    => 'Parcial',
            'atrasado'   => 'Atrasado',
            'cancelado'  => 'Cancelado',
        ];
    }

    /**
     * Retorna label legível de um status.
     */
    public static function getStatusLabel(string $status): string
    {
        $labels = self::getStatusLabels();
        return $labels[$status] ?? ucfirst($status);
    }

    /**
     * Mapa de etapas do pipeline para labels legíveis (pt-BR).
     */
    public static function getStageLabels(): array
    {
        return [
            'contato'    => 'Contato',
            'orcamento'  => 'Orçamento',
            'venda'      => 'Venda',
            'producao'   => 'Produção',
            'preparacao' => 'Preparação',
            'envio'      => 'Envio/Entrega',
            'financeiro' => 'Financeiro',
            'concluido'  => 'Concluído',
        ];
    }

    /**
     * Mapa de prioridades para labels legíveis (pt-BR).
     */
    public static function getPriorityLabels(): array
    {
        return [
            'urgente' => 'Urgente',
            'alta'    => 'Alta',
            'normal'  => 'Normal',
            'baixa'   => 'Baixa',
        ];
    }

    /**
     * Retorna label legível de uma prioridade.
     */
    public static function getPriorityLabel(string $priority): string
    {
        $labels = self::getPriorityLabels();
        return $labels[$priority] ?? ucfirst($priority);
    }

    // ═══════════════════════════════════════════
    // CATÁLOGO DE PRODUTOS (RELATÓRIO)
    // ═══════════════════════════════════════════

    /**
     * Retorna produtos com informações completas para relatório:
     * nome, SKU, categoria, subcategoria, preços por tabela, setores e variações de grade.
     *
     * @param int|null $productId Filtro por produto específico (null = todos)
     * @param bool $includeVariations Incluir variações de grade
     * @return array ['products' => [...], 'price_tables' => [...]]
     */
    public function getProductsCatalog(?int $productId = null, bool $includeVariations = true): array
    {
        $where = '';
        $params = [];
        if ($productId) {
            $where = 'WHERE p.id = :pid';
            $params[':pid'] = $productId;
        }

        $sql = "SELECT p.id, p.name, p.sku, p.price, p.description,
                       c.name AS category_name,
                       sc.name AS subcategory_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN subcategories sc ON p.subcategory_id = sc.id
                {$where}
                ORDER BY p.name ASC";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Buscar todas as tabelas de preço
        $stmtPt = $this->conn->query("SELECT id, name FROM price_tables ORDER BY is_default DESC, name ASC");
        $priceTables = $stmtPt->fetchAll(PDO::FETCH_ASSOC);

        // Para cada produto, buscar preços por tabela, setores e variações
        foreach ($products as &$prod) {
            // Preços nas tabelas
            $stmtPrices = $this->conn->prepare(
                "SELECT pti.price_table_id, pti.price
                 FROM price_table_items pti
                 WHERE pti.product_id = :pid"
            );
            $stmtPrices->bindValue(':pid', $prod['id'], PDO::PARAM_INT);
            $stmtPrices->execute();
            $priceRows = $stmtPrices->fetchAll(PDO::FETCH_ASSOC);
            $prod['table_prices'] = [];
            foreach ($priceRows as $pr) {
                $prod['table_prices'][$pr['price_table_id']] = $pr['price'];
            }

            // Setores de produção
            $stmtSectors = $this->conn->prepare(
                "SELECT s.name
                 FROM product_sectors ps
                 JOIN production_sectors s ON ps.sector_id = s.id
                 WHERE ps.product_id = :pid
                 ORDER BY ps.sort_order ASC"
            );
            $stmtSectors->bindValue(':pid', $prod['id'], PDO::PARAM_INT);
            $stmtSectors->execute();
            $prod['sectors'] = $stmtSectors->fetchAll(PDO::FETCH_COLUMN);

            // Variações de grade
            $prod['variations'] = [];
            if ($includeVariations) {
                $stmtVar = $this->conn->prepare(
                    "SELECT combination_label, sku, price_override
                     FROM product_grade_combinations
                     WHERE product_id = :pid AND is_active = 1
                     ORDER BY combination_label ASC"
                );
                $stmtVar->bindValue(':pid', $prod['id'], PDO::PARAM_INT);
                $stmtVar->execute();
                $prod['variations'] = $stmtVar->fetchAll(PDO::FETCH_ASSOC);
            }
        }
        unset($prod);

        return [
            'products'     => $products,
            'price_tables' => $priceTables,
        ];
    }

    // ═══════════════════════════════════════════
    // ESTOQUE POR ARMAZÉM (RELATÓRIO)
    // ═══════════════════════════════════════════

    /**
     * Retorna itens de estoque agrupados por armazém com dados do produto.
     *
     * @param int|null $productId Filtro por produto específico (null = todos)
     * @param int|null $warehouseId Filtro por armazém específico (null = todos)
     * @return array ['items' => [...], 'warehouses' => [...]]
     */
    public function getStockByWarehouse(?int $productId = null, ?int $warehouseId = null): array
    {
        $where = ["1=1"];
        $params = [];

        if ($productId) {
            $where[] = "si.product_id = :pid";
            $params[':pid'] = $productId;
        }
        if ($warehouseId) {
            $where[] = "si.warehouse_id = :wid";
            $params[':wid'] = $warehouseId;
        }

        $whereStr = implode(' AND ', $where);

        $sql = "SELECT si.id, si.quantity, si.min_quantity, si.location_code,
                       p.id AS product_id, p.name AS product_name, p.sku AS product_sku, p.price AS product_price,
                       c.name AS category_name,
                       w.id AS warehouse_id, w.name AS warehouse_name,
                       pgc.combination_label, pgc.sku AS combination_sku
                FROM stock_items si
                JOIN products p ON si.product_id = p.id
                JOIN warehouses w ON si.warehouse_id = w.id
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_grade_combinations pgc ON si.combination_id = pgc.id
                WHERE {$whereStr}
                ORDER BY w.name ASC, p.name ASC, pgc.combination_label ASC";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Lista de armazéns ativos
        $stmtW = $this->conn->query("SELECT id, name FROM warehouses WHERE is_active = 1 ORDER BY name ASC");
        $warehouses = $stmtW->fetchAll(PDO::FETCH_ASSOC);

        return [
            'items'      => $items,
            'warehouses' => $warehouses,
        ];
    }

    // ═══════════════════════════════════════════
    // MOVIMENTAÇÕES DE ESTOQUE (RELATÓRIO)
    // ═══════════════════════════════════════════

    /**
     * Retorna movimentações de estoque dentro de um período com produto e usuário.
     *
     * @param string $start Data inicial (Y-m-d)
     * @param string $end   Data final (Y-m-d)
     * @return array Lista de movimentações
     */
    public function getStockMovements(string $start, string $end): array
    {
        $sql = "SELECT sm.id,
                       sm.type,
                       sm.quantity,
                       sm.quantity_before,
                       sm.quantity_after,
                       sm.reason,
                       sm.reference_type,
                       sm.reference_id,
                       DATE_FORMAT(sm.created_at, '%d/%m/%Y %H:%i') AS created_at_fmt,
                       sm.created_at,
                       p.name AS product_name,
                       p.sku AS product_sku,
                       pgc.combination_label,
                       w.name AS warehouse_name,
                       dw.name AS dest_warehouse_name,
                       COALESCE(u.name, 'Sistema') AS user_name
                FROM stock_movements sm
                JOIN products p ON sm.product_id = p.id
                JOIN warehouses w ON sm.warehouse_id = w.id
                LEFT JOIN product_grade_combinations pgc ON sm.combination_id = pgc.id
                LEFT JOIN warehouses dw ON sm.destination_warehouse_id = dw.id
                LEFT JOIN users u ON sm.user_id = u.id
                WHERE DATE(sm.created_at) BETWEEN :start AND :end
                ORDER BY sm.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':start', $start);
        $stmt->bindParam(':end', $end);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna lista de todos os produtos para uso em selects de filtro.
     *
     * @return array Lista simples [id, name]
     */
    public function getProductsForSelect(): array
    {
        $sql = "SELECT id, name FROM products ORDER BY name ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna lista de armazéns ativos para uso em selects de filtro.
     *
     * @return array Lista simples [id, name]
     */
    public function getWarehousesForSelect(): array
    {
        $sql = "SELECT id, name FROM warehouses WHERE is_active = 1 ORDER BY name ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ═══════════════════════════════════════════
    // USUÁRIOS PARA SELECT (FILTROS)
    // ═══════════════════════════════════════════

    /**
     * Retorna lista de usuários ativos para uso em selects de filtro.
     *
     * @return array Lista simples [id, name]
     */
    public function getUsersForSelect(): array
    {
        $sql = "SELECT id, name FROM users ORDER BY name ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ═══════════════════════════════════════════
    // COMISSÕES POR PERÍODO (RELATÓRIO)
    // ═══════════════════════════════════════════

    /**
     * Retorna comissões registradas num período, opcionalmente filtradas por usuário.
     * Agrupa por funcionário para o relatório, com subtotais por usuário.
     *
     * @param string   $start  Data inicial (Y-m-d)
     * @param string   $end    Data final (Y-m-d)
     * @param int|null $userId Filtro por usuário específico (null = todos)
     * @return array ['items' => [...], 'totals' => [...], 'by_user' => [...]]
     */
    public function getCommissionsByPeriod(string $start, string $end, ?int $userId = null): array
    {
        $where = "DATE(cr.created_at) BETWEEN :start AND :end";
        $params = [':start' => $start, ':end' => $end];

        if ($userId) {
            $where .= " AND cr.user_id = :uid";
            $params[':uid'] = $userId;
        }

        // Dados detalhados
        $sql = "SELECT cr.id, cr.order_id, cr.user_id, cr.origem_regra,
                       cr.tipo_calculo, cr.base_calculo,
                       cr.valor_base, cr.valor_comissao, cr.percentual_aplicado,
                       cr.status,
                       DATE_FORMAT(cr.created_at, '%d/%m/%Y') AS created_at_fmt,
                       cr.created_at,
                       u.name AS user_name,
                       c.name AS customer_name,
                       fc.nome AS forma_nome
                FROM comissoes_registradas cr
                JOIN users u ON cr.user_id = u.id
                JOIN orders o ON cr.order_id = o.id
                LEFT JOIN customers c ON o.customer_id = c.id
                LEFT JOIN formas_comissao fc ON cr.forma_comissao_id = fc.id
                WHERE {$where}
                ORDER BY u.name ASC, cr.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Agrupar por usuário
        $byUser = [];
        foreach ($items as $item) {
            $uid = $item['user_id'];
            if (!isset($byUser[$uid])) {
                $byUser[$uid] = [
                    'user_id'   => $uid,
                    'user_name' => $item['user_name'],
                    'items'     => [],
                    'total_valor_base'    => 0,
                    'total_comissao'      => 0,
                    'count'               => 0,
                    'total_paga'          => 0,
                    'total_aprovada'      => 0,
                    'total_calculada'     => 0,
                    'total_cancelada'     => 0,
                ];
            }
            $byUser[$uid]['items'][] = $item;
            $byUser[$uid]['total_valor_base'] += (float) $item['valor_base'];
            $byUser[$uid]['total_comissao']   += (float) $item['valor_comissao'];
            $byUser[$uid]['count']++;
            $byUser[$uid]['total_' . $item['status']] += (float) $item['valor_comissao'];
        }

        // Totais gerais
        $totalValorBase = array_sum(array_column($items, 'valor_base'));
        $totalComissao  = array_sum(array_column($items, 'valor_comissao'));
        $totalPaga      = array_sum(array_map(fn($i) => $i['status'] === 'paga' ? (float)$i['valor_comissao'] : 0, $items));
        $totalAprovada  = array_sum(array_map(fn($i) => $i['status'] === 'aprovada' ? (float)$i['valor_comissao'] : 0, $items));
        $totalCalculada = array_sum(array_map(fn($i) => $i['status'] === 'calculada' ? (float)$i['valor_comissao'] : 0, $items));

        return [
            'items'   => $items,
            'by_user' => array_values($byUser),
            'totals'  => [
                'total_registros'   => count($items),
                'total_valor_base'  => $totalValorBase,
                'total_comissao'    => $totalComissao,
                'total_paga'        => $totalPaga,
                'total_aprovada'    => $totalAprovada,
                'total_calculada'   => $totalCalculada,
                'total_funcionarios'=> count($byUser),
            ],
        ];
    }

    // ═══════════════════════════════════════════
    // LABELS — COMISSÕES
    // ═══════════════════════════════════════════

    /**
     * Mapa de status de comissão para labels legíveis (pt-BR).
     */
    public static function getCommissionStatusLabels(): array
    {
        return [
            'calculada' => 'Calculada',
            'aprovada'  => 'Aprovada',
            'paga'      => 'Paga',
            'cancelada' => 'Cancelada',
        ];
    }

    /**
     * Retorna label legível de um status de comissão.
     */
    public static function getCommissionStatusLabel(string $status): string
    {
        $labels = self::getCommissionStatusLabels();
        return $labels[$status] ?? ucfirst($status);
    }

    /**
     * Mapa de tipos de movimentação para labels legíveis (pt-BR).
     */
    public static function getMovementTypeLabels(): array
    {
        return [
            'entrada'        => 'Entrada',
            'saida'          => 'Saída',
            'ajuste'         => 'Ajuste',
            'transferencia'  => 'Transferência',
        ];
    }

    /**
     * Retorna label legível de um tipo de movimentação.
     */
    public static function getMovementTypeLabel(string $type): string
    {
        $labels = self::getMovementTypeLabels();
        return $labels[$type] ?? ucfirst($type);
    }
}
