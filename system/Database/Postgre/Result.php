<?php

declare(strict_types=1);



namespace CodeIgniter\Database\Postgre;

use CodeIgniter\Database\BaseResult;
use CodeIgniter\Entity\Entity;
use PgSql\Connection as PgSqlConnection;
use PgSql\Result as PgSqlResult;
use stdClass;


class Result extends BaseResult
{
    
    public function getFieldCount(): int
    {
        return pg_num_fields($this->resultID);
    }

    
    public function getFieldNames(): array
    {
        $fieldNames = [];

        for ($i = 0, $c = $this->getFieldCount(); $i < $c; $i++) {
            $fieldNames[] = pg_field_name($this->resultID, $i);
        }

        return $fieldNames;
    }

    
    public function getFieldData(): array
    {
        $retVal = [];

        for ($i = 0, $c = $this->getFieldCount(); $i < $c; $i++) {
            $retVal[$i]             = new stdClass();
            $retVal[$i]->name       = pg_field_name($this->resultID, $i);
            $retVal[$i]->type       = pg_field_type_oid($this->resultID, $i);
            $retVal[$i]->type_name  = pg_field_type($this->resultID, $i);
            $retVal[$i]->max_length = pg_field_size($this->resultID, $i);
            $retVal[$i]->length     = $retVal[$i]->max_length;
            
            
        }

        return $retVal;
    }

    
    public function freeResult()
    {
        if ($this->resultID !== false) {
            pg_free_result($this->resultID);
            $this->resultID = false;
        }
    }

    
    public function dataSeek(int $n = 0)
    {
        return pg_result_seek($this->resultID, $n);
    }

    
    protected function fetchAssoc()
    {
        return pg_fetch_assoc($this->resultID);
    }

    
    protected function fetchObject(string $className = 'stdClass')
    {
        if (is_subclass_of($className, Entity::class)) {
            return empty($data = $this->fetchAssoc()) ? false : (new $className())->injectRawData($data);
        }

        return pg_fetch_object($this->resultID, null, $className);
    }

    
    public function getNumRows(): int
    {
        if (! is_int($this->numRows)) {
            $this->numRows = pg_num_rows($this->resultID);
        }

        return $this->numRows;
    }
}
