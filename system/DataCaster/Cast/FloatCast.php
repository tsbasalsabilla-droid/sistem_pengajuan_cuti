<?php

declare(strict_types=1);



namespace CodeIgniter\DataCaster\Cast;


class FloatCast extends BaseCast
{
    public static function get(
        mixed $value,
        array $params = [],
        ?object $helper = null,
    ): float {
        if (! is_float($value) && ! is_string($value)) {
            self::invalidTypeValueError($value);
        }

        return (float) $value;
    }
}
