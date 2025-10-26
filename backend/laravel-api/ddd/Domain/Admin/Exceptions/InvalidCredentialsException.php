<?php

declare(strict_types=1);

namespace Ddd\Domain\Admin\Exceptions;

use DomainException;

class InvalidCredentialsException extends DomainException
{
    public function __construct()
    {
        parent::__construct('メールアドレスまたはパスワードが正しくありません');
    }
}
