<?php

/**
 * Configuração de e-mail SMTP
 * Segue o mesmo padrão de database.php (getenv com fallback)
 */

return [
    'host'       => getenv('MAIL_HOST') ?: 'localhost',
    'port'       => (int) (getenv('MAIL_PORT') ?: 587),
    'username'   => getenv('MAIL_USERNAME') ?: '',
    'password'   => getenv('MAIL_PASSWORD') ?: '',
    'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',
    'from_email' => getenv('MAIL_FROM_EMAIL') ?: 'noreply@akti.com.br',
    'from_name'  => getenv('MAIL_FROM_NAME') ?: 'Akti Sistema',
];
