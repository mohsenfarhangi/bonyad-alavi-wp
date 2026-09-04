<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions;

/**
 * Mutable state shared by actions that belong to one ActionManager execution.
 * It lets actions pass safe scalar/object identifiers to later actions without
 * mutating the submission payload or introducing hard-coded coupling.
 */
final class ActionRuntime
{
    /** @var array<string,mixed> */
    private array $values = [];
    /** @var list<string> */
    private array $events = [];
    /** @var list<string> */
    private array $executedActionKeys = [];
    private string $redirectUrl = '';

    public function __construct(private readonly bool $retry = false) {}

    public function isRetry(): bool
    {
        return $this->retry;
    }

    public function set(string $key, mixed $value): void
    {
        $key = sanitize_key($key);
        if ($key === '') return;
        $this->values[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[sanitize_key($key)] ?? $default;
    }

    /** @return array<string,mixed> */
    public function all(): array
    {
        return $this->values;
    }

    public function setRedirect(string $url): void
    {
        // First effective Redirect wins for the whole chain.
        if ($this->redirectUrl === '' && $url !== '') $this->redirectUrl = $url;
    }

    public function redirectUrl(): string
    {
        return $this->redirectUrl;
    }

    public function emit(string $eventKey): void
    {
        $eventKey = trim($eventKey);
        if ($eventKey === '' || in_array($eventKey, $this->events, true)) return;
        $this->events[] = $eventKey;
    }

    /** @return list<string> */
    public function emittedEvents(): array
    {
        return $this->events;
    }

    public function recordExecution(string $actionKey): void
    {
        if ($actionKey !== '' && !in_array($actionKey, $this->executedActionKeys, true)) {
            $this->executedActionKeys[] = $actionKey;
        }
    }

    /** @return list<string> */
    public function executedActionKeys(): array
    {
        return $this->executedActionKeys;
    }
}
