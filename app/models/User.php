<?php
namespace Akti\Models;

use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use PDO;

/**
 * Modelo User
 *
 * Responsabilidade: operações CRUD de usuários, autenticação e verificação de permissões.
 * Utiliza prepared statements (PDO) e emite eventos via EventDispatcher após operações
 * significativas (login, create, update, delete).
 *
 * Observações:
 * - Senhas são armazenadas com password_hash(BCRYPT).
 * - Validações e regras de negócio devem ser aplicadas no Controller antes de chamar
 *   os métodos deste model (unicidade de email, força de senha, limites do tenant, etc.).
 *
 * @package Akti\Models
 */
class User {
    private $conn;
    private $table_name = "users";

    /** @var int|null ID do usuário */
    public $id;
    /** @var string|null Nome completo do usuário */
    public $name;
    /** @var string|null Email do usuário (único) */
    public $email;
    /** @var string|null Senha em texto antes do hash (não logar) */
    protected $password;
    /** @var string|null Papel do usuário (ex: 'admin', 'user') */
    public $role;
    /** @var int|null ID do grupo de permissões do usuário */
    public $group_id;

    /**
     * Construtor
     *
     * @param PDO $db Conexão PDO (já configurada para o tenant atual)
     */
    public function __construct(\PDO $db) {
        $this->conn = $db;
    }

    public function setPassword(string $password): void {
        $this->password = $password;
    }

    public function getPassword(): ?string {
        return $this->password;
    }

    /**
     * Tenta autenticar um usuário pelo e-mail e senha.
     * Emite evento `model.user.logged_in` em caso de sucesso.
     *
     * @param string $email
     * @param string $password
     * @return bool true se autenticado, false caso contrário
     */
    public function login($email, $password) {
        $query = "SELECT id, name, password, role, group_id FROM " . $this->table_name . " WHERE email = :email LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row && password_verify($password, $row['password'])) {
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->role = $row['role'];
            $this->group_id = $row['group_id'];
            EventDispatcher::dispatch('model.user.logged_in', new Event('model.user.logged_in', [
                'id' => $this->id,
                'name' => $this->name,
                'role' => $this->role,
                'group_id' => $this->group_id
            ]));
            return true;
        }
        
