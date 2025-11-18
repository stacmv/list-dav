<?php

declare(strict_types=1);

namespace ListDAV\Core;

use Closure;
use Psr\Container\ContainerInterface;
use RuntimeException;

class Container implements ContainerInterface
{
    /** @var array<string, Closure|object> */
    private array $bindings = [];

    /** @var array<string, object> */
    private array $instances = [];

    /**
     * @param Closure|object $concrete
     */
    public function bind(string $id, Closure|object $concrete): void
    {
        $this->bindings[$id] = $concrete;
    }

    public function singleton(string $id, Closure $concrete): void
    {
        $this->bindings[$id] = function () use ($id, $concrete) {
            if (!isset($this->instances[$id])) {
                $this->instances[$id] = $concrete($this);
            }
            return $this->instances[$id];
        };
    }

    public function instance(string $id, object $instance): void
    {
        $this->instances[$id] = $instance;
    }

    public function get(string $id): mixed
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (!isset($this->bindings[$id])) {
            throw new RuntimeException("No binding found for: {$id}");
        }

        $concrete = $this->bindings[$id];

        if ($concrete instanceof Closure) {
            return $concrete($this);
        }

        return $concrete;
    }

    public function has(string $id): bool
    {
        return isset($this->bindings[$id]) || isset($this->instances[$id]);
    }

    /**
     * @template T
     * @param class-string<T> $id
     * @return T
     */
    public function make(string $id): object
    {
        /** @var T */
        return $this->get($id);
    }
}
