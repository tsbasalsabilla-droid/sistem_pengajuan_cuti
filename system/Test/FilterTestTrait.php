<?php

declare(strict_types=1);



namespace CodeIgniter\Test;

use Closure;
use CodeIgniter\Exceptions\InvalidArgumentException;
use CodeIgniter\Exceptions\RuntimeException;
use CodeIgniter\Filters\Exceptions\FilterException;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\Filters\Filters;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Router\RouteCollection;
use Config\Filters as FiltersConfig;


trait FilterTestTrait
{
    
    private $doneFilterSetUp = false;

    
    protected $request;

    
    protected $response;

    
    protected $filtersConfig;

    
    protected $filters;

    
    protected $collection;

    
    
    

    
    protected function setUpFilterTestTrait(): void
    {
        if ($this->doneFilterSetUp === true) {
            return;
        }

        
        
        
        $this->request ??= clone service('request');
        $this->response ??= clone service('response');

        
        $this->filtersConfig ??= config(FiltersConfig::class);
        $this->filters ??= new Filters($this->filtersConfig, $this->request, $this->response);

        if ($this->collection === null) {
            $this->collection = service('routes')->loadRoutes();
        }

        $this->doneFilterSetUp = true;
    }

    
    
    

    
    protected function getFilterCaller($filter, string $position): Closure
    {
        if (! in_array($position, ['before', 'after'], true)) {
            throw new InvalidArgumentException('Invalid filter position passed: ' . $position);
        }

        if ($filter instanceof FilterInterface) {
            $filterInstances = [$filter];
        }

        if (is_string($filter)) {
            
            if (! str_contains($filter, '\\')) {
                if (! isset($this->filtersConfig->aliases[$filter])) {
                    throw new RuntimeException("No filter found with alias '{$filter}'");
                }

                $filterClasses = (array) $this->filtersConfig->aliases[$filter];
            } else {
                
                $filterClasses = [$filter];
            }

            $filterInstances = [];

            foreach ($filterClasses as $class) {
                
                $filter = new $class();

                if (! $filter instanceof FilterInterface) {
                    throw FilterException::forIncorrectInterface($filter::class);
                }

                $filterInstances[] = $filter;
            }
        }

        $request = clone $this->request;

        if ($position === 'before') {
            return static function (?array $params = null) use ($filterInstances, $request) {
                $result = null;

                foreach ($filterInstances as $filter) {
                    $result = $filter->before($request, $params);

                    
                    
                    if ($result instanceof RequestInterface) {
                        $request = $result;

                        continue;
                    }
                    if ($result instanceof ResponseInterface) {
                        return $result;
                    }
                    if (empty($result)) {
                        continue;
                    }
                }

                return $result;
            };
        }

        $response = clone $this->response;

        return static function (?array $params = null) use ($filterInstances, $request, $response) {
            $result = null;

            foreach ($filterInstances as $filter) {
                $result = $filter->after($request, $response, $params);

                
                
                if ($result instanceof ResponseInterface) {
                    $response = $result;

                    continue;
                }
            }

            return $result;
        };
    }

    
    protected function getFiltersForRoute(string $route, string $position): array
    {
        if (! in_array($position, ['before', 'after'], true)) {
            throw new InvalidArgumentException('Invalid filter position passed:' . $position);
        }

        $this->filters->reset();

        $routeFilters = $this->collection->getFiltersForRoute($route);

        if ($routeFilters !== []) {
            $this->filters->enableFilters($routeFilters, $position);
        }

        $aliases = $this->filters->initialize($route)->getFilters();

        $this->filters->reset();

        return $aliases[$position];
    }

    
    
    

    
    protected function assertFilter(string $route, string $position, string $alias): void
    {
        $filters = $this->getFiltersForRoute($route, $position);

        $this->assertContains(
            $alias,
            $filters,
            "Filter '{$alias}' does not apply to '{$route}'.",
        );
    }

    
    protected function assertNotFilter(string $route, string $position, string $alias)
    {
        $filters = $this->getFiltersForRoute($route, $position);

        $this->assertNotContains(
            $alias,
            $filters,
            "Filter '{$alias}' applies to '{$route}' when it should not.",
        );
    }

    
    protected function assertHasFilters(string $route, string $position)
    {
        $filters = $this->getFiltersForRoute($route, $position);

        $this->assertNotEmpty(
            $filters,
            "No filters found for '{$route}' when at least one was expected.",
        );
    }

    
    protected function assertNotHasFilters(string $route, string $position)
    {
        $filters = $this->getFiltersForRoute($route, $position);

        $this->assertSame(
            [],
            $filters,
            "Found filters for '{$route}' when none were expected: " . implode(', ', $filters) . '.',
        );
    }
}
