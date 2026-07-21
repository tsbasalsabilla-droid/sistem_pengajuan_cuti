<?php

declare(strict_types=1);



namespace CodeIgniter\Database;


interface ConnectionInterface
{
    
    public function initialize();

    
    public function connect(bool $persistent = false);

    
    public function persistentConnect();

    
    public function reconnect();

    
    public function getConnection(?string $alias = null);

    
    public function setDatabase(string $databaseName);

    
    public function getDatabase(): string;

    
    public function error(): array;

    
    public function getPlatform(): string;

    
    public function getVersion(): string;

    
    public function query(string $sql, $binds = null);

    
    public function simpleQuery(string $sql);

    
    public function table($tableName);

    
    public function getLastQuery();

    
    public function escape($str);

    
    public function callFunction(string $functionName, ...$params);

    
    public function isWriteType($sql): bool;
}
