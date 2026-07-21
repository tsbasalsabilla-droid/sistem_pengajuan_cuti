<?php

declare(strict_types=1);



namespace CodeIgniter\Cookie\Exceptions;

use CodeIgniter\Exceptions\FrameworkException;


class CookieException extends FrameworkException
{
    
    public static function forInvalidExpiresTime(string $type)
    {
        return new static(lang('Cookie.invalidExpiresTime', [$type]));
    }

    
    public static function forInvalidExpiresValue()
    {
        return new static(lang('Cookie.invalidExpiresValue'));
    }

    
    public static function forInvalidCookieName(string $name)
    {
        return new static(lang('Cookie.invalidCookieName', [$name]));
    }

    
    public static function forEmptyCookieName()
    {
        return new static(lang('Cookie.emptyCookieName'));
    }

    
    public static function forInvalidSecurePrefix()
    {
        return new static(lang('Cookie.invalidSecurePrefix'));
    }

    
    public static function forInvalidHostPrefix()
    {
        return new static(lang('Cookie.invalidHostPrefix'));
    }

    
    public static function forInvalidSameSite(string $sameSite)
    {
        return new static(lang('Cookie.invalidSameSite', [$sameSite]));
    }

    
    public static function forInvalidSameSiteNone()
    {
        return new static(lang('Cookie.invalidSameSiteNone'));
    }

    
    public static function forInvalidCookieInstance(array $data)
    {
        return new static(lang('Cookie.invalidCookieInstance', $data));
    }

    
    public static function forUnknownCookieInstance(array $data)
    {
        return new static(lang('Cookie.unknownCookieInstance', $data));
    }
}
