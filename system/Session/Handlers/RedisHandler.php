<?php

declare(strict_types=1);



namespace CodeIgniter\Session\Handlers;

use CodeIgniter\I18n\Time;
use CodeIgniter\Session\Exceptions\SessionException;
use CodeIgniter\Session\PersistsConnection;
use Config\Session as SessionConfig;
use Redis;
use RedisException;


class RedisHandler extends BaseHandler
{
    use PersistsConnection;

    private const DEFAULT_PORT     = 6379;
    private const DEFAULT_PROTOCOL = 'tcp';

    
    protected $redis;

    
    protected $keyPrefix = 'ci_session:';

    
    protected $lockKey;

    
    protected $keyExists = false;

    
    protected $sessionExpiration = 7200;

    
    private int $lockRetryInterval = 100_000;

    
    private int $lockMaxRetries = 300;

    
    public function __construct(SessionConfig $config, string $ipAddress)
    {
        parent::__construct($config, $ipAddress);

        
        $this->sessionExpiration = ($config->expiration === 0)
            ? (int) ini_get('session.gc_maxlifetime')
            : $config->expiration;

        
        $this->keyPrefix .= $config->cookieName . ':';

        $this->setSavePath();

        if ($this->matchIP === true) {
            $this->keyPrefix .= $this->ipAddress . ':';
        }

        $this->lockRetryInterval = $config->lockWait ?? $this->lockRetryInterval;
        $this->lockMaxRetries    = $config->lockAttempts ?? $this->lockMaxRetries;
    }

