<?php
namespace Akti\Models;

use PDO;

/**
 * Model: Commission
 * Gerencia formas de comissão, vínculos por grupo/usuário/produto,
 * faixas progressivas e registros de comissão calculada.
 *
 * @package Akti\Models
 */
class Commission
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    // ═══════════════════════════════════════════════════
    // FORMAS DE COMISSÃO (CRUD)
    // ═══════════════════════════════════════════════════

    /**
     * Retorna todas as formas de comissão.
     */
    public function getAllFormas(): array
    {
        $sql = "SELECT f.*, 
                       (SELECT COUNT(*) FROM comissao_faixas cf WHERE cf.forma_comissao_id = f.id) as total_faixas,
                       (SELECT COUNT(*) FROM grupo_formas_comissao gf WHERE gf.forma_comissao_id = f.id AND gf.ativo = 1) as total_grupos,
                       (SELECT COUNT(*) FROM usuario_forma_comissao uf WHERE uf.forma_comissao_id = f.id AND uf.ativo = 1) as total_usuarios
                FROM formas_comissao f
                ORDER BY f.nome ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna uma forma de comissão pelo ID.
     */
    public function getForma(int $id): ?array
    {
        $sql = "SELECT * FROM formas_comissao WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Cria uma nova forma de comissão.
     */
    public function createForma(array $data): int
    {
        $sql = "INSERT INTO formas_comissao (nome, descricao, tipo_calculo, base_calculo, valor, ativo)
                VALUES (:nome, :descricao, :tipo_calculo, :base_calculo, :valor, :ativo)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':nome'         => $data['nome'],
            ':descricao'    => $data['descricao'] ?? null,
            ':tipo_calculo' => $data['tipo_calculo'] ?? 'percentual',
            ':base_calculo' => $data['base_calculo'] ?? 'valor_venda',
            ':valor'        => $data['valor'] ?? 0,
            ':ativo'        => $data['ativo'] ?? 1,
        ]);
        return (int) $this->conn->lastInsertId();
    }

    /**
     * Atualiza uma forma de comissão.
     */
    public function updateForma(int $id, array $data): bool
    {
        $sql = "UPDATE formas_comissao SET 
                    nome = :nome, descricao = :descricao, tipo_calculo = :tipo_calculo,
                    base_calculo = :base_calculo, valor = :valor, ativo = :ativo
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':id'           => $id,
            ':nome'         => $data['nome'],
            ':descricao'    => $data['descricao'] ?? null,
            ':tipo_calculo' => $data['tipo_calculo'] ?? 'percentual',
            ':base_calculo' => $data['base_calculo'] ?? 'valor_venda',
            ':valor'        => $data['valor'] ?? 0,
            ':ativo'        => $data['ativo'] ?? 1,
        ]);
    }

    /**
     * Remove uma forma de comissão.
     */
    public function deleteForma(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM formas_comissao WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // ═══════════════════════════════════════════════════
    // VÍNCULOS: GRUPO ↔ FORMA DE COMISSÃO
    // ═══════════════════════════════════════════════════

    /**
     * Retorna todas as vinculações de grupo.
     */
    public function getGrupoFormas(?int $groupId = null): array
    {
        $sql = "SELECT gf.*, fc.nome as forma_nome, fc.tipo_calculo, fc.valor, 
                       ug.name as group_name
                FROM grupo_formas_comissao gf
                JOIN formas_comissao fc ON gf.forma_comissao_id = fc.id
                JOIN user_groups ug ON gf.group_id = ug.id";
        $params = [];
        if ($groupId) {
            $sql .= " WHERE gf.group_id = :gid";
            $params[':gid'] = $groupId;
        }
        $sql .= " ORDER BY ug.name ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Vincula uma forma de comissão a um grupo.
     */
    public function linkGrupoForma(int $groupId, int $formaId): bool
    {
        $sql = "INSERT INTO grupo_formas_comissao (group_id, forma_comissao_id, ativo) 
                VALUES (:gid, :fid, 1)
                ON DUPLICATE KEY UPDATE ativo = 1";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':gid' => $groupId, ':fid' => $formaId]);
    }

    /**
     * Remove vínculo grupo-forma.
     */
    public function unlinkGrupoForma(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM grupo_formas_comissao WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // ═══════════════════════════════════════════════════
    // VÍNCULOS: USUÁRIO ↔ FORMA DE COMISSÃO
    // ═══════════════════════════════════════════════════

    /**
     * Retorna todas as vinculações de usuário.
     */
    public function getUsuarioFormas(?int $userId = null): array
    {
        $sql = "SELECT uf.*, fc.nome as forma_nome, fc.tipo_calculo, fc.valor,
                       u.name as user_name
                FROM usuario_forma_comissao uf
                JOIN formas_comissao fc ON uf.forma_comissao_id = fc.id
                JOIN users u ON uf.user_id = u.id";
        $params = [];
        if ($userId) {
            $sql .= " WHERE uf.user_id = :uid";
            $params[':uid'] = $userId;
        }
        $sql .= " ORDER BY u.name ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Vincula uma forma de comissão a um usuário.
     */
    public function linkUsuarioForma(int $userId, int $formaId): bool
    {
        $sql = "INSERT INTO usuario_forma_comissao (user_id, forma_comissao_id, ativo)
                VALUES (:uid, :fid, 1)
                ON DUPLICATE KEY UPDATE ativo = 1";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':uid' => $userId, ':fid' => $formaId]);
    }

    /**
     * Remove vínculo usuário-forma.
     */
    public function unlinkUsuarioForma(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM usuario_forma_comissao WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // ═══════════════════════════════════════════════════
    // REGRAS POR PRODUTO/CATEGORIA
    // ═══════════════════════════════════════════════════

    /**
     * Retorna todas as regras de comissão por produto.
     */
    public function getComissaoProdutos(): array
    {
        $sql = "SELECT cp.*, 
                       p.name as product_name, 
                       c.name as category_name
                FROM comissao_produto cp
                LEFT JOIN products p ON cp.product_id = p.id
                LEFT JOIN categories c ON cp.category_id = c.id
                ORDER BY cp.id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna regra de comissão para um produto específico.
     */
    public function getComissaoProduto(int $productId): ?array
    {
        $sql = "SELECT * FROM comissao_produto WHERE product_id = :pid AND ativo = 1 LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':pid' => $productId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Retorna regra de comissão para uma categoria.
     */
    public function getComissaoCategoria(int $categoryId): ?array
    {
        $sql = "SELECT * FROM comissao_produto WHERE category_id = :cid AND product_id IS NULL AND ativo = 1 LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':cid' => $categoryId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Cria/atualiza regra de comissão por produto.
     */
    public function saveComissaoProduto(array $data): int
    {
        if (!empty($data['id'])) {
            $sql = "UPDATE comissao_produto SET product_id = :pid, category_id = :cid,
                        tipo_calculo = :tipo, valor = :valor, ativo = :ativo
                    WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':id'    => $data['id'],
                ':pid'   => $data['product_id'] ?: null,
                ':cid'   => $data['category_id'] ?: null,
                ':tipo'  => $data['tipo_calculo'],
                ':valor' => $data['valor'],
                ':ativo' => $data['ativo'] ?? 1,
            ]);
            return (int) $data['id'];
        }

        $sql = "INSERT INTO comissao_produto (product_id, category_id, tipo_calculo, valor, ativo)
                VALUES (:pid, :cid, :tipo, :valor, :ativo)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':pid'   => $data['product_id'] ?: null,
            ':cid'   => $data['category_id'] ?: null,
            ':tipo'  => $data['tipo_calculo'],
            ':valor' => $data['valor'],
            ':ativo' => $data['ativo'] ?? 1,
        ]);
        return (int) $this->conn->lastInsertId();
    }

    /**
     * Remove regra de comissão por produto.
     */
    public function deleteComissaoProduto(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM comissao_produto WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // ═══════════════════════════════════════════════════
    // FAIXAS PROGRESSIVAS
    // ═══════════════════════════════════════════════════

    /**
     * Retorna faixas de uma forma de comissão.
     */
    public function getFaixas(int $formaId): array
    {
        $sql = "SELECT * FROM comissao_faixas WHERE forma_comissao_id = :fid ORDER BY faixa_min ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':fid' => $formaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Salva faixas para uma forma de comissão (replace all).
     */
    public function saveFaixas(int $formaId, array $faixas): bool
    {
        // Remove todas as faixas existentes
        $del = $this->conn->prepare("DELETE FROM comissao_faixas WHERE forma_comissao_id = :fid");
        $del->execute([':fid' => $formaId]);

        // Insere novas
        $sql = "INSERT INTO comissao_faixas (forma_comissao_id, faixa_min, faixa_max, percentual)
                VALUES (:fid, :min, :max, :pct)";
        $stmt = $this->conn->prepare($sql);
        foreach ($faixas as $f) {
            $stmt->execute([
                ':fid' => $formaId,
                ':min' => $f['faixa_min'],
                ':max' => $f['faixa_max'] !== '' ? $f['faixa_max'] : null,
                ':pct' => $f['percentual'],
            ]);
        }
        return true;
    }

    // ═══════════════════════════════════════════════════
    // COMISSÕES REGISTRADAS (LOG)
    // ═══════════════════════════════════════════════════

    /**
     * Registra uma comissão calculada.
     */
    public function registrarComissao(array $data): int
    {
        $sql = "INSERT INTO comissoes_registradas 
                (order_id, user_id, forma_comissao_id, origem_regra, tipo_calculo, 
                 base_calculo, valor_base, valor_comissao, percentual_aplicado, status, observacao)
                VALUES (:oid, :uid, :fid, :origem, :tipo, :base, :vb, :vc, :pct, :status, :obs)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':oid'    => $data['order_id'],
            ':uid'    => $data['user_id'],
            ':fid'    => $data['forma_comissao_id'] ?? null,
            ':origem' => $data['origem_regra'] ?? 'padrao',
            ':tipo'   => $data['tipo_calculo'],
            ':base'   => $data['base_calculo'],
            ':vb'     => $data['valor_base'],
            ':vc'     => $data['valor_comissao'],
            ':pct'    => $data['percentual_aplicado'] ?? null,
            ':status' => $data['status'] ?? 'calculada',
            ':obs'    => $data['observacao'] ?? null,
        ]);
        return (int) $this->conn->lastInsertId();
    }

    /**
     * Retorna comissões registradas com filtros e paginação.
     */
    public function getComissoesRegistradas(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $where = "1=1";
        $params = [];

        if (!empty($filters['user_id'])) {
            $where .= " AND cr.user_id = :uid";
            $params[':uid'] = $filters['user_id'];
        }
        if (!empty($filters['status'])) {
            $where .= " AND cr.status = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['month'])) {
            $where .= " AND MONTH(cr.created_at) = :month";
            $params[':month'] = $filters['month'];
        }
        if (!empty($filters['year'])) {
            $where .= " AND YEAR(cr.created_at) = :year";
            $params[':year'] = $filters['year'];
        }
        if (!empty($filters['date_from'])) {
            $where .= " AND DATE(cr.created_at) >= :dfrom";
            $params[':dfrom'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where .= " AND DATE(cr.created_at) <= :dto";
            $params[':dto'] = $filters['date_to'];
        }
        if (!empty($filters['search'])) {
            $where .= " AND (u.name LIKE :search OR o.id LIKE :search2)";
            $params[':search'] = '%' . $filters['search'] . '%';
            $params[':search2'] = '%' . $filters['search'] . '%';
        }

        // Total
        $countSql = "SELECT COUNT(*) FROM comissoes_registradas cr
                     JOIN users u ON cr.user_id = u.id
                     JOIN orders o ON cr.order_id = o.id
                     WHERE $where";
        $cStmt = $this->conn->prepare($countSql);
        $cStmt->execute($params);
        $total = (int) $cStmt->fetchColumn();

        // Somatórios
        $sumSql = "SELECT 
                       COALESCE(SUM(cr.valor_comissao), 0) as total_comissao,
                       COALESCE(SUM(CASE WHEN cr.status = 'paga' THEN cr.valor_comissao ELSE 0 END), 0) as total_paga,
                       COALESCE(SUM(CASE WHEN cr.status IN ('aprovada','aguardando_pagamento') THEN cr.valor_comissao ELSE 0 END), 0) as total_aprovada,
                       COALESCE(SUM(CASE WHEN cr.status = 'aguardando_pagamento' THEN cr.valor_comissao ELSE 0 END), 0) as total_aguardando,
                       COALESCE(SUM(CASE WHEN cr.status = 'calculada' THEN cr.valor_comissao ELSE 0 END), 0) as total_calculada
                   FROM comissoes_registradas cr
                   JOIN users u ON cr.user_id = u.id
                   JOIN orders o ON cr.order_id = o.id
                   WHERE $where";
        $sStmt = $this->conn->prepare($sumSql);
        $sStmt->execute($params);
        $summary = $sStmt->fetch(PDO::FETCH_ASSOC);

        $offset = ($page - 1) * $perPage;
        $dataSql = "SELECT cr.*, u.name as user_name, o.total_amount as order_total,
                           c.name as customer_name, fc.nome as forma_nome
                    FROM comissoes_registradas cr
                    JOIN users u ON cr.user_id = u.id
                    JOIN orders o ON cr.order_id = o.id
                    LEFT JOIN customers c ON o.customer_id = c.id
                    LEFT JOIN formas_comissao fc ON cr.forma_comissao_id = fc.id
                    WHERE $where
                    ORDER BY cr.created_at DESC
                    LIMIT :limit OFFSET :offset";
        $dStmt = $this->conn->prepare($dataSql);
        foreach ($params as $k => $v) {
            $dStmt->bindValue($k, $v);
        }
        $dStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $dStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $dStmt->execute();

        return [
            'data'        => $dStmt->fetchAll(PDO::FETCH_ASSOC),
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => (int) ceil($total / $perPage),
            'summary'     => $summary,
        ];
    }

    /**
     * Retorna uma comissão registrada por ID.
     */
    public function getComissaoRegistrada(int $id): ?array
    {
        $sql = "SELECT cr.*, u.name as user_name, o.total_amount as order_total,
                       c.name as customer_name, fc.nome as forma_nome
                FROM comissoes_registradas cr
                JOIN users u ON cr.user_id = u.id
                JOIN orders o ON cr.order_id = o.id
                LEFT JOIN customers c ON o.customer_id = c.id
                LEFT JOIN formas_comissao fc ON cr.forma_comissao_id = fc.id
                WHERE cr.id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Atualiza status de uma comissão registrada.
     *
     * Fluxo de status:
     *   calculada → aprovada → aguardando_pagamento → paga
     *   (qualquer status pode ir para 'cancelada')
     */
    public function updateComissaoStatus(int $id, string $status, ?int $approvedBy = null): bool
    {
        $extra = '';
        $params = [':id' => $id, ':status' => $status];
        if ($status === 'aprovada' && $approvedBy) {
            $extra = ", approved_by = :ab, approved_at = NOW()";
            $params[':ab'] = $approvedBy;
        }
        if ($status === 'aguardando_pagamento' && $approvedBy) {
            // Quando muda para aguardando_pagamento, registrar quem aprovou (se ainda não tinha)
            $extra = ", approved_by = COALESCE(approved_by, :ab), approved_at = COALESCE(approved_at, NOW())";
            $params[':ab'] = $approvedBy;
        }
        if ($status === 'paga') {
            $extra .= ", paid_at = NOW()";
        }
        if ($status === 'cancelada') {
            $extra .= ", paid_at = NULL, approved_at = NULL, approved_by = NULL";
        }
        $sql = "UPDATE comissoes_registradas SET status = :status $extra WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Retorna lista de vendedores com comissões pendentes (para o modal de lote).
     *
     * @return array [['user_id' => int, 'user_name' => string, 'pendentes_aprovacao' => int,
     *                  'pendentes_pagamento' => int, 'total_valor' => float]]
     */
    public function getVendedoresComPendentes(): array
    {
        $sql = "SELECT cr.user_id, u.name as user_name,
                       SUM(CASE WHEN cr.status IN ('calculada') THEN 1 ELSE 0 END) as pendentes_aprovacao,
                       SUM(CASE WHEN cr.status IN ('aprovada','aguardando_pagamento') THEN 1 ELSE 0 END) as pendentes_pagamento,
                       COALESCE(SUM(CASE WHEN cr.status != 'cancelada' AND cr.status != 'paga' THEN cr.valor_comissao ELSE 0 END), 0) as total_valor
                FROM comissoes_registradas cr
                JOIN users u ON cr.user_id = u.id
                WHERE cr.status IN ('calculada','aprovada','aguardando_pagamento')
                GROUP BY cr.user_id
                ORDER BY u.name ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna comissões pendentes de um vendedor específico.
     *
     * @param int $userId
     * @param string|null $statusFilter  'aprovacao' | 'pagamento' | null (todos pendentes)
     * @return array
     */
    public function getComissoesPendentesPorVendedor(int $userId, ?string $statusFilter = null): array
    {
        $where = "cr.user_id = :uid";
        $params = [':uid' => $userId];

        if ($statusFilter === 'aprovacao') {
            $where .= " AND cr.status = 'calculada'";
        } elseif ($statusFilter === 'pagamento') {
            $where .= " AND cr.status IN ('aprovada','aguardando_pagamento')";
        } else {
            $where .= " AND cr.status IN ('calculada','aprovada','aguardando_pagamento')";
        }

        $sql = "SELECT cr.*, u.name as user_name, o.total_amount as order_total,
                       c.name as customer_name, fc.nome as forma_nome
                FROM comissoes_registradas cr
                JOIN users u ON cr.user_id = u.id
                JOIN orders o ON cr.order_id = o.id
                LEFT JOIN customers c ON o.customer_id = c.id
                LEFT JOIN formas_comissao fc ON cr.forma_comissao_id = fc.id
                WHERE {$where}
                ORDER BY cr.created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verifica se já existe comissão calculada para um pedido + usuário.
     */
    public function existeComissao(int $orderId, int $userId): bool
    {
        $sql = "SELECT COUNT(*) FROM comissoes_registradas 
                WHERE order_id = :oid AND user_id = :uid AND status != 'cancelada'";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':oid' => $orderId, ':uid' => $userId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    // ═══════════════════════════════════════════════════
    // CONFIGURAÇÕES DO MÓDULO
    // ═══════════════════════════════════════════════════

    /**
     * Retorna todas as configurações.
     */
    public function getConfig(): array
    {
        $stmt = $this->conn->prepare("SELECT * FROM comissao_config ORDER BY id ASC");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $config = [];
        foreach ($rows as $r) {
            $config[$r['config_key']] = $r['config_value'];
        }
        return $config;
    }

    /**
     * Retorna valor de uma configuração específica.
     */
    public function getConfigValue(string $key, $default = null)
    {
        $stmt = $this->conn->prepare("SELECT config_value FROM comissao_config WHERE config_key = :k");
        $stmt->execute([':k' => $key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    }

    /**
     * Salva configuração.
     */
    public function saveConfig(string $key, string $value): bool
    {
        $sql = "INSERT INTO comissao_config (config_key, config_value) VALUES (:k, :v)
                ON DUPLICATE KEY UPDATE config_value = :v2";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':k' => $key, ':v' => $value, ':v2' => $value]);
    }

    // ═══════════════════════════════════════════════════
    // DASHBOARD / RESUMO
    // ═══════════════════════════════════════════════════

    /**
     * Retorna resumo geral de comissões.
     */
    public function getDashboardSummary(?int $month = null, ?int $year = null): array
    {
        if (!$month) $month = (int) date('m');
        if (!$year) $year = (int) date('Y');

        $summary = [];

        // Total comissões do mês (todas)
        $q = "SELECT COALESCE(SUM(valor_comissao), 0) FROM comissoes_registradas
              WHERE MONTH(created_at) = :m AND YEAR(created_at) = :y AND status != 'cancelada'";
        $s = $this->conn->prepare($q);
        $s->execute([':m' => $month, ':y' => $year]);
        $summary['total_mes'] = (float) $s->fetchColumn();

        // Total pagas no mês
        $q = "SELECT COALESCE(SUM(valor_comissao), 0) FROM comissoes_registradas
              WHERE status = 'paga' AND MONTH(paid_at) = :m AND YEAR(paid_at) = :y";
        $s = $this->conn->prepare($q);
        $s->execute([':m' => $month, ':y' => $year]);
        $summary['total_pago_mes'] = (float) $s->fetchColumn();

        // Pendentes de aprovação
        $q = "SELECT COUNT(*), COALESCE(SUM(valor_comissao), 0) FROM comissoes_registradas WHERE status = 'calculada'";
        $s = $this->conn->prepare($q);
        $s->execute();
        $row = $s->fetch(PDO::FETCH_NUM);
        $summary['pendentes_count'] = (int) $row[0];
        $summary['pendentes_valor'] = (float) $row[1];

        // Aprovadas (aguardando pagamento)
        $q = "SELECT COUNT(*), COALESCE(SUM(valor_comissao), 0) FROM comissoes_registradas WHERE status IN ('aprovada','aguardando_pagamento')";
        $s = $this->conn->prepare($q);
        $s->execute();
        $row = $s->fetch(PDO::FETCH_NUM);
        $summary['aprovadas_count'] = (int) $row[0];
        $summary['aprovadas_valor'] = (float) $row[1];

        // Aguardando pagamento (sub-status)
        $q = "SELECT COUNT(*), COALESCE(SUM(valor_comissao), 0) FROM comissoes_registradas WHERE status = 'aguardando_pagamento'";
        $s = $this->conn->prepare($q);
        $s->execute();
        $row = $s->fetch(PDO::FETCH_NUM);
        $summary['aguardando_count'] = (int) $row[0];
        $summary['aguardando_valor'] = (float) $row[1];

        // Top 5 comissionados do mês
        $q = "SELECT u.name, u.id as user_id, 
                     SUM(cr.valor_comissao) as total,
                     COUNT(*) as qty
              FROM comissoes_registradas cr
              JOIN users u ON cr.user_id = u.id
              WHERE MONTH(cr.created_at) = :m AND YEAR(cr.created_at) = :y AND cr.status != 'cancelada'
              GROUP BY cr.user_id
              ORDER BY total DESC
              LIMIT 5";
        $s = $this->conn->prepare($q);
        $s->execute([':m' => $month, ':y' => $year]);
        $summary['top_comissionados'] = $s->fetchAll(PDO::FETCH_ASSOC);

        // Gráfico últimos 6 meses
        $summary['chart'] = $this->getChartData(6);

        return $summary;
    }

    /**
     * Dados para gráfico mensal.
     */
    public function getChartData(int $months = 6): array
    {
        $data = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $m = (int) date('m', strtotime("-{$i} months"));
            $y = (int) date('Y', strtotime("-{$i} months"));
            $q = "SELECT COALESCE(SUM(valor_comissao), 0) FROM comissoes_registradas
                  WHERE MONTH(created_at) = :m AND YEAR(created_at) = :y AND status != 'cancelada'";
            $s = $this->conn->prepare($q);
            $s->execute([':m' => $m, ':y' => $y]);
            $monthNames = ['','Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
            $data[] = [
                'label' => $monthNames[$m] . '/' . $y,
                'value' => (float) $s->fetchColumn(),
            ];
        }
        return $data;
    }

    // ═══════════════════════════════════════════════════
    // RESOLUÇÃO DE REGRAS (usado pelo CommissionEngine)
    // ═══════════════════════════════════════════════════

    /**
     * Busca a regra ativa para um usuário (prioridade 1: usuário).
     */
    public function getRegraUsuario(int $userId): ?array
    {
        $sql = "SELECT fc.* FROM usuario_forma_comissao uf
                JOIN formas_comissao fc ON uf.forma_comissao_id = fc.id
                WHERE uf.user_id = :uid AND uf.ativo = 1 AND fc.ativo = 1
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Busca a regra ativa para o grupo do usuário (prioridade 2: grupo).
     */
    public function getRegraGrupo(int $userId): ?array
    {
        $sql = "SELECT fc.* FROM users u
                JOIN grupo_formas_comissao gf ON u.group_id = gf.group_id
                JOIN formas_comissao fc ON gf.forma_comissao_id = fc.id
                WHERE u.id = :uid AND gf.ativo = 1 AND fc.ativo = 1
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Retorna lista de todos os usuários com suas regras resolvidas.
     */
    public function getUsuariosComRegras(): array
    {
        $sql = "SELECT u.id, u.name, u.email, u.group_id, ug.name as group_name,
                       ufc.forma_comissao_id as user_forma_id,
                       fc_u.nome as user_forma_nome,
                       gfc.forma_comissao_id as group_forma_id,
                       fc_g.nome as group_forma_nome
                FROM users u
                LEFT JOIN user_groups ug ON u.group_id = ug.id
                LEFT JOIN usuario_forma_comissao ufc ON ufc.user_id = u.id AND ufc.ativo = 1
                LEFT JOIN formas_comissao fc_u ON ufc.forma_comissao_id = fc_u.id AND fc_u.ativo = 1
                LEFT JOIN grupo_formas_comissao gfc ON gfc.group_id = u.group_id AND gfc.ativo = 1
                LEFT JOIN formas_comissao fc_g ON gfc.forma_comissao_id = fc_g.id AND fc_g.ativo = 1
                ORDER BY u.name ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
