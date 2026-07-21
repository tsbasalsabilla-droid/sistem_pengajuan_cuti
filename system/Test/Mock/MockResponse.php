<?php

declare(strict_types=1);



namespace CodeIgniter\Test\Mock;

use CodeIgniter\HTTP\Response;

class MockResponse extends Response
{
    
    protected $pretend = true;

    
    public function getPretend()
    {
        return $this->pretend;
    }

    
    public function misbehave()
    {
        $this->statusCode = 0;
    }
}
