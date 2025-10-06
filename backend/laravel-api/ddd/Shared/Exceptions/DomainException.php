<?php

declare(strict_types=1);

namespace Ddd\Shared\Exceptions;

use Exception;

abstract class DomainException extends Exception
{
    /**
     * Get the HTTP status code for this exception.
     */
    abstract public function getStatusCode(): int;

    /**
     * Get a machine-readable error code.
     */
    abstract public function getErrorCode(): string;
}
