<?php

declare(strict_types=1);



namespace CodeIgniter\Entity\Cast;

class ObjectCast extends BaseCast
{
    public static function get($value, array $params = []): object
    {
        return (object) $value;
    }
}
