<?php

declare(strict_types=1);



namespace CodeIgniter\Test\Mock;

use CodeIgniter\Database\BaseResult;
use stdClass;


class MockResult extends BaseResult
{
    
    public function getFieldCount(): int
    {
        return 0;
    }

    
    public function getFieldNames(): array
    {
        return [];
    }

    
    public function getFieldData(): array
    {
        return [];
    }

    
    public function freeResult()
    {
    }

    
    public function dataSeek($n = 0)
    {
        return true;
    }

    
    protected function fetchAssoc()
    {
        return [];
    }

    
    protected function fetchObject($className = stdClass::class)
    {
        return new $className();
    }

    
    public function getNumRows(): int
    {
        return 0;
    }
}
