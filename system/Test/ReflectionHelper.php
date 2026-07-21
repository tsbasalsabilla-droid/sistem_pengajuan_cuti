<?php

declare(strict_types=1);



namespace CodeIgniter\Test;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionObject;
use ReflectionProperty;


trait ReflectionHelper
{
    
    public static function getPrivateMethodInvoker($obj, $method)
    {
        $refMethod = new ReflectionMethod($obj, $method);
        $obj       = (gettype($obj) === 'object') ? $obj : null;

        return static fn (...$args): mixed => $refMethod->invokeArgs($obj, $args);
    }

    
    private static function getAccessibleRefProperty($obj, $property)
    {
        $refClass = is_object($obj) ? new ReflectionObject($obj) : new ReflectionClass($obj);

        return $refClass->getProperty($property);
    }

    
    public static function setPrivateProperty($obj, $property, $value): void
    {
        $refProperty = self::getAccessibleRefProperty($obj, $property);

        if (is_object($obj)) {
            $refProperty->setValue($obj, $value);
        } else {
            $refProperty->setValue(null, $value);
        }
    }

    
    public static function getPrivateProperty($obj, $property)
    {
        $refProperty = self::getAccessibleRefProperty($obj, $property);

        return is_string($obj) ? $refProperty->getValue() : $refProperty->getValue($obj);
    }
}
