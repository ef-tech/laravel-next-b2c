<?php

declare(strict_types=1);

namespace Ddd\Domain\Admin\Exceptions;

use RuntimeException;

final class AdminNotFoundException extends RuntimeException
{
    public function __construct(string $message = 'Admin not found')
    {
        parent::__construct($message);
    }
}
