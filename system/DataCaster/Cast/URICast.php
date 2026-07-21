<?php

declare(strict_types=1);



namespace CodeIgniter\DataCaster\Cast;

use CodeIgniter\HTTP\URI;


class URICast extends BaseCast
{
    public static function get(
        mixed $value,
        array $params = [],
        ?object $helper = null,
    ): URI {
        if (! is_string($value)) {
            self::invalidTypeValueError($value);
        }

        return new URI($value);
    }

    public static function set(
        mixed $value,
        array $params = [],
        ?object $helper = null,
    ): string {
        if (! $value instanceof URI) {
            self::invalidTypeValueError($value);
        }

        return (string) $value;
    }
}
