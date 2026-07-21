<?php

declare(strict_types=1);



namespace CodeIgniter\Cache\Handlers;

use CodeIgniter\Exceptions\BadMethodCallException;
use CodeIgniter\I18n\Time;
use Config\Cache;


class WincacheHandler extends BaseHandler
{
    
    public function __construct(Cache $config)
    {
        $this->prefix = $config->prefix;
    }

    public function initialize(): void
    {
    }

    public function get(string $key): mixed
    {
        $key     = static::validateKey($key, $this->prefix);
        $success = false;

        $data = wincache_ucache_get($key, $success);

        
        return $success ? $data : null;
    }

    public function save(string $key, mixed $value, int $ttl = 60): bool
    {
        $key = static::validateKey($key, $this->prefix);

        return wincache_ucache_set($key, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        $key = static::validateKey($key, $this->prefix);

        return wincache_ucache_delete($key);
    }

    public function deleteMatching(string $pattern): never
    {
        throw new BadMethodCallException('The deleteMatching method is not implemented for Wincache. You must select File, Redis or Predis handlers to use it.');
    }

    public function increment(string $key, int $offset = 1): bool
    {
        $key = static::validateKey($key, $this->prefix);

        $result = wincache_ucache_inc($key, $offset);

        return $result !== false;
    }

    public function decrement(string $key, int $offset = 1): bool
    {
        $key = static::validateKey($key, $this->prefix);

        $result = wincache_ucache_dec($key, $offset);

        return $result !== false;
    }

    public function clean(): bool
    {
        return wincache_ucache_clear();
    }

    public function getCacheInfo(): array|false
    {
        return wincache_ucache_info(true);
    }

    public function getMetaData(string $key): ?array
    {
        $key = static::validateKey($key, $this->prefix);

        if ($stored = wincache_ucache_info(false, $key)) {
            $age      = $stored['ucache_entries'][1]['age_seconds'];
            $ttl      = $stored['ucache_entries'][1]['ttl_seconds'];
            $hitcount = $stored['ucache_entries'][1]['hitcount'];

            return [
                'expire'   => $ttl > 0 ? Time::now()->getTimestamp() + $ttl : null,
                'hitcount' => $hitcount,
                'age'      => $age,
                'ttl'      => $ttl,
            ];
        }

        return null;
    }

    public function isSupported(): bool
    {
        return extension_loaded('wincache') && ini_get('wincache.ucenabled');
    }
}
