<?php

declare(strict_types=1);



use CodeIgniter\I18n\Time;



if (! function_exists('now')) {
    
    function now(?string $timezone = null): int
    {
        $timezone = ($timezone === null || $timezone === '') ? app_timezone() : $timezone;

        if ($timezone === 'local' || $timezone === date_default_timezone_get()) {
            return Time::now()->getTimestamp();
        }

        $time = Time::now($timezone);
        sscanf(
            $time->format('j-n-Y G:i:s'),
            '%d-%d-%d %d:%d:%d',
            $day,
            $month,
            $year,
            $hour,
            $minute,
            $second,
        );

        return mktime($hour, $minute, $second, $month, $day, $year);
    }
}

if (! function_exists('timezone_select')) {
    
    function timezone_select(string $class = '', string $default = '', int $what = DateTimeZone::ALL, ?string $country = null): string
    {
        $timezones = DateTimeZone::listIdentifiers($what, $country);

        $buffer = "<select name='timezone' class='{$class}'>\n";

        foreach ($timezones as $timezone) {
            $selected = ($timezone === $default) ? 'selected' : '';
            $buffer .= "<option value='{$timezone}' {$selected}>{$timezone}</option>\n";
        }

        return $buffer . ("</select>\n");
    }
}
