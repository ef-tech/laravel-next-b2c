<?php

declare(strict_types=1);

namespace Ddd\Shared\Traits;

trait RecordsDomainEvents
{
    /** @var array<int, object> */
    private array $domainEvents = [];

    /**
     * Record a domain event.
     */
    protected function recordThat(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * Pull all recorded domain events and clear the internal collection.
     *
     * @return array<int, object>
     */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
