<?php

declare(strict_types=1);



namespace CodeIgniter\Test\Mock;

use CodeIgniter\Log\Handlers\HandlerInterface;
use Config\Logger;
use Tests\Support\Log\Handlers\TestHandler;

class MockLogger extends Logger
{
    
    public $threshold = 9;

    
    public string $dateFormat = 'Y-m-d';

    
    public array $handlers = [
        
        TestHandler::class => [
            
            'handles' => [
                'critical',
                'alert',
                'emergency',
                'debug',
                'error',
                'info',
                'notice',
                'warning',
            ],

            
            'path' => '',
        ],
    ];
}
