<?php

declare(strict_types=1);



namespace CodeIgniter\I18n;

use DateTimeImmutable;
use Stringable;


class Time extends DateTimeImmutable implements Stringable
{
    use TimeTrait;
}
