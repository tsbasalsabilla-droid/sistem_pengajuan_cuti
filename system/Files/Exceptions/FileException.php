<?php

declare(strict_types=1);



namespace CodeIgniter\Files\Exceptions;

use CodeIgniter\Exceptions\DebugTraceableTrait;
use CodeIgniter\Exceptions\RuntimeException;

class FileException extends RuntimeException implements ExceptionInterface
{
    use DebugTraceableTrait;

    
    public static function forUnableToMove(?string $from = null, ?string $to = null, ?string $error = null)
    {
        return new static(lang('Files.cannotMove', [$from, $to, $error]));
    }

    
    public static function forExpectedDirectory(string $caller)
    {
        return new static(lang('Files.expectedDirectory', [$caller]));
    }

    
    public static function forExpectedFile(string $caller)
    {
        return new static(lang('Files.expectedFile', [$caller]));
    }
}
