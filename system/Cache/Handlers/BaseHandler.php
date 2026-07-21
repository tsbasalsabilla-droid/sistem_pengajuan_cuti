<?php

declare(strict_types=1);



namespace CodeIgniter\Cache\Handlers;

use Closure;
use CodeIgniter\Cache\CacheInterface;
use CodeIgniter\Exceptions\InvalidArgumentException;
use Config\Cache;


abstract class BaseHandler implements CacheInterface
{
    
    public const RESERVED_CHARACTERS = '{}()/\@:';

    
    public const MAX_KEY_LENGTH = PHP_INT_MAX;

    
    protected $prefix;

    
    public static function validateKey($key, $prefix = ''): string
    {
        if (! is_string($key)) {
            throw new InvalidArgumentException('Cache key must be a string');
        }
        if ($key === '') {
            throw new InvalidArgumentException('Cache key cannot be empty.');
        }

        $reserved = config(Cache::class)->reservedCharacters;

        if ($reserved !== '' && strpbrk($key, $reserved) !== false) {
            throw new InvalidArgumentException('Cache key contains reserved characters ' . $reserved);
        }

        
        return strlen($prefix . $key) > static::MAX_KEY_LENGTH ? $prefix . md5($key) : $prefix . $key;
    }

    public function remember(string $key, int $ttl, Closure $callback): mixed
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $this->save($key, $value = $callback(), $ttl);

        return $value;
    }

    
    public function ping(): bool
    {
        return true;
    }

    
    public function reconnect(): bool
    {
        return true;
    }
}
