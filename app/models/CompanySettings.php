<?php
namespace Akti\Models;
use PDO;

/**
 * Model: CompanySettings
 * Gerencia configurações da empresa (key-value store)
 */
class CompanySettings {
    private $conn;
    private $table = 'company_settings';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Retorna todas as configurações como array associativo
     */
    public function getAll() {
        $stmt = $this->conn->query("SELECT setting_key, setting_value FROM {$this->table}");
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }

    /**
     * Retorna uma configuração específica
     */
    public function get($key, $default = '') {
        $stmt = $this->conn->prepare("SELECT setting_value FROM {$this->table} WHERE setting_key = :key");
        $stmt->execute([':key' => $key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    }

    /**
     * Salva uma configuração (INSERT ou UPDATE)
     */
    public function set($key, $value) {
        $stmt = $this->conn->prepare("INSERT INTO {$this->table} (setting_key, setting_value) VALUES (:key, :val) ON DUPLICATE KEY UPDATE setting_value = :val2");
        return $stmt->execute([':key' => $key, ':val' => $value, ':val2' => $value]);
    }

    /**
     * Salva múltiplas configurações de uma vez
     */
    public function saveAll($data) {
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Formata o endereço da empresa como string legível
     */
    public function getFormattedAddress() {
        $s = $this->getAll();
        return self::formatAddressFromArray([
            'address_type'   => $s['company_address_type'] ?? '',
            'address_name'   => $s['company_address_name'] ?? '',
            'address_number' => $s['company_address_number'] ?? '',
            'neighborhood'   => $s['company_neighborhood'] ?? '',
            'complement'     => $s['company_complement'] ?? '',
            'zipcode'        => $s['company_zipcode'] ?? '',
        ], $s['company_city'] ?? '', $s['company_state'] ?? '');
    }

    /**
     * Helper estático para formatar endereço de JSON armazenado na tabela customers
     */
    public static function formatCustomerAddress($jsonOrArray) {
        if (is_string($jsonOrArray)) {
            $data = json_decode($jsonOrArray, true);
            if (!$data) return $jsonOrArray; // Se não for JSON válido, retorna como está
        } else {
            $data = $jsonOrArray;
        }
        return self::formatAddressFromArray($data);
    }

    /**
     * Formata endereço a partir de um array
     */
    public static function formatAddressFromArray($data, $city = '', $state = '') {
        $parts = [];
        
        $type = $data['address_type'] ?? '';
        $name = $data['address_name'] ?? '';
        $number = $data['address_number'] ?? '';
        $neighborhood = $data['neighborhood'] ?? '';
        $complement = $data['complement'] ?? '';
        $zipcode = $data['zipcode'] ?? '';

        if ($type && $name) {
            $parts[] = "$type $name";
        } elseif ($name) {
            $parts[] = $name;
        }

        if ($number) {
            $parts[] = "nº $number";
        }

        if ($complement) {
            $parts[] = $complement;
        }

        if ($neighborhood) {
            $parts[] = $neighborhood;
        }

        $line1 = implode(', ', $parts);

        // Cidade/Estado/CEP
        $line2Parts = [];
        if ($city) $line2Parts[] = $city;
        if ($state) $line2Parts[] = $state;
        if ($zipcode) $line2Parts[] = "CEP: $zipcode";
        $line2 = implode(' - ', $line2Parts);

        if ($line2) {
            return $line1 ? "$line1 — $line2" : $line2;
        }
        return $line1;
    }
}
