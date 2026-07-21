<?php

declare(strict_types=1);



namespace CodeIgniter\Test\Mock;

use CodeIgniter\Events\Events;

class MockEvents extends Events
{
    
    public function getListeners()
    {
        return self::$listeners;
    }

    
    public function getEventsFile()
    {
        return self::$files;
    }

    
    public function getSimulate()
    {
        return self::$simulate;
    }

    
    public function unInitialize()
    {
        static::$initialized = false;
    }
}
