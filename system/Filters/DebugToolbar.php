<?php

declare(strict_types=1);



namespace CodeIgniter\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;


class DebugToolbar implements FilterInterface
{
    
    public function before(RequestInterface $request, $arguments = null)
    {
        return null;
    }

    
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        service('toolbar')->prepare($request, $response);

        return null;
    }
}
