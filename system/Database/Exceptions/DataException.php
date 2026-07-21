<?php

declare(strict_types=1);



namespace CodeIgniter\Database\Exceptions;

use CodeIgniter\Exceptions\DebugTraceableTrait;
use CodeIgniter\Exceptions\RuntimeException;

class DataException extends RuntimeException implements ExceptionInterface
{
    use DebugTraceableTrait;

    
    public static function forInvalidMethodTriggered(string $method)
    {
        return new static(lang('Database.invalidEvent', [$method]));
    }

    
    public static function forEmptyDataset(string $mode)
    {
        return new static(lang('Database.emptyDataset', [$mode]));
    }

    
    public static function forEmptyPrimaryKey(string $mode)
    {
        return new static(lang('Database.emptyPrimaryKey', [$mode]));
    }

    
    public static function forInvalidArgument(string $argument)
    {
        return new static(lang('Database.invalidArgument', [$argument]));
    }

    
    public static function forInvalidAllowedFields(string $model)
    {
        return new static(lang('Database.invalidAllowedFields', [$model]));
    }

    
    public static function forTableNotFound(string $table)
    {
        return new static(lang('Database.tableNotFound', [$table]));
    }

    
    public static function forEmptyInputGiven(string $argument)
    {
        return new static(lang('Database.forEmptyInputGiven', [$argument]));
    }

    
    public static function forFindColumnHaveMultipleColumns()
    {
        return new static(lang('Database.forFindColumnHaveMultipleColumns'));
    }
}
