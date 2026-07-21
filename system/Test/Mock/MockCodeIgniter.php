<?php

declare(strict_types=1);



namespace CodeIgniter\Test\Mock;

use CodeIgniter\CodeIgniter;

class MockCodeIgniter extends CodeIgniter
{
    protected ?string $context = 'web';

    
    protected function callExit($code)
    {
        
    }
}
