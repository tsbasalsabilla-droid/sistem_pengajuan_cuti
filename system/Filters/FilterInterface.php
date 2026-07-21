<?php

declare(strict_types=1);



namespace CodeIgniter\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;


interface FilterInterface
{
    
    public function before(RequestInterface $request, $arguments = null);

    
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null);
}
