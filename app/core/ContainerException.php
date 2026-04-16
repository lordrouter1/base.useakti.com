<?php

namespace Akti\Core;

use Psr\Container\ContainerExceptionInterface;

/**
 * Exceção lançada quando ocorre erro no container de dependências.
 */
class ContainerException extends \RuntimeException implements ContainerExceptionInterface
{
}
