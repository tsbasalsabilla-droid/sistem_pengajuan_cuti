<?php

declare(strict_types=1);



namespace CodeIgniter\Filters;

use CodeIgniter\Config\Filters as BaseFiltersConfig;
use CodeIgniter\Filters\Exceptions\FilterException;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Feature;
use Config\Filters as FiltersConfig;
use Config\Modules;


class Filters
{
    
    protected $config;

    
    protected $request;

    
    protected $response;

    
    protected $modules;

    
    protected $initialized = false;

    
    protected $filters = [
        'before' => [],
        'after'  => [],
    ];

    
    protected $filtersClass = [
        'before' => [],
        'after'  => [],
    ];

    
    protected array $filterClassInstances = [];

    
    protected $arguments = [];

    
    protected $argumentsClass = [];

    
    public function __construct($config, RequestInterface $request, ResponseInterface $response, ?Modules $modules = null)
    {
        $this->config  = $config;
        $this->request = &$request;
        $this->setResponse($response);

        $this->modules = $modules instanceof Modules ? $modules : new Modules();

        if ($this->modules->shouldDiscover('filters')) {
            $this->discoverFilters();
        }
    }

    
    private function discoverFilters(): void
    {
        $locator = service('locator');

        
        $filters = $this->config;

        $files = $locator->search('Config/Filters.php');

        foreach ($files as $file) {
            
            $className = $locator->getClassname($file);

            
            if ($className === FiltersConfig::class || $className === BaseFiltersConfig::class) {
                continue;
            }

            include $file;
        }
    }

    
    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;
    }

    
    public function run(string $uri, string $position = 'before')
    {
        $this->initialize(strtolower($uri));

        if ($position === 'before') {
            return $this->runBefore($this->filtersClass[$position]);
        }

        
        return $this->runAfter($this->filtersClass[$position]);
    }

    
    private function runBefore(array $filterClassList)
    {
        foreach ($filterClassList as $filterClassInfo) {
            $className = $filterClassInfo[0];
            $arguments = ($filterClassInfo[1] === []) ? null : $filterClassInfo[1];

            $instance = $this->createFilter($className);

            $result = $instance->before($this->request, $arguments);

            if ($result instanceof RequestInterface) {
                $this->request = $result;

                continue;
            }

            
            
            if ($result instanceof ResponseInterface) {
                
                return $result;
            }

            
            if (empty($result)) {
                continue;
            }

            return $result;
        }

        return $this->request;
    }

    
    private function runAfter(array $filterClassList): ResponseInterface
    {
        foreach ($filterClassList as $filterClassInfo) {
            $className = $filterClassInfo[0];
            $arguments = ($filterClassInfo[1] === []) ? null : $filterClassInfo[1];

            $instance = $this->createFilter($className);

            $result = $instance->after($this->request, $this->response, $arguments);

            if ($result instanceof ResponseInterface) {
                $this->response = $result;

                continue;
            }
        }

        return $this->response;
    }

    
    private function createFilter(string $className): FilterInterface
    {
        if (isset($this->filterClassInstances[$className])) {
            return $this->filterClassInstances[$className];
        }

        $instance = new $className();

        if (! $instance instanceof FilterInterface) {
            throw FilterException::forIncorrectInterface($instance::class);
        }

        $this->filterClassInstances[$className] = $instance;

        return $instance;
    }

    
    public function getRequiredClasses(string $position): array
    {
        [$filters, $aliases] = $this->getRequiredFilters($position);

        if ($filters === []) {
            return [];
        }

        $filterClassList = [];

        foreach ($filters as $alias) {
            if (is_array($aliases[$alias])) {
                foreach ($this->config->aliases[$alias] as $class) {
                    $filterClassList[] = [$class, []];
                }
            } else {
                $filterClassList[] = [$aliases[$alias], []];
            }
        }

        return $filterClassList;
    }

    
    public function runRequired(string $position = 'before')
    {
        $filterClassList = $this->getRequiredClasses($position);

        if ($filterClassList === []) {
            return $position === 'before' ? $this->request : $this->response;
        }

        if ($position === 'before') {
            return $this->runBefore($filterClassList);
        }

        
        return $this->runAfter($filterClassList);
    }

    
    public function getRequiredFilters(string $position = 'before'): array
    {
        
        if (! isset($this->config->required[$position])) {
            $baseConfig = config(BaseFiltersConfig::class); 
            $filters    = $baseConfig->required[$position];
            $aliases    = $baseConfig->aliases;
        } else {
            $filters = $this->config->required[$position];
            $aliases = $this->config->aliases;
        }

        if ($filters === []) {
            return [[], $aliases];
        }

        if ($position === 'after') {
            if (in_array('toolbar', $this->filters['after'], true)) {
                
                $filters = $this->setToolbarToLast($filters, true);
            } else {
                
                $filters = $this->setToolbarToLast($filters);
            }
        }

        foreach ($filters as $alias) {
            if (! array_key_exists($alias, $aliases)) {
                throw FilterException::forNoAlias($alias);
            }
        }

        return [$filters, $aliases];
    }

    
    private function setToolbarToLast(array $filters, bool $remove = false): array
    {
        $afters = [];
        $found  = false;

        foreach ($filters as $alias) {
            if ($alias === 'toolbar') {
                $found = true;

                continue;
            }

            $afters[] = $alias;
        }

        if ($found && ! $remove) {
            $afters[] = 'toolbar';
        }

        return $afters;
    }

    
    public function initialize(?string $uri = null)
    {
        if ($this->initialized === true) {
            return $this;
        }

        
        $uri = urldecode($uri ?? '');

        $oldFilterOrder = config(Feature::class)->oldFilterOrder ?? false;
        if ($oldFilterOrder) {
            $this->processGlobals($uri);
            $this->processMethods();
            $this->processFilters($uri);
        } else {
            $this->processFilters($uri);
            $this->processMethods();
            $this->processGlobals($uri);
        }

        
        $this->filters['after'] = $this->setToolbarToLast($this->filters['after']);

        
        
        $this->filters['before'] = array_unique($this->filters['before']);
        $this->filters['after']  = array_unique($this->filters['after']);

        $this->processAliasesToClass('before');
        $this->processAliasesToClass('after');

        $this->initialized = true;

        return $this;
    }

    
    public function reset(): self
    {
        $this->initialized = false;

        $this->arguments = $this->argumentsClass = [];

        $this->filters = $this->filtersClass = [
            'before' => [],
            'after'  => [],
        ];

        return $this;
    }

    
    public function getFilters(): array
    {
        return $this->filters;
    }

    
    public function getFiltersClass(): array
    {
        return $this->filtersClass;
    }

    
    public function addFilter(string $class, ?string $alias = null, string $position = 'before', string $section = 'globals')
    {
        $alias ??= md5($class);

        if (! isset($this->config->{$section})) {
            $this->config->{$section} = [];
        }

        if (! isset($this->config->{$section}[$position])) {
            $this->config->{$section}[$position] = [];
        }

        $this->config->aliases[$alias] = $class;

        $this->config->{$section}[$position][] = $alias;

        return $this;
    }

    
    private function enableFilter(string $filter, string $position = 'before'): void
    {
        
        [$alias, $arguments] = $this->getCleanName($filter);
        $filter              = ($arguments === []) ? $alias : $alias . ':' . implode(',', $arguments);

        if (class_exists($alias)) {
            $this->config->aliases[$alias] = $alias;
        } elseif (! array_key_exists($alias, $this->config->aliases)) {
            throw FilterException::forNoAlias($alias);
        }

        if (! isset($this->filters[$position][$filter])) {
            $this->filters[$position][] = $filter;
        }

        
        
        $this->filters[$position] = array_unique($this->filters[$position]);
    }

    
    private function getCleanName(string $filter): array
    {
        $arguments = [];

        if (! str_contains($filter, ':')) {
            return [$filter, $arguments];
        }

        [$alias, $arguments] = explode(':', $filter);

        $arguments = explode(',', $arguments);
        array_walk($arguments, static function (&$item): void {
            $item = trim($item);
        });

        return [$alias, $arguments];
    }

    
    public function enableFilters(array $filters, string $when = 'before')
    {
        foreach ($filters as $filter) {
            $this->enableFilter($filter, $when);
        }

        return $this;
    }

    
    public function getArguments(?string $key = null)
    {
        return ((string) $key === '') ? $this->arguments : $this->arguments[$key];
    }

    
    
    

    
    protected function processGlobals(?string $uri = null)
    {
        if (! isset($this->config->globals) || ! is_array($this->config->globals)) {
            return;
        }

        $uri = strtolower(trim($uri ?? '', '/ '));

        
        $sets = ['before', 'after'];

        $filters = [];

        foreach ($sets as $set) {
            if (isset($this->config->globals[$set])) {
                
                foreach ($this->config->globals[$set] as $alias => $rules) {
                    $keep = true;
                    if (is_array($rules)) {
                        
                        if (isset($rules['except'])) {
                            
                            $check = $rules['except'];
                            if ($this->checkExcept($uri, $check)) {
                                $keep = false;
                            }
                        }
                    } else {
                        $alias = $rules; 
                    }

                    if ($keep) {
                        $filters[$set][] = $alias;
                    }
                }
            }
        }

        if (isset($filters['before'])) {
            $oldFilterOrder = config(Feature::class)->oldFilterOrder ?? false;
            if ($oldFilterOrder) {
                $this->filters['before'] = array_merge($this->filters['before'], $filters['before']);
            } else {
                $this->filters['before'] = array_merge($filters['before'], $this->filters['before']);
            }
        }

        if (isset($filters['after'])) {
            $this->filters['after'] = array_merge($this->filters['after'], $filters['after']);
        }
    }

    
    protected function processMethods()
    {
        if (! isset($this->config->methods) || ! is_array($this->config->methods)) {
            return;
        }

        $method = $this->request->getMethod();

        $found = false;

        if (array_key_exists($method, $this->config->methods)) {
            $found = true;
        }
        
        
        
        elseif (array_key_exists(strtolower($method), $this->config->methods)) {
            @trigger_error(
                'Setting lowercase HTTP method key "' . strtolower($method) . '" is deprecated.'
                . ' Use uppercase HTTP method like "' . strtoupper($method) . '".',
                E_USER_DEPRECATED,
            );

            $found  = true;
            $method = strtolower($method);
        }

        if ($found) {
            $oldFilterOrder = config(Feature::class)->oldFilterOrder ?? false;
            if ($oldFilterOrder) {
                $this->filters['before'] = array_merge($this->filters['before'], $this->config->methods[$method]);
            } else {
                $this->filters['before'] = array_merge($this->config->methods[$method], $this->filters['before']);
            }
        }
    }

    
    protected function processFilters(?string $uri = null)
    {
        if (! isset($this->config->filters) || $this->config->filters === []) {
            return;
        }

        $uri = strtolower(trim($uri, '/ '));

        
        $filters = [];

        foreach ($this->config->filters as $filter => $settings) {
            
            [$alias, $arguments] = $this->getCleanName($filter);
            $filter              = ($arguments === []) ? $alias : $alias . ':' . implode(',', $arguments);

            
            if (isset($settings['before'])) {
                $path = $settings['before'];

                if ($this->pathApplies($uri, $path)) {
                    $filters['before'][] = $filter;
                }
            }

            if (isset($settings['after'])) {
                $path = $settings['after'];

                if ($this->pathApplies($uri, $path)) {
                    $filters['after'][] = $filter;
                }
            }
        }

        $oldFilterOrder = config(Feature::class)->oldFilterOrder ?? false;

        if (isset($filters['before'])) {
            if ($oldFilterOrder) {
                $this->filters['before'] = array_merge($this->filters['before'], $filters['before']);
            } else {
                $this->filters['before'] = array_merge($filters['before'], $this->filters['before']);
            }
        }

        if (isset($filters['after'])) {
            if (! $oldFilterOrder) {
                $filters['after'] = array_reverse($filters['after']);
            }

            $this->filters['after'] = array_merge($this->filters['after'], $filters['after']);
        }
    }

    
    protected function processAliasesToClass(string $position)
    {
        $filterClassList = [];

        foreach ($this->filters[$position] as $filter) {
            
            [$alias, $arguments] = $this->getCleanName($filter);

            if (! array_key_exists($alias, $this->config->aliases)) {
                throw FilterException::forNoAlias($alias);
            }

            if (is_array($this->config->aliases[$alias])) {
                foreach ($this->config->aliases[$alias] as $class) {
                    $filterClassList[] = [$class, $arguments];
                }
            } else {
                $filterClassList[] = [$this->config->aliases[$alias], $arguments];
            }
        }

        if ($position === 'before') {
            $this->filtersClass[$position] = array_merge($filterClassList, $this->filtersClass[$position]);
        } else {
            $this->filtersClass[$position] = array_merge($this->filtersClass[$position], $filterClassList);
        }
    }

    
    private function pathApplies(string $uri, $paths)
    {
        
        if ($paths === '' || $paths === []) {
            return true;
        }

        
        if (is_string($paths)) {
            $paths = [$paths];
        }

        return $this->checkPseudoRegex($uri, $paths);
    }

    
    private function checkExcept(string $uri, $paths): bool
    {
        
        if ($paths === []) {
            return false;
        }

        
        if (is_string($paths)) {
            $paths = [$paths];
        }

        return $this->checkPseudoRegex($uri, $paths);
    }

    
    private function checkPseudoRegex(string $uri, array $paths): bool
    {
        
        foreach ($paths as $path) {
            
            $path = str_replace('/', '\/', trim($path, '/ '));
            
            $path = strtolower(str_replace('*', '.*', $path));

            
            if (preg_match('#\A' . $path . '\z#u', $uri, $match) === 1) {
                return true;
            }
        }

        return false;
    }
}
