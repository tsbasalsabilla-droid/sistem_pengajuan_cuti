<?php

declare(strict_types=1);



namespace CodeIgniter\Throttle;

use CodeIgniter\Cache\CacheInterface;
use CodeIgniter\I18n\Time;


class Throttler implements ThrottlerInterface
{
    
    protected $cache;

    
    protected $tokenTime = 0;

    
    protected $prefix = 'throttler_';

    
    protected $testTime;

    
    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    
    public function getTokenTime(): int
    {
        return $this->tokenTime;
    }

    
    public function check(string $key, int $capacity, int $seconds, int $cost = 1): bool
    {
        $tokenName = $this->prefix . $key;

        
        $rate = $capacity / $seconds;
        
        $refresh = 1 / $rate;

        
        $tokens = $this->cache->get($tokenName);

        
        if ($tokens === null) {
            
            
            $tokens = $capacity - $cost;
            $this->cache->save($tokenName, $tokens, $seconds);
            $this->cache->save($tokenName . 'Time', $this->time(), $seconds);

            $this->tokenTime = 0;

            return true;
        }

        
        
        $throttleTime = $this->cache->get($tokenName . 'Time');
        $elapsed      = $this->time() - $throttleTime;

        
        
        
        $tokens += $rate * $elapsed;
        $tokens = min($tokens, $capacity);

        
        
        if ($tokens >= 1) {
            $tokens -= $cost;
            $this->cache->save($tokenName, $tokens, $seconds);
            $this->cache->save($tokenName . 'Time', $this->time(), $seconds);

            $this->tokenTime = 0;

            return true;
        }

        
        
        
        $newTokenAvailable = (int) round((1 - $tokens) * $refresh);
        $this->tokenTime   = max(1, $newTokenAvailable);

        return false;
    }

    
    public function remove(string $key): self
    {
        $tokenName = $this->prefix . $key;

        $this->cache->delete($tokenName);
        $this->cache->delete($tokenName . 'Time');

        return $this;
    }

    
    public function setTestTime(int $time)
    {
        $this->testTime = $time;

        return $this;
    }

    
    public function time(): int
    {
        return $this->testTime ?? Time::now()->getTimestamp();
    }
}
