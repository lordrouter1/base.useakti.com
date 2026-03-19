<?php
namespace Akti\Models;

use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use PDO;

/**
 * Model: NfeCredential
 * CRUD para credenciais SEFAZ do tenant (tabela nfe_credentials).
 *
 * Entradas: Conexão PDO ($db), parâmetros das funções.
 * Saídas: Arrays de dados, booleanos.
 * Eventos: 'model.nfe_credential.updated'
 * Não deve conter HTML, echo, print ou acesso direto a $_POST/$_GET.
 */
class NfeCredential
{
    private $conn;
    private $table = 'nfe_credentials';

    /**
     * @param PDO $db Conexão PDO
     */
    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Retorna as credenciais do tenant (sempre id=1, registro único por tenant).
     * @return array|false
     */
    public function get()
    {
        $q = "SELECT * FROM {$this->table} WHERE id = 1 LIMIT 1";
        $s = $this->conn->prepare($q);
        $s->execute();
        return $s->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Atualiza credenciais SEFAZ.
     * @param array $data Campos a atualizar
     * @return bool
     */
    public function update(array $data): bool
    {
        $allowedFields = [
            'cnpj', 'ie', 'razao_social', 'nome_fantasia', 'crt',
            'uf', 'cod_municipio', 'municipio',
            'logradouro', 'numero', 'bairro', 'cep', 'complemento', 'telefone',
            'certificate_path', 'certificate_password', 'certificate_expiry',
            'environment', 'serie_nfe', 'proximo_numero',
            'csc_id', 'csc_token',
        ];

        $fields = [];
        $params = [':id' => 1];

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
            EventDispatcher::dispatch('model.nfe_credential.updated', new Event('model.nfe_credential.updated', [
                'fields' => array_diff_key($data, array_flip(['certificate_password'])),
            ]));
        }

        return $result;
    }

    /**
     * Retorna o próximo número de NF-e com lock (FOR UPDATE) para evitar duplicidade.
     * Deve ser chamado dentro de uma transação.
     * @return int
     */
    public function getNextNumberForUpdate(): int
    {
        $q = "SELECT proximo_numero FROM {$this->table} WHERE id = 1 FOR UPDATE";
        $s = $this->conn->prepare($q);
        $s->execute();
        return (int) $s->fetchColumn();
    }

    /**
     * Incrementa o próximo número de NF-e.
     * Deve ser chamado dentro de uma transação após getNextNumberForUpdate().
     * @return bool
     */
    public function incrementNextNumber(): bool
    {
        $q = "UPDATE {$this->table} SET proximo_numero = proximo_numero + 1 WHERE id = 1";
        $s = $this->conn->prepare($q);
        return $s->execute();
    }

    /**
     * Verifica se as credenciais mínimas estão preenchidas para emissão.
     * @return array ['valid' => bool, 'missing' => string[]]
     */
    public function validateForEmission(): array
    {
        $cred = $this->get();
        if (!$cred) {
            return ['valid' => false, 'missing' => ['Nenhuma credencial configurada']];
        }

        $missing = [];
        $required = [
            'cnpj'             => 'CNPJ',
            'ie'               => 'Inscrição Estadual',
            'razao_social'     => 'Razão Social',
            'uf'               => 'UF',
            'cod_municipio'    => 'Código Município',
            'logradouro'       => 'Logradouro',
            'numero'           => 'Número',
            'bairro'           => 'Bairro',
            'cep'              => 'CEP',
            'certificate_path' => 'Certificado Digital',
        ];

        foreach ($required as $field => $label) {
            if (empty($cred[$field])) {
                $missing[] = $label;
            }
        }

        // Verificar se certificado existe no disco
        if (!empty($cred['certificate_path']) && !file_exists($cred['certificate_path'])) {
            $missing[] = 'Certificado Digital (arquivo não encontrado)';
        }

        return [
            'valid'   => empty($missing),
            'missing' => $missing,
        ];
    }

    /**
     * Criptografa a senha do certificado antes de salvar.
     * Usa openssl_encrypt com chave derivada.
     * @param string $password Senha em texto plano
     * @return string Senha criptografada
     */
    public static function encryptPassword(string $password): string
    {
        $key = self::getEncryptionKey();
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($password, 'aes-256-cbc', $key, 0, $iv);
        return base64_encode($iv . '::' . $encrypted);
    }

    /**
     * Descriptografa a senha do certificado.
     * @param string $encryptedPassword Senha criptografada
     * @return string Senha em texto plano
     */
    public static function decryptPassword(string $encryptedPassword): string
    {
        if (empty($encryptedPassword)) {
            return '';
        }
        $key = self::getEncryptionKey();
        $data = base64_decode($encryptedPassword);
        if (strpos($data, '::') === false) {
            return ''; // formato inválido
        }
        [$iv, $encrypted] = explode('::', $data, 2);
        $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $iv);
        return $decrypted !== false ? $decrypted : '';
    }

    /**
     * Retorna a chave de criptografia para senhas de certificado.
     * Usa uma combinação do nome do banco (tenant) + salt fixo.
     * @return string
     */
    private static function getEncryptionKey(): string
    {
        $tenantDb = $_SESSION['tenant']['db_name'] ?? 'akti_default';
        $salt = 'akti_nfe_cert_v1';
        return hash('sha256', $tenantDb . $salt, true);
    }
}