    protected function setSavePath(): void
    {
        if ($this->savePath === '') {
            throw SessionException::forEmptySavepath();
        }

        $url   = parse_url($this->savePath);
        $query = [];

        if ($url === false) {
            
            if (preg_match('#unix://(/[^:?]+)(\?.+)?#', $this->savePath, $matches)) {
                $host = $matches[1];
                $port = 0;

                if (isset($matches[2])) {
                    parse_str(ltrim($matches[2], '?'), $query);
                }
            } else {
                throw SessionException::forInvalidSavePathFormat($this->savePath);
            }
        } else {
            
            if (isset($url['path']) && $url['path'][0] === '/') {
                $host = $url['path'];
                $port = 0;
            } else {
                
                if (! isset($url['host'])) {
                    throw SessionException::forInvalidSavePathFormat($this->savePath);
                }

                $protocol = $url['scheme'] ?? self::DEFAULT_PROTOCOL;
                $host     = $protocol . '://' . $url['host'];
                $port     = $url['port'] ?? self::DEFAULT_PORT;
            }

            if (isset($url['query'])) {
                parse_str($url['query'], $query);
            }
        }

        $persistent = isset($query['persistent']) ? filter_var($query['persistent'], FILTER_VALIDATE_BOOL) : null;
        $password   = $query['auth'] ?? null;
        $database   = isset($query['database']) ? (int) $query['database'] : 0;
        $timeout    = isset($query['timeout']) ? (float) $query['timeout'] : 0.0;
        $prefix     = $query['prefix'] ?? null;

        $this->savePath = [
            'host'       => $host,
            'port'       => $port,
            'password'   => $password,
            'database'   => $database,
            'timeout'    => $timeout,
            'persistent' => $persistent,
        ];

        if ($prefix !== null) {
            $this->keyPrefix = $prefix;
        }
    }

    
    public function open($path, $name): bool
    {
        if (empty($this->savePath)) {
            return false;
        }

        if ($this->hasPersistentConnection()) {
            $redis = $this->getPersistentConnection();

            try {
                $pingReply = $redis->ping();

                if (in_array($pingReply, [true, '+PONG'], true)) {
                    $this->redis = $redis;

                    return true;
                }
            } catch (RedisException) {
                $this->setPersistentConnection(null);
            }
        }

        $redis = new Redis();

        $funcConnection = isset($this->savePath['persistent']) && $this->savePath['persistent'] === true
            ? 'pconnect'
            : 'connect';

        if ($redis->{$funcConnection}($this->savePath['host'], $this->savePath['port'], $this->savePath['timeout']) === false) {
            $this->logger->error('Session: Unable to connect to Redis with the configured settings.');
        } elseif (isset($this->savePath['password']) && ! $redis->auth($this->savePath['password'])) {
            $this->logger->error('Session: Unable to authenticate to Redis instance.');
        } elseif (isset($this->savePath['database']) && ! $redis->select($this->savePath['database'])) {
            $this->logger->error(
                'Session: Unable to select Redis database with index ' . $this->savePath['database'],
            );
        } else {
            $this->setPersistentConnection($redis);
            $this->redis = $redis;

            return true;
        }

        return false;
    }

    
    public function read($id): false|string
    {
        if (isset($this->redis) && $this->lockSession($id)) {
            if (! isset($this->sessionID)) {
                $this->sessionID = $id;
            }

            $data = $this->redis->get($this->keyPrefix . $id);

            if (is_string($data)) {
                $this->keyExists = true;
            } else {
                $data = '';
            }

            $this->fingerprint = md5($data);

            return $data;
        }

        return false;
    }

    
    public function write($id, $data): bool
    {
        if (! isset($this->redis)) {
            return false;
        }

        if ($this->sessionID !== $id) {
            if (! $this->releaseLock() || ! $this->lockSession($id)) {
                return false;
            }

            $this->keyExists = false;
            $this->sessionID = $id;
        }

        if (isset($this->lockKey)) {
            $this->redis->expire($this->lockKey, 300);

            if ($this->fingerprint !== ($fingerprint = md5($data)) || $this->keyExists === false) {
                if ($this->redis->set($this->keyPrefix . $id, $data, $this->sessionExpiration)) {
                    $this->fingerprint = $fingerprint;
                    $this->keyExists   = true;

                    return true;
                }

                return false;
            }

            return $this->redis->expire($this->keyPrefix . $id, $this->sessionExpiration);
        }

        return false;
    }

    
    public function close(): bool
    {
        if (isset($this->redis)) {
            try {
                $pingReply = $this->redis->ping();

                if (in_array($pingReply, [true, '+PONG'], true) && isset($this->lockKey) && ! $this->releaseLock()) {
                    return false;
                }
            } catch (RedisException $e) {
                $this->logger->error('Session: Got RedisException on close(): ' . $e->getMessage());
            }

            return true;
        }

        return true;
    }

    
    public function destroy($id): bool
    {
        if (isset($this->redis, $this->lockKey)) {
            if (($result = $this->redis->del($this->keyPrefix . $id)) !== 1) {
                $this->logger->debug(
                    'Session: Redis::del() expected to return 1, got ' . var_export($result, true) . ' instead.',
                );
            }

            return $this->destroyCookie();
        }

        return false;
    }

    
    public function gc($max_lifetime): int
    {
        return 1;
    }

    
    protected function lockSession(string $sessionID): bool
    {
        $lockKey = $this->keyPrefix . $sessionID . ':lock';

        
        
        
        if ($this->lockKey === $lockKey) {
            
            return $this->redis->expire($this->lockKey, 300);
        }

        $attempt = 0;

        do {
            $result = $this->redis->set(
                $lockKey,
                (string) Time::now()->getTimestamp(),
                
                
                ['nx', 'ex' => 300],
            );

            if (! $result) {
                usleep($this->lockRetryInterval);

                continue;
            }

            $this->lockKey = $lockKey;
            break;
        } while (++$attempt < $this->lockMaxRetries);

        if ($attempt === 300) {
            $this->logger->error(
                'Session: Unable to obtain lock for ' . $this->keyPrefix . $sessionID
                . ' after 300 attempts, aborting.',
            );

            return false;
        }

        $this->lock = true;

        return true;
    }

    
    protected function releaseLock(): bool
    {
        if (isset($this->redis, $this->lockKey) && $this->lock) {
            if (! $this->redis->del($this->lockKey)) {
                $this->logger->error('Session: Error while trying to free lock for ' . $this->lockKey);

                return false;
            }

            $this->lockKey = null;
            $this->lock    = false;
        }

        return true;
    }
}
