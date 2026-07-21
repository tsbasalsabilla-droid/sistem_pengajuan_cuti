<?php

declare(strict_types=1);



namespace CodeIgniter\HTTP\Exceptions;

use CodeIgniter\Exceptions\FrameworkException;


class HTTPException extends FrameworkException implements ExceptionInterface
{
    
    public static function forMissingCurl()
    {
        return new static(lang('HTTP.missingCurl'));
    }

    
    public static function forSSLCertNotFound(string $cert)
    {
        return new static(lang('HTTP.sslCertNotFound', [$cert]));
    }

    
    public static function forInvalidSSLKey(string $key)
    {
        return new static(lang('HTTP.invalidSSLKey', [$key]));
    }

    
    public static function forCurlError(string $errorNum, string $error)
    {
        return new static(lang('HTTP.curlError', [$errorNum, $error]));
    }

    
    public static function forInvalidNegotiationType(string $type)
    {
        return new static(lang('HTTP.invalidNegotiationType', [$type]));
    }

    
    public static function forInvalidJSON(?string $error = null)
    {
        return new static(lang('HTTP.invalidJSON', [$error]));
    }

    
    public static function forInvalidHTTPProtocol(string $invalidVersion)
    {
        return new static(lang('HTTP.invalidHTTPProtocol', [$invalidVersion]));
    }

    
    public static function forEmptySupportedNegotiations()
    {
        return new static(lang('HTTP.emptySupportedNegotiations'));
    }

    
    public static function forInvalidRedirectRoute(string $route)
    {
        return new static(lang('HTTP.invalidRoute', [$route]));
    }

    
    public static function forMissingResponseStatus()
    {
        return new static(lang('HTTP.missingResponseStatus'));
    }

    
    public static function forInvalidStatusCode(int $code)
    {
        return new static(lang('HTTP.invalidStatusCode', [$code]));
    }

    
    public static function forUnkownStatusCode(int $code)
    {
        return new static(lang('HTTP.unknownStatusCode', [$code]));
    }

    
    public static function forUnableToParseURI(string $uri)
    {
        return new static(lang('HTTP.cannotParseURI', [$uri]));
    }

    
    public static function forURISegmentOutOfRange(int $segment)
    {
        return new static(lang('HTTP.segmentOutOfRange', [$segment]));
    }

    
    public static function forInvalidPort(int $port)
    {
        return new static(lang('HTTP.invalidPort', [$port]));
    }

    
    public static function forMalformedQueryString()
    {
        return new static(lang('HTTP.malformedQueryString'));
    }

    
    public static function forAlreadyMoved()
    {
        return new static(lang('HTTP.alreadyMoved'));
    }

    
    public static function forInvalidFile(?string $path = null)
    {
        return new static(lang('HTTP.invalidFile'));
    }

    
    public static function forMoveFailed(string $source, string $target, string $error)
    {
        return new static(lang('HTTP.moveFailed', [$source, $target, $error]));
    }

    
    public static function forInvalidSameSiteSetting(string $samesite)
    {
        return new static(lang('Security.invalidSameSiteSetting', [$samesite]));
    }

    
    public static function forUnsupportedJSONFormat()
    {
        return new static(lang('HTTP.unsupportedJSONFormat'));
    }
}
