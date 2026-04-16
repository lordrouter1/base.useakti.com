<?php
namespace Akti\Services;

use Akti\Models\CustomerContact;
use Akti\Utils\Input;
use PDO;

/**
 * Service: CustomerContactService
 * CRUD de contatos adicionais de clientes.
 */
class CustomerContactService
{
    private PDO $db;
    private CustomerContact $contactModel;

    /**
     * Construtor da classe CustomerContactService.
     *
     * @param PDO $db Conexão PDO com o banco de dados
     * @param CustomerContact $contactModel Contact model
     */
    public function __construct(PDO $db, CustomerContact $contactModel)
    {
        $this->db = $db;
        $this->contactModel = $contactModel;
    }

    /**
     * Lista contatos de um cliente.
     */
    public function listByCustomer(int $customerId): array
    {
        return $this->contactModel->readByCustomer($customerId);
    }

    /**
     * Cria ou atualiza um contato.
     *
     * @return array ['success' => bool, 'message' => string, 'id' => int|null]
     */
    public function save(array $data): array
    {
        $contactId  = (int) ($data['contact_id'] ?? 0);
        $customerId = (int) ($data['customer_id'] ?? 0);
        $name       = trim($data['name'] ?? '');

        if (!$customerId || !$name) {
            return ['success' => false, 'message' => 'Cliente e nome do contato são obrigatórios.', 'id' => null];
        }

        $record = [
            'customer_id' => $customerId,
            'name'        => $name,
            'role'        => $data['role'] ?? null,
            'email'       => $data['email'] ?? null,
            'phone'       => preg_replace('/\D/', '', $data['phone'] ?? ''),
            'is_primary'  => (int) ($data['is_primary'] ?? 0),
            'notes'       => $data['notes'] ?? null,
        ];

        if ($contactId > 0) {
            $record['id'] = $contactId;
            $this->contactModel->update($record);
            return ['success' => true, 'message' => 'Contato atualizado.', 'id' => $contactId];
        }

        $newId = $this->contactModel->create($record);
        return ['success' => true, 'message' => 'Contato adicionado.', 'id' => $newId];
    }

    /**
     * Remove um contato.
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function delete(int $contactId): array
    {
        if ($contactId <= 0) {
            return ['success' => false, 'message' => 'ID do contato é obrigatório.'];
        }

        $this->contactModel->delete($contactId);
        return ['success' => true, 'message' => 'Contato removido.'];
    }
}
