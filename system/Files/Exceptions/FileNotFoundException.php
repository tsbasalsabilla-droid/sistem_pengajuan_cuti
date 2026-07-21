<?php

declare(strict_types=1);



namespace CodeIgniter\Files\Exceptions;

use CodeIgniter\Exceptions\DebugTraceableTrait;
use CodeIgniter\Exceptions\RuntimeException;

class FileNotFoundException extends RuntimeException implements ExceptionInterface
{
    use DebugTraceableTrait;

    
    public static function forFileNotFound(string $path)
    {
        return new static(lang('Files.fileNotFound', [$path]));
    }
}
