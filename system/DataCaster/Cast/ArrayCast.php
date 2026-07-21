<?php

declare(strict_types=1);



namespace CodeIgniter\DataCaster\Cast;


class ArrayCast extends BaseCast implements CastInterface
{
    public static function get(
        mixed $value,
        array $params = [],
        ?object $helper = null,
    ): array {
        if (! is_string($value)) {
            self::invalidTypeValueError($value);
        }

        if ((str_starts_with($value, 'a:') || str_starts_with($value, 's:'))) {
            $value = unserialize($value, ['allowed_classes' => false]);
        }

        return (array) $value;
    }

    public static function set(
        mixed $value,
        array $params = [],
        ?object $helper = null,
    ): string {
        return serialize($value);
    }
}
