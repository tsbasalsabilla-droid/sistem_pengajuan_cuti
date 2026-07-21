<?php

declare(strict_types=1);



namespace CodeIgniter\Format\Exceptions;

use CodeIgniter\Exceptions\DebugTraceableTrait;
use CodeIgniter\Exceptions\RuntimeException;


class FormatException extends RuntimeException
{
    use DebugTraceableTrait;

    
    public static function forInvalidFormatter(string $class)
    {
        return new static(lang('Format.invalidFormatter', [$class]));
    }

    
    public static function forInvalidJSON(?string $error = null)
    {
        return new static(lang('Format.invalidJSON', [$error]));
    }

    
    public static function forInvalidMime(string $mime)
    {
        return new static(lang('Format.invalidMime', [$mime]));
    }

    
    public static function forMissingExtension()
    {
        return new static(lang('Format.missingExtension'));
    }
}
