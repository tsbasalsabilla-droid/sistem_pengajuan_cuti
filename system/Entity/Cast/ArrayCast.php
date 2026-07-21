<?php

declare(strict_types=1);



namespace CodeIgniter\Entity\Cast;

class ArrayCast extends BaseCast
{
    public static function get($value, array $params = []): array
    {
        if (is_string($value) && (str_starts_with($value, 'a:') || str_starts_with($value, 's:'))) {
            $value = unserialize($value);
        }

        return (array) $value;
    }

    public static function set($value, array $params = []): string
    {
        return serialize($value);
    }
}
