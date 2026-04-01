<?php
namespace Akti\Models;

use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use Akti\Core\Log;
use PDO;

/**
 * Model: NfeDocument
 * CRUD para documentos NF-e emitidos (tabela nfe_documents).
 *
 * Entradas: Conexão PDO ($db), parâmetros das funções.
 * Saídas: Arrays de dados, booleanos, IDs inseridos.
 * Eventos: 'model.nfe_document.created', 'model.nfe_document.updated',
 *          'model.nfe_document.authorized', 'model.nfe_document.cancelled'
 * Não deve conter HTML, echo, print ou acesso direto a $_POST/$_GET.
 */
class NfeDocument
{
    private $conn;
    private $table = 'nfe_documents';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // ══════════════════════════════════════════════════════════════
    // CRUD
    // ══════════════════════════════════════════════════════════════

    /**
     * Cria um novo registro de NF-e.
     * @param array $data
     * @return int ID da NF-e criada
     */
    public function create(array $data): int
    {
        $q = "INSERT INTO {$this->table}
              (order_id, modelo, numero, serie, status, natureza_op, valor_total, valor_produtos,
               valor_desconto, valor_frete, dest_cnpj_cpf, dest_nome, dest_ie, dest_uf, tp_emis)
              VALUES
              (:order_id, :modelo, :numero, :serie, :status, :natureza_op, :valor_total, :valor_produtos,
               :valor_desconto, :valor_frete, :dest_cnpj_cpf, :dest_nome, :dest_ie, :dest_uf, :tp_emis)";

        $s = $this->conn->prepare($q);
        $s->execute([
            ':order_id'       => $data['order_id'] ?? null,
            ':modelo'         => $data['modelo'] ?? 55,
            ':numero'         => $data['numero'],
            ':serie'          => $data['serie'] ?? 1,
            ':status'         => $data['status'] ?? 'rascunho',
            ':natureza_op'    => $data['natureza_op'] ?? 'VENDA DE MERCADORIA',
            ':valor_total'    => $data['valor_total'] ?? 0,
            ':valor_produtos' => $data['valor_produtos'] ?? 0,
            ':valor_desconto' => $data['valor_desconto'] ?? 0,
            ':valor_frete'    => $data['valor_frete'] ?? 0,
            ':dest_cnpj_cpf'  => $data['dest_cnpj_cpf'] ?? null,
            ':dest_nome'      => $data['dest_nome'] ?? null,
            ':dest_ie'        => $data['dest_ie'] ?? null,
            ':dest_uf'        => $data['dest_uf'] ?? null,
            ':tp_emis'        => $data['tp_emis'] ?? 1,
        ]);

        $id = (int) $this->conn->lastInsertId();

        EventDispatcher::dispatch('model.nfe_document.created', new Event('model.nfe_document.created', [
            'id'       => $id,
            'order_id' => $data['order_id'] ?? null,
            'numero'   => $data['numero'],
        ]));

        return $id;
    }

