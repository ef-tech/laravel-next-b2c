<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

final class DddServiceProvider extends ServiceProvider
{
    /**
     * Register DDD layer services.
     */
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(
            \Ddd\Domain\User\Repositories\UserRepository::class,
            \Ddd\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRepository::class
        );

        // Infrastructure services (singleton)
        $this->app->singleton(
            \Ddd\Application\Shared\Services\TransactionManager\TransactionManager::class,
            \Ddd\Infrastructure\Services\TransactionManager\LaravelTransactionManager::class
        );

        $this->app->singleton(
            \Ddd\Application\Shared\Services\Events\EventBus::class,
            \Ddd\Infrastructure\Services\Events\LaravelEventBus::class
        );
    }

    /**
     * Bootstrap DDD layer services.
     */
    public function boot(): void
    {
        //
    }
}
