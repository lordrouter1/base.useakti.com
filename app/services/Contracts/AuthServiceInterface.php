<?php

namespace Akti\Services\Contracts;

/**
 * Interface AuthServiceInterface.
 */
interface AuthServiceInterface
{
    /**
     * Attempt login.
     *
     * @param string $email Endereço de email
     * @param string $password Senha
     * @param string $ip Ip
     * @param string $postedTenant Posted tenant
     * @param string $resolvedTenant Resolved tenant
     * @param string|null $captchaResponse Captcha response
     * @return array
     */
    public function attemptLogin(
        string $email,
        string $password,
        string $ip,
        string $postedTenant,
        string $resolvedTenant,
        ?string $captchaResponse = null
    ): array;
}
