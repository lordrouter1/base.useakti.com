<?php

namespace Akti\Models;

use PDO;
use Akti\Core\EventDispatcher;
use Akti\Core\Event;

class Supplier
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function readAll(): array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM suppliers WHERE deleted_at IS NULL ORDER BY company_name ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function readPaginated(int $page = 1, int $perPage = 15, string $search = ''): array
    {
        $where = ' WHERE deleted_at IS NULL';
        $params = [];
        if ($search) {
            $where .= ' AND (company_name LIKE :search OR trade_name LIKE :s2 OR document LIKE :s3 OR email LIKE :s4)';
            $params[':search'] = '%' . $search . '%';
            $params[':s2'] = '%' . $search . '%';
            $params[':s3'] = '%' . $search . '%';
            $params[':s4'] = '%' . $search . '%';
        }

        $countStmt = $this->conn->prepare("SELECT COUNT(*) FROM suppliers" . $where);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT * FROM suppliers {$where} ORDER BY company_name ASC LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data'         => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total'        => $total,
            'pages'        => (int) ceil($total / $perPage),
            'current_page' => $page,
        ];
    }

    public function readOne(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM suppliers WHERE id = :id AND deleted_at IS NULL");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO suppliers (tenant_id, company_name, trade_name, document, state_registration, email, phone, website, contact_name, address, address_number, complement, neighborhood, city, state, zip_code, notes, status)
             VALUES (:tenant_id, :company_name, :trade_name, :document, :state_registration, :email, :phone, :website, :contact_name, :address, :address_number, :complement, :neighborhood, :city, :state, :zip_code, :notes, :status)"
        );
        $stmt->execute([
            ':tenant_id'          => $data['tenant_id'],
            ':company_name'       => $data['company_name'],
            ':trade_name'         => $data['trade_name'] ?? null,
            ':document'           => $data['document'] ?? null,
            ':state_registration' => $data['state_registration'] ?? null,
            ':email'              => $data['email'] ?? null,
            ':phone'              => $data['phone'] ?? null,
            ':website'            => $data['website'] ?? null,
            ':contact_name'       => $data['contact_name'] ?? null,
            ':address'            => $data['address'] ?? null,
            ':address_number'     => $data['address_number'] ?? null,
            ':complement'         => $data['complement'] ?? null,
            ':neighborhood'       => $data['neighborhood'] ?? null,
            ':city'               => $data['city'] ?? null,
            ':state'              => $data['state'] ?? null,
            ':zip_code'           => $data['zip_code'] ?? null,
            ':notes'              => $data['notes'] ?? null,
            ':status'             => $data['status'] ?? 'active',
        ]);
        $id = (int) $this->conn->lastInsertId();
        EventDispatcher::dispatch('model.supplier.created', new Event('model.supplier.created', ['id' => $id, 'name' => $data['company_name']]));
        return $id;
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE suppliers SET
                company_name = :company_name, trade_name = :trade_name, document = :document,
                state_registration = :state_registration, email = :email, phone = :phone,
                website = :website, contact_name = :contact_name, address = :address,
                address_number = :address_number, complement = :complement, neighborhood = :neighborhood,
                city = :city, state = :state, zip_code = :zip_code, notes = :notes, status = :status
             WHERE id = :id"
        );
        $result = $stmt->execute([
            ':id'                 => $id,
            ':company_name'       => $data['company_name'],
            ':trade_name'         => $data['trade_name'] ?? null,
            ':document'           => $data['document'] ?? null,
            ':state_registration' => $data['state_registration'] ?? null,
            ':email'              => $data['email'] ?? null,
            ':phone'              => $data['phone'] ?? null,
            ':website'            => $data['website'] ?? null,
            ':contact_name'       => $data['contact_name'] ?? null,
            ':address'            => $data['address'] ?? null,
            ':address_number'     => $data['address_number'] ?? null,
            ':complement'         => $data['complement'] ?? null,
            ':neighborhood'       => $data['neighborhood'] ?? null,
            ':city'               => $data['city'] ?? null,
            ':state'              => $data['state'] ?? null,
            ':zip_code'           => $data['zip_code'] ?? null,
            ':notes'              => $data['notes'] ?? null,
            ':status'             => $data['status'] ?? 'active',
        ]);
        EventDispatcher::dispatch('model.supplier.updated', new Event('model.supplier.updated', ['id' => $id]));
        return $result;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare("UPDATE suppliers SET deleted_at = NOW() WHERE id = :id");
        $result = $stmt->execute([':id' => $id]);
        EventDispatcher::dispatch('model.supplier.deleted', new Event('model.supplier.deleted', ['id' => $id]));
        return $result;
    }

    public function countAll(): int
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM suppliers WHERE deleted_at IS NULL");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }
}
