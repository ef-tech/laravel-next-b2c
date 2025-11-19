<?php

declare(strict_types=1);

namespace Ddd\Shared\Exceptions;

use Exception;

abstract class DomainException extends Exception
{
    use HasProblemDetails;

    /**
     * Get the HTTP status code for this exception.
     */
    abstract public function getStatusCode(): int;

    /**
     * Get a machine-readable error code.
     */
    abstract public function getErrorCode(): string;

    /**
     * Get a human-readable error title.
     */
    abstract protected function getTitle(): string;
}
