<?php
namespace Akti\Services;

use Akti\Utils\Validator;
use PDO;

/**
 * Service: ExternalApiService
 *
 * Proxy para APIs externas (ViaCEP, BrasilAPI CNPJ) com cache em sessão.
 *
 * @package Akti\Services
 */
class ExternalApiService
{
    /** @var int Tempo de cache em segundos (1 hora) */
    private const CACHE_TTL = 3600;

    /**
     * Consulta endereço por CEP via ViaCEP.
     *
     * @param string $cep CEP (apenas dígitos)
     * @return array ['success' => bool, 'data' => array|null, 'message' => string|null, 'cached' => bool]
     */
    public function searchCep(string $cep): array
    {
        $cep = preg_replace('/\D/', '', $cep);

        if (strlen($cep) !== 8) {
            return ['success' => false, 'message' => 'CEP deve ter 8 dígitos.'];
        }

        // Verificar cache
        $cacheKey = 'cep_cache_' . $cep;
        if (isset($_SESSION[$cacheKey]) && $_SESSION[$cacheKey]['expires'] > time()) {
            return ['success' => true, 'data' => $_SESSION[$cacheKey]['data'], 'cached' => true];
        }

        $url = "https://viacep.com.br/ws/{$cep}/json/";
        $response = $this->httpGet($url, 10);

        if ($response === null) {
            return ['success' => false, 'message' => 'Não foi possível consultar o CEP.'];
        }

        $viaCep = json_decode($response, true);

        if (!$viaCep || isset($viaCep['erro'])) {
            return ['success' => false, 'message' => 'CEP não encontrado.'];
        }

        $data = [
            'zipcode'              => preg_replace('/\D/', '', $viaCep['cep'] ?? ''),
            'address_street'       => $viaCep['logradouro'] ?? '',
            'address_neighborhood' => $viaCep['bairro'] ?? '',
            'address_city'         => $viaCep['localidade'] ?? '',
            'address_state'        => $viaCep['uf'] ?? '',
            'address_ibge'         => $viaCep['ibge'] ?? '',
        ];

        // Cachear
        $_SESSION[$cacheKey] = ['data' => $data, 'expires' => time() + self::CACHE_TTL];

        return ['success' => true, 'data' => $data, 'cached' => false];
    }

    /**
     * Consulta dados de empresa por CNPJ via BrasilAPI.
     *
     * @param string $cnpj CNPJ (apenas dígitos)
     * @return array ['success' => bool, 'data' => array|null, 'message' => string|null, 'cached' => bool]
     */
    public function searchCnpj(string $cnpj): array
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);

        if (strlen($cnpj) !== 14) {
            return ['success' => false, 'message' => 'CNPJ deve ter 14 dígitos.'];
        }

        if (!Validator::isValidCnpj($cnpj)) {
            return ['success' => false, 'message' => 'CNPJ inválido.'];
        }

        // Verificar cache
        $cacheKey = 'cnpj_cache_' . $cnpj;
        if (isset($_SESSION[$cacheKey]) && $_SESSION[$cacheKey]['expires'] > time()) {
            return ['success' => true, 'data' => $_SESSION[$cacheKey]['data'], 'cached' => true];
        }

        $url = "https://brasilapi.com.br/api/cnpj/v1/{$cnpj}";
        $response = $this->httpGet($url, 15);

        if ($response === null) {
            return ['success' => false, 'message' => 'Não foi possível consultar o CNPJ.'];
        }

        $apiData = json_decode($response, true);

        if (!$apiData || isset($apiData['message'])) {
            return ['success' => false, 'message' => $apiData['message'] ?? 'CNPJ não encontrado.'];
        }

        $data = [
            'name'                 => $apiData['razao_social'] ?? '',
            'fantasy_name'         => $apiData['nome_fantasia'] ?? '',
            'document'             => $cnpj,
            'email'                => strtolower($apiData['email'] ?? ''),
            'phone'                => preg_replace('/\D/', '', $apiData['ddd_telefone_1'] ?? ''),
            'zipcode'              => preg_replace('/\D/', '', $apiData['cep'] ?? ''),
            'address_street'       => $apiData['logradouro'] ?? '',
            'address_number'       => $apiData['numero'] ?? '',
            'address_complement'   => $apiData['complemento'] ?? '',
            'address_neighborhood' => $apiData['bairro'] ?? '',
            'address_city'         => $apiData['municipio'] ?? '',
            'address_state'        => $apiData['uf'] ?? '',
        ];

        // Cachear
        $_SESSION[$cacheKey] = ['data' => $data, 'expires' => time() + self::CACHE_TTL];

        return ['success' => true, 'data' => $data, 'cached' => false];
    }

    /**
     * Executa requisição HTTP GET com timeout.
     *
     * @param string $url     URL para acessar
     * @param int    $timeout Timeout em segundos
     * @return string|null Resposta ou null em caso de falha
     */
    private function httpGet(string $url, int $timeout = 10): ?string
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => $timeout,
                'header'  => "User-Agent: Akti/1.0\r\n",
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        return $response !== false ? $response : null;
    }
}
