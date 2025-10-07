<?php

declare(strict_types=1);

namespace Ddd\Application\Shared\Services\TransactionManager;

interface TransactionManager
{
    /**
     * Execute a callback within a database transaction.
     *
     * @return mixed The result of the callback
     */
    public function run(callable $callback): mixed;
}
