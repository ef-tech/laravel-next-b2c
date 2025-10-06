<?php

declare(strict_types=1);

namespace Ddd\Infrastructure\Services\TransactionManager;

use Illuminate\Support\Facades\DB;

final readonly class LaravelTransactionManager implements TransactionManager
{
    public function run(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
