<?php

declare(strict_types=1);



namespace CodeIgniter\Entity\Cast;

class CSVCast extends BaseCast
{
    public static function get($value, array $params = []): array
    {
        return explode(',', $value);
    }

    public static function set($value, array $params = []): string
    {
        return implode(',', $value);
    }
}
