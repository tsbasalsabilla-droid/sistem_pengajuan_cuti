<?php

declare(strict_types=1);



namespace CodeIgniter\Entity\Cast;

abstract class BaseCast implements CastInterface
{
    public static function get($value, array $params = [])
    {
        return $value;
    }

    public static function set($value, array $params = [])
    {
        return $value;
    }
}