        return false;
    }

    /**
     * Retorna um PDOStatement com todos os usuários (junto com o nome do grupo quando houver).
     *
     * @return array
     */
    public function readAll() {
        $query = "SELECT u.*, g.name as group_name 
                  FROM " . $this->table_name . " u 
                  LEFT JOIN user_groups g ON u.group_id = g.id 
                  ORDER BY u.name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    

    /**
     * Retorna a quantidade total de usuários.
     *
     * @return int
     */
    public function countAll() {
        $query = "SELECT COUNT(*) FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Verifica se um e-mail já existe na tabela de usuários, opcionalmente excluindo um ID.
     *
     * @param string   $email     E-mail a verificar
     * @param int|null $excludeId ID a excluir da verificação (para edição)
     * @return bool true se o e-mail já está em uso
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email";
        $params = [':email' => $email];

        if ($excludeId !== null) {
            $query .= " AND id != :eid";
            $params[':eid'] = $excludeId;
        }

        $query .= " LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna usuários paginados com JOIN no grupo.
     *
     * @param int $page   Página atual (1-based)
     * @param int $perPage Registros por página
     * @return array
     */
    public function readPaginated(int $page = 1, int $perPage = 15): array
    {
        $offset = ($page - 1) * $perPage;
        $query = "SELECT u.*, g.name as group_name
                  FROM {$this->table_name} u
                  LEFT JOIN user_groups g ON u.group_id = g.id
                  ORDER BY u.name ASC
                  LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Cria um novo usuário na tabela `users`.
     * Emite evento `model.user.created` em caso de sucesso.
     *
     * @return bool true se criado com sucesso, false caso contrário
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (name, email, password, role, group_id, created_at) 
                  VALUES (:name, :email, :password, :role, :group_id, NOW())";
        
        $stmt = $this->conn->prepare($query);

        // Sanitização de entrada é feita no Controller (via Input/Sanitizer).
        // Escape de saída é feito na View (via e()). 
        // O Model NÃO deve aplicar htmlspecialchars — isso corrompe dados no banco.
        $this->password = password_hash($this->password, PASSWORD_BCRYPT);
        
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':role', $this->role);
        $stmt->bindParam(':group_id', $this->group_id);

        if($stmt->execute()) {
            $id = $this->conn->lastInsertId();
            EventDispatcher::dispatch('model.user.created', new Event('model.user.created', [
                'id' => $id,
                'name' => $this->name,
                'email' => $this->email,
                'role' => $this->role,
                'group_id' => $this->group_id,
            ]));
            return true;
        }
        return false;
    }

    /**
     * Busca um usuário pelo ID e popula as propriedades do objeto.
     *
     * @param int $id
     * @return array|false Row do usuário como array associativo ou false se não encontrado
     */
    public function readOne($id) {
        $query = "SELECT u.*, g.name as group_name 
                  FROM " . $this->table_name . " u 
                  LEFT JOIN user_groups g ON u.group_id = g.id 
                  WHERE u.id = :id LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->email = $row['email'];
            $this->role = $row['role'];
            $this->group_id = $row['group_id'];
            return $row;
        }
        return false;
    }

    /**
     * Atualiza os dados do usuário. Se a senha estiver presente, atualiza-a também.
     * Emite evento `model.user.updated` em caso de sucesso.
     *
     * @return bool true se atualizado com sucesso, false caso contrário
     */
    public function update() {
        // Build query efficiently based on whether password checks out
        $query = "UPDATE " . $this->table_name . " 
                  SET name = :name, 
                      email = :email, 
                      role = :role, 
                      group_id = :group_id";
        
        if (!empty($this->password)) {
            $query .= ", password = :password";
        }
        
        $query .= " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);

        // Sanitização de entrada é feita no Controller (via Input/Sanitizer).
        // Escape de saída é feito na View (via e()). 
        // O Model NÃO deve aplicar htmlspecialchars — isso corrompe dados no banco.
        
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':role', $this->role);
        $stmt->bindParam(':group_id', $this->group_id);
        $stmt->bindParam(':id', $this->id);

        if (!empty($this->password)) {
            $this->password = password_hash($this->password, PASSWORD_BCRYPT);
            $stmt->bindParam(':password', $this->password);
        }

        if($stmt->execute()) {
            EventDispatcher::dispatch('model.user.updated', new Event('model.user.updated', [
                'id' => $this->id,
                'name' => $this->name,
                'email' => $this->email,
                'role' => $this->role,
            ]));
            return true;
        }
        return false;
    }

    /**
     * Remove um usuário pelo ID.
     * Emite evento `model.user.deleted` em caso de sucesso.
     *
     * @param int $id
     * @return bool true se excluído com sucesso, false caso contrário
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if($stmt->execute()) {
            EventDispatcher::dispatch('model.user.deleted', new Event('model.user.deleted', ['id' => $id]));
            return true;
        }
        return false;
    }

    /**
     * Verifica se o usuário informado possui permissão para acessar uma página.
     * Admins têm acesso irrestrito. Para demais usuários, verifica permissões do grupo.
     *
     * @param int $userId
     * @param string $page Nome da página/rota a ser verificada
     * @return bool true se permitido, false caso contrário
     */
    public function checkPermission($userId, $page) {
        // Obter usuario e Role
        $query = "SELECT role, group_id FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
        
        if($stmt->rowCount() == 0) return false;
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user['role'] === 'admin') {
            return true;
        }

        // Se não, verifica permissões do grupo
        if ($user['group_id']) {
            $query = "SELECT * FROM group_permissions WHERE group_id = :group_id AND page_name = :page_name";
            $stmtPermissions = $this->conn->prepare($query);
            $stmtPermissions->bindParam(':group_id', $user['group_id']);
            $stmtPermissions->bindParam(':page_name', $page);
            $stmtPermissions->execute();
            if ($stmtPermissions->rowCount() > 0) {
                return true;
            }
        }
        
        return false; 
    }

    /**
     * Retorna os IDs de setores permitidos para o usuário.
     * Admin tem acesso a todos. Se o grupo não tem restrições, retorna vazio (= todos).
     *
     * @param int $userId
     * @return int[] Array de IDs de setor permitidos (vazio = todos)
     */
    public function getAllowedSectorIds($userId) {
        $query = "SELECT role, group_id FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || $user['role'] === 'admin') {
            return []; // vazio = acesso total
        }

        if ($user['group_id']) {
            $stmtPerms = $this->conn->prepare("SELECT page_name FROM group_permissions WHERE group_id = :gid AND page_name LIKE 'sector_%'");
            $stmtPerms->bindParam(':gid', $user['group_id']);
            $stmtPerms->execute();
            $perms = $stmtPerms->fetchAll(PDO::FETCH_COLUMN);
            $sectorIds = [];
            foreach ($perms as $p) {
                $sectorIds[] = (int) str_replace('sector_', '', $p);
            }
            return $sectorIds;
        }

        return []; // sem grupo = acesso total
    }
}
