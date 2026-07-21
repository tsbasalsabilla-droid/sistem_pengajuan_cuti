<?php

declare(strict_types=1);



namespace CodeIgniter\Entity\Cast;

use CodeIgniter\HTTP\URI;

class URICast extends BaseCast
{
    public static function get($value, array $params = []): URI
    {
        return $value instanceof URI ? $value : new URI($value);
    }
}
