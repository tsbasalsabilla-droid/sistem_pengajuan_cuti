<?php

declare(strict_types=1);



namespace CodeIgniter\Router;

use Closure;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\Exceptions\BadRequestException;
use CodeIgniter\HTTP\Exceptions\RedirectException;
use CodeIgniter\HTTP\Method;
use CodeIgniter\HTTP\Request;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Router\Attributes\Filter;
use CodeIgniter\Router\Attributes\RouteAttributeInterface;
use CodeIgniter\Router\Exceptions\RouterException;
use Config\App;
use Config\Feature;
use Config\Routing;
use ReflectionClass;
use Throwable;


class Router implements RouterInterface
{
    
    public const HTTP_METHODS = [
        Method::GET,
        Method::HEAD,
        Method::POST,
        Method::PATCH,
        Method::PUT,
        Method::DELETE,
        Method::OPTIONS,
        Method::TRACE,
        Method::CONNECT,
        'CLI',
    ];

    
    protected $collection;

    
    protected $directory;

    
    protected $controller;

    
    protected $method;

    
    protected $params = [];

    
    protected $indexPage = 'index.php';

    
    protected $translateURIDashes = false;

    
    protected $matchedRoute;

    
    protected $matchedRouteOptions;

    
    protected $detectedLocale;

    
    protected $filtersInfo = [];

