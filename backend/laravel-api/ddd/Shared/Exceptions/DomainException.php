<?php

declare(strict_types=1);

namespace Ddd\Shared\Exceptions;

use App\Enums\ErrorCode;
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

    /**
     * Get a human-readable error title.
     */
    abstract protected function getTitle(): string;

    /**
     * Convert the exception to RFC 7807 Problem Details format.
     *
     * @return array<string, mixed> RFC 7807形式の配列
     */
    public function toProblemDetails(): array
    {
        return [
            'type' => ErrorCode::fromString($this->getErrorCode())?->getType()
                ?? config('app.url').'/errors/'.strtolower($this->getErrorCode()),
            'title' => $this->getTitle(),
            'status' => $this->getStatusCode(),
            'detail' => $this->getMessage(),
            'error_code' => $this->getErrorCode(),
            'trace_id' => request()->header('X-Request-ID'),
            'instance' => request()->getRequestUri(),
            'timestamp' => now()->toIso8601ZuluString(),
        ];
    }

    /**
     * Get the error type URI for RFC 7807.
     *
     * @deprecated Use ErrorCode::fromString()->getType() instead. Will be removed in v2.0
     *
     * @return string RFC 7807 compliant error type URI
     */
    protected function getErrorType(): string
    {
        return ErrorCode::fromString($this->getErrorCode())?->getType()
            ?? config('app.url').'/errors/'.strtolower($this->getErrorCode());
    }
}
