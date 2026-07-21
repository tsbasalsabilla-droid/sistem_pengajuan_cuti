<?php

declare(strict_types=1);



namespace CodeIgniter\Entity\Cast;

class FloatCast extends BaseCast
{
    public static function get($value, array $params = []): float
    {
        return (float) $value;
    }
}
