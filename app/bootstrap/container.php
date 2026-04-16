<?php

/**
 * Bootstrap do Container PSR-11.
 *
 * Registra todos os bindings e singletons necessários
 * para resolução automática de dependências.
 *
 * @return \Akti\Core\Container Container configurado
 */

use Akti\Core\Container;

$container = new Container();

// ── PDO como singleton (via Database existente) ──
/**
 * Class Unknown.
 */
$container->singleton(\PDO::class, function () {
    return \Database::getInstance();
});

return $container;
