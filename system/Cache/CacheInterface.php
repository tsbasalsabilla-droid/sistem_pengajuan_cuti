<?php

declare(strict_types=1);



namespace CodeIgniter\Cache;

use Closure;

interface CacheInterface
{
    
    public function initialize(): void;

    
    public function get(string $key): mixed;

    
    public function save(string $key, mixed $value, int $ttl = 60): bool;

    
    public function remember(string $key, int $ttl, Closure $callback): mixed;

    
    public function delete(string $key): bool;

    
    public function deleteMatching(string $pattern): int;

    
    public function increment(string $key, int $offset = 1): bool|int;

    
    public function decrement(string $key, int $offset = 1): bool|int;

    
    public function clean(): bool;

    
    public function getCacheInfo(): array|false|object|null;

    
    public function getMetaData(string $key): ?array;

    
    public function isSupported(): bool;
}
