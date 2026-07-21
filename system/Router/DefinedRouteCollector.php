<?php

declare(strict_types=1);



namespace CodeIgniter\Router;

use Closure;
use Generator;


final  class DefinedRouteCollector
{
    public function __construct(private RouteCollectionInterface $routeCollection)
    {
    }

    
    public function collect(): Generator
    {
        $methods = Router::HTTP_METHODS;

        foreach ($methods as $method) {
            $routes = $this->routeCollection->getRoutes($method);

            foreach ($routes as $route => $handler) {
                
                
                $route = (string) $route;

                if (is_string($handler) || $handler instanceof Closure) {
                    if ($handler instanceof Closure) {
                        $view = $this->routeCollection->getRoutesOptions($route, $method)['view'] ?? false;

                        $handler = $view ? '(View) ' . $view : '(Closure)';
                    }

                    $routeName = $this->routeCollection->getRoutesOptions($route, $method)['as'] ?? $route;

                    yield [
                        'method'  => $method,
                        'route'   => $route,
                        'name'    => $routeName,
                        'handler' => $handler,
                    ];
                }
            }
        }
    }
}
