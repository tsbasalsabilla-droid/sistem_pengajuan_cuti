<?php

declare(strict_types=1);



namespace CodeIgniter\Entity\Cast;

use CodeIgniter\Entity\Exceptions\CastException;

class TimestampCast extends BaseCast
{
    public static function get($value, array $params = [])
    {
        $value = strtotime($value);

        if ($value === false) {
            throw CastException::forInvalidTimestamp();
        }

        return $value;
    }
}
