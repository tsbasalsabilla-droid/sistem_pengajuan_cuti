<?php

declare(strict_types=1);



namespace CodeIgniter\Entity\Cast;

use CodeIgniter\I18n\Time;
use DateTimeInterface;
use Exception;

class DatetimeCast extends BaseCast
{
    
    public static function get($value, array $params = [])
    {
        if ($value instanceof Time) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return Time::createFromInstance($value);
        }

        if (is_numeric($value)) {
            return Time::createFromTimestamp((int) $value, date_default_timezone_get());
        }

        if (is_string($value)) {
            return Time::parse($value);
        }

        return $value;
    }
}
