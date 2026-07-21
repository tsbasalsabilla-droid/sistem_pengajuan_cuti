<?php

declare(strict_types=1);



namespace CodeIgniter\Router\Attributes;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

interface RouteAttributeInterface
{
    
    public function before(RequestInterface $request): RequestInterface|ResponseInterface|null;

    
    public function after(RequestInterface $request, ResponseInterface $response): ?ResponseInterface;
}
