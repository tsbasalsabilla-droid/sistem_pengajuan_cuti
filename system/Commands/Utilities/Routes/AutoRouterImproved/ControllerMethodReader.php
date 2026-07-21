<?php

declare(strict_types=1);



namespace CodeIgniter\Commands\Utilities\Routes\AutoRouterImproved;

use Config\Routing;
use ReflectionClass;
use ReflectionMethod;


final  class ControllerMethodReader
{
    private bool $translateURIDashes;
    private bool $translateUriToCamelCase;

    
    public function __construct(
        private string $namespace,
        private array $httpMethods,
    ) {
        $config                        = config(Routing::class);
        $this->translateURIDashes      = $config->translateURIDashes;
        $this->translateUriToCamelCase = $config->translateUriToCamelCase;
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
        $classInUri = $this->convertClassNameToUri($classname);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $methodName = $method->getName();

            foreach ($this->httpMethods as $httpVerb) {
                if (str_starts_with($methodName, strtolower($httpVerb))) {
                    
                    $methodInUri = $this->convertMethodNameToUri($httpVerb, $methodName);

                    
                    if ($methodInUri === $defaultMethod) {
                        $routeForDefaultController = $this->getRouteForDefaultController(
                            $classShortname,
                            $defaultController,
                            $classInUri,
                            $classname,
                            $methodName,
                            $httpVerb,
                            $method,
                        );

                        if ($routeForDefaultController !== []) {
                            
                            
                            
                            $output = [...$output, ...$routeForDefaultController];

                            continue;
                        }

                        [$params, $routeParams] = $this->getParameters($method);

                        
                        $output[] = [
                            'method'       => $httpVerb,
                            'route'        => $classInUri,
                            'route_params' => $routeParams,
                            'handler'      => '\\' . $classname . '::' . $methodName,
                            'params'       => $params,
                        ];

                        continue;
                    }

                    $route = $classInUri . '/' . $methodInUri;

                    [$params, $routeParams] = $this->getParameters($method);

                    
                    
                    if ($classShortname === $defaultController) {
                        $route = 'x ' . $route;
                    }

                    $output[] = [
                        'method'       => $httpVerb,
                        'route'        => $route,
                        'route_params' => $routeParams,
                        'handler'      => '\\' . $classname . '::' . $methodName,
                        'params'       => $params,
                    ];
                }
            }
        }

        return $output;
    }

    private function getParameters(ReflectionMethod $method): array
    {
        $params      = [];
        $routeParams = '';
        $refParams   = $method->getParameters();

        foreach ($refParams as $param) {
            $required = true;
            if ($param->isOptional()) {
                $required = false;

                $routeParams .= '[/..]';
            } else {
                $routeParams .= '/..';
            }

            
            $params[$param->getName()] = $required;
        }

        return [$params, $routeParams];
    }

    
    private function convertClassNameToUri(string $classname): string
    {
        
        $pattern = '/' . preg_quote($this->namespace, '/') . '/';
        $class   = ltrim(preg_replace($pattern, '', $classname), '\\');

        $classParts = explode('\\', $class);
        $classPath  = '';

        foreach ($classParts as $part) {
            
            
            $classPath .= lcfirst($part) . '/';
        }

        $classUri = rtrim($classPath, '/');

        return $this->translateToUri($classUri);
    }

    
    private function convertMethodNameToUri(string $httpVerb, string $methodName): string
    {
        $methodUri = lcfirst(substr($methodName, strlen($httpVerb)));

        return $this->translateToUri($methodUri);
    }

    
    private function translateToUri(string $string): string
    {
        if ($this->translateUriToCamelCase) {
            $string = strtolower(
                preg_replace('/([a-z\d])([A-Z])/', '$1-$2', $string),
            );
        } elseif ($this->translateURIDashes) {
            $string = str_replace('_', '-', $string);
        }

        return $string;
    }

    
    private function getRouteForDefaultController(
        string $classShortname,
        string $defaultController,
        string $uriByClass,
        string $classname,
        string $methodName,
        string $httpVerb,
        ReflectionMethod $method,
    ): array {
        $output = [];

        if ($classShortname === $defaultController) {
            $pattern                = '#' . preg_quote(lcfirst($defaultController), '#') . '\z#';
            $routeWithoutController = rtrim(preg_replace($pattern, '', $uriByClass), '/');
            $routeWithoutController = $routeWithoutController !== '' && $routeWithoutController !== '0' ? $routeWithoutController : '/';

            [$params, $routeParams] = $this->getParameters($method);

            if ($routeWithoutController === '/' && $routeParams !== '') {
                $routeWithoutController = '';
            }

            $output[] = [
                'method'       => $httpVerb,
                'route'        => $routeWithoutController,
                'route_params' => $routeParams,
                'handler'      => '\\' . $classname . '::' . $methodName,
                'params'       => $params,
            ];
        }

        return $output;
    }
}
