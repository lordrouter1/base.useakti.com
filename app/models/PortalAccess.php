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
     * Gera e armazena um token de link mágico
     * @param int $accessId
     * @param int $expiryHours Horas de validade (padrão 24)
     * @return string Token gerado (128 chars hex)
     */
    public function generateMagicToken(int $accessId, int $expiryHours = 24): string
    {
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
}
