<?php

declare(strict_types=1);



namespace CodeIgniter\DataCaster\Cast;

use CodeIgniter\I18n\Time;


class TimestampCast extends BaseCast
{
    public static function get(
        mixed $value,
        array $params = [],
        ?object $helper = null,
    ): Time {
        if (! is_int($value) && ! is_string($value)) {
            self::invalidTypeValueError($value);
        }

        return Time::createFromTimestamp((int) $value, date_default_timezone_get());
    }

    public static function set(
        mixed $value,
        array $params = [],
        ?object $helper = null,
    ): int {
        if (! $value instanceof Time) {
            self::invalidTypeValueError($value);
        }

        return $value->getTimestamp();
    }
}
