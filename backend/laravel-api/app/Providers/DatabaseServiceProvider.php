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
                $statementTimeout = (int) env('DB_STATEMENT_TIMEOUT', 60000);
                $idleTxTimeout = (int) env('DB_IDLE_TX_TIMEOUT', 60000);
                $lockTimeout = (int) env('DB_LOCK_TIMEOUT', 0);

                $event->connection->statement("SET statement_timeout = {$statementTimeout}");
                $event->connection->statement("SET idle_in_transaction_session_timeout = {$idleTxTimeout}");
                $event->connection->statement("SET lock_timeout = {$lockTimeout}");
            }
        });
    }
}
