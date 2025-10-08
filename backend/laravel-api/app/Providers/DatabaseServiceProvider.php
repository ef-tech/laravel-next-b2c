<?php

namespace App\Providers;

use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // PostgreSQL接続時にタイムアウト設定を適用
        Event::listen(ConnectionEstablished::class, function ($event) {
            if ($event->connection->getDriverName() === 'pgsql') {
                $config = config('database.connections.pgsql');
                $statementTimeout = (int) ($config['statement_timeout'] ?? 60000);
                $idleTxTimeout = (int) ($config['idle_in_transaction_session_timeout'] ?? 60000);
                $lockTimeout = (int) ($config['lock_timeout'] ?? 0);

                $event->connection->statement("SET statement_timeout = {$statementTimeout}");
                $event->connection->statement("SET idle_in_transaction_session_timeout = {$idleTxTimeout}");
                $event->connection->statement("SET lock_timeout = {$lockTimeout}");
            }
        });
    }
}
