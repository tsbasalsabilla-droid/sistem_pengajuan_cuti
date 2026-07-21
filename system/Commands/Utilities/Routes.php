<?php

declare(strict_types=1);



namespace CodeIgniter\Commands\Utilities;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Commands\Utilities\Routes\AutoRouteCollector;
use CodeIgniter\Commands\Utilities\Routes\AutoRouterImproved\AutoRouteCollector as AutoRouteCollectorImproved;
use CodeIgniter\Commands\Utilities\Routes\FilterCollector;
use CodeIgniter\Commands\Utilities\Routes\SampleURIGenerator;
use CodeIgniter\Router\DefinedRouteCollector;
use CodeIgniter\Router\Router;
use Config\Feature;
use Config\Routing;


class Routes extends BaseCommand
{
    
    protected $group = 'CodeIgniter';

    
    protected $name = 'routes';

    
    protected $description = 'Displays all routes.';

    
    protected $usage = 'routes';

    
    protected $arguments = [];

    
    protected $options = [
        '-h'     => 'Sort by Handler.',
        '--host' => 'Specify hostname in request URI.',
    ];

    
    public function run(array $params)
    {
        $sortByHandler = array_key_exists('h', $params);
        $host          = $params['host'] ?? null;

        
        if ($host !== null) {
            service('superglobals')->setServer('HTTP_HOST', $host);
        }

        $collection = service('routes')->loadRoutes();

        
        if ($host !== null) {
            service('superglobals')->unsetServer('HTTP_HOST');
        }

        $methods = Router::HTTP_METHODS;

        $tbody           = [];
        $uriGenerator    = new SampleURIGenerator();
        $filterCollector = new FilterCollector();

        $definedRouteCollector = new DefinedRouteCollector($collection);

        foreach ($definedRouteCollector->collect() as $route) {
            $sampleUri = $uriGenerator->get($route['route']);
            $filters   = $filterCollector->get($route['method'], $sampleUri);

            $routeName = ($route['route'] === $route['name']) ? '»' : $route['name'];

            $tbody[] = [
                strtoupper($route['method']),
                $route['route'],
                $routeName,
                $route['handler'],
                implode(' ', array_map(class_basename(...), $filters['before'])),
                implode(' ', array_map(class_basename(...), $filters['after'])),
            ];
        }

        if ($collection->shouldAutoRoute()) {
            $autoRoutesImproved = config(Feature::class)->autoRoutesImproved ?? false;

            if ($autoRoutesImproved) {
                $autoRouteCollector = new AutoRouteCollectorImproved(
                    $collection->getDefaultNamespace(),
                    $collection->getDefaultController(),
                    $collection->getDefaultMethod(),
                    $methods,
                    $collection->getRegisteredControllers('*'),
                );

                $autoRoutes = $autoRouteCollector->get();

                
                $routingConfig = config(Routing::class);

                if ($routingConfig instanceof Routing) {
                    foreach ($routingConfig->moduleRoutes as $uri => $namespace) {
                        $autoRouteCollector = new AutoRouteCollectorImproved(
                            $namespace,
                            $collection->getDefaultController(),
                            $collection->getDefaultMethod(),
                            $methods,
                            $collection->getRegisteredControllers('*'),
                            $uri,
                        );

                        $autoRoutes = [...$autoRoutes, ...$autoRouteCollector->get()];
                    }
                }
            } else {
                $autoRouteCollector = new AutoRouteCollector(
                    $collection->getDefaultNamespace(),
                    $collection->getDefaultController(),
                    $collection->getDefaultMethod(),
                );

                $autoRoutes = $autoRouteCollector->get();

                foreach ($autoRoutes as &$routes) {
                    
                    $filters = $filterCollector->get('AUTO', $uriGenerator->get($routes[1]));

                    $routes[] = implode(' ', array_map(class_basename(...), $filters['before']));
                    $routes[] = implode(' ', array_map(class_basename(...), $filters['after']));
                }
            }

            $tbody = [...$tbody, ...$autoRoutes];
        }

        $thead = [
            'Method',
            'Route',
            'Name',
            $sortByHandler ? 'Handler ↓' : 'Handler',
            'Before Filters',
            'After Filters',
        ];

        
        if ($sortByHandler) {
            usort($tbody, static fn ($handler1, $handler2): int => strcmp($handler1[3], $handler2[3]));
        }

        if ($host !== null) {
            CLI::write('Host: ' . $host);
        }

        CLI::table($tbody, $thead);

        $this->showRequiredFilters();
    }

    private function showRequiredFilters(): void
    {
        $filterCollector = new FilterCollector();

        $required = $filterCollector->getRequiredFilters();

        $filters = [];

        foreach ($required['before'] as $filter) {
            $filters[] = CLI::color($filter, 'yellow');
        }

        CLI::write('Required Before Filters: ' . implode(', ', $filters));

        $filters = [];

        foreach ($required['after'] as $filter) {
            $filters[] = CLI::color($filter, 'yellow');
        }

        CLI::write(' Required After Filters: ' . implode(', ', $filters));
    }
}
