<?php

declare(strict_types=1);



namespace CodeIgniter\View;

use CodeIgniter\Cache\CacheInterface;
use CodeIgniter\Config\Factories;
use CodeIgniter\View\Cells\Cell as BaseCell;
use CodeIgniter\View\Exceptions\ViewException;
use ReflectionException;
use ReflectionMethod;


class Cell
{
    
    protected $cache;

    
    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    
    public function render(string $library, $params = null, int $ttl = 0, ?string $cacheName = null): string
    {
        [$instance, $method] = $this->determineClass($library);

        $class = is_object($instance)
            ? $instance::class
            : null;

        $params = $this->prepareParams($params);

        
        $cacheName ??= str_replace(['\\', '/'], '', $class) . $method . md5(serialize($params));

        $output = $this->cache->get($cacheName);

        if (is_string($output) && $output !== '') {
            return $output;
        }

        if (method_exists($instance, 'initController')) {
            $instance->initController(service('request'), service('response'), service('logger'));
        }

        if (! method_exists($instance, $method)) {
            throw ViewException::forInvalidCellMethod($class, $method);
        }

        $output = $instance instanceof BaseCell
            ? $this->renderCell($instance, $method, $params)
            : $this->renderSimpleClass($instance, $method, $params, $class);

        
        if ($ttl !== 0) {
            $this->cache->save($cacheName, $output, $ttl);
        }

        return $output;
    }

    
    public function prepareParams($params)
    {
        if (
            in_array($params, [null, '', []], true)
            || (! is_string($params) && ! is_array($params))
        ) {
            return [];
        }

        if (is_string($params)) {
            $newParams = [];
            $separator = ' ';

            if (str_contains($params, ',')) {
                $separator = ',';
            }

            $params = explode($separator, $params);
            unset($separator);

            foreach ($params as $p) {
                if ($p !== '') {
                    [$key, $val] = explode('=', $p);

                    $newParams[trim($key)] = trim($val, ', ');
                }
            }

            $params = $newParams;
            unset($newParams);
        }

        return $params;
    }

    
    protected function determineClass(string $library): array
    {
        
        
        $library = str_replace('::', ':', $library);

        
        
        if (! str_contains($library, ':')) {
            $library .= ':render';
        }

        [$class, $method] = explode(':', $library);

        if ($class === '') {
            throw ViewException::forNoCellClass();
        }

        
        $object = Factories::cells($class, ['getShared' => false]);

        if (! is_object($object)) {
            throw ViewException::forInvalidCellClass($class);
        }

        if ($method === '') {
            $method = 'index';
        }

        return [
            $object,
            $method,
        ];
    }

    
    final protected function renderCell(BaseCell $instance, string $method, array $params): string
    {
        
        
        $publicProperties  = $instance->getPublicProperties();
        $privateProperties = array_column($instance->getNonPublicProperties(), 'name');
        $publicParams      = array_intersect_key($params, $publicProperties);

        foreach ($params as $key => $value) {
            $getter = 'get' . ucfirst((string) $key) . 'Property';
            if (in_array($key, $privateProperties, true) && method_exists($instance, $getter)) {
                $publicParams[$key] = $value;
            }
        }

        
        
        $instance = $instance->fill($publicParams);

        
        
        if (method_exists($instance, 'mount')) {
            
            
            $mountParams = $this->getMethodParams($instance, 'mount', $params);
            $instance->mount(...$mountParams);
        }

        return $instance->{$method}();
    }

    
    private function getMethodParams(BaseCell $instance, string $method, array $params): array
    {
        $mountParams = [];

        try {
            $reflectionMethod = new ReflectionMethod($instance, $method);
            $reflectionParams = $reflectionMethod->getParameters();

            foreach ($reflectionParams as $reflectionParam) {
                $paramName = $reflectionParam->getName();

                if (array_key_exists($paramName, $params)) {
                    $mountParams[] = $params[$paramName];
                }
            }
        } catch (ReflectionException) {
            
        }

        return $mountParams;
    }

    
    final protected function renderSimpleClass($instance, string $method, array $params, string $class): string
    {
        
        
        $refMethod  = new ReflectionMethod($instance, $method);
        $paramCount = $refMethod->getNumberOfParameters();
        $refParams  = $refMethod->getParameters();

        if ($paramCount === 0) {
            if ($params !== []) {
                throw ViewException::forMissingCellParameters($class, $method);
            }

            $output = $instance->{$method}();
        } elseif (($paramCount === 1)
            && ((! array_key_exists($refParams[0]->name, $params))
            || (array_key_exists($refParams[0]->name, $params)
            && count($params) !== 1))
        ) {
            $output = $instance->{$method}($params);
        } else {
            $fireArgs     = [];
            $methodParams = [];

            foreach ($refParams as $arg) {
                $methodParams[$arg->name] = true;
                if (array_key_exists($arg->name, $params)) {
                    $fireArgs[$arg->name] = $params[$arg->name];
                }
            }

            foreach (array_keys($params) as $key) {
                if (! isset($methodParams[$key])) {
                    throw ViewException::forInvalidCellParameter($key);
                }
            }

            $output = $instance->{$method}(...array_values($fireArgs));
        }

        return $output;
    }
}
