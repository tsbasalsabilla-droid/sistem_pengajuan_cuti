<?php

declare(strict_types=1);



namespace CodeIgniter\Test\Mock;

use CodeIgniter\CodeIgniter;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\BaseResult;
use CodeIgniter\Database\Query;
use CodeIgniter\Database\TableName;
use stdClass;


class MockConnection extends BaseConnection
{
    
    protected $returnValues = [];

    
    protected $schema;

    
    public $database;

    
    public $lastQuery;

    
    public function shouldReturn(string $method, $return)
    {
        $this->returnValues[$method] = $return;

        return $this;
    }

    
    public function query(string $sql, $binds = null, bool $setEscapeFlags = true, string $queryClass = '')
    {
        
        $queryClass = str_replace('Connection', 'Query', static::class);

        $query = new $queryClass($this);

        $query->setQuery($sql, $binds, $setEscapeFlags);

        if ($this->swapPre !== '' && $this->DBPrefix !== '') {
            $query->swapPrefix($this->DBPrefix, $this->swapPre);
        }

        $startTime = microtime(true);

        $this->lastQuery = $query;

        $this->resultID = $this->simpleQuery($query->getQuery());

        if ($this->resultID === false) {
            $query->setDuration($startTime, $startTime);

            
            return false;
        }

        $query->setDuration($startTime);

        
        if ($query->isWriteType()) {
            return true;
        }

        
        
        $resultClass = str_replace('Connection', 'Result', static::class);

        return new $resultClass($this->connID, $this->resultID);
    }

    
    public function connect(bool $persistent = false)
    {
        $return = $this->returnValues['connect'] ?? true;

        if (is_array($return)) {
            
            
            $return = array_shift($this->returnValues['connect']);
        }

        return $return;
    }

    
    public function reconnect(): bool
    {
        return true;
    }

    
    public function setDatabase(string $databaseName)
    {
        $this->database = $databaseName;

        return true;
    }

    
    public function getVersion(): string
    {
        return CodeIgniter::CI_VERSION;
    }

    
    protected function execute(string $sql)
    {
        return $this->returnValues['execute'];
    }

    
    public function affectedRows(): int
    {
        return 1;
    }

    
    public function error(): array
    {
        return [
            'code'    => 0,
            'message' => '',
        ];
    }

    public function insertID(): int
    {
        return $this->connID->insert_id;
    }

    
    protected function _listTables(bool $constrainByPrefix = false, ?string $tableName = null): string
    {
        return '';
    }

    
    protected function _listColumns($table = ''): string
    {
        return '';
    }

    
    protected function _fieldData(string $table): array
    {
        return [];
    }

    
    protected function _indexData(string $table): array
    {
        return [];
    }

    
    protected function _foreignKeyData(string $table): array
    {
        return [];
    }

    
    protected function _close()
    {
    }

    
    protected function _transBegin(): bool
    {
        return true;
    }

    
    protected function _transCommit(): bool
    {
        return true;
    }

    
    protected function _transRollback(): bool
    {
        return true;
    }
}
