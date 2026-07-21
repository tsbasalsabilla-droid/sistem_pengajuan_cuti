<?php

declare(strict_types=1);



namespace CodeIgniter\Router;

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Router\Exceptions\MethodNotFoundException;
use Config\Routing;
use ReflectionClass;
use ReflectionException;


final class AutoRouterImproved implements AutoRouterInterface
{
    
    private ?string $directory = null;

    
    private string $controller;

    
    private string $method;

    
    private array $params = [];

    
    private  bool $translateUriToCamelCase;

    
    private string $namespace;

    
    private array $moduleRoutes;

    
    private array $segments = [];

    
    private ?int $controllerPos = null;

    
    private ?int $methodPos = null;

    
    private ?int $paramPos = null;

    
    private ?string $uri = null;

    
    public function __construct(
        
        private  array $protectedControllers,
        string $namespace,
        private  string $defaultController,
        
        private  string $defaultMethod,
        
        private  bool $translateURIDashes,
    ) {
        $this->namespace = rtrim($namespace, '\\');

        $routingConfig                 = config(Routing::class);
        $this->moduleRoutes            = $routingConfig->moduleRoutes;
        $this->translateUriToCamelCase = $routingConfig->translateUriToCamelCase;

        
        $this->controller = $this->defaultController;
    }

    private function createSegments(string $uri): array
    {
        $segments = explode('/', $uri);
        $segments = array_filter($segments, static fn ($segment): bool => $segment !== '');

        
        return array_values($segments);
    }

    
    private function searchFirstController(): bool
    {
        $segments = $this->segments;

        $controller = '\\' . $this->namespace;

        $controllerPos = -1;

        while ($segments !== []) {
            $segment = array_shift($segments);
            $controllerPos++;

            $class = $this->translateURI($segment);

            
            if (! $this->isValidSegment($class)) {
                return false;
            }

            $controller .= '\\' . $class;

            if (class_exists($controller)) {
                $this->controller    = $controller;
                $this->controllerPos = $controllerPos;

                $this->checkUriForController($controller);

                
                $this->params = $segments;
                if ($segments !== []) {
                    $this->paramPos = $this->controllerPos + 1;
                }

                return true;
            }
        }

        return false;
    }

    
    private function searchLastDefaultController(): bool
    {
        $segments = $this->segments;

        $segmentCount = count($this->segments);
        $paramPos     = null;
        $params       = [];

        while ($segments !== []) {
            if ($segmentCount > count($segments)) {
                $paramPos = count($segments);
            }

            $namespaces = array_map(
                $this->translateURI(...),
                $segments,
            );

            $controller = '\\' . $this->namespace
                . '\\' . implode('\\', $namespaces)
                . '\\' . $this->defaultController;

            if (class_exists($controller)) {
                $this->controller = $controller;
                $this->params     = $params;

                if ($params !== []) {
                    $this->paramPos = $paramPos;
                }

                return true;
            }

            
            array_unshift($params, array_pop($segments));
        }

        
        $controller = '\\' . $this->namespace
            . '\\' . $this->defaultController;

        if (class_exists($controller)) {
            $this->controller = $controller;
            $this->params     = $params;

            if ($params !== []) {
                $this->paramPos = 0;
            }

            return true;
        }

        return false;
    }

    
    public function getRoute(string $uri, string $httpVerb): array
    {
        $this->uri = $uri;
        $httpVerb  = strtolower($httpVerb);

        
        $this->params = [];

        $defaultMethod = $httpVerb . ucfirst($this->defaultMethod);
        $this->method  = $defaultMethod;

        $this->segments = $this->createSegments($uri);

        
        if (
            $this->segments !== []
            && array_key_exists($this->segments[0], $this->moduleRoutes)
        ) {
            $uriSegment      = array_shift($this->segments);
            $this->namespace = rtrim($this->moduleRoutes[$uriSegment], '\\');
        }

        if ($this->searchFirstController()) {
            
            $baseControllerName = class_basename($this->controller);

            
            if (
                strtolower($baseControllerName) === strtolower($this->defaultController)
            ) {
                throw new PageNotFoundException(
                    'Cannot access the default controller "' . $this->controller . '" with the controller name URI path.',
                );
            }
        } elseif ($this->searchLastDefaultController()) {
            
            $baseControllerName = class_basename($this->controller);
        } else {
            
            throw new PageNotFoundException('No controller is found for: ' . $uri);
        }

        
        
        $params = $this->params;

        $methodParam = array_shift($params);

        $method = '';
        if ($methodParam !== null) {
            $method = $httpVerb . $this->translateURI($methodParam);

            $this->checkUriForMethod($method);
        }

        if ($methodParam !== null && method_exists($this->controller, $method)) {
            
            $this->method = $method;
            $this->params = $params;

            
            $this->methodPos = $this->paramPos;
            if ($params === []) {
                $this->paramPos = null;
            }
            if ($this->paramPos !== null) {
                $this->paramPos++;
            }

            
            if (strtolower($baseControllerName) === strtolower($this->defaultController)) {
                throw new PageNotFoundException(
                    'Cannot access the default controller "' . $this->controller . '::' . $this->method . '"',
                );
            }

            
            if (strtolower($this->method) === strtolower($defaultMethod)) {
                throw new PageNotFoundException(
                    'Cannot access the default method "' . $this->method . '" with the method name URI path.',
                );
            }
        } elseif (method_exists($this->controller, $defaultMethod)) {
            
            $this->method = $defaultMethod;
        } else {
            
            throw PageNotFoundException::forControllerNotFound($this->controller, $method);
        }

        
        $this->protectDefinedRoutes();

        
        $this->checkRemap();

        
        
        $this->checkUnderscore();

        
        try {
            $this->checkParameters();
        } catch (MethodNotFoundException) {
            throw PageNotFoundException::forControllerNotFound($this->controller, $this->method);
        }

        $this->setDirectory();

        return [$this->directory, $this->controller, $this->method, $this->params];
    }

    
    public function getPos(): array
    {
        return [
            'controller' => $this->controllerPos,
            'method'     => $this->methodPos,
            'params'     => $this->paramPos,
        ];
    }

    
    private function setDirectory()
    {
        $segments = explode('\\', trim($this->controller, '\\'));

        
        array_pop($segments);

        $namespaces = implode('\\', $segments);

        $dir = str_replace(
            '\\',
            '/',
            ltrim(substr($namespaces, strlen($this->namespace)), '\\'),
        );

        if ($dir !== '') {
            $this->directory = $dir . '/';
        }
    }

