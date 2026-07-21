<?php

declare(strict_types=1);



namespace CodeIgniter\Database;

use stdClass;


interface ResultInterface
{
    
    public function getResult(string $type = 'object'): array;

    
    public function getCustomResultObject(string $className);

    
    public function getResultArray(): array;

    
    public function getResultObject(): array;

    
    public function getRow($n = 0, string $type = 'object');

    
    public function getCustomRowObject(int $n, string $className);

    
    public function getRowArray(int $n = 0);

    
    public function getRowObject(int $n = 0);

    
    public function setRow($key, $value = null);

    
    public function getFirstRow(string $type = 'object');

    
    public function getLastRow(string $type = 'object');

    
    public function getNextRow(string $type = 'object');

    
    public function getPreviousRow(string $type = 'object');

    
    public function getNumRows(): int;

    
    public function getUnbufferedRow(string $type = 'object');

    
    public function getFieldCount(): int;

    
    public function getFieldNames(): array;

    
    public function getFieldData(): array;

    
    public function freeResult();

    
    public function dataSeek(int $n = 0);
}
