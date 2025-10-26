<?php

declare(strict_types=1);

namespace Ddd\Domain\Admin\Exceptions;

use DomainException;

class AccountDisabledException extends DomainException
{
    public function __construct()
    {
        parent::__construct('アカウントが無効化されています');
    }
}
