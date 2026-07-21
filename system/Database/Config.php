<?php

declare(strict_types=1);



namespace CodeIgniter\Database;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Exceptions\InvalidArgumentException;
use Config\Database as DbConfig;


class Config extends BaseConfig
{
    
    protected static $instances = [];

    
    protected static $factory;

    
    public static function connect($group = null, bool $getShared = true)
    {
        
        if ($group instanceof BaseConnection) {
            return $group;
        }

        if (is_array($group)) {
            $config = $group;
            $group  = 'custom-' . md5(json_encode($config));
        } else {
            $dbConfig = config(DbConfig::class);

            if ($group === null) {
                $group = (ENVIRONMENT === 'testing') ? 'tests' : $dbConfig->defaultGroup;
            }

            assert(is_string($group));

            if (! isset($dbConfig->{$group})) {
                throw new InvalidArgumentException('"' . $group . '" is not a valid database connection group.');
            }

            $config = $dbConfig->{$group};
        }

        if ($getShared && isset(static::$instances[$group])) {
            return static::$instances[$group];
        }

        static::ensureFactory();

        $connection = static::$factory->load($config, $group);

        if ($getShared) {
            static::$instances[$group] = $connection;
        }

        return $connection;
    }

    
    public static function getConnections(): array
    {
        return static::$instances;
    }

    
    public static function forge($group = null)
    {
        $db = static::connect($group);

        return static::$factory->loadForge($db);
    }

    
    public static function utils($group = null)
    {
        $db = static::connect($group);

        return static::$factory->loadUtils($db);
    }

    
    public static function seeder(?string $group = null)
    {
        $config = config(DbConfig::class);

        return new Seeder($config, static::connect($group));
    }

    
    protected static function ensureFactory()
    {
        if (static::$factory instanceof Database) {
            return;
        }

        static::$factory = new Database();
    }

    
    public static function reconnectForWorkerMode(): void
    {
        foreach (static::$instances as $connection) {
            $connection->reconnect();
        }
    }

    
    public static function cleanupForWorkerMode(): void
    {
        foreach (static::$instances as $group => $connection) {
            if ($connection->transDepth > 0) {
                log_message('error', "Uncommitted transaction detected in database group '{$group}'. Transactions must be completed before request ends.");

                while ($connection->transDepth > 0) {
                    $connection->transRollback();
                }
            }

            $connection->resetTransStatus();
        }
    }
}
