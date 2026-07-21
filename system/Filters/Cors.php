<?php

declare(strict_types=1);



namespace CodeIgniter\Filters;

use CodeIgniter\HTTP\Cors as CorsService;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;


class Cors implements FilterInterface
{
    private ?CorsService $cors = null;

    
    public function __construct(array $config = [])
    {
        if ($config !== []) {
            $this->cors = new CorsService($config);
        }
    }

    
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! $request instanceof IncomingRequest) {
            return null;
        }

        $this->createCorsService($arguments);

        
        $response = service('response');

        if ($this->cors->isPreflightRequest($request)) {
            $response = $this->cors->handlePreflightRequest($request, $response);

            
            
            
            
            $response->appendHeader('Vary', 'Access-Control-Request-Method');

            return $response;
        }

        if ($request->is('OPTIONS')) {
            
            
            
            
            $response->appendHeader('Vary', 'Access-Control-Request-Method');
        }

        $this->cors->addResponseHeaders($request, $response);

        return null;
    }

    
    private function createCorsService(?array $arguments): void
    {
        $this->cors ??= ($arguments === null) ? CorsService::factory()
            : CorsService::factory($arguments[0]);
    }

    
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        if (! $request instanceof IncomingRequest) {
            return null;
        }

        $this->createCorsService($arguments);

        if ($this->cors->hasResponseHeaders($request, $response)) {
            return null;
        }

        
        
        
        
        if ($request->is('OPTIONS')) {
            $response->appendHeader('Vary', 'Access-Control-Request-Method');
        }

        return $this->cors->addResponseHeaders($request, $response);
    }
}
