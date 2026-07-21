<?php

declare(strict_types=1);



namespace CodeIgniter\Commands\Utilities\Routes\AutoRouterImproved;

use CodeIgniter\Commands\Utilities\Routes\ControllerFinder;
use CodeIgniter\Commands\Utilities\Routes\FilterCollector;


final  class AutoRouteCollector
{
    
    public function __construct(
        private string $namespace,
        private string $defaultController,
        private string $defaultMethod,
        private array $httpMethods,
        private array $protectedControllers,
        private string $prefix = '',
    ) {
    }

    
    public function get(): array
    {
        $finder = new ControllerFinder($this->namespace);
        $reader = new ControllerMethodReader($this->namespace, $this->httpMethods);

        $tbody = [];

        foreach ($finder->find() as $class) {
            
            if (in_array('\\' . $class, $this->protectedControllers, true)) {
                continue;
            }

            $routes = $reader->read(
                $class,
                $this->defaultController,
                $this->defaultMethod,
            );

            if ($routes === []) {
                continue;
            }

            $routes = $this->addFilters($routes);

            foreach ($routes as $item) {
                $route = $item['route'] . $item['route_params'];

                
                if ($this->prefix !== '' && $route === '/') {
                    $route = $this->prefix;
                } elseif ($this->prefix !== '') {
                    $route = $this->prefix . '/' . $route;
                }

                $tbody[] = [
                    strtoupper($item['method']) . '(auto)',
                    $route,
                    '',
                    $item['handler'],
                    $item['before'],
                    $item['after'],
                ];
            }
        }

        return $tbody;
    }

    
    private function addFilters(array $routes): array
    {
        $filterCollector = new FilterCollector(true);

        foreach ($routes as &$route) {
            $routePath = $route['route'];

            
            if ($this->prefix !== '' && $route === '/') {
                $routePath = $this->prefix;
            } elseif ($this->prefix !== '') {
                $routePath = $this->prefix . '/' . $routePath;
            }

            
            $sampleUri      = $this->generateSampleUri($route);
            $filtersLongest = $filterCollector->get($route['method'], $routePath . $sampleUri);

            
            $sampleUri       = $this->generateSampleUri($route, false);
            $filtersShortest = $filterCollector->get($route['method'], $routePath . $sampleUri);

            
            $filters = [
                'before' => array_intersect($filtersLongest['before'], $filtersShortest['before']),
                'after'  => array_intersect($filtersLongest['after'], $filtersShortest['after']),
            ];

            $route['before'] = implode(' ', array_map(class_basename(...), $filters['before']));
            $route['after']  = implode(' ', array_map(class_basename(...), $filters['after']));
        }

        return $routes;
    }

    private function generateSampleUri(array $route, bool $longest = true): string
    {
        $sampleUri = '';

        if (isset($route['params'])) {
            $i = 1;

            foreach ($route['params'] as $required) {
                if ($longest && ! $required) {
                    $sampleUri .= '/' . $i++;
                }
            }
        }

        return $sampleUri;
    }
}
