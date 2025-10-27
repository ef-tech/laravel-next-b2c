<?php

declare(strict_types=1);

namespace Ddd\Domain\Admin\Exceptions;

use RuntimeException;

final class AdminNotFoundException extends RuntimeException
{
    private string $errorCode = 'ADMIN_NOT_FOUND';

    public function __construct(string $message = 'Admin not found')
    {
        parent::__construct($message);
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
        return 404;
    }
}
