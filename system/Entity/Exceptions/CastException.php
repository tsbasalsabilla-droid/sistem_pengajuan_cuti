<?php

declare(strict_types=1);



namespace CodeIgniter\Entity\Exceptions;

use CodeIgniter\Exceptions\FrameworkException;
use CodeIgniter\Exceptions\HasExitCodeInterface;


class CastException extends FrameworkException implements HasExitCodeInterface
{
    public function getExitCode(): int
    {
        return EXIT_CONFIG;
    }

    
    public static function forInvalidInterface(string $class)
    {
        return new static(lang('Cast.baseCastMissing', [$class]));
    }

    
    public static function forInvalidJsonFormat(int $error)
    {
        return match ($error) {
            JSON_ERROR_DEPTH          => new static(lang('Cast.jsonErrorDepth')),
            JSON_ERROR_STATE_MISMATCH => new static(lang('Cast.jsonErrorStateMismatch')),
            JSON_ERROR_CTRL_CHAR      => new static(lang('Cast.jsonErrorCtrlChar')),
            JSON_ERROR_SYNTAX         => new static(lang('Cast.jsonErrorSyntax')),
            JSON_ERROR_UTF8           => new static(lang('Cast.jsonErrorUtf8')),
            default                   => new static(lang('Cast.jsonErrorUnknown')),
        };
    }

    
    public static function forInvalidMethod(string $method)
    {
        return new static(lang('Cast.invalidCastMethod', [$method]));
    }

    
    public static function forInvalidTimestamp()
    {
        return new static(lang('Cast.invalidTimestamp'));
    }

    
    public static function forMissingEnumClass()
    {
        return new static(lang('Cast.enumMissingClass'));
    }

    
    public static function forNotEnum(string $class)
    {
        return new static(lang('Cast.enumNotEnum', [$class]));
    }

    
    public static function forInvalidEnumValue(string $enumClass, mixed $value)
    {
        return new static(lang('Cast.enumInvalidValue', [$enumClass, $value]));
    }

    
    public static function forInvalidEnumCaseName(string $enumClass, string $caseName)
    {
        return new static(lang('Cast.enumInvalidCaseName', [$caseName, $enumClass]));
    }

    
    public static function forInvalidEnumType(string $expectedClass, string $actualClass)
    {
        return new static(lang('Cast.enumInvalidType', [$actualClass, $expectedClass]));
    }
}
