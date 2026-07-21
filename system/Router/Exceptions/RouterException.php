<?php

declare(strict_types=1);



namespace CodeIgniter\Router\Exceptions;

use CodeIgniter\Exceptions\FrameworkException;


class RouterException extends FrameworkException implements ExceptionInterface
{
    
    public static function forInvalidParameterType()
    {
        return new static(lang('Router.invalidParameter'));
    }

    
    public static function forMissingDefaultRoute()
    {
        return new static(lang('Router.missingDefaultRoute'));
    }

    
    public static function forControllerNotFound(string $controller, string $method)
    {
        return new static(lang('HTTP.controllerNotFound', [$controller, $method]));
    }

    
    public static function forInvalidRoute(string $route)
    {
        return new static(lang('HTTP.invalidRoute', [$route]));
    }

    
    public static function forDynamicController(string $handler)
    {
        return new static(lang('Router.invalidDynamicController', [$handler]));
    }

    
    public static function forInvalidControllerName(string $handler)
    {
        return new static(lang('Router.invalidControllerName', [$handler]));
    }
}
