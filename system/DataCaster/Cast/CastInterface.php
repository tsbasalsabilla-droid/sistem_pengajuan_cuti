<?php

declare(strict_types=1);



namespace CodeIgniter\DataCaster\Cast;

interface CastInterface
{
    
    public static function get(
        mixed $value,
        array $params = [],
        ?object $helper = null,
    ): mixed;

    
    public static function set(
        mixed $value,
        array $params = [],
        ?object $helper = null,
    ): mixed;
}
