<?php

declare(strict_types=1);



namespace CodeIgniter\Security\Exceptions;

use CodeIgniter\Exceptions\FrameworkException;
use CodeIgniter\Exceptions\HTTPExceptionInterface;

class SecurityException extends FrameworkException implements HTTPExceptionInterface
{
    
    public static function forDisallowedAction()
    {
        return new static(lang('Security.disallowedAction'), 403);
    }

    
    public static function forInsecureCookie(): static
    {
        return new static(lang('Security.insecureCookie'));
    }

    
    public static function forInvalidUTF8Chars(string $source, string $string)
    {
        return new static(
            'Invalid UTF-8 characters in ' . $source . ': ' . $string,
            400,
        );
    }

    
    public static function forInvalidControlChars(string $source, string $string)
    {
        return new static(
            'Invalid Control characters in ' . $source . ': ' . $string,
            400,
        );
    }

    
    public static function forInvalidSameSite(string $samesite)
    {
        return new static(lang('Security.invalidSameSite', [$samesite]));
    }
}
