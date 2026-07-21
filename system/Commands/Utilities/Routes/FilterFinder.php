<?php

declare(strict_types=1);



namespace CodeIgniter\Commands\Utilities\Routes;

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Filters\Filters;
use CodeIgniter\HTTP\Exceptions\BadRequestException;
use CodeIgniter\HTTP\Exceptions\RedirectException;
use CodeIgniter\Router\Router;
use Config\Feature;


final  class FilterFinder
{
    private Router $router;
    private Filters $filters;

    public function __construct(?Router $router = null, ?Filters $filters = null)
    {
        $this->router  = $router ?? service('router');
        $this->filters = $filters ?? service('filters');
    }

    private function getRouteFilters(string $uri): array
    {
        $this->router->handle($uri);

        return $this->router->getFilters();
    }

    
    public function find(string $uri): array
    {
        $this->filters->reset();

        try {
            
            $routeFilters = $this->getRouteFilters($uri);
            $this->filters->enableFilters($routeFilters, 'before');
            $oldFilterOrder = config(Feature::class)->oldFilterOrder ?? false;
            if (! $oldFilterOrder) {
                $routeFilters = array_reverse($routeFilters);
            }
            $this->filters->enableFilters($routeFilters, 'after');

            $this->filters->initialize($uri);

            return $this->filters->getFilters();
        } catch (RedirectException) {
            return [
                'before' => [],
                'after'  => [],
            ];
        } catch (BadRequestException|PageNotFoundException) {
            return [
                'before' => ['<unknown>'],
                'after'  => ['<unknown>'],
            ];
        }
    }

    
    public function findClasses(string $uri): array
    {
        $this->filters->reset();

        try {
            
            $routeFilters = $this->getRouteFilters($uri);
            $this->filters->enableFilters($routeFilters, 'before');
            $oldFilterOrder = config(Feature::class)->oldFilterOrder ?? false;
            if (! $oldFilterOrder) {
                $routeFilters = array_reverse($routeFilters);
            }
            $this->filters->enableFilters($routeFilters, 'after');

            $this->filters->initialize($uri);

            $filterClassList = $this->filters->getFiltersClass();

            $filterClasses = [
                'before' => [],
                'after'  => [],
            ];

            foreach ($filterClassList['before'] as $classInfo) {
                $classWithArguments = ($classInfo[1] === []) ? $classInfo[0]
                    : $classInfo[0] . ':' . implode(',', $classInfo[1]);

                $filterClasses['before'][] = $classWithArguments;
            }

            foreach ($filterClassList['after'] as $classInfo) {
                $classWithArguments = ($classInfo[1] === []) ? $classInfo[0]
                    : $classInfo[0] . ':' . implode(',', $classInfo[1]);

                $filterClasses['after'][] = $classWithArguments;
            }

            return $filterClasses;
        } catch (RedirectException) {
            return [
                'before' => [],
                'after'  => [],
            ];
        } catch (BadRequestException|PageNotFoundException) {
            return [
                'before' => ['<unknown>'],
                'after'  => ['<unknown>'],
            ];
        }
    }

    
    public function getRequiredFilters(): array
    {
        [$requiredBefore] = $this->filters->getRequiredFilters('before');
        [$requiredAfter]  = $this->filters->getRequiredFilters('after');

        return [
            'before' => $requiredBefore,
            'after'  => $requiredAfter,
        ];
    }

    
    public function getRequiredFilterClasses(): array
    {
        $before = $this->filters->getRequiredClasses('before');
        $after  = $this->filters->getRequiredClasses('after');

        $requiredBefore = [];
        $requiredAfter  = [];

        foreach ($before as $classInfo) {
            $requiredBefore[] = $classInfo[0];
        }

        foreach ($after as $classInfo) {
            $requiredAfter[] = $classInfo[0];
        }

        return [
            'before' => $requiredBefore,
            'after'  => $requiredAfter,
        ];
    }
}
