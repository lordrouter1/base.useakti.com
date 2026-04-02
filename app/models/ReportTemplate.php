<?php

namespace Akti\Models;

use PDO;

class ReportTemplate
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function readAll(?int $userId = null): array
    {
        $where = 'WHERE (is_shared = 1';
        $params = [];
        if ($userId) {
            $where .= ' OR user_id = :user_id';
            $params[':user_id'] = $userId;
        }
        $where .= ')';

        $stmt = $this->conn->prepare("SELECT * FROM report_templates {$where} ORDER BY name ASC");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function readOne(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM report_templates WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO report_templates (tenant_id, user_id, name, entity, columns, filters, grouping, sorting, is_shared)
             VALUES (:tenant_id, :user_id, :name, :entity, :columns, :filters, :grouping, :sorting, :is_shared)"
        );
        $stmt->execute([
            ':tenant_id' => $data['tenant_id'],
            ':user_id'   => $data['user_id'] ?? null,
            ':name'      => $data['name'],
            ':entity'    => $data['entity'],
            ':columns'   => json_encode($data['columns'] ?? []),
            ':filters'   => json_encode($data['filters'] ?? []),
            ':grouping'  => json_encode($data['grouping'] ?? []),
            ':sorting'   => json_encode($data['sorting'] ?? []),
            ':is_shared' => $data['is_shared'] ?? 0,
        ]);
        return (int) $this->conn->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE report_templates SET
                name = :name, entity = :entity, columns = :columns,
                filters = :filters, grouping = :grouping, sorting = :sorting, is_shared = :is_shared
             WHERE id = :id"
        );
        return $stmt->execute([
            ':id'        => $id,
            ':name'      => $data['name'],
            ':entity'    => $data['entity'],
            ':columns'   => json_encode($data['columns'] ?? []),
            ':filters'   => json_encode($data['filters'] ?? []),
            ':grouping'  => json_encode($data['grouping'] ?? []),
            ':sorting'   => json_encode($data['sorting'] ?? []),
            ':is_shared' => $data['is_shared'] ?? 0,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM report_templates WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getAvailableEntities(): array
    {
        return [
            'orders'     => ['label' => 'Pedidos', 'columns' => ['id', 'customer_name', 'total', 'status', 'created_at', 'delivery_date']],
            'customers'  => ['label' => 'Clientes', 'columns' => ['id', 'name', 'email', 'phone', 'document', 'city', 'state', 'created_at']],
            'products'   => ['label' => 'Produtos', 'columns' => ['id', 'name', 'sku', 'price', 'category_name', 'stock_qty', 'created_at']],
            'financial'  => ['label' => 'Financeiro', 'columns' => ['id', 'type', 'amount', 'category', 'description', 'date', 'status']],
            'suppliers'  => ['label' => 'Fornecedores', 'columns' => ['id', 'company_name', 'document', 'email', 'phone', 'city', 'status']],
            'quotes'     => ['label' => 'Orçamentos', 'columns' => ['id', 'customer_name', 'total', 'status', 'valid_until', 'created_at']],
        ];
    }

    public function executeReport(int $id): array
    {
        $template = $this->readOne($id);
        if (!$template) {
            return ['data' => [], 'error' => 'Template not found'];
        }

        $entity = $template['entity'];
        $columns = json_decode($template['columns'], true) ?: ['*'];
        $filters = json_decode($template['filters'], true) ?: [];
        $sorting = json_decode($template['sorting'], true) ?: [];

        $tableMap = [
            'orders'    => 'orders',
            'customers' => 'customers',
            'products'  => 'products',
            'financial' => 'financial_transactions',
            'suppliers' => 'suppliers',
            'quotes'    => 'quotes',
        ];

        $table = $tableMap[$entity] ?? null;
        if (!$table) {
            return ['data' => [], 'error' => 'Invalid entity'];
        }

        $allowedColumns = $this->getAvailableEntities()[$entity]['columns'] ?? [];
        $safeColumns = array_intersect($columns, $allowedColumns);
        if (empty($safeColumns)) {
            $safeColumns = ['*'];
        }
        $selectColumns = implode(', ', array_map(fn($c) => "`{$c}`", $safeColumns));

        $where = ' WHERE 1=1';
        $params = [];
        foreach ($filters as $i => $filter) {
            $field = preg_replace('/[^a-zA-Z0-9_]/', '', $filter['field'] ?? '');
            if (!$field || !in_array($field, $allowedColumns)) continue;
            $op = match ($filter['operator'] ?? '=') {
                '>', '<', '>=', '<=', '!=' => $filter['operator'],
                'like' => 'LIKE',
                default => '=',
            };
            $paramKey = ":f{$i}";
            $value = $filter['value'] ?? '';
            if ($op === 'LIKE') $value = "%{$value}%";
            $where .= " AND `{$field}` {$op} {$paramKey}";
            $params[$paramKey] = $value;
        }

        $orderBy = '';
        if (!empty($sorting)) {
            $sortParts = [];
            foreach ($sorting as $sort) {
                $sortField = preg_replace('/[^a-zA-Z0-9_]/', '', $sort['field'] ?? '');
                if ($sortField && in_array($sortField, $allowedColumns)) {
                    $dir = strtoupper($sort['direction'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
                    $sortParts[] = "`{$sortField}` {$dir}";
                }
            }
            if ($sortParts) $orderBy = ' ORDER BY ' . implode(', ', $sortParts);
        }

        $sql = "SELECT {$selectColumns} FROM `{$table}`{$where}{$orderBy} LIMIT 1000";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return ['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }
}
