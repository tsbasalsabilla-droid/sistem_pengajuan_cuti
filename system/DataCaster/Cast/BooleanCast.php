<?php

declare(strict_types=1);



namespace CodeIgniter\DataCaster\Cast;


class BooleanCast extends BaseCast
{
    public static function get(
        mixed $value,
        array $params = [],
        ?object $helper = null,
    ): bool {
        
        if ($value === 't') {
            return true;
        }
        if ($value === 'f') {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
