<?php

declare(strict_types=1);



namespace CodeIgniter\Cache\Exceptions;

use CodeIgniter\Exceptions\DebugTraceableTrait;
use CodeIgniter\Exceptions\RuntimeException;

class CacheException extends RuntimeException
{
    use DebugTraceableTrait;

    
    public static function forUnableToWrite(string $path)
    {
        return new static(lang('Cache.unableToWrite', [$path]));
    }

    
    public static function forInvalidHandlers()
    {
        return new static(lang('Cache.invalidHandlers'));
    }

    
    public static function forNoBackup()
    {
        return new static(lang('Cache.noBackup'));
    }

    
    public static function forHandlerNotFound()
    {
        return new static(lang('Cache.handlerNotFound'));
    }
}
