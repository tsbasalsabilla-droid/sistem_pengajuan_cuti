<?php

declare(strict_types=1);



namespace CodeIgniter\DataCaster\Cast;


final class IntBoolCast extends BaseCast
{
    public static function get(
        mixed $value,
        array $params = [],
        ?object $helper = null,
    ): bool {
        if (! is_int($value) && ! is_string($value)) {
            self::invalidTypeValueError($value);
        }

        return (bool) $value;
    }

    public static function set(
        mixed $value,
        array $params = [],
        ?object $helper = null,
    ): int {
        if (! is_bool($value)) {
            self::invalidTypeValueError($value);
        }

        return (int) $value;
    }
}
