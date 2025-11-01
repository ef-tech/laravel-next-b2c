<?php

declare(strict_types=1);

namespace Ddd\Shared\Exceptions;

use Exception;

/**
 * Infrastructure Layer Exception
 *
 * Infrastructure層の外部システムエラーを表現する基底クラス。
 * HTTP 500番台ステータスコード（502 Bad Gateway、503 Service Unavailable、504 Gateway Timeout等）を返却する。
 *
 * Requirements:
 * - 2.3: Infrastructure層で外部システムエラーが発生する時、InfrastructureExceptionのサブクラスが例外を投げること
 * - 2.4: InfrastructureException生成時、getErrorCode()メソッドで独自エラーコードを返却すること
 */
class InfrastructureException extends Exception
{
    /**
     * @var int HTTPステータスコード（デフォルト: 503 Service Unavailable）
     */
    protected int $statusCode = 503;

    /**
     * @var string エラーコード（DOMAIN-SUBDOMAIN-CODE形式、デフォルト: INFRA-0001）
     */
    protected string $errorCode = 'INFRA-0001';

    /**
     * Get the HTTP status code for this exception.
     *
     * @return int HTTPステータスコード（502, 503, 504等）
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get a machine-readable error code.
     *
     * @return string DOMAIN-SUBDOMAIN-CODE形式（例: INFRA-DB-5001）
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Convert the exception to RFC 7807 Problem Details format.
     *
     * @return array<string, mixed> RFC 7807形式の配列
     */
    public function toProblemDetails(): array
    {
        return [
            'type' => config('app.url').'/errors/'.strtolower($this->getErrorCode()),
            'title' => class_basename($this),
            'status' => $this->getStatusCode(),
            'detail' => $this->getMessage(),
            'error_code' => $this->getErrorCode(),
            'trace_id' => request()->header('X-Request-ID'),
            'instance' => request()->getRequestUri(),
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
