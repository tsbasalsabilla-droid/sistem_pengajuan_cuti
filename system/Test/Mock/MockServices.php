<?php

declare(strict_types=1);



namespace CodeIgniter\Test\Mock;

use CodeIgniter\Autoloader\FileLocator;
use CodeIgniter\Config\BaseService;

class MockServices extends BaseService
{
    
    public $psr4 = [
        'Tests/Support' => TESTPATH . '_support/',
    ];

    
    public $classmap = [];

    public function __construct()
    {
        
        
    }

    public static function locator(bool $getShared = true)
    {
        return new FileLocator(static::autoloader());
    }
}
