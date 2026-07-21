<?php

declare(strict_types=1);



namespace CodeIgniter\Cache;

use CodeIgniter\Cache\Exceptions\CacheException;
use CodeIgniter\Exceptions\CriticalError;
use CodeIgniter\Test\Mock\MockCache;
use Config\Cache;


class CacheFactory
{
    
    public static $mockClass = MockCache::class;

    
    public static $mockServiceName = 'cache';

    
    public static function getHandler(Cache $config, ?string $handler = null, ?string $backup = null)
    {
        if (! isset($config->validHandlers) || $config->validHandlers === []) {
            throw CacheException::forInvalidHandlers();
        }

        if (! isset($config->handler) || ! isset($config->backupHandler)) {
            throw CacheException::forNoBackup();
        }

        $handler ??= $config->handler;
        $backup ??= $config->backupHandler;

        if (! array_key_exists($handler, $config->validHandlers) || ! array_key_exists($backup, $config->validHandlers)) {
            throw CacheException::forHandlerNotFound();
        }

        $adapter = new $config->validHandlers[$handler]($config);

        if (! $adapter->isSupported()) {
            $adapter = new $config->validHandlers[$backup]($config);

            if (! $adapter->isSupported()) {
                
                $adapter = new $config->validHandlers['dummy']();
            }
        }

        
        
        try {
            $adapter->initialize();
        } catch (CriticalError $e) {
            log_message('critical', $e . ' Resorting to using ' . $backup . ' handler.');

            
            $adapter = self::getHandler($config, $backup, 'dummy');
        }

        return $adapter;
    }
}
