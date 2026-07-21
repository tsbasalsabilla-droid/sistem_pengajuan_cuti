<?php

declare(strict_types=1);



namespace CodeIgniter\I18n;

use DateTime;
use Exception;


class TimeLegacy extends DateTime
{
    use TimeTrait;

    
    public function setTimestamp($timestamp): static
    {
        $time = date('Y-m-d H:i:s', $timestamp);

        return static::parse($time, $this->timezone, $this->locale);
    }
}
