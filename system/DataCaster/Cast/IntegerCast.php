<?php

declare(strict_types=1);



namespace CodeIgniter\DataCaster\Cast;


class IntegerCast extends BaseCast
{
    public static function get(
        mixed $value,
        array $params = [],
        ?object $helper = null,
    ): int {
        if (! is_string($value) && ! is_int($value)) {
            self::invalidTypeValueError($value);
        }

        return (int) $value;
    }
}
