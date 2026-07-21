<?php

declare(strict_types=1);



namespace CodeIgniter\Database;

use CodeIgniter\Exceptions\BadMethodCallException;


interface PreparedQueryInterface
{
    
    public function execute(...$data);

    
    public function prepare(string $sql, array $options = []);

    
    public function close(): bool;

    
    public function getQueryString(): string;

    
    public function getErrorCode(): int;

    
    public function getErrorMessage(): string;
}
