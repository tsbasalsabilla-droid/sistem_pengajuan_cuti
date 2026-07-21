<?php

declare(strict_types=1);



namespace CodeIgniter\Database\MySQLi;

use CodeIgniter\Database\BasePreparedQuery;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Exceptions\BadMethodCallException;
use mysqli;
use mysqli_result;
use mysqli_sql_exception;
use mysqli_stmt;


class PreparedQuery extends BasePreparedQuery
{
    
    public function _prepare(string $sql, array $options = []): PreparedQuery
    {
        
        
        $sql = rtrim($sql, ';');

        if (! $this->statement = $this->db->mysqli->prepare($sql)) {
            $this->errorCode   = $this->db->mysqli->errno;
            $this->errorString = $this->db->mysqli->error;

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

        
        $bindTypes  = '';
        $binaryData = [];

        
        foreach ($data as $key => $item) {
            if (is_int($item)) {
                $bindTypes .= 'i';
            } elseif (is_numeric($item)) {
                $bindTypes .= 'd';
            } elseif (is_string($item) && $this->isBinary($item)) {
                $bindTypes .= 'b';
                $binaryData[$key] = $item;
            } else {
                $bindTypes .= 's';
            }
        }

        
        $this->statement->bind_param($bindTypes, ...$data);

        
        foreach ($binaryData as $key => $value) {
            $this->statement->send_long_data($key, $value);
        }

        try {
            return $this->statement->execute();
        } catch (mysqli_sql_exception $e) {
            if ($this->db->DBDebug) {
                throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
            }

            return false;
        }
    }

    
    public function _getResult()
    {
        return $this->statement->get_result();
    }

    
    protected function _close(): bool
    {
        return $this->statement->close();
    }
}
