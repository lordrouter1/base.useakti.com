<?php
namespace Akti\Models;

use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use PDO;

/**
 * Model: PortalAccess
 * Gerencia autenticação e acesso de clientes ao Portal.
 * Entradas: Conexão PDO ($db), parâmetros das funções.
 * Saídas: Arrays de dados, booleanos, IDs inseridos.
 * Eventos: 'portal.customer.logged_in', 'portal.access.created', 'portal.access.locked'
 * Não deve conter HTML, echo, print ou acesso direto a $_POST/$_GET.
 */
class PortalAccess
{
    private $conn;
    private $table = 'customer_portal_access';

    /** Máximo de tentativas antes de bloquear */
    const MAX_FAILED_ATTEMPTS = 5;

    /** Duração do bloqueio em minutos */
    const LOCKOUT_MINUTES = 15;

    /**
     * Construtor do model
     * @param PDO $db Conexão PDO
     */
    public function __construct($db)
    {
        $this->conn = $db;
    }

    // ══════════════════════════════════════════════
    // CRUD
    // ══════════════════════════════════════════════

    /**
     * Cria um acesso ao portal para um cliente
     * @param array $data [customer_id, email, password (plain), lang]
     * @return int ID do acesso criado
     */
    public function create(array $data): int
    {
        $passwordHash = !empty($data['password']) ? password_hash($data['password'], PASSWORD_BCRYPT) : null;

        $query = "INSERT INTO {$this->table} (customer_id, email, password_hash, is_active, lang, created_at)
                  VALUES (:customer_id, :email, :password_hash, 1, :lang, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':customer_id'  => $data['customer_id'],
            ':email'        => $data['email'],
            ':password_hash' => $passwordHash,
            ':lang'         => $data['lang'] ?? 'pt-br',
        ]);

        $newId = (int) $this->conn->lastInsertId();

        EventDispatcher::dispatch('portal.access.created', new Event('portal.access.created', [
            'id'          => $newId,
            'customer_id' => $data['customer_id'],
            'email'       => $data['email'],
        ]));

