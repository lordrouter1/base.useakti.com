<?php
namespace Akti\Models;

use PDO;

/**
 * Model: NfeLog
 * CRUD para logs de comunicação com SEFAZ (tabela nfe_logs).
 *
 * Entradas: Conexão PDO ($db), parâmetros das funções.
 * Saídas: Arrays de dados, IDs inseridos.
 * Não deve conter HTML, echo, print ou acesso direto a $_POST/$_GET.
 */
class NfeLog
{
    private $conn;
    private $table = 'nfe_logs';

    /**
     * Construtor da classe NfeLog.
     *
     * @param \PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(\PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Registra um log de comunicação SEFAZ.
     * @param array $data
     * @return int ID do log criado
     */
    public function create(array $data): int
    {
        $q = "INSERT INTO {$this->table}
              (nfe_document_id, order_id, action, status, code_sefaz, message,
               xml_request, xml_response, user_id, ip_address)
              VALUES
              (:doc_id, :oid, :action, :status, :code, :msg, :xml_req, :xml_resp, :uid, :ip)";

        $s = $this->conn->prepare($q);
        $s->execute([
            ':doc_id'   => $data['nfe_document_id'] ?? null,
            ':oid'      => $data['order_id'] ?? null,
            ':action'   => $data['action'] ?? 'info',
            ':status'   => $data['status'] ?? 'info',
            ':code'     => $data['code_sefaz'] ?? null,
            ':msg'      => $data['message'] ?? null,
            ':xml_req'  => $data['xml_request'] ?? null,
            ':xml_resp' => $data['xml_response'] ?? null,
            ':uid'      => $data['user_id'] ?? ($_SESSION['user_id'] ?? null),
            ':ip'       => $data['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? null),
        ]);

        return (int) $this->conn->lastInsertId();
    }

    /**
     * Retorna logs por nfe_document_id.
     * @param int $docId
     * @return array
     */
    public function getByDocument(int $docId): array
    {
        $q = "SELECT * FROM {$this->table} WHERE nfe_document_id = :id ORDER BY id DESC";
        $s = $this->conn->prepare($q);
        $s->execute([':id' => $docId]);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna logs por order_id.
     * @param int $orderId
     * @return array
     */
    public function getByOrder(int $orderId): array
    {
        $q = "SELECT * FROM {$this->table} WHERE order_id = :oid ORDER BY id DESC";
        $s = $this->conn->prepare($q);
        $s->execute([':oid' => $orderId]);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna logs recentes (para painel).
     * @param int $limit
     * @return array
     */
    public function getRecent(int $limit = 50): array
    {
        $q = "SELECT l.*, n.numero as nfe_numero, n.serie as nfe_serie
              FROM {$this->table} l
              LEFT JOIN nfe_documents n ON l.nfe_document_id = n.id
              ORDER BY l.id DESC LIMIT :lim";
        $s = $this->conn->prepare($q);
        $s->bindValue(':lim', $limit, PDO::PARAM_INT);
        $s->execute();
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }
}
