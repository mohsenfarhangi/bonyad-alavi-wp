<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Core;

use Closure;
use RuntimeException;

final class Container
{
    /** @var array<string, Closure|object> */
    private array $bindings = [];

    /** @var array<string, object> */
    private array $instances = [];

    public function singleton(string $id, mixed $value): void
    {
        if (!is_object($value)) {
            throw new RuntimeException("Container binding must be an object or Closure: {$id}");
        }
        $this->bindings[$id] = $value;
    }

    public function set(string $id, object $value): void
    {
        $this->instances[$id] = $value;
    }

    public function has(string $id): bool
    {
        return isset($this->instances[$id]) || isset($this->bindings[$id]);
    }

    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (!isset($this->bindings[$id])) {
            throw new RuntimeException("Service not bound: {$id}");
        }

        $binding = $this->bindings[$id];
        $object = $binding instanceof Closure ? $binding($this) : $binding;
        $this->instances[$id] = $object;

        return $object;
    }
}
