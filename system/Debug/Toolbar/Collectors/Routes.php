<?php

declare(strict_types=1);



namespace CodeIgniter\Debug\Toolbar\Collectors;

use CodeIgniter\Router\DefinedRouteCollector;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;


class Routes extends BaseCollector
{
    
    protected $hasTimeline = false;

    
    protected $hasTabContent = true;

    
    protected $title = 'Routes';

    
    public function display(): array
    {
        $rawRoutes = service('routes', true);
        $router    = service('router', null, null, true);

        
        
        if (is_callable($router->controllerName())) {
            $method = new ReflectionFunction($router->controllerName());
        } else {
            try {
                $method = new ReflectionMethod($router->controllerName(), $router->methodName());
            } catch (ReflectionException) {
                try {
                    
                    
                    $method = new ReflectionMethod($router->controllerName(), '_remap');
                } catch (ReflectionException) {
                    
                    return [
                        'matchedRoute' => [],
                        'routes'       => [],
                    ];
                }
            }
        }

        $rawParams = $method->getParameters();

        $params = [];

        foreach ($rawParams as $key => $param) {
            $params[] = [
                'name'  => '$' . $param->getName() . ' = ',
                'value' => $router->params()[$key] ??
                    ' <empty> | default: '
                    . var_export(
                        $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null,
                        true,
                    ),
            ];
        }

        $matchedRoute = [
            [
                'directory'  => $router->directory(),
                'controller' => $router->controllerName(),
                'method'     => $router->methodName(),
                'paramCount' => count($router->params()),
                'truePCount' => count($params),
                'params'     => $params,
            ],
        ];

        
        $routes = [];

        $definedRouteCollector = new DefinedRouteCollector($rawRoutes);

        foreach ($definedRouteCollector->collect() as $route) {
            
            if ($route['handler'] !== '(Closure)') {
                $routes[] = [
                    'method'  => strtoupper($route['method']),
                    'route'   => $route['route'],
                    'handler' => $route['handler'],
                ];
            }
        }

        return [
            'matchedRoute' => $matchedRoute,
            'routes'       => $routes,
        ];
    }

    
    public function getBadgeValue(): int
    {
        $rawRoutes = service('routes', true);

        return count($rawRoutes->getRoutes());
    }

    
    public function icon(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAFDSURBVEhL7ZRNSsNQFIUjVXSiOFEcuQIHDpzpxC0IGYeE/BEInbWlCHEDLsSiuANdhKDjgm6ggtSJ+l25ldrmmTwIgtgDh/t37r1J+16cX0dRFMtpmu5pWAkrvYjjOB7AETzStBFW+inxu3KUJMmhludQpoflS1zXban4LYqiO224h6VLTHr8Z+z8EpIHFF9gG78nDVmW7UgTHKjsCyY98QP+pcq+g8Ku2s8G8X3f3/I8b038WZTp+bO38zxfFd+I6YY6sNUvFlSDk9CRhiAI1jX1I9Cfw7GG1UB8LAuwbU0ZwQnbRDeEN5qqBxZMLtE1ti9LtbREnMIuOXnyIf5rGIb7Wq8HmlZgwYBH7ORTcKH5E4mpjeGt9fBZcHE2GCQ3Vt7oTNPNg+FXLHnSsHkw/FR+Gg2bB8Ptzrst/v6C/wrH+QB+duli6MYJdQAAAABJRU5ErkJggg==';
    }
}
