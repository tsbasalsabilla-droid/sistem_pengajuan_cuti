<?php

declare(strict_types=1);



namespace CodeIgniter\DataCaster\Cast;

use BackedEnum;
use CodeIgniter\DataCaster\Exceptions\CastException;
use ReflectionEnum;
use UnitEnum;


class EnumCast extends BaseCast implements CastInterface
{
    public static function get(
        mixed $value,
        array $params = [],
        ?object $helper = null,
    ): BackedEnum|UnitEnum {
        if (! is_string($value) && ! is_int($value)) {
            self::invalidTypeValueError($value);
        }

        $enumClass = $params[0] ?? null;

        if ($enumClass === null) {
            throw CastException::forMissingEnumClass();
        }

        if (! enum_exists($enumClass)) {
            throw CastException::forNotEnum($enumClass);
        }

        $reflection = new ReflectionEnum($enumClass);

        
        if (! $reflection->isBacked()) {
            
            foreach ($enumClass::cases() as $case) {
                if ($case->name === $value) {
                    return $case;
                }
            }

            throw CastException::forInvalidEnumCaseName($enumClass, $value);
        }

        
        $backingType = $reflection->getBackingType();

        
        if ($backingType->getName() === 'int') {
            $value = (int) $value;
        } elseif ($backingType->getName() === 'string') {
            $value = (string) $value;
        }

        $enum = $enumClass::tryFrom($value);

        if ($enum === null) {
            throw CastException::forInvalidEnumValue($enumClass, $value);
        }

        return $enum;
    }

    public static function set(
        mixed $value,
        array $params = [],
        ?object $helper = null,
    ): int|string {
        if (! is_object($value) || ! enum_exists($value::class)) {
            self::invalidTypeValueError($value);
        }

        
        $enumClass = $params[0] ?? null;

        if ($enumClass === null) {
            throw CastException::forMissingEnumClass();
        }

        if (! enum_exists($enumClass)) {
            throw CastException::forNotEnum($enumClass);
        }

        
        if (! $value instanceof $enumClass) {
            throw CastException::forInvalidEnumType($enumClass, $value::class);
        }

        $reflection = new ReflectionEnum($value::class);

        
        if ($reflection->isBacked()) {
            
            return $value->value;
        }

        
        
        return $value->name;
    }
}
