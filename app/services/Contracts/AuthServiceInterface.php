<?php

namespace Akti\Services\Contracts;

interface AuthServiceInterface
{
    public function attemptLogin(
        string $email,
        string $password,
        string $ip,
        string $postedTenant,
        string $resolvedTenant,
        ?string $captchaResponse = null
    ): array;
}
