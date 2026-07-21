<?php

declare(strict_types=1);



namespace CodeIgniter\Exceptions;


class ModelException extends FrameworkException
{
    
    public static function forNoPrimaryKey(string $modelName)
    {
        return new static(lang('Database.noPrimaryKey', [$modelName]));
    }

    
    public static function forNoDateFormat(string $modelName)
    {
        return new static(lang('Database.noDateFormat', [$modelName]));
    }

    
    public static function forMethodNotAvailable(string $modelName, string $methodName)
    {
        return new static(lang('Database.methodNotAvailable', [$modelName, $methodName]));
    }
}
