<?php

declare(strict_types=1);



namespace CodeIgniter\Router;


interface AutoRouterInterface
{
    
    public function getRoute(string $uri, string $httpVerb): array;
}