        return $newId;
    }

    /**
     * Retorna acesso pelo e-mail
     * @param string $email
     * @return array|null
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Retorna acesso pelo customer_id
     * @param int $customerId
     * @return array|null
     */
    public function findByCustomerId(int $customerId): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE customer_id = :cid LIMIT 1");
        $stmt->execute([':cid' => $customerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Retorna acesso pelo ID
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Retorna todos os acessos com dados do cliente
     * @return array
     */
    public function readAll(): array
    {
        $query = "SELECT pa.*, c.name AS customer_name, c.phone AS customer_phone
                  FROM {$this->table} pa
                  JOIN customers c ON c.id = pa.customer_id
                  ORDER BY pa.created_at DESC";
        $stmt = $this->conn->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Atualiza dados de acesso
     * @param int $id
     * @param array $data Campos a atualizar
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];

        if (isset($data['email'])) {
            $fields[] = 'email = :email';
            $params[':email'] = $data['email'];
        }
        if (isset($data['password']) && $data['password'] !== '') {
            $fields[] = 'password_hash = :password_hash';
            $params[':password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        if (isset($data['is_active'])) {
            $fields[] = 'is_active = :is_active';
            $params[':is_active'] = (int) $data['is_active'];
        }
        if (isset($data['lang'])) {
            $fields[] = 'lang = :lang';
            $params[':lang'] = $data['lang'];
        }

        if (empty($fields)) {
            return false;
        }

        $query = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($params);
    }

    /**
     * Remove acesso ao portal
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // ══════════════════════════════════════════════
    // AUTENTICAÇÃO
    // ══════════════════════════════════════════════

    /**
     * Verifica se a conta está bloqueada por tentativas falhas
     * @param array $access Registro do portal_access
     * @return bool true se bloqueada
     */
    public function isLocked(array $access): bool
    {
        if ($access['failed_attempts'] < self::MAX_FAILED_ATTEMPTS) {
            return false;
        }
        if (empty($access['locked_until'])) {
            return false;
        }
        return strtotime($access['locked_until']) > time();
    }

    /**
     * Valida senha do cliente
     * @param string $password Senha em texto plano
     * @param string $hash Hash armazenado
     * @return bool
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Registra tentativa de login falha
     * @param int $accessId
     * @return void
     */
    public function registerFailedAttempt(int $accessId): void
    {
        $stmt = $this->conn->prepare(
            "UPDATE {$this->table}
             SET failed_attempts = failed_attempts + 1,
                 locked_until = IF(failed_attempts + 1 >= :max, DATE_ADD(NOW(), INTERVAL :lock_min MINUTE), locked_until)
             WHERE id = :id"
        );
        $stmt->execute([
            ':max'      => self::MAX_FAILED_ATTEMPTS,
            ':lock_min' => self::LOCKOUT_MINUTES,
            ':id'       => $accessId,
        ]);

        // Verificar se agora está bloqueado para disparar evento
        $access = $this->findById($accessId);
        if ($access && $access['failed_attempts'] >= self::MAX_FAILED_ATTEMPTS) {
            EventDispatcher::dispatch('portal.access.locked', new Event('portal.access.locked', [
                'access_id'   => $accessId,
                'customer_id' => $access['customer_id'],
                'email'       => $access['email'],
            ]));
        }
    }

    /**
     * Registra login bem-sucedido (zera tentativas, atualiza last_login)
     * @param int $accessId
     * @param string $ip
     * @return void
     */
    public function registerSuccessfulLogin(int $accessId, string $ip): void
    {
        $stmt = $this->conn->prepare(
            "UPDATE {$this->table}
             SET failed_attempts = 0,
                 locked_until = NULL,
                 last_login_at = NOW(),
                 last_login_ip = :ip
             WHERE id = :id"
        );
        $stmt->execute([':ip' => $ip, ':id' => $accessId]);
    }

    // ══════════════════════════════════════════════
    // MAGIC LINK
    // ══════════════════════════════════════════════

    /**
     * Gera e armazena um token de link mágico.
     * Lê expiryHours da config 'magic_link_expiry_hours' se não informado.
     * @param int $accessId
     * @param int|null $expiryHours Horas de validade (null = ler da config, fallback 24)
     * @return string Token gerado (128 chars hex)
     */
    public function generateMagicToken(int $accessId, ?int $expiryHours = null): string
    {
        if ($expiryHours === null) {
            $expiryHours = (int) $this->getConfig('magic_link_expiry_hours', '24');
            if ($expiryHours < 1) {
                $expiryHours = 24;
            }
        }

        $token = bin2hex(random_bytes(64));
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiryHours} hours"));

        $stmt = $this->conn->prepare(
            "UPDATE {$this->table}
             SET magic_token = :token, magic_token_expires_at = :expires
             WHERE id = :id"
        );
        $stmt->execute([
            ':token'   => $token,
            ':expires' => $expiresAt,
            ':id'      => $accessId,
        ]);

        return $token;
    }

    /**
     * Valida um token de link mágico
     * @param string $token
     * @return array|null Registro do acesso se válido, null se inválido/expirado
     */
    public function validateMagicToken(string $token): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM {$this->table}
             WHERE magic_token = :token
               AND magic_token_expires_at > NOW()
               AND is_active = 1
             LIMIT 1"
        );
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Invalida o token mágico após uso (uso único)
     * @param int $accessId
     * @return void
     */
    public function invalidateMagicToken(int $accessId): void
    {
        $stmt = $this->conn->prepare(
            "UPDATE {$this->table}
             SET magic_token = NULL, magic_token_expires_at = NULL
             WHERE id = :id"
        );
        $stmt->execute([':id' => $accessId]);
    }

    // ══════════════════════════════════════════════
    // RESET DE SENHA (Esqueci minha senha)
    // ══════════════════════════════════════════════

    /**
     * Gera e armazena um token de reset de senha
     * @param int $accessId
     * @param int $expiryHours Horas de validade (padrão 1)
     * @return string Token gerado (128 chars hex)
     */
    public function generateResetToken(int $accessId, int $expiryHours = 1): string
    {
        $token = bin2hex(random_bytes(64));
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiryHours} hours"));

        $stmt = $this->conn->prepare(
            "UPDATE {$this->table}
             SET reset_token = :token, reset_token_expires_at = :expires
             WHERE id = :id"
        );
        $stmt->execute([
            ':token'   => $token,
            ':expires' => $expiresAt,
            ':id'      => $accessId,
        ]);

        return $token;
    }

    /**
     * Valida um token de reset de senha
     * @param string $token
     * @return array|null Registro do acesso se válido, null se inválido/expirado
     */
    public function validateResetToken(string $token): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM {$this->table}
             WHERE reset_token = :token
               AND reset_token_expires_at > NOW()
               AND is_active = 1
             LIMIT 1"
        );
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Invalida o token de reset após uso
     * @param int $accessId
     * @return void
     */
    public function invalidateResetToken(int $accessId): void
    {
        $stmt = $this->conn->prepare(
            "UPDATE {$this->table}
             SET reset_token = NULL, reset_token_expires_at = NULL
             WHERE id = :id"
        );
        $stmt->execute([':id' => $accessId]);
    }

    /**
     * Reseta a senha do cliente (usado no fluxo de recuperação)
     * @param int $accessId
     * @param string $newPassword Senha em texto plano
     * @return bool
     */
    public function resetPassword(int $accessId, string $newPassword): bool
    {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $this->conn->prepare(
            "UPDATE {$this->table}
             SET password_hash = :hash, failed_attempts = 0, locked_until = NULL
             WHERE id = :id"
        );
        return $stmt->execute([':hash' => $hash, ':id' => $accessId]);
    }

    // ══════════════════════════════════════════════
    // AUTO-REGISTRO
    // ══════════════════════════════════════════════

    /**
     * Verifica se o e-mail já está cadastrado no portal
     * @param string $email
     * @return bool
     */
    public function emailExists(string $email): bool
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM {$this->table} WHERE email = :email");
        $stmt->execute([':email' => $email]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Verifica se um customer_id já tem acesso ao portal
     * @param int $customerId
     * @return bool
     */
    public function customerHasAccess(int $customerId): bool
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM {$this->table} WHERE customer_id = :cid");
        $stmt->execute([':cid' => $customerId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    // ══════════════════════════════════════════════
    // CONFIGURAÇÕES DO PORTAL
    // ══════════════════════════════════════════════

    /**
     * Retorna uma configuração do portal
     * @param string $key
     * @param string $default
     * @return string
     */
    public function getConfig(string $key, string $default = ''): string
    {
        $stmt = $this->conn->prepare("SELECT config_value FROM customer_portal_config WHERE config_key = :key");
        $stmt->execute([':key' => $key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (string) $val : $default;
    }

    /**
     * Retorna todas as configurações do portal
     * @return array Associativo [key => value]
     */
    public function getAllConfig(): array
    {
        $stmt = $this->conn->query("SELECT config_key, config_value FROM customer_portal_config");
        $config = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $config[$row['config_key']] = $row['config_value'];
        }
        return $config;
    }

    /**
     * Atualiza uma configuração do portal
     * @param string $key
     * @param string $value
     * @return bool
     */
    public function setConfig(string $key, string $value): bool
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO customer_portal_config (config_key, config_value)
             VALUES (:key, :value)
             ON DUPLICATE KEY UPDATE config_value = :value2"
        );
        return $stmt->execute([':key' => $key, ':value' => $value, ':value2' => $value]);
    }

    // ══════════════════════════════════════════════
    // ESTATÍSTICAS (para dashboard)
    // ══════════════════════════════════════════════

    /** @var bool|null Cache: coluna customer_approval_status existe na tabela orders */
    private ?bool $hasApprovalColumn = null;

    /**
     * Verifica se a coluna customer_approval_status existe em orders.
     * Resultado é cacheado por instância para evitar queries repetidas.
     */
    private function hasApprovalColumn(): bool
    {
        if ($this->hasApprovalColumn === null) {
            $stmt = $this->conn->prepare(
                "SELECT COUNT(*) FROM information_schema.columns
                 WHERE table_schema = DATABASE()
                   AND table_name = 'orders'
                   AND column_name = 'customer_approval_status'"
            );
            $stmt->execute();
            $this->hasApprovalColumn = (int) $stmt->fetchColumn() > 0;
        }
        return $this->hasApprovalColumn;
    }

    /**
     * Retorna contadores para o dashboard do cliente
     * @param int $customerId
     * @return array [active_orders, pending_approval, open_installments, total_open_amount]
     */
    public function getDashboardStats(int $customerId): array
    {
        // Pedidos ativos (não concluídos/cancelados)
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) FROM orders
             WHERE customer_id = :cid AND status NOT IN ('concluido','cancelado')"
        );
        $stmt->execute([':cid' => $customerId]);
        $activeOrders = (int) $stmt->fetchColumn();

        // Pedidos aguardando aprovação (só consulta se a coluna existir)
        $pendingApproval = 0;
        if ($this->hasApprovalColumn()) {
            $stmt = $this->conn->prepare(
                "SELECT COUNT(*) FROM orders
                 WHERE customer_id = :cid AND customer_approval_status = 'pendente'"
            );
            $stmt->execute([':cid' => $customerId]);
            $pendingApproval = (int) $stmt->fetchColumn();
        }

        // Parcelas em aberto
        $openInstallments = 0;
        $totalOpenAmount  = 0.0;
        try {
            $stmt = $this->conn->prepare(
                "SELECT COUNT(*), COALESCE(SUM(i.amount), 0)
                 FROM order_installments i
                 JOIN orders o ON o.id = i.order_id
                 WHERE o.customer_id = :cid AND i.status IN ('pendente','atrasada')"
            );
            $stmt->execute([':cid' => $customerId]);
            $row = $stmt->fetch(PDO::FETCH_NUM);
            $openInstallments = (int) ($row[0] ?? 0);
            $totalOpenAmount  = (float) ($row[1] ?? 0);
        } catch (\PDOException $e) {
            // Tabela order_installments pode não existir ainda
        }

        return [
            'active_orders'      => $activeOrders,
            'pending_approval'   => $pendingApproval,
            'open_installments'  => $openInstallments,
            'total_open_amount'  => $totalOpenAmount,
        ];
    }

    /**
     * Retorna pedidos recentes do cliente
     * @param int $customerId
     * @param int $limit
     * @return array
     */
    public function getRecentOrders(int $customerId, int $limit = 5): array
    {
        $approvalCol = $this->hasApprovalColumn()
            ? ', customer_approval_status'
            : ", NULL AS customer_approval_status";

        $stmt = $this->conn->prepare(
            "SELECT id, status, pipeline_stage, total_amount AS total, created_at{$approvalCol}, tracking_code
             FROM orders
             WHERE customer_id = :cid
             ORDER BY created_at DESC
             LIMIT :lim"
        );
        $stmt->bindValue(':cid', $customerId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna notificações recentes (parcelas próximas do vencimento, mudanças de status)
     * @param int $customerId
     * @param int $limit
     * @return array
     */
    public function getRecentNotifications(int $customerId, int $limit = 5): array
    {
        $notifications = [];

        // Parcelas vencendo em até 7 dias
        try {
            $stmt = $this->conn->prepare(
                "SELECT i.id, i.amount, i.due_date, i.installment_number, o.id AS order_id
                 FROM order_installments i
                 JOIN orders o ON o.id = i.order_id
                 WHERE o.customer_id = :cid
                   AND i.status = 'pendente'
                   AND i.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                 ORDER BY i.due_date ASC
                 LIMIT :lim"
            );
            $stmt->bindValue(':cid', $customerId, PDO::PARAM_INT);
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $installments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($installments as $inst) {
                $daysUntil = (int) ((strtotime($inst['due_date']) - time()) / 86400);
                $notifications[] = [
                    'type'    => 'installment_due',
                    'icon'    => 'fas fa-wallet',
                    'color'   => $daysUntil <= 2 ? 'danger' : 'warning',
                    'message' => "Parcela {$inst['installment_number']} do pedido #{$inst['order_id']} vence em {$daysUntil} dia(s)",
                    'date'    => $inst['due_date'],
                    'link'    => "?page=portal&action=orderDetail&id={$inst['order_id']}",
                ];
            }
        } catch (\PDOException $e) {
            // Tabela order_installments pode não existir ainda
        }

        // Pedidos aguardando aprovação (só consulta se a coluna existir)
        if ($this->hasApprovalColumn()) {
            $stmt = $this->conn->prepare(
                "SELECT id, total_amount AS total, created_at
                 FROM orders
                 WHERE customer_id = :cid AND customer_approval_status = 'pendente'
                 ORDER BY created_at DESC
                 LIMIT :lim"
            );
            $stmt->bindValue(':cid', $customerId, PDO::PARAM_INT);
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $pending = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($pending as $order) {
                $notifications[] = [
                    'type'    => 'approval_pending',
                    'icon'    => 'fas fa-clipboard-check',
                    'color'   => 'info',
                    'message' => "Orçamento #{$order['id']} aguarda sua aprovação",
                    'date'    => $order['created_at'],
                    'link'    => "?page=portal&action=orderDetail&id={$order['id']}",
                ];
            }
        }

        // Ordenar por data desc
        usort($notifications, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return array_slice($notifications, 0, $limit);
    }

    // ══════════════════════════════════════════════
    // PEDIDOS DO PORTAL (Fase 2)
    // ══════════════════════════════════════════════

    /**
     * Pipeline stages na ordem correta do fluxo.
     */
    private const PIPELINE_STAGES = [
        'contato', 'orcamento', 'venda', 'producao',
        'preparacao', 'envio', 'financeiro', 'concluido',
    ];

    /**
     * Retorna pedidos do cliente com filtro e paginação.
     *
     * @param int    $customerId
     * @param string $filter  'all', 'open', 'approval', 'done'
     * @param int    $limit
     * @param int    $offset
     * @return array
     */
    public function getOrdersByCustomer(int $customerId, string $filter = 'all', int $limit = 10, int $offset = 0): array
    {
        $where = "o.customer_id = :cid";
        $params = [':cid' => $customerId];

        switch ($filter) {
            case 'open':
                $where .= " AND o.status NOT IN ('concluido','cancelado')";
                break;
            case 'approval':
                if ($this->hasApprovalColumn()) {
                    $where .= " AND o.customer_approval_status = 'pendente'";
                } else {
                    // Se a coluna não existe, nenhum pedido é "approval"
                    $where .= " AND 1 = 0";
                }
                break;
            case 'done':
                $where .= " AND o.status IN ('concluido')";
                break;
        }

        $approvalCol = $this->hasApprovalColumn()
            ? ', o.customer_approval_status'
            : ", NULL AS customer_approval_status";

        $sql = "SELECT o.id, o.status, o.pipeline_stage, o.total_amount, o.discount,
                       o.created_at, o.tracking_code, o.payment_status{$approvalCol},
                       (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS items_count
                FROM orders o
                WHERE {$where}
                ORDER BY o.created_at DESC
                LIMIT :lim OFFSET :off";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':cid', $customerId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Conta total de pedidos do cliente para paginação.
     *
     * @param int    $customerId
     * @param string $filter
     * @return int
     */
    public function countOrdersByCustomer(int $customerId, string $filter = 'all'): int
    {
        $where = "customer_id = :cid";

        switch ($filter) {
            case 'open':
                $where .= " AND status NOT IN ('concluido','cancelado')";
                break;
            case 'approval':
                if ($this->hasApprovalColumn()) {
                    $where .= " AND customer_approval_status = 'pendente'";
                } else {
                    return 0;
                }
                break;
            case 'done':
                $where .= " AND status IN ('concluido')";
                break;
        }

        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM orders WHERE {$where}");
        $stmt->bindValue(':cid', $customerId, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Retorna os dados completos de um pedido (com verificação de propriedade).
     *
     * @param int $orderId
     * @param int $customerId
     * @return array|null
     */
    public function getOrderDetail(int $orderId, int $customerId): ?array
    {
        $approvalCols = $this->hasApprovalColumn()
            ? ', customer_approval_status, customer_approval_at, customer_approval_ip, customer_approval_notes'
            : ", NULL AS customer_approval_status, NULL AS customer_approval_at, NULL AS customer_approval_ip, NULL AS customer_approval_notes";

        $sql = "SELECT o.*, c.name AS customer_name, c.email AS customer_email{$approvalCols}
                FROM orders o
                JOIN customers c ON c.id = o.customer_id
                WHERE o.id = :oid AND o.customer_id = :cid
                LIMIT 1";

        // Se as colunas de approval existem, elas já estão em o.* mas adicionamos explicitamente para clareza
        // Caso o.* já inclua, o MySQL lida sem erro com duplicação em SELECT
        // Solução: usar apenas o.* quando a coluna existe
        if ($this->hasApprovalColumn()) {
            $sql = "SELECT o.*, c.name AS customer_name, c.email AS customer_email
                    FROM orders o
                    JOIN customers c ON c.id = o.customer_id
                    WHERE o.id = :oid AND o.customer_id = :cid
                    LIMIT 1";
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':oid', $orderId, PDO::PARAM_INT);
        $stmt->bindValue(':cid', $customerId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Retorna os itens de um pedido com dados do produto.
     *
     * @param int $orderId
     * @return array
     */
    public function getOrderItems(int $orderId): array
    {
        $sql = "SELECT oi.id, oi.product_id, oi.quantity, oi.unit_price, oi.subtotal,
                       oi.discount AS item_discount, oi.grade_description,
                       p.name AS product_name, p.sku
                FROM order_items oi
                LEFT JOIN products p ON p.id = oi.product_id
                WHERE oi.order_id = :oid
                ORDER BY oi.id ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':oid', $orderId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna parcelas de um pedido (resiliente se tabela não existir).
     *
     * @param int $orderId
     * @return array
     */
    public function getOrderInstallments(int $orderId): array
    {
        try {
            $sql = "SELECT id, installment_number, amount, due_date, status, paid_date, payment_method
                    FROM order_installments
                    WHERE order_id = :oid
                    ORDER BY installment_number ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':oid', $orderId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Retorna custos extras de um pedido.
     *
     * @param int $orderId
     * @return array
     */
    public function getOrderExtraCosts(int $orderId): array
    {
        try {
            $sql = "SELECT id, description, amount FROM order_extra_costs WHERE order_id = :oid ORDER BY id ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':oid', $orderId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Monta timeline do pipeline para um pedido, baseado no estágio atual e histórico.
     *
     * @param array $order Dados do pedido (precisa de 'pipeline_stage' e 'status')
     * @return array Lista de etapas com status (completed / current / pending / cancelled)
     */
    public function getOrderTimeline(array $order): array
    {
        $currentStage = $order['pipeline_stage'] ?? 'contato';
        $isCancelled  = ($order['status'] ?? '') === 'cancelado';

        // Buscar histórico de movimentação do pipeline
        $history = [];
        try {
            $stmt = $this->conn->prepare(
                "SELECT to_stage, created_at FROM pipeline_history
                 WHERE order_id = :oid ORDER BY created_at ASC"
            );
            $stmt->bindValue(':oid', (int) $order['id'], PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $history[$row['to_stage']] = $row['created_at'];
            }
        } catch (\PDOException $e) {
            // pipeline_history pode não existir
        }

        $currentIndex = array_search($currentStage, self::PIPELINE_STAGES);
        if ($currentIndex === false) {
            $currentIndex = 0;
        }

        $timeline = [];
        foreach (self::PIPELINE_STAGES as $index => $stage) {
            $stepStatus = 'pending';
            if ($isCancelled) {
                $stepStatus = ($index <= $currentIndex) ? 'completed' : 'cancelled';
                if ($index === $currentIndex) {
                    $stepStatus = 'cancelled';
                }
            } elseif ($index < $currentIndex) {
                $stepStatus = 'completed';
            } elseif ($index === $currentIndex) {
                $stepStatus = 'current';
            }

            $timeline[] = [
                'stage'      => $stage,
                'status'     => $stepStatus,
                'date'       => $history[$stage] ?? null,
                'is_current' => ($index === $currentIndex && !$isCancelled),
            ];
        }

        return $timeline;
    }

    /**
     * Atualiza o status de aprovação de um pedido pelo cliente.
     * Segurança: valida que o pedido pertence ao customer_id.
     *
     * @param int         $orderId
     * @param int         $customerId
     * @param string      $status  'aprovado' ou 'recusado'
     * @param string      $ip
     * @param string|null $notes
     * @return bool
     */
    public function updateApprovalStatus(int $orderId, int $customerId, string $status, string $ip, ?string $notes): bool
    {
        if (!$this->hasApprovalColumn()) {
            return false;
        }

        $stmt = $this->conn->prepare(
            "UPDATE orders
             SET customer_approval_status = :status,
                 customer_approval_at = NOW(),
                 customer_approval_ip = :ip,
                 customer_approval_notes = :notes
             WHERE id = :oid AND customer_id = :cid AND customer_approval_status = 'pendente'"
        );
        $result = $stmt->execute([
            ':status' => $status,
            ':ip'     => $ip,
            ':notes'  => $notes,
            ':oid'    => $orderId,
            ':cid'    => $customerId,
        ]);

        // Sincronizar: se aprovado pelo portal, marcar também quote_confirmed_at
        if ($result && $stmt->rowCount() > 0 && $status === 'aprovado') {
            $sync = $this->conn->prepare(
                "UPDATE orders SET quote_confirmed_at = NOW(), quote_confirmed_ip = :ip WHERE id = :oid"
            );
            $sync->execute([':ip' => $ip, ':oid' => $orderId]);
        }

        return $result;
    }

    /**
     * Cancela a aprovação/rejeição do pedido, revertendo o status para 'pendente'.
     * Permitido apenas se o status atual for 'aprovado' ou 'recusado'.
     *
     * @param int    $orderId
     * @param int    $customerId
     * @param string $ip
     * @return bool
     */
    public function cancelApprovalStatus(int $orderId, int $customerId, string $ip): bool
    {
        if (!$this->hasApprovalColumn()) {
            return false;
        }

        $stmt = $this->conn->prepare(
            "UPDATE orders
             SET customer_approval_status = 'pendente',
                 customer_approval_at = NOW(),
                 customer_approval_ip = :ip,
                 customer_approval_notes = NULL
             WHERE id = :oid AND customer_id = :cid
               AND customer_approval_status IN ('aprovado', 'recusado')"
        );
        $result = $stmt->execute([
            ':ip'  => $ip,
            ':oid' => $orderId,
            ':cid' => $customerId,
        ]);

        // Sincronizar: limpar também quote_confirmed_at
        if ($result && $stmt->rowCount() > 0) {
            $sync = $this->conn->prepare(
                "UPDATE orders SET quote_confirmed_at = NULL, quote_confirmed_ip = NULL WHERE id = :oid"
            );
            $sync->execute([':oid' => $orderId]);
        }

        return $result;
    }

    // ══════════════════════════════════════════════
    // FINANCEIRO — Parcelas do Cliente (Fase 4)
    // ══════════════════════════════════════════════

    /**
     * Auto-marca parcelas pendentes como "atrasado" quando due_date < hoje.
     * Chamado automaticamente pelo dashboard para manter status financeiro atualizado.
     *
     * @param int $customerId
     * @return int Número de parcelas atualizadas
     */
    public function markOverdueInstallments(int $customerId): int
    {
        try {
            $sql = "UPDATE order_installments i
                    INNER JOIN orders o ON o.id = i.order_id
                    SET i.status = 'atrasado'
                    WHERE o.customer_id = :cid
                      AND i.status = 'pendente'
                      AND i.due_date < CURDATE()";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':cid' => $customerId]);
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            return 0;
        }
    }

    /**
     * Retorna parcelas do cliente com filtro de status.
     *
     * @param int    $customerId
     * @param string $filter  'all', 'open', 'paid'
     * @param int    $limit
     * @param int    $offset
     * @return array
     */
    public function getInstallmentsByCustomer(int $customerId, string $filter = 'all', int $limit = 20, int $offset = 0): array
    {
        try {
            $where = "o.customer_id = :cid";
            $params = [':cid' => $customerId];

            if ($filter === 'open') {
                $where .= " AND i.status IN ('pendente','atrasado')";
            } elseif ($filter === 'paid') {
                $where .= " AND i.status = 'pago'";
            }

            $sql = "SELECT i.*, o.id AS order_id, o.total_amount AS order_total,
                           o.pipeline_stage, o.created_at AS order_date
                    FROM order_installments i
                    INNER JOIN orders o ON o.id = i.order_id
                    WHERE {$where}
                    ORDER BY
                        CASE WHEN i.status IN ('pendente','atrasado') THEN 0 ELSE 1 END,
                        i.due_date ASC
                    LIMIT :lim OFFSET :off";
            $stmt = $this->conn->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Conta parcelas por filtro.
     *
     * @param int    $customerId
     * @param string $filter
     * @return int
     */
    public function countInstallmentsByCustomer(int $customerId, string $filter = 'all'): int
    {
        try {
            $where = "o.customer_id = :cid";
            $params = [':cid' => $customerId];

            if ($filter === 'open') {
                $where .= " AND i.status IN ('pendente','atrasado')";
            } elseif ($filter === 'paid') {
                $where .= " AND i.status = 'pago'";
            }

            $sql = "SELECT COUNT(*) FROM order_installments i
                    INNER JOIN orders o ON o.id = i.order_id
                    WHERE {$where}";
            $stmt = $this->conn->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->execute();
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            return 0;
        }
    }

    /**
     * Retorna resumo financeiro do cliente (total em aberto, total pago).
     *
     * @param int $customerId
     * @return array [total_open, total_paid, count_open, count_paid, count_overdue]
     */
    public function getFinancialSummary(int $customerId): array
    {
        $result = [
            'total_open'    => 0.0,
            'total_paid'    => 0.0,
            'count_open'    => 0,
            'count_paid'    => 0,
            'count_overdue' => 0,
        ];

        try {
            $sql = "SELECT
                        SUM(CASE WHEN i.status IN ('pendente','atrasado') THEN i.amount ELSE 0 END) AS total_open,
                        SUM(CASE WHEN i.status = 'pago' THEN i.amount ELSE 0 END) AS total_paid,
                        SUM(CASE WHEN i.status IN ('pendente','atrasado') THEN 1 ELSE 0 END) AS count_open,
                        SUM(CASE WHEN i.status = 'pago' THEN 1 ELSE 0 END) AS count_paid,
                        SUM(CASE WHEN i.status = 'atrasado' THEN 1 ELSE 0 END) AS count_overdue
                    FROM order_installments i
                    INNER JOIN orders o ON o.id = i.order_id
                    WHERE o.customer_id = :cid";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':cid' => $customerId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $result['total_open']    = (float) ($row['total_open'] ?? 0);
                $result['total_paid']    = (float) ($row['total_paid'] ?? 0);
                $result['count_open']    = (int) ($row['count_open'] ?? 0);
                $result['count_paid']    = (int) ($row['count_paid'] ?? 0);
                $result['count_overdue'] = (int) ($row['count_overdue'] ?? 0);
            }
        } catch (\PDOException $e) {
            // Tabela pode não existir
        }

        return $result;
    }

    /**
     * Retorna uma parcela específica com dados do pedido (filtrada por customer_id).
     *
     * @param int $installmentId
     * @param int $customerId
     * @return array|null
     */
    public function getInstallmentDetail(int $installmentId, int $customerId): ?array
    {
        try {
            $sql = "SELECT i.*, o.id AS order_id, o.total_amount AS order_total,
                           o.pipeline_stage, o.created_at AS order_date,
                           o.payment_link, o.payment_link_generated_at
                    FROM order_installments i
                    INNER JOIN orders o ON o.id = i.order_id
                    WHERE i.id = :iid AND o.customer_id = :cid
                    LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':iid' => $installmentId, ':cid' => $customerId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\PDOException $e) {
            return null;
        }
    }

    // ══════════════════════════════════════════════
    // RASTREAMENTO (Fase 4)
    // ══════════════════════════════════════════════

    /**
     * Retorna pedidos com informação de tracking do cliente.
     *
     * @param int $customerId
     * @return array
     */
    public function getTrackingOrders(int $customerId): array
    {
        try {
            $sql = "SELECT id, pipeline_stage, status, tracking_code, tracking_carrier,
                           tracking_url, shipping_address, total_amount, created_at
                    FROM orders
                    WHERE customer_id = :cid
                      AND pipeline_stage IN ('envio','concluido')
                    ORDER BY created_at DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':cid' => $customerId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Retorna detalhes de tracking de um pedido (com verificação de customer_id).
     *
     * @param int $orderId
     * @param int $customerId
     * @return array|null
     */
    public function getTrackingDetail(int $orderId, int $customerId): ?array
    {
        try {
            $sql = "SELECT id, pipeline_stage, status, tracking_code, tracking_carrier,
                           tracking_url, shipping_address, total_amount, created_at,
                           scheduled_date
                    FROM orders
                    WHERE id = :oid AND customer_id = :cid
                    LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':oid' => $orderId, ':cid' => $customerId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\PDOException $e) {
            return null;
        }
    }

    // ══════════════════════════════════════════════
    // DOCUMENTOS (Fase 5)
    // ══════════════════════════════════════════════

    /**
     * Retorna documentos (NF-e) vinculados aos pedidos do cliente.
     *
     * @param int $customerId
     * @return array
     */
    public function getDocumentsByCustomer(int $customerId): array
    {
        try {
            $sql = "SELECT n.id, n.order_id, n.number, n.series, n.status, n.xml_path,
                           n.pdf_path, n.created_at, o.total_amount AS order_total
                    FROM nfe_documents n
                    INNER JOIN orders o ON o.id = n.order_id
                    WHERE o.customer_id = :cid
                    ORDER BY n.created_at DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':cid' => $customerId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Retorna um documento específico vinculado a um pedido do cliente.
     *
     * @param int $documentId
     * @param int $customerId
     * @return array|null
     */
    public function getDocumentDetail(int $documentId, int $customerId): ?array
    {
        try {
            $sql = "SELECT n.*, o.total_amount AS order_total, o.pipeline_stage
                    FROM nfe_documents n
                    INNER JOIN orders o ON o.id = n.order_id
                    WHERE n.id = :did AND o.customer_id = :cid
                    LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':did' => $documentId, ':cid' => $customerId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\PDOException $e) {
            return null;
        }
    }

    // ══════════════════════════════════════════════
    // PRODUTOS — Busca para Novo Pedido (Fase 3)
    // ══════════════════════════════════════════════

    /**
     * Retorna produtos disponíveis com busca e paginação (para portal).
     *
     * @param string|null $search  Busca por nome
     * @param int|null    $categoryId  Filtrar por categoria
     * @param int         $limit
     * @param int         $offset
     * @return array ['data' => [...], 'total' => int]
     */
    public function getAvailableProducts(?string $search = null, ?int $categoryId = null, int $limit = 20, int $offset = 0): array
    {
        $where = ["p.stock_quantity > 0 OR p.stock_quantity IS NULL"];
        $params = [];

        if (!empty($search)) {
            $where[] = "(p.name LIKE :search OR p.description LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        if ($categoryId) {
            $where[] = "p.category_id = :cat_id";
            $params[':cat_id'] = $categoryId;
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        // Count
        $countSql = "SELECT COUNT(*) FROM products p {$whereClause}";
        $countStmt = $this->conn->prepare($countSql);
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        // Data
        $sql = "SELECT p.id, p.name, p.description, p.price, p.stock_quantity,
                       c.name AS category_name,
                       (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_main = 1 LIMIT 1) AS main_image_path
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                {$whereClause}
                ORDER BY p.name ASC
                LIMIT :lim OFFSET :off";
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ['data' => $data, 'total' => $total];
    }

    /**
     * Retorna categorias com contagem de produtos.
     *
     * @return array
     */
    public function getCategories(): array
    {
        $sql = "SELECT c.id, c.name, COUNT(p.id) AS product_count
                FROM categories c
                LEFT JOIN products p ON p.category_id = c.id
                GROUP BY c.id, c.name
                HAVING product_count > 0
                ORDER BY c.name ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna um produto específico por ID.
     *
     * @param int $productId
     * @return array|null
     */
    public function getProductById(int $productId): ?array
    {
        $sql = "SELECT p.id, p.name, p.description, p.price, p.stock_quantity,
                       c.name AS category_name,
                       (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_main = 1 LIMIT 1) AS main_image_path
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                WHERE p.id = :pid
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':pid' => $productId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Cria um pedido originado pelo portal (orçamento).
     *
     * @param int    $customerId
     * @param array  $cartItems  [ ['product_id'=>int, 'quantity'=>int, 'price'=>float], ... ]
     * @param string $notes      Observações do cliente
     * @return int   ID do pedido criado
     */
    public function createPortalOrder(int $customerId, array $cartItems, string $notes = ''): int
    {
        // Calcular total
        $total = 0.0;
        foreach ($cartItems as $item) {
            $total += (float) $item['price'] * (int) $item['quantity'];
        }

        // Inserir pedido
        $sql = "INSERT INTO orders
                (customer_id, total_amount, status, pipeline_stage, pipeline_entered_at,
                 priority, internal_notes, quote_notes, portal_origin, customer_approval_status, created_at)
                VALUES
                (:cid, :total, 'orcamento', 'orcamento', NOW(),
                 'normal', :notes, :qnotes, 1, 'pendente', NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':cid'    => $customerId,
            ':total'  => $total,
            ':notes'  => $notes ? "Portal: " . $notes : 'Pedido criado pelo portal',
            ':qnotes' => $notes,
        ]);

        $orderId = (int) $this->conn->lastInsertId();

        // Inserir itens
        $itemSql = "INSERT INTO order_items (order_id, product_id, quantity, unit_price)
                    VALUES (:oid, :pid, :qty, :price)";
        $itemStmt = $this->conn->prepare($itemSql);
        foreach ($cartItems as $item) {
            $itemStmt->execute([
                ':oid'   => $orderId,
                ':pid'   => $item['product_id'],
                ':qty'   => (int) $item['quantity'],
                ':price' => (float) $item['price'],
            ]);
        }

        return $orderId;
    }

    // ══════════════════════════════════════════════
    // 2FA (Two-Factor Authentication via E-mail)
    // ══════════════════════════════════════════════

    /**
     * Gera código 2FA de 6 dígitos e salva no acesso.
     *
     * @param int $accessId
     * @param int $expiryMinutes Validade em minutos (padrão 10)
     * @return string Código de 6 dígitos
     */
    public function generate2faCode(int $accessId, int $expiryMinutes = 10): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $stmt = $this->conn->prepare(
            "UPDATE {$this->table}
             SET two_factor_code = :code,
                 two_factor_expires_at = DATE_ADD(NOW(), INTERVAL :exp MINUTE)
             WHERE id = :id"
        );
        $stmt->execute([
            ':code' => $code,
            ':exp'  => $expiryMinutes,
            ':id'   => $accessId,
        ]);

        return $code;
    }

    /**
     * Valida código 2FA informado pelo cliente.
     *
     * @param int    $accessId
     * @param string $code Código digitado pelo cliente
     * @return bool
     */
    public function validate2faCode(int $accessId, string $code): bool
    {
        $stmt = $this->conn->prepare(
            "SELECT id FROM {$this->table}
             WHERE id = :id
               AND two_factor_code = :code
               AND two_factor_expires_at > NOW()"
        );
        $stmt->execute([':id' => $accessId, ':code' => $code]);

        if ($stmt->fetch()) {
            // Invalidar código após uso
            $upd = $this->conn->prepare(
                "UPDATE {$this->table}
                 SET two_factor_code = NULL, two_factor_expires_at = NULL
                 WHERE id = :id"
            );
            $upd->execute([':id' => $accessId]);
            return true;
        }

        return false;
    }

    /**
     * Verifica se o acesso tem 2FA habilitado.
     *
     * @param int $accessId
     * @return bool
     */
    public function is2faEnabled(int $accessId): bool
    {
        $stmt = $this->conn->prepare(
            "SELECT two_factor_enabled FROM {$this->table} WHERE id = :id"
        );
        $stmt->execute([':id' => $accessId]);
        return (int) $stmt->fetchColumn() === 1;
    }

    /**
     * Ativa ou desativa 2FA para um acesso.
     *
     * @param int  $accessId
     * @param bool $enable
     * @return bool
     */
    public function toggle2fa(int $accessId, bool $enable): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE {$this->table} SET two_factor_enabled = :val WHERE id = :id"
        );
        return $stmt->execute([':val' => $enable ? 1 : 0, ':id' => $accessId]);
    }

    // ══════════════════════════════════════════════
    // AVATAR
    // ══════════════════════════════════════════════

    /**
     * Atualiza o avatar do acesso ao portal.
     *
     * @param int    $accessId
     * @param string $avatarPath Caminho relativo do arquivo
     * @return bool
     */
    public function updateAvatar(int $accessId, string $avatarPath): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE {$this->table} SET avatar = :avatar WHERE id = :id"
        );
        return $stmt->execute([':avatar' => $avatarPath, ':id' => $accessId]);
    }

    /**
     * Retorna o caminho do avatar do acesso.
     *
     * @param int $accessId
     * @return string|null
     */
    public function getAvatar(int $accessId): ?string
    {
        $stmt = $this->conn->prepare(
            "SELECT avatar FROM {$this->table} WHERE id = :id"
        );
        $stmt->execute([':id' => $accessId]);
        $val = $stmt->fetchColumn();
        return $val ?: null;
    }

    // ══════════════════════════════════════════════
    // SESSÕES ATIVAS
    // ══════════════════════════════════════════════

    /**
     * Lista sessões ativas de um acesso.
     *
     * @param int $accessId
     * @return array
     */
    public function getActiveSessions(int $accessId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM customer_portal_sessions
             WHERE access_id = :aid
             ORDER BY last_activity DESC"
        );
        $stmt->execute([':aid' => $accessId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Remove sessões expiradas (limpeza periódica).
     *
     * @return int Número de sessões removidas
     */
    public function cleanExpiredSessions(): int
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM customer_portal_sessions
             WHERE expires_at IS NOT NULL AND expires_at < NOW()"
        );
        $stmt->execute();
        return $stmt->rowCount();
    }
}
