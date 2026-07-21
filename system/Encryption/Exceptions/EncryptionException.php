<?php

declare(strict_types=1);



namespace CodeIgniter\Encryption\Exceptions;

use CodeIgniter\Exceptions\DebugTraceableTrait;
use CodeIgniter\Exceptions\RuntimeException;


class EncryptionException extends RuntimeException
{
    use DebugTraceableTrait;

    
    public static function forNoDriverRequested()
    {
        return new static(lang('Encryption.noDriverRequested'));
    }

    
    public static function forNoHandlerAvailable(string $handler)
    {
        return new static(lang('Encryption.noHandlerAvailable', [$handler]));
    }

    
    public static function forUnKnownHandler(?string $driver = null)
    {
        return new static(lang('Encryption.unKnownHandler', [$driver]));
    }

    
    public static function forNeedsStarterKey()
    {
        return new static(lang('Encryption.starterKeyNeeded'));
    }

    
    public static function forAuthenticationFailed()
    {
        return new static(lang('Encryption.authenticationFailed'));
    }

    
    public static function forEncryptionFailed()
    {
        return new static(lang('Encryption.encryptionFailed'));
    }
}
