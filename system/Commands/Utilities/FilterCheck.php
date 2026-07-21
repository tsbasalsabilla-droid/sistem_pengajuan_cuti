<?php

declare(strict_types=1);



namespace CodeIgniter\Commands\Utilities;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Commands\Utilities\Routes\FilterCollector;


class FilterCheck extends BaseCommand
{
    
    protected $group = 'CodeIgniter';

    
    protected $name = 'filter:check';

    
    protected $description = 'Check filters for a route.';

    
    protected $usage = 'filter:check <HTTP method> <route>';

    
    protected $arguments = [
        'method' => 'The HTTP method. GET, POST, PUT, etc.',
        'route'  => 'The route (URI path) to check filters.',
    ];

    
    protected $options = [];

    
    public function run(array $params)
    {
        if (! $this->checkParams($params)) {
            return EXIT_ERROR;
        }

        $method = $params[0];
        $route  = $params[1];

        
        service('routes')->loadRoutes();

        $filterCollector = new FilterCollector();

        $filters = $filterCollector->get($method, $route);

        
        if ($filters['before'] === ['<unknown>']) {
            CLI::error(
                "Can't find a route: " .
                CLI::color(
                    '"' . strtoupper($method) . ' ' . $route . '"',
                    'black',
                    'light_gray',
                ),
            );

            return EXIT_ERROR;
        }

        $this->showTable($filterCollector, $filters, $method, $route);
        $this->showFilterClasses($filterCollector, $method, $route);

        return EXIT_SUCCESS;
    }

    
    private function checkParams(array $params): bool
    {
        if (! isset($params[0], $params[1])) {
            CLI::error('You must specify a HTTP verb and a route.');
            CLI::write('  Usage: ' . $this->usage);
            CLI::write('Example: filter:check GET /');
            CLI::write('         filter:check PUT products/1');

            return false;
        }

        return true;
    }

    
    private function showTable(
        FilterCollector $filterCollector,
        array $filters,
        string $method,
        string $route,
    ): void {
        $thead = [
            'Method',
            'Route',
            'Before Filters',
            'After Filters',
        ];

        $required = $filterCollector->getRequiredFilters();

        $coloredRequired = $this->colorItems($required);

        $before = array_merge($coloredRequired['before'], $filters['before']);
        $after  = array_merge($filters['after'], $coloredRequired['after']);

        $tbody   = [];
        $tbody[] = [
            strtoupper($method),
            $route,
            implode(' ', $before),
            implode(' ', $after),
        ];

        CLI::table($tbody, $thead);
    }

    
    private function colorItems(array $array): array
    {
        return array_map(function ($item): array|string {
            if (is_array($item)) {
                return $this->colorItems($item);
            }

            return CLI::color($item, 'yellow');
        }, $array);
    }

    private function showFilterClasses(
        FilterCollector $filterCollector,
        string $method,
        string $route,
    ): void {
        $requiredFilterClasses = $filterCollector->getRequiredFilterClasses();
        $filterClasses         = $filterCollector->getClasses($method, $route);

        $coloredRequiredFilterClasses = $this->colorItems($requiredFilterClasses);

        $classList = [
            'before' => array_merge($coloredRequiredFilterClasses['before'], $filterClasses['before']),
            'after'  => array_merge($filterClasses['after'], $coloredRequiredFilterClasses['after']),
        ];

        foreach ($classList as $position => $classes) {
            CLI::write(ucfirst($position) . ' Filter Classes:', 'cyan');
            CLI::write(implode(' → ', $classes));
        }
    }
}