    protected ?AutoRouterInterface $autoRouter = null;

    
    protected array $routeAttributes = ['class' => [], 'method' => []];

    
    protected string $permittedURIChars = '';

    
    public function __construct(RouteCollectionInterface $routes, ?Request $request = null)
    {
        $config = config(App::class);

        if (isset($config->permittedURIChars)) {
            $this->permittedURIChars = $config->permittedURIChars;
        }

        $this->collection = $routes;

        
        $this->controller = $this->collection->getDefaultController();
        $this->method     = $this->collection->getDefaultMethod();

        $this->collection->setHTTPVerb($request->getMethod() === '' ? service('superglobals')->server('REQUEST_METHOD') : $request->getMethod());

        $this->translateURIDashes = $this->collection->shouldTranslateURIDashes();

        if ($this->collection->shouldAutoRoute()) {
            $autoRoutesImproved = config(Feature::class)->autoRoutesImproved ?? false;
            if ($autoRoutesImproved) {
                assert($this->collection instanceof RouteCollection);

                $this->autoRouter = new AutoRouterImproved(
                    $this->collection->getRegisteredControllers('*'),
                    $this->collection->getDefaultNamespace(),
                    $this->collection->getDefaultController(),
                    $this->collection->getDefaultMethod(),
                    $this->translateURIDashes,
                );
            } else {
                $this->autoRouter = new AutoRouter(
                    $this->collection->getRoutes('CLI', false),
                    $this->collection->getDefaultNamespace(),
                    $this->collection->getDefaultController(),
                    $this->collection->getDefaultMethod(),
                    $this->translateURIDashes,
                );
            }
        }
    }

    
    public function handle(?string $uri = null)
    {
        
        if ($uri === null || $uri === '') {
            $uri = '/';
        }

        
        $uri = urldecode($uri);

        $this->checkDisallowedChars($uri);

        
        $this->filtersInfo = [];

        
        if ($this->checkRoutes($uri)) {
            if ($this->collection->isFiltered($this->matchedRoute[0])) {
                $this->filtersInfo = $this->collection->getFiltersForRoute($this->matchedRoute[0]);
            }

            $this->processRouteAttributes();

            return $this->controller;
        }

        
        
        
        if (! $this->collection->shouldAutoRoute()) {
            throw new PageNotFoundException(
                "Can't find a route for '{$this->collection->getHTTPVerb()}: {$uri}'.",
            );
        }

        
        $this->autoRoute($uri);

        $this->processRouteAttributes();

        return $this->controllerName();
    }

    
    public function getFilters(): array
    {
        $filters = $this->filtersInfo;

        
        foreach ($this->routeAttributes as $attributes) {
            foreach ($attributes as $attribute) {
                if ($attribute instanceof Filter) {
                    $filters = array_merge($filters, $attribute->getFilters());
                }
            }
        }

        return $filters;
    }

    
    public function controllerName()
    {
        return $this->translateURIDashes && ! $this->controller instanceof Closure
            ? str_replace('-', '_', $this->controller)
            : $this->controller;
    }

    
    public function methodName(): string
    {
        return $this->translateURIDashes
            ? str_replace('-', '_', $this->method)
            : $this->method;
    }

    
    public function get404Override()
    {
        $route = $this->collection->get404Override();

        if (is_string($route)) {
            $routeArray = explode('::', $route);

            return [
                $routeArray[0], 
                $routeArray[1] ?? 'index',   
            ];
        }

        if (is_callable($route)) {
            return $route;
        }

        return null;
    }

    
    public function params(): array
    {
        return $this->params;
    }

    
    public function directory(): string
    {
        if ($this->autoRouter instanceof AutoRouter) {
            return $this->autoRouter->directory();
        }

        return '';
    }

    
    public function getMatchedRoute()
    {
        return $this->matchedRoute;
    }

    
    public function getMatchedRouteOptions()
    {
        return $this->matchedRouteOptions;
    }

    
    public function setIndexPage($page): self
    {
        $this->indexPage = $page;

        return $this;
    }

    
    public function setTranslateURIDashes(bool $val = false): self
    {
        if ($this->autoRouter instanceof AutoRouter) {
            $this->autoRouter->setTranslateURIDashes($val);

            return $this;
        }

        return $this;
    }

    
    public function hasLocale()
    {
        return (bool) $this->detectedLocale;
    }

    
    public function getLocale()
    {
        return $this->detectedLocale;
    }

    
    protected function checkRoutes(string $uri): bool
    {
        $routes = $this->collection->getRoutes($this->collection->getHTTPVerb());

        
        if ($routes === []) {
            return false;
        }

        $uri = $uri === '/'
            ? $uri
            : trim($uri, '/ ');

        
        foreach ($routes as $routeKey => $handler) {
            $routeKey = $routeKey === '/'
                ? $routeKey
                
                
                : ltrim((string) $routeKey, '/ ');

            $matchedKey = $routeKey;

            
            if (str_contains($routeKey, '{locale}')) {
                $routeKey = str_replace('{locale}', '[^/]+', $routeKey);
            }

            
            if (preg_match('#^' . $routeKey . '$#u', $uri, $matches)) {
                
                if ($this->collection->isRedirect($routeKey)) {
                    
                    $redirectTo = preg_replace_callback('/(\([^\(]+\))/', static function (): string {
                        static $i = 1;

                        return '$' . $i++;
                    }, is_array($handler) ? key($handler) : $handler);

                    throw new RedirectException(
                        preg_replace('#\A' . $routeKey . '\z#u', $redirectTo, $uri),
                        $this->collection->getRedirectCode($routeKey),
                    );
                }
                
                
                if (str_contains($matchedKey, '{locale}')) {
                    preg_match(
                        '#^' . str_replace('{locale}', '(?<locale>[^/]+)', $matchedKey) . '$#u',
                        $uri,
                        $matched,
                    );

                    if ($this->collection->shouldUseSupportedLocalesOnly()
                        && ! in_array($matched['locale'], config(App::class)->supportedLocales, true)) {
                        
                        
                        throw PageNotFoundException::forLocaleNotSupported($matched['locale']);
                    }

                    $this->detectedLocale = $matched['locale'];
                    unset($matched);
                }

                
                
                
                if (! is_string($handler) && is_callable($handler)) {
                    $this->controller = $handler;

                    
                    array_shift($matches);

                    $this->params = $matches;

                    $this->setMatchedRoute($matchedKey, $handler);

                    return true;
                }

                if (str_contains($handler, '::')) {
                    [$controller, $methodAndParams] = explode('::', $handler);
                } else {
                    $controller      = $handler;
                    $methodAndParams = '';
                }

                
                if (str_contains($controller, '/')) {
                    throw RouterException::forInvalidControllerName($handler);
                }

                if (str_contains($handler, '$') && str_contains($routeKey, '(')) {
                    
                    if (str_contains($controller, '$')) {
                        throw RouterException::forDynamicController($handler);
                    }

                    if (config(Routing::class)->multipleSegmentsOneParam === false) {
                        
                        $segments = explode('/', preg_replace('#\A' . $routeKey . '\z#u', $handler, $uri));
                    } else {
                        if (str_contains($methodAndParams, '/')) {
                            [$method, $handlerParams] = explode('/', $methodAndParams, 2);
                            $params                   = explode('/', $handlerParams);
                            $handlerSegments          = array_merge([$controller . '::' . $method], $params);
                        } else {
                            $handlerSegments = [$handler];
                        }

                        $segments = [];

                        foreach ($handlerSegments as $segment) {
                            $segments[] = $this->replaceBackReferences($segment, $matches);
                        }
                    }
                } else {
                    $segments = explode('/', $handler);
                }

                $this->setRequest($segments);

                $this->setMatchedRoute($matchedKey, $handler);

                return true;
            }
        }

        return false;
    }

    
    private function replaceBackReferences(string $input, array $matches): string
    {
        $pattern = '/\$([1-' . count($matches) . '])/u';

        return preg_replace_callback(
            $pattern,
            static function ($match) use ($matches) {
                $index = (int) $match[1];

                return $matches[$index] ?? '';
            },
            $input,
        );
    }

    
    public function autoRoute(string $uri)
    {
        [$this->directory, $this->controller, $this->method, $this->params]
            = $this->autoRouter->getRoute($uri, $this->collection->getHTTPVerb());
    }

    
    protected function validateRequest(array $segments): array
    {
        return $this->scanControllers($segments);
    }

    
    protected function scanControllers(array $segments): array
    {
        $segments = array_filter($segments, static fn ($segment): bool => $segment !== '');
        
        $segments = array_values($segments);

        
        if (isset($this->directory)) {
            return $segments;
        }

        
        
        $c = count($segments);

        while ($c-- > 0) {
            $segmentConvert = ucfirst($this->translateURIDashes === true ? str_replace('-', '_', $segments[0]) : $segments[0]);
            
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

    
    public function setDirectory(?string $dir = null, bool $append = false, bool $validate = true)
    {
        if ($dir === null || $dir === '') {
            $this->directory = null;
        }

        if ($this->autoRouter instanceof AutoRouter) {
            $this->autoRouter->setDirectory($dir, $append, $validate);
        }
    }

    
    private function isValidSegment(string $segment): bool
    {
        return (bool) preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $segment);
    }

    
    protected function setRequest(array $segments = [])
    {
        
        if ($segments === []) {
            return;
        }

        [$controller, $method] = array_pad(explode('::', $segments[0]), 2, null);

        $this->controller = $controller;

        
        
        if (! empty($method)) {
            $this->method = $method;
        }

        array_shift($segments);

        $this->params = $segments;
    }

    
    protected function setDefaultController()
    {
        if (empty($this->controller)) {
            throw RouterException::forMissingDefaultRoute();
        }

        sscanf($this->controller, '%[^/]/%s', $class, $this->method);

        if (! is_file(APPPATH . 'Controllers/' . $this->directory . ucfirst($class) . '.php')) {
            return;
        }

        $this->controller = ucfirst($class);

        log_message('info', 'Used the default controller.');
    }

    
    protected function setMatchedRoute(string $route, $handler): void
    {
        $this->matchedRoute = [$route, $handler];

        $this->matchedRouteOptions = $this->collection->getRoutesOptions($route);
    }

    
    private function checkDisallowedChars(string $uri): void
    {
        foreach (explode('/', $uri) as $segment) {
            if ($segment !== '' && $this->permittedURIChars !== ''
                && preg_match('/\A[' . $this->permittedURIChars . ']+\z/iu', $segment) !== 1
            ) {
                throw new BadRequestException(
                    'The URI you submitted has disallowed characters: "' . $segment . '"',
                );
            }
        }
    }

    
    private function processRouteAttributes(): void
    {
        $this->routeAttributes = ['class' => [], 'method' => []];

        
        if (config('routing')->useControllerAttributes === false) {
            return;
        }

        
        if ($this->controller instanceof Closure) {
            return;
        }

        if (! class_exists($this->controller)) {
            return;
        }

        $reflectionClass = new ReflectionClass($this->controller);

        
        foreach ($reflectionClass->getAttributes() as $attribute) {
            try {
                $instance = $attribute->newInstance();

                if ($instance instanceof RouteAttributeInterface) {
                    $this->routeAttributes['class'][] = $instance;
                }
            } catch (Throwable $e) {
                $this->logRouteAttributeInstantiationFailure($attribute->getName(), $this->controller, null, $e);
            }
        }

        if ($this->method === '' || $this->method === null) {
            return;
        }

        
        if ($reflectionClass->hasMethod($this->method)) {
            $reflectionMethod = $reflectionClass->getMethod($this->method);

            foreach ($reflectionMethod->getAttributes() as $attribute) {
                try {
                    $instance = $attribute->newInstance();

                    if ($instance instanceof RouteAttributeInterface) {
                        $this->routeAttributes['method'][] = $instance;
                    }
                } catch (Throwable $e) {
                    $this->logRouteAttributeInstantiationFailure($attribute->getName(), $this->controller, $this->method, $e);
                }
            }
        }
    }

    
    private function logRouteAttributeInstantiationFailure(
        string $attributeName,
        string $controller,
        ?string $method,
        Throwable $e,
    ): void {
        $location = $controller;

        if ($method !== null && $method !== '') {
            $location .= '::' . $method . '()';
        }

        log_message(
            'error',
            'Failed to instantiate route attribute "{attribute}" on "{location}": {message}',
            [
                'attribute' => $attributeName,
                'location'  => $location,
                'message'   => $e->getMessage(),
            ],
        );
    }

    
    public function executeBeforeAttributes(RequestInterface $request): RequestInterface|ResponseInterface|null
    {
        
        foreach (['class', 'method'] as $level) {
            foreach ($this->routeAttributes[$level] as $attribute) {
                if (! $attribute instanceof RouteAttributeInterface) {
                    continue;
                }

                $result = $attribute->before($request);

                
                if ($result instanceof ResponseInterface) {
                    return $result;
                }

                
                if ($result instanceof RequestInterface) {
                    $request = $result;
                }
            }
        }

        return $request;
    }

    
    public function executeAfterAttributes(RequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        
        foreach (array_reverse(['class', 'method']) as $level) {
            foreach ($this->routeAttributes[$level] as $attribute) {
                if ($attribute instanceof RouteAttributeInterface) {
                    $result = $attribute->after($request, $response);

                    if ($result instanceof ResponseInterface) {
                        $response = $result;
                    }
                }
            }
        }

        return $response;
    }

    
    public function getRouteAttributes(): array
    {
        return $this->routeAttributes;
    }
}
