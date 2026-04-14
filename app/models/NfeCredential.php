<?php
namespace Akti\Models;

use Akti\Core\Log;

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
    public function __construct(\PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Busca credenciais SEFAZ ativas.
     * Suporta multi-filial: se filialId for informado, busca pela filial.
     * Caso contrário, busca a primeira credencial ativa.
     *
     * @param int|null $filialId ID da filial (ou null para a principal/ativa)
     * @return array|false
     */
    public function get(?int $filialId = null): array|false
    {
        if ($filialId !== null) {
            $q = "SELECT * FROM {$this->table} WHERE filial_id = :filial AND is_active = 1 LIMIT 1";
            $s = $this->conn->prepare($q);
            $s->execute([':filial' => $filialId]);
            $result = $s->fetch(PDO::FETCH_ASSOC);
            if ($result) return $result;
        }

        // Fallback: buscar a primeira credencial ativa (compatibilidade)
        $q = "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY id ASC LIMIT 1";
        $s = $this->conn->prepare($q);
        $s->execute();
        $result = $s->fetch(PDO::FETCH_ASSOC);

        // Último fallback: id = 1 (legado)
        if (!$result) {
            $q = "SELECT * FROM {$this->table} WHERE id = 1 LIMIT 1";
            $s = $this->conn->prepare($q);
            $s->execute();
            return $s->fetch(PDO::FETCH_ASSOC);
        }

        return $result;
    }

    /**
     * Lista todas as credenciais (filiais) cadastradas.
     * @return array
     */
    public function listAll(): array
    {
        $q = "SELECT id, filial_id, razao_social, nome_fantasia, cnpj, uf, is_active,
                     certificate_expiry, environment
              FROM {$this->table} ORDER BY id ASC";
        $s = $this->conn->prepare($q);
        $s->execute();
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Atualiza credenciais SEFAZ.
     * Suporta multi-filial: se $id for informado, atualiza a credencial específica.
     * Caso contrário, atualiza a credencial id=1 (legado/compatibilidade).
     *
     * @param array    $data Campos a atualizar
     * @param int|null $id   ID da credencial (null = id 1 para compatibilidade)
     * @return bool
     */
    public function update(array $data, ?int $id = null): bool
    {
        $allowedFields = [
            'cnpj', 'ie', 'razao_social', 'nome_fantasia', 'crt',
            'uf', 'cod_municipio', 'municipio',
            'logradouro', 'numero', 'bairro', 'cep', 'complemento', 'telefone',
            'certificate_path', 'certificate_password', 'certificate_expiry',
            'environment', 'serie_nfe', 'proximo_numero',
            'serie_nfce', 'proximo_numero_nfce',
            'csc_id', 'csc_token',
            'tp_emis', 'contingencia_justificativa', 'contingencia_ativada_em',
            'ultimo_nsu', 'filial_id', 'is_active',
        ];

        $fields = [];
        $credentialId = $id ?? 1;
        $params = [':id' => $credentialId];

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
                'id'     => $credentialId,
                'fields' => array_diff_key($data, array_flip(['certificate_password'])),
            ]));
        }

        return $result;
    }

    /**
     * Retorna o próximo número de NF-e com lock (FOR UPDATE) para evitar duplicidade.
     * Suporta multi-filial via $credentialId.
     * Deve ser chamado dentro de uma transação.
     *
     * @param int $credentialId ID da credencial (default: 1)
     * @return int
     */
    public function getNextNumberForUpdate(int $credentialId = 1): int
    {
        $q = "SELECT proximo_numero FROM {$this->table} WHERE id = :id FOR UPDATE";
        $s = $this->conn->prepare($q);
        $s->execute([':id' => $credentialId]);
        return (int) $s->fetchColumn();
    }

    /**
     * Incrementa o próximo número de NF-e.
     * Suporta multi-filial via $credentialId.
     * Deve ser chamado dentro de uma transação após getNextNumberForUpdate().
     *
     * @param int $credentialId ID da credencial (default: 1)
     * @return bool
     */
    public function incrementNextNumber(int $credentialId = 1): bool
    {
        $q = "UPDATE {$this->table} SET proximo_numero = proximo_numero + 1 WHERE id = :id";
        $s = $this->conn->prepare($q);
        return $s->execute([':id' => $credentialId]);
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
     *
     * Prioridade:
     * 1. Variável de ambiente APP_KEY (recomendado para produção)
     * 2. Arquivo .env na raiz do projeto
     * 3. Fallback: hash(db_name + salt) — inseguro, emite warning
     *
     * @return string Chave binária de 32 bytes
     */
    private static function getEncryptionKey(): string
    {
        // 1. Tentar variável de ambiente APP_KEY
        $appKey = getenv('APP_KEY');
        if (!empty($appKey)) {
            return hash('sha256', $appKey, true);
        }

        // 2. Tentar arquivo .env na raiz do projeto
        $envFile = (defined('AKTI_BASE_PATH') ? AKTI_BASE_PATH : __DIR__ . '/../../') . '.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if (strpos($line, '#') === 0) continue; // ignorar comentários
                if (strpos($line, 'APP_KEY=') === 0) {
                    $value = trim(substr($line, 8));
                    $value = trim($value, '"\'');
                    if (!empty($value)) {
                        return hash('sha256', $value, true);
                    }
                }
            }
        }

        // 3. Fallback inseguro — emitir warning em log
        $tenantDb = $_SESSION['tenant']['db_name'] ?? 'akti_default';
        $salt = 'akti_nfe_cert_v1';

        Log::warning('NfeCredential: APP_KEY não definida. '
            . 'A chave de criptografia do certificado está usando fallback inseguro. '
            . 'Defina APP_KEY na variável de ambiente ou no arquivo .env para maior segurança.');

        return hash('sha256', $tenantDb . $salt, true);
    }
}
