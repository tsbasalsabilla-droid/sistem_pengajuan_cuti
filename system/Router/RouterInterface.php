<?php

declare(strict_types=1);



namespace CodeIgniter\Router;

use Closure;
use CodeIgniter\HTTP\Request;
use CodeIgniter\HTTP\ResponseInterface;


interface RouterInterface
{
    
    public function __construct(RouteCollectionInterface $routes, ?Request $request = null);

    
    public function handle(?string $uri = null);

    
    public function controllerName();

    
    public function methodName();

    
    public function params();

    
    public function setIndexPage($page);
}