    private function protectDefinedRoutes(): void
    {
        $controller = strtolower($this->controller);

        foreach ($this->protectedControllers as $controllerInRoutes) {
            $routeLowerCase = strtolower($controllerInRoutes);

            if ($routeLowerCase === $controller) {
                throw new PageNotFoundException(
                    'Cannot access the controller in Defined Routes. Controller: ' . $controllerInRoutes,
                );
            }
        }
    }

    private function checkParameters(): void
    {
        try {
            $refClass = new ReflectionClass($this->controller);
        } catch (ReflectionException) {
            throw PageNotFoundException::forControllerNotFound($this->controller, $this->method);
        }

        try {
            $refMethod = $refClass->getMethod($this->method);
            $refParams = $refMethod->getParameters();
        } catch (ReflectionException) {
            throw new MethodNotFoundException();
        }

        if (! $refMethod->isPublic()) {
            throw new MethodNotFoundException();
        }

        if (count($refParams) < count($this->params)) {
            throw new PageNotFoundException(
                'The param count in the URI are greater than the controller method params.'
                . ' Handler:' . $this->controller . '::' . $this->method
                . ', URI:' . $this->uri,
            );
        }
    }

    private function checkRemap(): void
    {
        try {
            $refClass = new ReflectionClass($this->controller);
            $refClass->getMethod('_remap');

            throw new PageNotFoundException(
                'AutoRouterImproved does not support `_remap()` method.'
                . ' Controller:' . $this->controller,
            );
        } catch (ReflectionException) {
            
        }
    }

    private function checkUnderscore(): void
    {
        if ($this->translateURIDashes === false) {
            return;
        }

        $paramPos = $this->paramPos ?? count($this->segments);

        for ($i = 0; $i < $paramPos; $i++) {
            if (str_contains($this->segments[$i], '_')) {
                throw new PageNotFoundException(
                    'AutoRouterImproved prohibits access to the URI'
                    . ' containing underscores ("' . $this->segments[$i] . '")'
                    . ' when $translateURIDashes is enabled.'
                    . ' Please use the dash.'
                    . ' Handler:' . $this->controller . '::' . $this->method
                    . ', URI:' . $this->uri,
                );
            }
        }
    }

    
    private function checkUriForController(string $classname): void
    {
        if ($this->translateUriToCamelCase === false) {
            return;
        }

        if (! in_array(ltrim($classname, '\\'), get_declared_classes(), true)) {
            throw new PageNotFoundException(
                '"' . $classname . '" is not found.',
            );
        }
    }

    
    private function checkUriForMethod(string $method): void
    {
        if ($this->translateUriToCamelCase === false) {
            return;
        }

        if (
            
            
            
            
            
            method_exists($this->controller, $method)
            
            
            && ! in_array($method, get_class_methods($this->controller), true)
        ) {
            throw new PageNotFoundException(
                '"' . $this->controller . '::' . $method . '()" is not found.',
            );
        }
    }

    
    private function isValidSegment(string $segment): bool
    {
        return (bool) preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $segment);
    }

    
    private function translateURI(string $segment): string
    {
        if ($this->translateUriToCamelCase) {
            if (strtolower($segment) !== $segment) {
                throw new PageNotFoundException(
                    'AutoRouterImproved prohibits access to the URI'
                    . ' containing uppercase letters ("' . $segment . '")'
                    . ' when $translateUriToCamelCase is enabled.'
                    . ' Please use the dash.'
                    . ' URI:' . $this->uri,
                );
            }

            if (str_contains($segment, '--')) {
                throw new PageNotFoundException(
                    'AutoRouterImproved prohibits access to the URI'
                    . ' containing double dash ("' . $segment . '")'
                    . ' when $translateUriToCamelCase is enabled.'
                    . ' Please use the single dash.'
                    . ' URI:' . $this->uri,
                );
            }

            return str_replace(
                ' ',
                '',
                ucwords(
                    preg_replace('/[\-]+/', ' ', $segment),
                ),
            );
        }

        $segment = ucfirst($segment);

        if ($this->translateURIDashes) {
            return str_replace('-', '_', $segment);
        }

        return $segment;
    }
}
