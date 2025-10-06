<?php

declare(strict_types=1);

namespace Ddd\Infrastructure\Services\Events;

use Ddd\Application\Shared\Services\Events\EventBus;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;

final readonly class LaravelEventBus implements EventBus
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {}

    public function dispatch(object $event, bool $afterCommit = true): void
    {
        if ($afterCommit) {
            DB::afterCommit(fn () => $this->dispatcher->dispatch($event));
        } else {
            $this->dispatcher->dispatch($event);
        }
    }
}
