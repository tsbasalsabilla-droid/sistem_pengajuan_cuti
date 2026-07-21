<?php

declare(strict_types=1);



namespace CodeIgniter\Router;

use Closure;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;


final class AutoRouter implements AutoRouterInterface
{
    
    private ?string $directory = null;

    public function __construct(
        
        private  array $cliRoutes,
        
        private  string $defaultNamespace,
        
        private string $controller,
        
        private string $method,
        
        private bool $translateURIDashes,
    ) {
    }

    
    public function getRoute(string $uri, string $httpVerb): array
    {
        $segments = explode('/', $uri);

        
        $segments = $this->scanControllers($segments);

        
        
        if ($segments !== []) {
            $this->controller = ucfirst(array_shift($segments));
        }

        $controllerName = $this->controllerName();

        if (! $this->isValidSegment($controllerName)) {
            throw new PageNotFoundException($this->controller . ' is not a valid controller name');
        }

        
        
        
        if ($segments !== []) {
            $this->method = array_shift($segments) ?: $this->method;
        }

        
        if (strtolower($this->method) === 'initcontroller') {
            throw PageNotFoundException::forPageNotFound();
        }

        
        $params = [];

        if ($segments !== []) {
            $params = $segments;
        }

        
        if ($httpVerb !== 'CLI') {
            $controller = '\\' . $this->defaultNamespace;

            $controller .= $this->directory !== null ? str_replace('/', '\\', $this->directory) : '';
            $controller .= $controllerName;

            $controller = strtolower($controller);
            $methodName = strtolower($this->methodName());

            foreach ($this->cliRoutes as $handler) {
                if (is_string($handler)) {
                    $handler = strtolower($handler);

                    
                    if (str_contains($handler, '::$')) {
                        throw new PageNotFoundException(
                            'Cannot access CLI Route: ' . $uri,
                        );
                    }

                    if (str_starts_with($handler, $controller . '::' . $methodName)) {
                        throw new PageNotFoundException(
                            'Cannot access CLI Route: ' . $uri,
                        );
                    }

                    if ($handler === $controller) {
                        throw new PageNotFoundException(
                            'Cannot access CLI Route: ' . $uri,
                        );
                    }
                }
            }
        }

        
        $file = APPPATH . 'Controllers/' . $this->directory . $controllerName . '.php';

        if (! is_file($file)) {
            throw PageNotFoundException::forControllerNotFound($this->controller, $this->method);
        }

        include_once $file;

        
        
        if (! str_contains($this->controller, '\\') && strlen($this->defaultNamespace) > 1) {
            $this->controller = '\\' . ltrim(
                str_replace(
                    '/',
                    '\\',
                    $this->defaultNamespace . $this->directory . $controllerName,
                ),
                '\\',
            );
        }

        return [$this->directory, $this->controllerName(), $this->methodName(), $params];
    }

    
    public function setTranslateURIDashes(bool $val = false): self
    {
        $this->translateURIDashes = $val;

        return $this;
    }

    
    private function scanControllers(array $segments): array
    {
        $segments = array_filter($segments, static fn ($segment): bool => $segment !== '');
        
        $segments = array_values($segments);

        
        if (isset($this->directory)) {
            return $segments;
        }

        
        
        $c = count($segments);

        while ($c-- > 0) {
            $segmentConvert = ucfirst(
                $this->translateURIDashes ? str_replace('-', '_', $segments[0]) : $segments[0],
            );
            
            if (! $this->isValidSegment($segmentConvert)) {
                return $segments;
            }

            $test = APPPATH . 'Controllers/' . $this->directory . $segmentConvert;

            
            if (! is_file($test . '.php') && is_dir($test)) {
                $this->setDirectory($segmentConvert, true, false);
                array_shift($segments);

                continue;
            }

            return $segments;
        }

        
        return $segments;
    }

    
    private function isValidSegment(string $segment): bool
    {
        return (bool) preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $segment);
    }

    
    public function setDirectory(?string $dir = null, bool $append = false, bool $validate = true)
    {
        if ((string) $dir === '') {
            $this->directory = null;

            return;
        }

        if ($validate) {
            $segments = explode('/', trim($dir, '/'));

            foreach ($segments as $segment) {
                if (! $this->isValidSegment($segment)) {
                    return;
                }
            }
        }

        if (! $append || ((string) $this->directory === '')) {
            $this->directory = trim($dir, '/') . '/';
        } else {
            $this->directory .= trim($dir, '/') . '/';
        }
    }

    
    public function directory(): string
    {
        return ((string) $this->directory !== '') ? $this->directory : '';
    }

    private function controllerName(): string
    {
        return $this->translateURIDashes
            ? str_replace('-', '_', $this->controller)
            : $this->controller;
    }

    private function methodName(): string
    {
        return $this->translateURIDashes
            ? str_replace('-', '_', $this->method)
            : $this->method;
    }
}
