<?php

declare(strict_types=1);

namespace Ddd\Shared\Exceptions;

use Exception;

/**
 * Application Layer Exception
 *
 * Application層のユースケース実行エラーを表現する基底クラス。
 * HTTP 400番台ステータスコード（403 Forbidden、404 Not Found等）を返却する。
 *
 * Requirements:
 * - 2.2: Application層でユースケース実行エラーが発生する時、ApplicationExceptionのサブクラスが例外を投げること
 * - 2.4: ApplicationException生成時、getErrorCode()メソッドで独自エラーコードを返却すること
 */
class ApplicationException extends Exception
{
    /**
     * @var int HTTPステータスコード（デフォルト: 400 Bad Request）
     */
    protected int $statusCode = 400;

    /**
     * @var string エラーコード（DOMAIN-SUBDOMAIN-CODE形式、デフォルト: APP-0001）
     */
    protected string $errorCode = 'APP-0001';

    /**
     * Get the HTTP status code for this exception.
     *
     * @return int HTTPステータスコード（403, 404等）
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get a machine-readable error code.
     *
     * @return string DOMAIN-SUBDOMAIN-CODE形式（例: APP-RESOURCE-4001）
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
