<?php

declare(strict_types=1);



namespace CodeIgniter\DataCaster\Cast;


class CSVCast extends BaseCast
{
    public static function get(
        mixed $value,
        array $params = [],
        ?object $helper = null,
    ): array {
        if (! is_string($value)) {
            self::invalidTypeValueError($value);
        }

        return explode(',', $value);
    }

    public static function set(
        mixed $value,
        array $params = [],
        ?object $helper = null,
    ): string {
        if (! is_array($value)) {
            self::invalidTypeValueError($value);
        }

        return implode(',', $value);
    }
}