    /**
     * Retorna uma NF-e pelo ID.
     * @param int $id
     * @return array|false
     */
    public function readOne(int $id)
    {
        $q = "SELECT * FROM {$this->table} WHERE id = :id";
        $s = $this->conn->prepare($q);
        $s->execute([':id' => $id]);
        return $s->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Busca NF-e por número, série e modelo.
     * @param int $numero
     * @param int $serie
     * @param int $modelo
     * @return array|false
     */
    public function findByNumero(int $numero, int $serie = 1, int $modelo = 55)
    {
        $q = "SELECT * FROM {$this->table} WHERE numero = :numero AND serie = :serie AND modelo = :modelo LIMIT 1";
        $s = $this->conn->prepare($q);
        $s->execute([':numero' => $numero, ':serie' => $serie, ':modelo' => $modelo]);
        return $s->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna NF-e por order_id.
     * @param int $orderId
     * @return array|false
     */
    public function readByOrder(int $orderId)
    {
        $q = "SELECT * FROM {$this->table} WHERE order_id = :oid ORDER BY id DESC LIMIT 1";
        $s = $this->conn->prepare($q);
        $s->execute([':oid' => $orderId]);
        return $s->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna todas as NF-e de um pedido.
     * @param int $orderId
     * @return array
     */
    public function readAllByOrder(int $orderId): array
    {
        $q = "SELECT * FROM {$this->table} WHERE order_id = :oid ORDER BY id DESC";
        $s = $this->conn->prepare($q);
        $s->execute([':oid' => $orderId]);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Listagem paginada com filtros.
     * @param array $filters  status, month, year, search
     * @param int   $page
     * @param int   $perPage
     * @return array ['data' => [], 'total' => int]
     */
    public function readPaginated(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "n.status = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['month'])) {
            $where[] = "MONTH(n.created_at) = :month";
            $params[':month'] = (int) $filters['month'];
        }
        if (!empty($filters['year'])) {
            $where[] = "YEAR(n.created_at) = :year";
            $params[':year'] = (int) $filters['year'];
        }
        if (!empty($filters['search'])) {
            $where[] = "(n.numero LIKE :search OR n.chave LIKE :search2 OR n.dest_nome LIKE :search3)";
            $params[':search'] = '%' . $filters['search'] . '%';
            $params[':search2'] = '%' . $filters['search'] . '%';
            $params[':search3'] = '%' . $filters['search'] . '%';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $offset = ($page - 1) * $perPage;

        // Total
        $qCount = "SELECT COUNT(*) FROM {$this->table} n {$whereClause}";
        $sCount = $this->conn->prepare($qCount);
        $sCount->execute($params);
        $total = (int) $sCount->fetchColumn();

        // Dados
        $q = "SELECT n.*, o.id as order_num
              FROM {$this->table} n
              LEFT JOIN orders o ON n.order_id = o.id
              {$whereClause}
              ORDER BY n.id DESC
              LIMIT :limit OFFSET :offset";
        $s = $this->conn->prepare($q);
        foreach ($params as $k => $v) {
            $s->bindValue($k, $v);
        }
        $s->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $s->bindValue(':offset', $offset, PDO::PARAM_INT);
        $s->execute();

        return [
            'data'  => $s->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
        ];
    }

    /**
     * Atualiza campos da NF-e.
     * @param int   $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = [
            'chave', 'protocolo', 'recibo',
            'status', 'status_sefaz', 'motivo_sefaz',
            'modelo', 'tp_emis', 'contingencia_justificativa',
            'natureza_op',
            'valor_total', 'valor_produtos', 'valor_desconto', 'valor_frete',
            'valor_icms', 'valor_pis', 'valor_cofins', 'valor_ipi', 'valor_tributos_aprox',
            'dest_cnpj_cpf', 'dest_nome', 'dest_ie', 'dest_uf',
            'xml_envio', 'xml_autorizado', 'xml_cancelamento', 'xml_correcao',
            'xml_path', 'danfe_path', 'cancel_xml_path',
            'cancel_protocolo', 'cancel_motivo', 'cancel_date',
            'correcao_texto', 'correcao_seq', 'correcao_date',
            'emitted_at',
        ];

        $fields = [];
        $params = [':id' => $id];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $q = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        $s = $this->conn->prepare($q);
        $result = $s->execute($params);

        if ($result) {
            EventDispatcher::dispatch('model.nfe_document.updated', new Event('model.nfe_document.updated', [
                'id'     => $id,
                'fields' => array_keys($data),
            ]));
        }

        return $result;
    }

    /**
     * Marca NF-e como autorizada e sincroniza com a tabela orders.
     * @param int         $id
     * @param string      $chave
     * @param string      $protocolo
     * @param string      $xmlAutorizado
     * @param string|null $xmlPath  Caminho relativo do XML salvo em disco
     * @return bool
     */
    public function markAuthorized(int $id, string $chave, string $protocolo, string $xmlAutorizado, ?string $xmlPath = null): bool
    {
        $this->conn->beginTransaction();
        try {
            // Atualizar nfe_documents
            $updateData = [
                'chave'          => $chave,
                'protocolo'      => $protocolo,
                'status'         => 'autorizada',
                'status_sefaz'   => '100',
                'xml_autorizado' => $xmlAutorizado,
                'emitted_at'     => date('Y-m-d H:i:s'),
            ];
            if ($xmlPath !== null) {
                $updateData['xml_path'] = $xmlPath;
            }
            $this->update($id, $updateData);

            // Sincronizar com orders
            $doc = $this->readOne($id);
            if ($doc && $doc['order_id']) {
                $q = "UPDATE orders SET nfe_id = :nfe_id, nfe_status = 'autorizada',
                      nf_number = :nf_num, nf_access_key = :chave, nf_series = :serie, nf_status = 'emitida'
                      WHERE id = :oid";
                $s = $this->conn->prepare($q);
                $s->execute([
                    ':nfe_id' => $id,
                    ':nf_num' => $doc['numero'],
                    ':chave'  => $chave,
                    ':serie'  => $doc['serie'],
                    ':oid'    => $doc['order_id'],
                ]);
            }

            $this->conn->commit();

            EventDispatcher::dispatch('model.nfe_document.authorized', new Event('model.nfe_document.authorized', [
                'nfe_id'   => $id,
                'order_id' => $doc['order_id'] ?? null,
                'chave'    => $chave,
            ]));

            return true;
        } catch (\Exception $e) {
            Log::error('markAuthorized rollback: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->conn->rollBack();
            return false;
        }
    }

    /**
     * Marca NF-e como cancelada.
     * @param int    $id
     * @param string $protocolo
     * @param string $motivo
     * @param string $xmlCancelamento
     * @return bool
     */
    public function markCancelled(int $id, string $protocolo, string $motivo, string $xmlCancelamento): bool
    {
        $this->conn->beginTransaction();
        try {
            $this->update($id, [
                'status'           => 'cancelada',
                'cancel_protocolo' => $protocolo,
                'cancel_motivo'    => $motivo,
                'cancel_date'      => date('Y-m-d H:i:s'),
                'xml_cancelamento' => $xmlCancelamento,
            ]);

            $doc = $this->readOne($id);
            if ($doc && $doc['order_id']) {
                $q = "UPDATE orders SET nfe_status = 'cancelada', nf_status = 'cancelada' WHERE id = :oid";
                $s = $this->conn->prepare($q);
                $s->execute([':oid' => $doc['order_id']]);
            }

            $this->conn->commit();

            EventDispatcher::dispatch('model.nfe_document.cancelled', new Event('model.nfe_document.cancelled', [
                'nfe_id'   => $id,
                'order_id' => $doc['order_id'] ?? null,
            ]));

            return true;
        } catch (\Exception $e) {
            Log::error('markCancelled rollback: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->conn->rollBack();
            return false;
        }
    }

    /**
     * Conta NF-e por status (para cards de resumo).
     * @return array ['autorizada' => int, 'cancelada' => int, ...]
     */
    public function countByStatus(): array
    {
        $q = "SELECT status, COUNT(*) as cnt FROM {$this->table} GROUP BY status";
        $s = $this->conn->prepare($q);
        $s->execute();
        $result = [];
        while ($row = $s->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['status']] = (int) $row['cnt'];
        }
        return $result;
    }

    /**
     * Conta NF-e do mês atual.
     * @return int
     */
    public function countThisMonth(): int
    {
        $q = "SELECT COUNT(*) FROM {$this->table} WHERE MONTH(created_at) = :m AND YEAR(created_at) = :y";
        $s = $this->conn->prepare($q);
        $s->execute([':m' => date('m'), ':y' => date('Y')]);
        return (int) $s->fetchColumn();
    }

    /**
     * Soma valores das NF-e autorizadas do mês.
     * @return float
     */
    public function sumAuthorizedThisMonth(): float
    {
        $q = "SELECT COALESCE(SUM(valor_total), 0) FROM {$this->table}
              WHERE status = 'autorizada' AND MONTH(emitted_at) = :m AND YEAR(emitted_at) = :y";
        $s = $this->conn->prepare($q);
        $s->execute([':m' => date('m'), ':y' => date('Y')]);
        return (float) $s->fetchColumn();
    }
}
