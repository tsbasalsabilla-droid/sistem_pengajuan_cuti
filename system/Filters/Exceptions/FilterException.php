<?php

declare(strict_types=1);



namespace CodeIgniter\Filters\Exceptions;

use CodeIgniter\Exceptions\ConfigException;


class FilterException extends ConfigException
{
    
    public static function forNoAlias(string $alias)
    {
        return new static(lang('Filters.noFilter', [$alias]));
    }

    
    public static function forIncorrectInterface(string $class)
    {
        return new static(lang('Filters.incorrectInterface', [$class]));
    }
}
