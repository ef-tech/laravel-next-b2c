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
        // Repository bindings will be added here
        // TransactionManager and EventBus bindings will be added here
    }

    /**
     * Bootstrap DDD layer services.
     */
    public function boot(): void
    {
        //
    }
}
