<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Events;

final class EventDispatcher
{
    /** @var array<string, array<int, callable>> */
    private array $listeners = [];

    public function listen(string $event, callable $listener): void
    {
        $this->listeners[$event][] = $listener;
    }

    public function dispatch(object|string $event, mixed ...$payload): void
    {
        $name = is_object($event) ? $event::class : $event;
        $args = is_object($event) ? [$event, ...$payload] : $payload;

        foreach ($this->listeners[$name] ?? [] as $listener) {
            $listener(...$args);
        }

        do_action('afe_event_' . sanitize_key(str_replace(['\\', '.'], '_', $name)), ...$args);
    }
}
