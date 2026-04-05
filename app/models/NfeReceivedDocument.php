<?php
namespace Akti\Models;

use PDO;

/**
 * Model: NfeReceivedDocument
 * CRUD para documentos fiscais recebidos via DistDFe (tabela nfe_received_documents).
 *
 * Entradas: Conexão PDO ($db), parâmetros das funções.
 * Saídas: Arrays de dados, booleanos, IDs.
 * Não deve conter HTML, echo, print ou acesso direto a $_POST/$_GET.
 *
 * @package Akti\Models
 */
class NfeReceivedDocument
{
    private $conn;
    private $table = 'nfe_received_documents';

    public function __construct(\PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Insere ou atualiza um documento recebido pelo NSU.
     * @param array $data
     * @return int ID inserido/atualizado
     */
    public function upsert(array $data): int
    {
        $q = "INSERT INTO {$this->table}
              (nsu, schema_type, chave, cnpj_emitente, nome_emitente, ie_emitente,
               data_emissao, tipo_nfe, valor_total, situacao, summary_xml, xml_content, credential_id)
              VALUES
              (:nsu, :schema_type, :chave, :cnpj_emitente, :nome_emitente, :ie_emitente,
               :data_emissao, :tipo_nfe, :valor_total, :situacao, :summary_xml, :xml_content, :credential_id)
              ON DUPLICATE KEY UPDATE
                schema_type     = COALESCE(VALUES(schema_type), schema_type),
                chave           = COALESCE(VALUES(chave), chave),
                cnpj_emitente   = COALESCE(VALUES(cnpj_emitente), cnpj_emitente),
                nome_emitente   = COALESCE(VALUES(nome_emitente), nome_emitente),
                data_emissao    = COALESCE(VALUES(data_emissao), data_emissao),
                valor_total     = COALESCE(VALUES(valor_total), valor_total),
                situacao        = COALESCE(VALUES(situacao), situacao),
                xml_content     = COALESCE(VALUES(xml_content), xml_content),
                updated_at      = NOW()";

        $s = $this->conn->prepare($q);
        $s->execute([
            ':nsu'            => $data['nsu'],
            ':schema_type'    => $data['schema_type'] ?? null,
            ':chave'          => $data['chave'] ?? null,
            ':cnpj_emitente'  => $data['cnpj_emitente'] ?? null,
            ':nome_emitente'  => $data['nome_emitente'] ?? null,
            ':ie_emitente'    => $data['ie_emitente'] ?? null,
            ':data_emissao'   => $data['data_emissao'] ?? null,
            ':tipo_nfe'       => $data['tipo_nfe'] ?? null,
            ':valor_total'    => $data['valor_total'] ?? 0,
            ':situacao'       => $data['situacao'] ?? null,
            ':summary_xml'    => $data['summary_xml'] ?? null,
            ':xml_content'    => $data['xml_content'] ?? null,
            ':credential_id'  => $data['credential_id'] ?? null,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    /**
     * Retorna um documento recebido pelo ID.
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
     * Retorna documento pela chave de acesso.
     * @param string $chave
     * @return array|false
     */
    public function readByChave(string $chave)
    {
        $q = "SELECT * FROM {$this->table} WHERE chave = :chave LIMIT 1";
        $s = $this->conn->prepare($q);
        $s->execute([':chave' => $chave]);
        return $s->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Listagem paginada com filtros.
     * @param array $filters  status, search, date_start, date_end
     * @param int   $page
     * @param int   $perPage
     * @return array ['data' => [], 'total' => int]
     */
    public function readPaginated(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "manifestation_status = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $where[] = "(chave LIKE :search OR cnpj_emitente LIKE :search2 OR nome_emitente LIKE :search3)";
            $params[':search']  = '%' . $filters['search'] . '%';
            $params[':search2'] = '%' . $filters['search'] . '%';
            $params[':search3'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['date_start'])) {
            $where[] = "data_emissao >= :date_start";
            $params[':date_start'] = $filters['date_start'] . ' 00:00:00';
        }
        if (!empty($filters['date_end'])) {
            $where[] = "data_emissao <= :date_end";
            $params[':date_end'] = $filters['date_end'] . ' 23:59:59';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $offset = ($page - 1) * $perPage;

        // Total
        $qCount = "SELECT COUNT(*) FROM {$this->table} {$whereClause}";
        $sCount = $this->conn->prepare($qCount);
        $sCount->execute($params);
        $total = (int) $sCount->fetchColumn();

        // Dados
        $q = "SELECT * FROM {$this->table} {$whereClause} ORDER BY data_emissao DESC LIMIT :limit OFFSET :offset";
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
     * Atualiza status de manifestação.
     * @param int    $id
     * @param string $status pendente|ciencia|confirmada|desconhecida|nao_realizada
     * @param string|null $protocol
     * @return bool
     */
    public function updateManifestation(int $id, string $status, ?string $protocol = null): bool
    {
        $q = "UPDATE {$this->table}
              SET manifestation_status = :status,
                  manifestation_date = NOW(),
                  manifestation_protocol = :protocol,
                  updated_at = NOW()
              WHERE id = :id";
        $s = $this->conn->prepare($q);
        return $s->execute([
            ':status'   => $status,
            ':protocol' => $protocol,
            ':id'       => $id,
        ]);
    }

    /**
     * Marca documento como importado.
     * @param int $id
     * @return bool
     */
    public function markImported(int $id): bool
    {
        $q = "UPDATE {$this->table} SET imported = 1, updated_at = NOW() WHERE id = :id";
        $s = $this->conn->prepare($q);
        return $s->execute([':id' => $id]);
    }

    /**
     * Conta documentos por status de manifestação.
     * @return array
     */
    public function countByManifestationStatus(): array
    {
        $q = "SELECT manifestation_status, COUNT(*) as cnt FROM {$this->table} GROUP BY manifestation_status";
        $s = $this->conn->prepare($q);
        $s->execute();
        $result = [];
        while ($row = $s->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['manifestation_status']] = (int) $row['cnt'];
        }
        return $result;
    }

    /**
     * Retorna o último NSU consultado (valor máximo na tabela).
     * @return string
     */
    public function getLastNSU(): string
    {
        $q = "SELECT MAX(CAST(nsu AS UNSIGNED)) FROM {$this->table}";
        $s = $this->conn->prepare($q);
        $s->execute();
        return (string) ($s->fetchColumn() ?: '0');
    }
}
