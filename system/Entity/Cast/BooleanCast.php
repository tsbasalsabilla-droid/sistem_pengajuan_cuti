<?php

declare(strict_types=1);



namespace CodeIgniter\Entity\Cast;

class BooleanCast extends BaseCast
{
    public static function get($value, array $params = []): bool
    {
        return (bool) $value;
    }
}
