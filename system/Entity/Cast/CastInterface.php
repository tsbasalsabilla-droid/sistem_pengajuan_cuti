<?php

declare(strict_types=1);



namespace CodeIgniter\Entity\Cast;


interface CastInterface
{
    
    public static function get($value, array $params = []);

    
    public static function set($value, array $params = []);
}
