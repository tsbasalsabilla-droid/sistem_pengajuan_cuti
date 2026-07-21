<?php

declare(strict_types=1);



namespace CodeIgniter\Commands\Utilities\Routes;

use CodeIgniter\Filters\Filters;
use CodeIgniter\HTTP\Method;
use CodeIgniter\HTTP\Request;
use CodeIgniter\Router\Router;
use Config\Filters as FiltersConfig;


final  class FilterCollector
{
    public function __construct(
        
        private bool $resetRoutes = false,
    ) {
    }

    
    public function get(string $method, string $uri): array
    {
        if ($method === strtolower($method)) {
            @trigger_error(
                'Passing lowercase HTTP method "' . $method . '" is deprecated.'
                . ' Use uppercase HTTP method like "' . strtoupper($method) . '".',
                E_USER_DEPRECATED,
            );
        }

        
        $method = strtoupper($method);

        if ($method === 'CLI') {
            return [
                'before' => [],
                'after'  => [],
            ];
        }

        $request = service('incomingrequest', null, false);
        $request->setMethod($method);

        $router  = $this->createRouter($request);
        $filters = $this->createFilters($request);

        $finder = new FilterFinder($router, $filters);

        return $finder->find($uri);
    }

    
    public function getClasses(string $method, string $uri): array
    {
        if ($method === strtolower($method)) {
            @trigger_error(
                'Passing lowercase HTTP method "' . $method . '" is deprecated.'
                . ' Use uppercase HTTP method like "' . strtoupper($method) . '".',
                E_USER_DEPRECATED,
            );
        }

        
        $method = strtoupper($method);

        if ($method === 'CLI') {
            return [
                'before' => [],
                'after'  => [],
            ];
        }

        $request = service('incomingrequest', null, false);
        $request->setMethod($method);

        $router  = $this->createRouter($request);
        $filters = $this->createFilters($request);

        $finder = new FilterFinder($router, $filters);

        return $finder->findClasses($uri);
    }

    
    public function getRequiredFilters(): array
    {
        $request = service('incomingrequest', null, false);
        $request->setMethod(Method::GET);

        $router  = $this->createRouter($request);
        $filters = $this->createFilters($request);

        $finder = new FilterFinder($router, $filters);

        return $finder->getRequiredFilters();
    }

    
    public function getRequiredFilterClasses(): array
    {
        $request = service('incomingrequest', null, false);
        $request->setMethod(Method::GET);

        $router  = $this->createRouter($request);
        $filters = $this->createFilters($request);

        $finder = new FilterFinder($router, $filters);

        return $finder->getRequiredFilterClasses();
    }

    private function createRouter(Request $request): Router
    {
        $routes = service('routes');

        if ($this->resetRoutes) {
            $routes->resetRoutes();
        }

        return new Router($routes, $request);
    }

    private function createFilters(Request $request): Filters
    {
        $config = config(FiltersConfig::class);

        return new Filters($config, $request, service('response'));
    }
}
