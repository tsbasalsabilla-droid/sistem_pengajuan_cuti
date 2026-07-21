<?php

declare(strict_types=1);



namespace CodeIgniter\Entity\Cast;

class IntegerCast extends BaseCast
{
    public static function get($value, array $params = []): int
    {
        return (int) $value;
    }
}
