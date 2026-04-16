<?php

namespace Akti\Core;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Exceção lançada quando um recurso não é encontrado.
 */
class NotFoundException extends \RuntimeException implements NotFoundExceptionInterface
{
}
