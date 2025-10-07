<?php

declare(strict_types=1);

namespace Ddd\Application\Shared\Services\Events;

interface EventBus
{
    /**
     * Dispatch a domain event.
     *
     * @param  object  $event  The domain event to dispatch
     * @param  bool  $afterCommit  Whether to dispatch after transaction commit
     */
    public function dispatch(object $event, bool $afterCommit = true): void;
}
