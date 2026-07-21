<?php

declare(strict_types=1);



namespace CodeIgniter\Exceptions;

class PageNotFoundException extends RuntimeException implements HTTPExceptionInterface
{
    use DebugTraceableTrait;

    
    protected $code = 404;

    
    public static function forPageNotFound(?string $message = null)
    {
        return new static($message ?? self::lang('HTTP.pageNotFound'));
    }

    
    public static function forEmptyController()
    {
        return new static(self::lang('HTTP.emptyController'));
    }

    
    public static function forControllerNotFound(string $controller, string $method)
    {
        return new static(self::lang('HTTP.controllerNotFound', [$controller, $method]));
    }

    
    public static function forMethodNotFound(string $method)
    {
        return new static(self::lang('HTTP.methodNotFound', [$method]));
    }

    
    public static function forLocaleNotSupported(string $locale)
    {
        return new static(self::lang('HTTP.localeNotSupported', [$locale]));
    }

    
    private static function lang(string $line, array $args = []): string
    {
        $lang = service('language', null, false);

        return $lang->getLine($line, $args);
    }
}
