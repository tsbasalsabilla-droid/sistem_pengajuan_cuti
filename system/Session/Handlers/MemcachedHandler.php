<?php

declare(strict_types=1);



namespace CodeIgniter\Session\Handlers;

use CodeIgniter\I18n\Time;
use CodeIgniter\Session\Exceptions\SessionException;
use CodeIgniter\Session\PersistsConnection;
use Config\Session as SessionConfig;
use Memcached;


class MemcachedHandler extends BaseHandler
{
    use PersistsConnection;

    
    protected $memcached;

    
    protected $keyPrefix = 'ci_session:';

    
    protected $lockKey;

    
    protected $sessionExpiration = 7200;

    
    public function __construct(SessionConfig $config, string $ipAddress)
    {
        parent::__construct($config, $ipAddress);

        $this->sessionExpiration = $config->expiration;

        if ($this->savePath === '') {
            throw SessionException::forEmptySavepath();
        }

        
        $this->keyPrefix .= $config->cookieName . ':';

        if ($this->matchIP === true) {
            $this->keyPrefix .= $this->ipAddress . ':';
        }

        ini_set('memcached.sess_prefix', $this->keyPrefix);
    }

    
    public function open($path, $name): bool
    {
        if ($this->hasPersistentConnection()) {
            $memcached = $this->getPersistentConnection();
            $version   = $memcached->getVersion();

            if (is_array($version)) {
                foreach ($version as $serverVersion) {
                    if ($serverVersion !== false) {
                        $this->memcached = $memcached;

                        return true;
                    }
                }
            }

            $this->setPersistentConnection(null);
        }

        $this->memcached = new Memcached();
        $this->memcached->setOption(Memcached::OPT_BINARY_PROTOCOL, true); 

        $serverList = [];

        foreach ($this->memcached->getServerList() as $server) {
            $serverList[] = $server['host'] . ':' . $server['port'];
        }

        if (
            preg_match_all(
                '#,?([^,:]+)\:(\d{1,5})(?:\:(\d+))?#',
                $this->savePath,
                $matches,
                PREG_SET_ORDER,
            ) < 1
        ) {
            $this->memcached = null;
            $this->logger->error('Session: Invalid Memcached save path format: ' . $this->savePath);

            return false;
        }

        foreach ($matches as $match) {
            
            if (in_array($match[1] . ':' . $match[2], $serverList, true)) {
                $this->logger->debug(
                    'Session: Memcached server pool already has ' . $match[1] . ':' . $match[2],
                );

                continue;
            }

            if (! $this->memcached->addServer($match[1], (int) $match[2], $match[3] ?? 0)) {
                $this->logger->error(
                    'Could not add ' . $match[1] . ':' . $match[2] . ' to Memcached server pool.',
                );
            } else {
                $serverList[] = $match[1] . ':' . $match[2];
            }
        }

        if ($serverList === []) {
            $this->logger->error('Session: Memcached server pool is empty.');

            return false;
        }

        $this->setPersistentConnection($this->memcached);

        return true;
    }

    
    public function read($id): false|string
    {
        if (isset($this->memcached) && $this->lockSession($id)) {
            if (! isset($this->sessionID)) {
                $this->sessionID = $id;
            }

            $data = (string) $this->memcached->get($this->keyPrefix . $id);

            $this->fingerprint = md5($data);

            return $data;
        }

        return '';
    }

    
    public function write($id, $data): bool
    {
        if (! isset($this->memcached)) {
            return false;
        }

        if ($this->sessionID !== $id) {
            if (! $this->releaseLock() || ! $this->lockSession($id)) {
                return false;
            }

            $this->fingerprint = md5('');
            $this->sessionID   = $id;
        }

        if (isset($this->lockKey)) {
            $this->memcached->replace($this->lockKey, Time::now()->getTimestamp(), 300);

            if ($this->fingerprint !== ($fingerprint = md5($data))) {
                if ($this->memcached->set($this->keyPrefix . $id, $data, $this->sessionExpiration)) {
                    $this->fingerprint = $fingerprint;

                    return true;
                }

                return false;
            }

            return $this->memcached->touch($this->keyPrefix . $id, $this->sessionExpiration);
        }

        return false;
    }

    
    public function close(): bool
    {
        if (isset($this->memcached)) {
            if (isset($this->lockKey)) {
                $this->memcached->delete($this->lockKey);
            }

            return true;
        }

        return false;
    }

    
    public function destroy($id): bool
    {
        if (isset($this->memcached, $this->lockKey)) {
            $this->memcached->delete($this->keyPrefix . $id);

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
        if (isset($this->lockKey)) {
            return $this->memcached->replace($this->lockKey, Time::now()->getTimestamp(), 300);
        }

        $lockKey = $this->keyPrefix . $sessionID . ':lock';
        $attempt = 0;

        do {
            if ($this->memcached->get($lockKey) !== false) {
                sleep(1);

                continue;
            }

            if (! $this->memcached->set($lockKey, Time::now()->getTimestamp(), 300)) {
                $this->logger->error(
                    'Session: Error while trying to obtain lock for ' . $this->keyPrefix . $sessionID,
                );

                return false;
            }

            $this->lockKey = $lockKey;
            break;
        } while (++$attempt < 30);

        if ($attempt === 30) {
            $this->logger->error(
                'Session: Unable to obtain lock for ' . $this->keyPrefix . $sessionID . ' after 30 attempts, aborting.',
            );

            return false;
        }

        $this->lock = true;

        return true;
    }

    
    protected function releaseLock(): bool
    {
        if (isset($this->memcached, $this->lockKey) && $this->lock) {
            if (
                ! $this->memcached->delete($this->lockKey)
                && $this->memcached->getResultCode() !== Memcached::RES_NOTFOUND
            ) {
                $this->logger->error(
                    'Session: Error while trying to free lock for ' . $this->lockKey,
                );

                return false;
            }

            $this->lockKey = null;
            $this->lock    = false;
        }

        return true;
    }
}
