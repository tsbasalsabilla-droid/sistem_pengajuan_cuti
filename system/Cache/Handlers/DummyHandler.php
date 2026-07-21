<?php

declare(strict_types=1);



namespace CodeIgniter\Cache\Handlers;

use Closure;


class DummyHandler extends BaseHandler
{
    public function initialize(): void
    {
    }

    public function get(string $key): mixed
    {
        return null;
    }

    public function remember(string $key, int $ttl, Closure $callback): mixed
    {
        return null;
    }

    public function save(string $key, mixed $value, int $ttl = 60): bool
    {
        return true;
    }

    public function delete(string $key): bool
    {
        return true;
    }

    public function deleteMatching(string $pattern): int
    {
        return 0;
    }

    public function increment(string $key, int $offset = 1): bool
    {
        return true;
    }

    public function decrement(string $key, int $offset = 1): bool
    {
        return true;
    }

    public function clean(): bool
    {
        return true;
    }

    public function getCacheInfo(): ?array
    {
        return null;
    }

    public function getMetaData(string $key): ?array
    {
        return null;
    }

    public function isSupported(): bool
    {
        return true;
    }
}
