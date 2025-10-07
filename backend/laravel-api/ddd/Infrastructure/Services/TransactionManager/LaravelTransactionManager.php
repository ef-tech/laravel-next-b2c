<?php

declare(strict_types=1);

namespace Ddd\Infrastructure\Services\TransactionManager;

use Ddd\Application\Shared\Services\TransactionManager\TransactionManager;
use Illuminate\Support\Facades\DB;

final readonly class LaravelTransactionManager implements TransactionManager
{
    public function run(callable $callback): mixed
    {
        /** @phpstan-ignore argument.type, argument.templateType */
        return DB::transaction($callback);
    }
}
