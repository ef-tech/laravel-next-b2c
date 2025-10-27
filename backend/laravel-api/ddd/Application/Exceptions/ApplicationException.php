<?php

declare(strict_types=1);

namespace Ddd\Application\Exceptions;

use Exception;

/**
 * Application層例外のベースクラス
 *
 * 統一エラーコード（文字列）をサポートする例外基底クラス
 */
abstract class ApplicationException extends Exception
{
    /**
     * エラーコード（文字列）
     */
    protected string $errorCode;

    /**
     * コンストラクタ
     *
     * @param  string  $message  エラーメッセージ
     * @param  string  $errorCode  エラーコード（例: AUTH.INVALID_CREDENTIALS）
     */
    public function __construct(string $message, string $errorCode)
    {
        parent::__construct($message);
        $this->errorCode = $errorCode;
    }

    /**
     * エラーコード（文字列）を取得
     *
     * 注意: PHPネイティブのgetCode()はfinalメソッドのためオーバーライド不可
     * 文字列エラーコードを取得する際はgetErrorCode()を使用すること
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}
