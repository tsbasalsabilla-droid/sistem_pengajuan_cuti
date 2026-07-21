<?php

declare(strict_types=1);



namespace CodeIgniter\Test\Mock;

use CodeIgniter\Exceptions\BadMethodCallException;
use CodeIgniter\View\Table;

class MockTable extends Table
{
    
    public function __call($method, $params)
    {
        if (is_callable([$this, '_' . $method])) {
            return call_user_func_array([$this, '_' . $method], $params);
        }

        throw new BadMethodCallException('Method ' . $method . ' was not found');
    }
}
