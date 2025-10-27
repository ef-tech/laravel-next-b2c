<?php

declare(strict_types=1);

namespace Ddd\Domain\Admin\Exceptions;

use DomainException;

class InvalidCredentialsException extends DomainException
{
    private string $errorCode = 'AUTH.INVALID_CREDENTIALS';

    public function __construct()
    {
        parent::__construct('メールアドレスまたはパスワードが正しくありません');
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

    /**
     * HTTPステータスコードを取得
     */
    public function getStatusCode(): int
    {
        return 401;
    }
}
