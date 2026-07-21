<?php

declare(strict_types=1);



namespace CodeIgniter\Database\Postgre;

use CodeIgniter\Database\BasePreparedQuery;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Exceptions\BadMethodCallException;
use Exception;
use PgSql\Connection as PgSqlConnection;
use PgSql\Result as PgSqlResult;


class PreparedQuery extends BasePreparedQuery
{
    
    protected $name;

    
    protected $result;

    
    public function _prepare(string $sql, array $options = []): PreparedQuery
    {
        $this->name = (string) random_int(1, 10_000_000_000_000_000);

        $sql = $this->parameterize($sql);

        
        
        $this->query->setQuery($sql);

        if (! $this->statement = pg_prepare($this->db->connID, $this->name, $sql)) {
            $this->errorCode   = 0;
            $this->errorString = pg_last_error($this->db->connID);

            if ($this->db->DBDebug) {
                throw new DatabaseException($this->errorString . ' code: ' . $this->errorCode);
            }
        }

        return $this;
    }

    
    public function _execute(array $data): bool
    {
        if (! isset($this->statement)) {
            throw new BadMethodCallException('You must call prepare before trying to execute a prepared statement.');
        }

        foreach ($data as &$item) {
            if (is_string($item) && $this->isBinary($item)) {
                $item = pg_escape_bytea($this->db->connID, $item);
            }
        }

        $this->result = pg_execute($this->db->connID, $this->name, $data);

        return (bool) $this->result;
    }

    
    public function _getResult()
    {
        return $this->result;
    }

    
    protected function _close(): bool
    {
        return pg_query($this->db->connID, 'DEALLOCATE "' . $this->db->escapeIdentifiers($this->name) . '"') !== false;
    }

    
    public function parameterize(string $sql): string
    {
        
        $count = 0;

        return preg_replace_callback('/\?/', static function () use (&$count): string {
            $count++;

            return "\${$count}";
        }, $sql);
    }
}
