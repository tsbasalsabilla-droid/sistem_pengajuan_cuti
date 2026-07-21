<?php

declare(strict_types=1);



namespace CodeIgniter\Database\Exceptions;

use CodeIgniter\Exceptions\HasExitCodeInterface;
use CodeIgniter\Exceptions\RuntimeException;

class DatabaseException extends RuntimeException implements ExceptionInterface, HasExitCodeInterface
{
    public function getExitCode(): int
    {
        return EXIT_DATABASE;
    }
}
