<?php

declare(strict_types=1);



namespace CodeIgniter\Test\Mock;

use CodeIgniter\Database\BaseBuilder;

class MockBuilder extends BaseBuilder
{
    
    protected $supportedIgnoreStatements = [
        'update' => 'IGNORE',
        'insert' => 'IGNORE',
        'delete' => 'IGNORE',
    ];
}
