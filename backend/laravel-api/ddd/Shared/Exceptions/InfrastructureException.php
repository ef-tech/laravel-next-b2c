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
abstract class InfrastructureException extends Exception
{
    use HasProblemDetails;

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
     * Get a human-readable error title.
     *
     * @return string エラータイトル（サブクラスで実装）
     */
    abstract protected function getTitle(): string;
}
