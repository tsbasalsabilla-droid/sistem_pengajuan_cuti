<?php

declare(strict_types=1);



namespace CodeIgniter\Session;


trait PersistsConnection
{
    
    protected static $connectionPool = [];

    
    protected function getConnectionIdentifier(): string
    {
        return hash('xxh128', serialize([
            'class'     => static::class,
            'savePath'  => $this->savePath,
            'keyPrefix' => $this->keyPrefix,
        ]));
    }

    
    protected function hasPersistentConnection(): bool
    {
        $identifier = $this->getConnectionIdentifier();

        return isset(self::$connectionPool[$identifier]);
    }

    
    protected function getPersistentConnection(): ?object
    {
        $identifier = $this->getConnectionIdentifier();

        return self::$connectionPool[$identifier] ?? null;
    }

    
    protected function setPersistentConnection(?object $connection): void
    {
        $identifier = $this->getConnectionIdentifier();

        if ($connection === null) {
            unset(self::$connectionPool[$identifier]);
        } else {
            self::$connectionPool[$identifier] = $connection;
        }
    }

    
    public static function resetPersistentConnections(): void
    {
        self::$connectionPool = [];
    }
}
