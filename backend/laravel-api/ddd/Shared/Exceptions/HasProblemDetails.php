<?php

declare(strict_types=1);

namespace Ddd\Shared\Exceptions;

/**
 * RFC 7807 Problem Details機能を提供するトレイト
 *
 * 例外クラスにRFC 7807形式のレスポンス生成機能を追加する
 */
trait HasProblemDetails
{
    /**
     * Get the HTTP status code for this exception.
     *
     * @return int HTTPステータスコード
     */
    abstract public function getStatusCode(): int;

    /**
     * Get a machine-readable error code.
     *
     * @return string DOMAIN-SUBDOMAIN-CODE形式
     */
    abstract public function getErrorCode(): string;

    /**
     * Get a human-readable error title.
     *
     * @return string エラータイトル
     */
    abstract protected function getTitle(): string;

    /**
     * Get the exception message.
     *
     * @return string 例外メッセージ
     */
    abstract public function getMessage(): string;

    /**
     * Convert the exception to RFC 7807 Problem Details format.
     *
     * @return array<string, mixed> RFC 7807形式の配列
     */
    public function toProblemDetails(): array
    {
        return [
            'type' => config('app.url').'/errors/'.strtolower($this->getErrorCode()),
            'title' => $this->getTitle(),
            'status' => $this->getStatusCode(),
            'detail' => $this->getMessage(),
            'error_code' => $this->getErrorCode(),
            'trace_id' => request()->header('X-Request-ID'),
            'instance' => request()->getRequestUri(),
            'timestamp' => now()->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
