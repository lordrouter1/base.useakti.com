<?php
require __DIR__ . '/../vendor/autoload.php';
$token = \Akti\Utils\JwtHelper::encode(
    ['sub' => 1, 'tenant_id' => 'teste'],
    'dev-only-secret',
    3600
);
echo $token . "\n";
