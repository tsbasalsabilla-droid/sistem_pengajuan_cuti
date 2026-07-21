<?php

declare(strict_types=1);



namespace CodeIgniter\Cookie;

use ArrayIterator;
use CodeIgniter\Cookie\Exceptions\CookieException;
use Countable;
use IteratorAggregate;
use Traversable;


class CookieStore implements Countable, IteratorAggregate
{
    
    protected $cookies = [];

    
    public static function fromCookieHeaders(array $headers, bool $raw = false)
    {
        
        $cookies = array_filter(array_map(static function (string $header) use ($raw) {
            try {
                return Cookie::fromHeaderString($header, $raw);
            } catch (CookieException $e) {
                log_message('error', (string) $e);

                return false;
            }
        }, $headers));

        return new static($cookies);
    }

    
    final public function __construct(array $cookies)
    {
        $this->validateCookies($cookies);

        foreach ($cookies as $cookie) {
            $this->cookies[$cookie->getId()] = $cookie;
        }
    }

    
    public function has(string $name, string $prefix = '', ?string $value = null): bool
    {
        $name = $prefix . $name;

        foreach ($this->cookies as $cookie) {
            if ($cookie->getPrefixedName() !== $name) {
                continue;
            }

            if ($value === null) {
                return true; 
            }

            return $cookie->getValue() === $value;
        }

        return false;
    }

    
    public function get(string $name, string $prefix = ''): Cookie
    {
        $name = $prefix . $name;

        foreach ($this->cookies as $cookie) {
            if ($cookie->getPrefixedName() === $name) {
                return $cookie;
            }
        }

        throw CookieException::forUnknownCookieInstance([$name, $prefix]);
    }

    
    public function put(Cookie $cookie)
    {
        $store = clone $this;

        $store->cookies[$cookie->getId()] = $cookie;

        return $store;
    }

    
    public function remove(string $name, string $prefix = '')
    {
        $default = Cookie::setDefaults();

        $id = implode(';', [$prefix . $name, $default['path'], $default['domain']]);

        $store = clone $this;

        foreach (array_keys($store->cookies) as $index) {
            if ($index === $id) {
                unset($store->cookies[$index]);
            }
        }

        return $store;
    }

    
    public function display(): array
    {
        return $this->cookies;
    }

    
    public function clear(): void
    {
        $this->cookies = [];
    }

    
    public function count(): int
    {
        return count($this->cookies);
    }

    
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->cookies);
    }

    
    protected function validateCookies(array $cookies): void
    {
        foreach ($cookies as $index => $cookie) {
            $type = get_debug_type($cookie);

            if (! $cookie instanceof Cookie) {
                throw CookieException::forInvalidCookieInstance([static::class, Cookie::class, $type, $index]);
            }
        }
    }
}
