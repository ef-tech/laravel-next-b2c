<?php

declare(strict_types=1);

namespace Ddd\Shared\Exceptions;

use App\Enums\ErrorCode;

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
     * フォールバックtype URI生成時のサニタイズ処理:
     * - RFC 3986準拠: [a-z0-9\-] のみを許可（unreserved文字セットサブセット）
     * - 正規表現 /[^a-z0-9\-]/ を使用して安全な文字セットのみを許可
     * - 小文字変換後にサニタイズ処理を適用
     * - 空文字列の場合はデフォルト値 'unknown' を使用
     * - 元のエラーコードは error_code フィールドで保持（トレーサビリティ確保）
     *
     * 参考: PR #141 Codexレビュー指摘（セキュリティ強化）
     *
     * @return array<string, mixed> RFC 7807形式の配列
     */
    public function toProblemDetails(): array
    {
        $errorCode = $this->getErrorCode();

        // ErrorCode enum定義済みエラーの場合はサニタイズ処理を実行しない（既存動作維持）
        $typeUri = ErrorCode::fromString($errorCode)?->getType();

        // フォールバックURI生成時のみサニタイズ処理を適用
        if ($typeUri === null) {
            // RFC 3986準拠のサニタイズ処理: [a-z0-9\-] のみを許可
            $sanitized = preg_replace('/[^a-z0-9\-]/', '', strtolower($errorCode));
            // 空文字列の場合はデフォルト値 'unknown' を使用
            $sanitized = $sanitized !== '' ? $sanitized : 'unknown';
            $typeUri = config('app.url').'/errors/'.$sanitized;
        }

        return [
            'type' => $typeUri,
            'title' => $this->getTitle(),
            'status' => $this->getStatusCode(),
            'detail' => $this->getMessage(),
            'error_code' => $errorCode, // 元のエラーコードを保持（サニタイズ前の値）
            'trace_id' => request()->header('X-Request-ID'),
            'instance' => request()->getRequestUri(),
            'timestamp' => now()->toIso8601ZuluString(),
        ];
    }
}
