<?php

declare(strict_types=1);



namespace CodeIgniter\Entity\Cast;

class StringCast extends BaseCast
{
    public static function get($value, array $params = []): string
    {
        return (string) $value;
    }
}
