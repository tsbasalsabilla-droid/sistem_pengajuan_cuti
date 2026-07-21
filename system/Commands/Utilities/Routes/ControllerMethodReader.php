<?php

declare(strict_types=1);



namespace CodeIgniter\Commands\Utilities\Routes;

use ReflectionClass;
use ReflectionMethod;


final  class ControllerMethodReader
{
    
    public function __construct(private string $namespace)
    {
    }

    
    public function read(string $class, string $defaultController = 'Home', string $defaultMethod = 'index'): array
    {
        $reflection = new ReflectionClass($class);

        if ($reflection->isAbstract()) {
            return [];
        }

        $classname      = $reflection->getName();
        $classShortname = $reflection->getShortName();

        $output     = [];
        $uriByClass = $this->getUriByClass($classname);

        if ($this->hasRemap($reflection)) {
            $methodName = '_remap';

            $routeWithoutController = $this->getRouteWithoutController(
                $classShortname,
                $defaultController,
                $uriByClass,
                $classname,
                $methodName,
            );
            $output = [...$output, ...$routeWithoutController];

            $output[] = [
                'route'   => $uriByClass . '[/...]',
                'handler' => '\\' . $classname . '::' . $methodName,
            ];

            return $output;
        }

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $methodName = $method->getName();

            $route = $uriByClass . '/' . $methodName;

            
            
            if (preg_match('#\AbaseController.*#', $route) === 1) {
                continue;
            }
            if (preg_match('#.*/initController\z#', $route) === 1) {
                continue;
            }

            if ($methodName === $defaultMethod) {
                $routeWithoutController = $this->getRouteWithoutController(
                    $classShortname,
                    $defaultController,
                    $uriByClass,
                    $classname,
                    $methodName,
                );
                $output = [...$output, ...$routeWithoutController];

                $output[] = [
                    'route'   => $uriByClass,
                    'handler' => '\\' . $classname . '::' . $methodName,
                ];
            }

            $output[] = [
                'route'   => $route . '[/...]',
                'handler' => '\\' . $classname . '::' . $methodName,
            ];
        }

        return $output;
    }

    
    private function hasRemap(ReflectionClass $class): bool
    {
        if ($class->hasMethod('_remap')) {
            $remap = $class->getMethod('_remap');

            return $remap->isPublic();
        }

        return false;
    }

    
    private function getUriByClass(string $classname): string
    {
        
        $pattern = '/' . preg_quote($this->namespace, '/') . '/';
        $class   = ltrim(preg_replace($pattern, '', $classname), '\\');

        $classParts = explode('\\', $class);
        $classPath  = '';

        foreach ($classParts as $part) {
            
            
            $classPath .= lcfirst($part) . '/';
        }

        return rtrim($classPath, '/');
    }

    
    private function getRouteWithoutController(
        string $classShortname,
        string $defaultController,
        string $uriByClass,
        string $classname,
        string $methodName,
    ): array {
        if ($classShortname !== $defaultController) {
            return [];
        }

        $pattern                = '#' . preg_quote(lcfirst($defaultController), '#') . '\z#';
        $routeWithoutController = rtrim(preg_replace($pattern, '', $uriByClass), '/');
        $routeWithoutController = $routeWithoutController !== '' && $routeWithoutController !== '0' ? $routeWithoutController : '/';

        return [[
            'route'   => $routeWithoutController,
            'handler' => '\\' . $classname . '::' . $methodName,
        ]];
    }
}
