<?php

declare(strict_types=1);



namespace CodeIgniter\CLI\Exceptions;

use CodeIgniter\Exceptions\DebugTraceableTrait;
use CodeIgniter\Exceptions\RuntimeException;


class CLIException extends RuntimeException
{
    use DebugTraceableTrait;

    
    public static function forInvalidColor(string $type, string $color)
    {
        return new static(lang('CLI.invalidColor', [$type, $color]));
    }
}
