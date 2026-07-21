<?php

declare(strict_types=1);



namespace CodeIgniter\Cache\Handlers;

use CodeIgniter\Exceptions\BadMethodCallException;
use CodeIgniter\Exceptions\CriticalError;
use CodeIgniter\I18n\Time;
use Config\Cache;
use Exception;
use Memcache;
use Memcached;


class MemcachedHandler extends BaseHandler
{
    
    protected $memcached;

    
    protected $config = [
        'host'   => '127.0.0.1',
        'port'   => 11211,
        'weight' => 1,
        'raw'    => false,
    ];

    
    public function __construct(Cache $config)
    {
        $this->prefix = $config->prefix;

        $this->config = array_merge($this->config, $config->memcached);
    }

    public function initialize(): void
    {
        try {
            if (class_exists(Memcached::class)) {
                $this->memcached = new Memcached();

                if ($this->config['raw']) {
                    $this->memcached->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
                }

                $this->memcached->addServer(
                    $this->config['host'],
                    $this->config['port'],
                    $this->config['weight'],
                );

                $stats = $this->memcached->getStats();

                
                
                if (! is_array($stats) || ! isset($stats[$this->config['host'] . ':' . $this->config['port']])) {
                    throw new CriticalError('Cache: Memcached connection failed.');
                }
            } elseif (class_exists(Memcache::class)) {
                $this->memcached = new Memcache();

                if (! $this->memcached->connect($this->config['host'], $this->config['port'])) {
                    throw new CriticalError('Cache: Memcache connection failed.');
                }

                $this->memcached->addServer(
                    $this->config['host'],
                    $this->config['port'],
                    true,
                    $this->config['weight'],
                );
            } else {
                throw new CriticalError('Cache: Not support Memcache(d) extension.');
            }
        } catch (Exception $e) {
            throw new CriticalError('Cache: Memcache(d) connection refused (' . $e->getMessage() . ').', $e->getCode(), $e);
        }
    }

    public function get(string $key): mixed
    {
        $data = [];
        $key  = static::validateKey($key, $this->prefix);

        if ($this->memcached instanceof Memcached) {
            $data = $this->memcached->get($key);

            
            if ($this->memcached->getResultCode() === Memcached::RES_NOTFOUND) {
                return null;
            }
        } elseif ($this->memcached instanceof Memcache) {
            $flags = false;
            $data  = $this->memcached->get($key, $flags);

            
            if ($flags === false) {
                return null;
            }
        }

        return is_array($data) ? $data[0] : $data;
    }

    public function save(string $key, mixed $value, int $ttl = 60): bool
    {
        $key = static::validateKey($key, $this->prefix);

        if (! $this->config['raw']) {
            $value = [
                $value,
                Time::now()->getTimestamp(),
                $ttl,
            ];
        }

        if ($this->memcached instanceof Memcached) {
            return $this->memcached->set($key, $value, $ttl);
        }

        if ($this->memcached instanceof Memcache) {
            return $this->memcached->set($key, $value, 0, $ttl);
        }

        return false;
    }

    public function delete(string $key): bool
    {
        $key = static::validateKey($key, $this->prefix);

        return $this->memcached->delete($key);
    }

    public function deleteMatching(string $pattern): never
    {
        throw new BadMethodCallException('The deleteMatching method is not implemented for Memcached. You must select File, Redis or Predis handlers to use it.');
    }

    public function increment(string $key, int $offset = 1): false|int
    {
        if (! $this->config['raw']) {
            return false;
        }

        $key = static::validateKey($key, $this->prefix);

        return $this->memcached->increment($key, $offset, $offset, 60);
    }

    public function decrement(string $key, int $offset = 1): false|int
    {
        if (! $this->config['raw']) {
            return false;
        }

        $key = static::validateKey($key, $this->prefix);

        

        return $this->memcached->decrement($key, $offset, $offset, 60);
    }

    public function clean(): bool
    {
        return $this->memcached->flush();
    }

    public function getCacheInfo(): array|false
    {
        return $this->memcached->getStats();
    }

    public function getMetaData(string $key): ?array
    {
        $key    = static::validateKey($key, $this->prefix);
        $stored = $this->memcached->get($key);

        
        if (! is_array($stored) || count($stored) !== 3) {
            return null;
        }

        [$data, $time, $limit] = $stored;

        return [
            'expire' => $limit > 0 ? $time + $limit : null,
            'mtime'  => $time,
            'data'   => $data,
        ];
    }

    public function isSupported(): bool
    {
        return extension_loaded('memcached') || extension_loaded('memcache');
    }

    public function ping(): bool
    {
        $version = $this->memcached->getVersion();

        if ($this->memcached instanceof Memcached) {
            
            if (! is_array($version)) {
                return false;
            }

            $serverKey = $this->config['host'] . ':' . $this->config['port'];

            return isset($version[$serverKey]) && $version[$serverKey] !== false;
        }

        if ($this->memcached instanceof Memcache) {
            
            return is_string($version) && $version !== '';
        }

        return false;
    }

    public function reconnect(): bool
    {
        if ($this->memcached instanceof Memcached) {
            $this->memcached->quit();
        } elseif ($this->memcached instanceof Memcache) {
            $this->memcached->close();
        }

        try {
            $this->initialize();

            return true;
        } catch (CriticalError $e) {
            log_message('error', 'Cache: Memcached reconnection failed: ' . $e->getMessage());

            return false;
        }
    }
}
