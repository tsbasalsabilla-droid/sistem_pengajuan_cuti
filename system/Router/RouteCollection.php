<?php

declare(strict_types=1);



namespace CodeIgniter\Router;

use Closure;
use CodeIgniter\Autoloader\FileLocatorInterface;
use CodeIgniter\Exceptions\InvalidArgumentException;
use CodeIgniter\HTTP\Method;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Router\Exceptions\RouterException;
use Config\App;
use Config\Modules;
use Config\Routing;


class RouteCollection implements RouteCollectionInterface
{
    
    protected $defaultNamespace = '\\';

    
    protected $defaultController = 'Home';

    
    protected $defaultMethod = 'index';

    
    protected $defaultPlaceholder = 'any';

    
    protected $translateURIDashes = false;

    
    protected $autoRoute = false;

    
    protected $override404;

    
    protected array $routeFiles = [];

    
    protected $placeholders = [
        'any'      => '.*',
        'segment'  => '[^/]+',
        'alphanum' => '[a-zA-Z0-9]+',
        'num'      => '[0-9]+',
        'alpha'    => '[a-zA-Z]+',
        'hash'     => '[^/]+',
    ];

    
    protected $routes = [
        '*'             => [],
        Method::OPTIONS => [],
        Method::GET     => [],
        Method::HEAD    => [],
        Method::POST    => [],
        Method::PATCH   => [],
        Method::PUT     => [],
        Method::DELETE  => [],
        Method::TRACE   => [],
        Method::CONNECT => [],
        'CLI'           => [],
    ];

    
    protected $routesNames = [
        '*'             => [],
        Method::OPTIONS => [],
        Method::GET     => [],
        Method::HEAD    => [],
        Method::POST    => [],
        Method::PATCH   => [],
        Method::PUT     => [],
        Method::DELETE  => [],
        Method::TRACE   => [],
        Method::CONNECT => [],
        'CLI'           => [],
    ];

    
    protected $routesOptions = [];

    
    protected $HTTPVerb = '*';

    
    public $defaultHTTPMethods = Router::HTTP_METHODS;

    
    protected $group;

    
    protected $currentSubdomain;

    
    protected $currentOptions;

    
    protected $didDiscover = false;

    
    protected $fileLocator;

    
    protected $moduleConfig;

    
    protected $prioritize = false;

    
    protected $prioritizeDetected = false;

    
    private ?string $httpHost = null;

    
    protected bool $useSupportedLocalesOnly = false;

    
    public function __construct(FileLocatorInterface $locator, Modules $moduleConfig, Routing $routing)
    {
        $this->fileLocator  = $locator;
        $this->moduleConfig = $moduleConfig;

        $this->httpHost = service('request')->getServer('HTTP_HOST');

        
        $this->defaultNamespace   = rtrim($routing->defaultNamespace, '\\') . '\\';
        $this->defaultController  = $routing->defaultController;
        $this->defaultMethod      = $routing->defaultMethod;
        $this->translateURIDashes = $routing->translateURIDashes;
        $this->override404        = $routing->override404;
        $this->autoRoute          = $routing->autoRoute;
        $this->routeFiles         = $routing->routeFiles;
        $this->prioritize         = $routing->prioritize;

        
        foreach ($this->routeFiles as $routeKey => $routesFile) {
            $realpath                    = realpath($routesFile);
            $this->routeFiles[$routeKey] = ($realpath === false) ? $routesFile : $realpath;
        }
    }

    
    public function loadRoutes(string $routesFile = APPPATH . 'Config/Routes.php')
    {
        if ($this->didDiscover) {
            return $this;
        }

        
        $realpath   = realpath($routesFile);
        $routesFile = ($realpath === false) ? $routesFile : $realpath;

        
        
        $routeFiles = $this->routeFiles;
        if (! in_array($routesFile, $routeFiles, true)) {
            $routeFiles[] = $routesFile;
        }

        
        
        $routes = $this;

        foreach ($routeFiles as $routesFile) {
            if (! is_file($routesFile)) {
                log_message('warning', sprintf('Routes file not found: "%s"', $routesFile));

                continue;
            }

            require $routesFile;
        }

        $this->discoverRoutes();

        return $this;
    }

    
    protected function discoverRoutes()
    {
        if ($this->didDiscover) {
            return;
        }

        
        
        $routes = $this;

        if ($this->moduleConfig->shouldDiscover('routes')) {
            $files = $this->fileLocator->search('Config/Routes.php');

            foreach ($files as $file) {
                
                if (in_array($file, $this->routeFiles, true)) {
                    continue;
                }

                include $file;
            }
        }

        $this->didDiscover = true;
    }

    
    public function addPlaceholder($placeholder, ?string $pattern = null): RouteCollectionInterface
    {
        if (! is_array($placeholder)) {
            $placeholder = [$placeholder => $pattern];
        }

        $this->placeholders = array_merge($this->placeholders, $placeholder);

        return $this;
    }

    
    public function getPlaceholders(): array
    {
        return $this->placeholders;
    }

    
    public function setDefaultNamespace(string $value): RouteCollectionInterface
    {
        $this->defaultNamespace = esc(strip_tags($value));
        $this->defaultNamespace = rtrim($this->defaultNamespace, '\\') . '\\';

        return $this;
    }

    
    public function setDefaultController(string $value): RouteCollectionInterface
    {
        $this->defaultController = esc(strip_tags($value));

        return $this;
    }

    
    public function setDefaultMethod(string $value): RouteCollectionInterface
    {
        $this->defaultMethod = esc(strip_tags($value));

        return $this;
    }

    
    public function setTranslateURIDashes(bool $value): RouteCollectionInterface
    {
        $this->translateURIDashes = $value;

        return $this;
    }

    
    public function setAutoRoute(bool $value): RouteCollectionInterface
    {
        $this->autoRoute = $value;

        return $this;
    }

    
    public function set404Override($callable = null): RouteCollectionInterface
    {
        $this->override404 = $callable;

        return $this;
    }

    
    public function get404Override()
    {
        return $this->override404;
    }

    
    public function setDefaultConstraint(string $placeholder): RouteCollectionInterface
    {
        if (array_key_exists($placeholder, $this->placeholders)) {
            $this->defaultPlaceholder = $placeholder;
        }

        return $this;
    }

    
    public function getDefaultController(): string
    {
        return $this->defaultController;
    }

    
    public function getDefaultMethod(): string
    {
        return $this->defaultMethod;
    }

    
    public function getDefaultNamespace(): string
    {
        return $this->defaultNamespace;
    }

    
    public function shouldTranslateURIDashes(): bool
    {
        return $this->translateURIDashes;
    }

    
    public function shouldAutoRoute(): bool
    {
        return $this->autoRoute;
    }

    
    public function getRoutes(?string $verb = null, bool $includeWildcard = true): array
    {
        if ((string) $verb === '') {
            $verb = $this->getHTTPVerb();
        }

        
        
        
        $this->discoverRoutes();

        $routes = [];

        if (isset($this->routes[$verb])) {
            
            
            $collection = $includeWildcard ? $this->routes[$verb] + ($this->routes['*'] ?? []) : $this->routes[$verb];

            foreach ($collection as $routeKey => $r) {
                $routes[$routeKey] = $r['handler'];
            }
        }

        
        if ($this->prioritizeDetected && $this->prioritize && $routes !== []) {
            $order = [];

            foreach ($routes as $key => $value) {
                $key                    = $key === '/' ? $key : ltrim($key, '/ ');
                $priority               = $this->getRoutesOptions($key, $verb)['priority'] ?? 0;
                $order[$priority][$key] = $value;
            }

            ksort($order);
            $routes = array_merge(...$order);
        }

        return $routes;
    }

    
    public function getRoutesOptions(?string $from = null, ?string $verb = null): array
    {
        $options = $this->loadRoutesOptions($verb);

        return ((string) $from !== '') ? $options[$from] ?? [] : $options;
    }

    
    public function getHTTPVerb(): string
    {
        return $this->HTTPVerb;
    }

    
    public function setHTTPVerb(string $verb)
    {
        if ($verb !== '*' && $verb === strtolower($verb)) {
            @trigger_error(
                'Passing lowercase HTTP method "' . $verb . '" is deprecated.'
                . ' Use uppercase HTTP method like "' . strtoupper($verb) . '".',
                E_USER_DEPRECATED,
            );
        }

        
        $this->HTTPVerb = strtoupper($verb);

        return $this;
    }

    
    public function map(array $routes = [], ?array $options = null): RouteCollectionInterface
    {
        foreach ($routes as $from => $to) {
            $this->add($from, $to, $options);
        }

        return $this;
    }

    
    public function add(string $from, $to, ?array $options = null): RouteCollectionInterface
    {
        $this->create('*', $from, $to, $options);

        return $this;
    }

    
    public function addRedirect(string $from, string $to, int $status = 302)
    {
        
        if (array_key_exists($to, $this->routesNames['*'])) {
            $routeName  = $to;
            $routeKey   = $this->routesNames['*'][$routeName];
            $redirectTo = [$routeKey => $this->routes['*'][$routeKey]['handler']];
        } elseif (array_key_exists($to, $this->routesNames[Method::GET])) {
            $routeName  = $to;
            $routeKey   = $this->routesNames[Method::GET][$routeName];
            $redirectTo = [$routeKey => $this->routes[Method::GET][$routeKey]['handler']];
        } else {
            
            $redirectTo = $to;
        }

        $this->create('*', $from, $redirectTo, ['redirect' => $status]);

        return $this;
    }

    
    public function isRedirect(string $routeKey): bool
    {
        if (isset($this->routes['*'][$routeKey]['redirect'])) {
            return true;
        }

        
        $routeName = $this->routes['*'][$routeKey]['name'] ?? null;
        if ($routeName === $routeKey) {
            $routeKey = $this->routesNames['*'][$routeName];

            return isset($this->routes['*'][$routeKey]['redirect']);
        }

        return false;
    }

    
    public function getRedirectCode(string $routeKey): int
    {
        if (isset($this->routes['*'][$routeKey]['redirect'])) {
            return $this->routes['*'][$routeKey]['redirect'];
        }

        
        $routeName = $this->routes['*'][$routeKey]['name'] ?? null;
        if ($routeName === $routeKey) {
            $routeKey = $this->routesNames['*'][$routeName];

            return $this->routes['*'][$routeKey]['redirect'];
        }

        return 0;
    }

    
    public function group(string $name, ...$params)
    {
        $oldGroup   = $this->group;
        $oldOptions = $this->currentOptions;

        
        
        
        $this->group = $name !== '' ? trim($oldGroup . '/' . $name, '/') : $oldGroup;

        $callback = array_pop($params);

        if ($params !== [] && is_array($params[0])) {
            $options = array_shift($params);

            if (isset($options['filter'])) {
                
                $currentFilter     = (array) ($this->currentOptions['filter'] ?? []);
                $options['filter'] = array_merge($currentFilter, (array) $options['filter']);
            }

            
            $this->currentOptions = array_merge(
                $this->currentOptions ?? [],
                $options,
            );
        }

        if (is_callable($callback)) {
            $callback($this);
        }

        $this->group          = $oldGroup;
        $this->currentOptions = $oldOptions;
    }

    

    
    public function resource(string $name, ?array $options = null): RouteCollectionInterface
    {
        
        
        
        $newName = implode('\\', array_map(ucfirst(...), explode('/', $name)));

        
        
        if (isset($options['controller'])) {
            $newName = ucfirst(esc(strip_tags($options['controller'])));
        }

        
        
        $id = $options['placeholder'] ?? $this->placeholders[$this->defaultPlaceholder] ?? '(:segment)';

        
        $id = '(' . trim($id, '()') . ')';

        $methods = isset($options['only']) ? (is_string($options['only']) ? explode(',', $options['only']) : $options['only']) : ['index', 'show', 'create', 'update', 'delete', 'new', 'edit'];

        if (isset($options['except'])) {
            $options['except'] = is_array($options['except']) ? $options['except'] : explode(',', $options['except']);

            foreach ($methods as $i => $method) {
                if (in_array($method, $options['except'], true)) {
                    unset($methods[$i]);
                }
            }
        }

        if (in_array('index', $methods, true)) {
            $this->get($name, $newName . '::index', $options);
        }
        if (in_array('new', $methods, true)) {
            $this->get($name . '/new', $newName . '::new', $options);
        }
        if (in_array('edit', $methods, true)) {
            $this->get($name . '/' . $id . '/edit', $newName . '::edit/$1', $options);
        }
        if (in_array('show', $methods, true)) {
            $this->get($name . '/' . $id, $newName . '::show/$1', $options);
        }
        if (in_array('create', $methods, true)) {
            $this->post($name, $newName . '::create', $options);
        }
        if (in_array('update', $methods, true)) {
            $this->put($name . '/' . $id, $newName . '::update/$1', $options);
            $this->patch($name . '/' . $id, $newName . '::update/$1', $options);
        }
        if (in_array('delete', $methods, true)) {
            $this->delete($name . '/' . $id, $newName . '::delete/$1', $options);
        }

        
        if (isset($options['websafe'])) {
            if (in_array('delete', $methods, true)) {
                $this->post($name . '/' . $id . '/delete', $newName . '::delete/$1', $options);
            }
            if (in_array('update', $methods, true)) {
                $this->post($name . '/' . $id, $newName . '::update/$1', $options);
            }
        }

        return $this;
    }

    
    public function presenter(string $name, ?array $options = null): RouteCollectionInterface
    {
        
        
        
        $newName = implode('\\', array_map(ucfirst(...), explode('/', $name)));

        
        
        if (isset($options['controller'])) {
            $newName = ucfirst(esc(strip_tags($options['controller'])));
        }

        
        
        $id = $options['placeholder'] ?? $this->placeholders[$this->defaultPlaceholder] ?? '(:segment)';

        
        $id = '(' . trim($id, '()') . ')';

        $methods = isset($options['only']) ? (is_string($options['only']) ? explode(',', $options['only']) : $options['only']) : ['index', 'show', 'new', 'create', 'edit', 'update', 'remove', 'delete'];

        if (isset($options['except'])) {
            $options['except'] = is_array($options['except']) ? $options['except'] : explode(',', $options['except']);

            foreach ($methods as $i => $method) {
                if (in_array($method, $options['except'], true)) {
                    unset($methods[$i]);
                }
            }
        }

        if (in_array('index', $methods, true)) {
            $this->get($name, $newName . '::index', $options);
        }
        if (in_array('show', $methods, true)) {
            $this->get($name . '/show/' . $id, $newName . '::show/$1', $options);
        }
        if (in_array('new', $methods, true)) {
            $this->get($name . '/new', $newName . '::new', $options);
        }
        if (in_array('create', $methods, true)) {
            $this->post($name . '/create', $newName . '::create', $options);
        }
        if (in_array('edit', $methods, true)) {
            $this->get($name . '/edit/' . $id, $newName . '::edit/$1', $options);
        }
        if (in_array('update', $methods, true)) {
            $this->post($name . '/update/' . $id, $newName . '::update/$1', $options);
        }
        if (in_array('remove', $methods, true)) {
            $this->get($name . '/remove/' . $id, $newName . '::remove/$1', $options);
        }
        if (in_array('delete', $methods, true)) {
            $this->post($name . '/delete/' . $id, $newName . '::delete/$1', $options);
        }
        if (in_array('show', $methods, true)) {
            $this->get($name . '/' . $id, $newName . '::show/$1', $options);
        }
        if (in_array('create', $methods, true)) {
            $this->post($name, $newName . '::create', $options);
        }

        return $this;
    }

    
    public function match(array $verbs = [], string $from = '', $to = '', ?array $options = null): RouteCollectionInterface
    {
        if ($from === '' || empty($to)) {
            throw new InvalidArgumentException('You must supply the parameters: from, to.');
        }

        foreach ($verbs as $verb) {
            if ($verb === strtolower($verb)) {
                @trigger_error(
                    'Passing lowercase HTTP method "' . $verb . '" is deprecated.'
                    . ' Use uppercase HTTP method like "' . strtoupper($verb) . '".',
                    E_USER_DEPRECATED,
                );
            }

            
            $verb = strtolower($verb);

            $this->{$verb}($from, $to, $options);
        }

        return $this;
    }

    
    public function get(string $from, $to, ?array $options = null): RouteCollectionInterface
    {
        $this->create(Method::GET, $from, $to, $options);

        return $this;
    }

    
    public function post(string $from, $to, ?array $options = null): RouteCollectionInterface
    {
        $this->create(Method::POST, $from, $to, $options);

        return $this;
    }

    
    public function put(string $from, $to, ?array $options = null): RouteCollectionInterface
    {
        $this->create(Method::PUT, $from, $to, $options);

        return $this;
    }

    
    public function delete(string $from, $to, ?array $options = null): RouteCollectionInterface
    {
        $this->create(Method::DELETE, $from, $to, $options);

        return $this;
    }

    
    public function head(string $from, $to, ?array $options = null): RouteCollectionInterface
    {
        $this->create(Method::HEAD, $from, $to, $options);

        return $this;
    }

    
    public function patch(string $from, $to, ?array $options = null): RouteCollectionInterface
    {
        $this->create(Method::PATCH, $from, $to, $options);

        return $this;
    }

    
    public function options(string $from, $to, ?array $options = null): RouteCollectionInterface
    {
        $this->create(Method::OPTIONS, $from, $to, $options);

        return $this;
    }

    
    public function cli(string $from, $to, ?array $options = null): RouteCollectionInterface
    {
        $this->create('CLI', $from, $to, $options);

        return $this;
    }

    
    public function view(string $from, string $view, ?array $options = null): RouteCollectionInterface
    {
        $to = static fn (...$data) => service('renderer')
            ->setData(['segments' => $data], 'raw')
            ->render($view, $options);

        $routeOptions = $options ?? [];
        $routeOptions = array_merge($routeOptions, ['view' => $view]);

        $this->create(Method::GET, $from, $to, $routeOptions);

        return $this;
    }

    
    public function environment(string $env, Closure $callback): RouteCollectionInterface
    {
        if ($env === ENVIRONMENT) {
            $callback($this);
        }

        return $this;
    }

    
    public function reverseRoute(string $search, ...$params)
    {
        if ($search === '') {
            return false;
        }

        
        foreach ($this->routesNames as $verb => $collection) {
            if (array_key_exists($search, $collection)) {
                $routeKey = $collection[$search];

                $from = $this->routes[$verb][$routeKey]['from'];

                return $this->buildReverseRoute($from, $params);
            }
        }

        
        $namespace = trim($this->defaultNamespace, '\\') . '\\';
        if (
            ! str_starts_with($search, '\\')
            && ! str_starts_with($search, $namespace)
        ) {
            $search = $namespace . $search;
        }

        
        
        foreach ($this->routes as $collection) {
            foreach ($collection as $route) {
                $to   = $route['handler'];
                $from = $route['from'];

                
                if (! is_string($to)) {
                    continue;
                }

                
                
                $to     = ltrim($to, '\\');
                $search = ltrim($search, '\\');

                
                
                if (! str_starts_with($to, $search)) {
                    continue;
                }

                
                
                if (substr_count($to, '$') !== count($params)) {
                    continue;
                }

                return $this->buildReverseRoute($from, $params);
            }
        }

        
        return false;
    }

    
    protected function localizeRoute(string $route): string
    {
        return strtr($route, ['{locale}' => service('request')->getLocale()]);
    }

    
    public function isFiltered(string $search, ?string $verb = null): bool
    {
        $options = $this->loadRoutesOptions($verb);

        return isset($options[$search]['filter']);
    }

    
    public function getFiltersForRoute(string $search, ?string $verb = null): array
    {
        $options = $this->loadRoutesOptions($verb);

        if (! array_key_exists($search, $options) || ! array_key_exists('filter', $options[$search])) {
            return [];
        }

        if (is_string($options[$search]['filter'])) {
            return [$options[$search]['filter']];
        }

        return $options[$search]['filter'];
    }

    
    protected function fillRouteParams(string $from, ?array $params = null): string
    {
        
        preg_match_all('/\(([^)]+)\)/', $from, $matches);

        if (empty($matches[0])) {
            return '/' . ltrim($from, '/');
        }

        
        $patterns = $matches[0];

        foreach ($patterns as $index => $pattern) {
            if (preg_match('#^' . $pattern . '$#u', $params[$index]) !== 1) {
                throw RouterException::forInvalidParameterType();
            }

            
            
            $pos  = strpos($from, $pattern);
            $from = substr_replace($from, $params[$index], $pos, strlen($pattern));
        }

        return '/' . ltrim($from, '/');
    }

    
    protected function buildReverseRoute(string $from, array $params): string
    {
        $locale = null;

        
        preg_match_all('/\(([^)]+)\)/', $from, $matches);

        if (empty($matches[0])) {
            if (str_contains($from, '{locale}')) {
                $locale = $params[0] ?? null;
            }

            $from = $this->replaceLocale($from, $locale);

            return '/' . ltrim($from, '/');
        }

        
        $placeholderCount = count($matches[0]);
        if (count($params) > $placeholderCount) {
            $locale = $params[$placeholderCount];
        }

        
        $placeholders = $matches[0];

        foreach ($placeholders as $index => $placeholder) {
            if (! isset($params[$index])) {
                throw new InvalidArgumentException(
                    'Missing argument for "' . $placeholder . '" in route "' . $from . '".',
                );
            }

            
            $placeholderName = substr($placeholder, 2, -1);
            
            $pattern = $this->placeholders[$placeholderName] ?? $placeholder;

            if (preg_match('#^' . $pattern . '$#u', (string) $params[$index]) !== 1) {
                throw RouterException::forInvalidParameterType();
            }

            
            
            $pos  = strpos($from, $placeholder);
            $from = substr_replace($from, (string) $params[$index], $pos, strlen($placeholder));
        }

        $from = $this->replaceLocale($from, $locale);

        return '/' . ltrim($from, '/');
    }

    
    private function replaceLocale(string $route, ?string $locale = null): string
    {
        if (! str_contains($route, '{locale}')) {
            return $route;
        }

        
        if ((string) $locale !== '') {
            $config = config(App::class);
            if (! in_array($locale, $config->supportedLocales, true)) {
                $locale = null;
            }
        }

        if ((string) $locale === '') {
            $locale = service('request')->getLocale();
        }

        return strtr($route, ['{locale}' => $locale]);
    }

    
    protected function create(string $verb, string $from, $to, ?array $options = null)
    {
        $overwrite = false;
        $prefix    = $this->group === null ? '' : $this->group . '/';

        $from = esc(strip_tags($prefix . $from));

        
        
        if ($from !== '/') {
            $from = trim($from, '/');
        }

        
        if (is_array($to) && isset($to[0])) {
            $to = $this->processArrayCallableSyntax($from, $to);
        }

        
        if (isset($options['filter'])) {
            $currentFilter     = (array) ($this->currentOptions['filter'] ?? []);
            $options['filter'] = array_merge($currentFilter, (array) $options['filter']);
        }

        $options = array_merge($this->currentOptions ?? [], $options ?? []);

        
        if (isset($options['priority'])) {
            $options['priority'] = abs((int) $options['priority']);

            if ($options['priority'] > 0) {
                $this->prioritizeDetected = true;
            }
        }

        
        if (! empty($options['hostname'])) {
            
            if (! $this->checkHostname($options['hostname'])) {
                return;
            }

            $overwrite = true;
        }
        
        elseif (! empty($options['subdomain'])) {
            
            
            if (! $this->checkSubdomains($options['subdomain'])) {
                return;
            }

            $overwrite = true;
        }

        
        
        
        if (isset($options['offset']) && is_string($to)) {
            
            $to = preg_replace('/(\$\d+)/', '$X', $to);

            for ($i = (int) $options['offset'] + 1; $i < (int) $options['offset'] + 7; $i++) {
                $to = preg_replace_callback(
                    '/\$X/',
                    static fn ($m): string => '$' . $i,
                    $to,
                    1,
                );
            }
        }

        $routeKey = $from;

        
        
        foreach ($this->placeholders as $tag => $pattern) {
            $routeKey = str_ireplace(':' . $tag, $pattern, $routeKey);
        }

        
        if (! isset($options['redirect']) && is_string($to)) {
            
            if (! str_contains($to, '\\') || strpos($to, '\\') > 0) {
                $namespace = $options['namespace'] ?? $this->defaultNamespace;
                $to        = trim($namespace, '\\') . '\\' . $to;
            }
            
            
            $to = '\\' . ltrim($to, '\\');
        }

        $name = $options['as'] ?? $routeKey;

        helper('array');

        
        
        
        
        
        $routeKeyExists = isset($this->routes[$verb][$routeKey]);
        if ((isset($this->routesNames[$verb][$name]) || $routeKeyExists) && ! $overwrite) {
            return;
        }

        $this->routes[$verb][$routeKey] = [
            'name'    => $name,
            'handler' => $to,
            'from'    => $from,
        ];
        $this->routesOptions[$verb][$routeKey] = $options;
        $this->routesNames[$verb][$name]       = $routeKey;

        
        if (isset($options['redirect']) && is_numeric($options['redirect'])) {
            $this->routes['*'][$routeKey]['redirect'] = $options['redirect'];
        }
    }

    
    private function checkHostname($hostname): bool
    {
        
        if (! isset($this->httpHost)) {
            return false;
        }

        
        if (is_array($hostname)) {
            $hostnameLower = array_map(strtolower(...), $hostname);

            return in_array(strtolower($this->httpHost), $hostnameLower, true);
        }

        return strtolower($this->httpHost) === strtolower($hostname);
    }

    private function processArrayCallableSyntax(string $from, array $to): string
    {
        
        
        if (is_callable($to, true, $callableName)) {
            
            $params = $this->getMethodParams($from);

            return '\\' . $callableName . $params;
        }

        
        
        if (
            isset($to[0], $to[1])
            && is_callable($to[0], true, $callableName)
            && is_string($to[1])
        ) {
            $to = '\\' . $callableName . '/' . $to[1];
        }

        return $to;
    }

    
    private function getMethodParams(string $from): string
    {
        preg_match_all('/\(.+?\)/', $from, $matches);
        $count = count($matches[0]);

        $params = '';

        for ($i = 1; $i <= $count; $i++) {
            $params .= '/$' . $i;
        }

        return $params;
    }

    
    private function checkSubdomains($subdomains): bool
    {
        
        if (! isset($this->httpHost)) {
            return false;
        }

        if ($this->currentSubdomain === null) {
            $this->currentSubdomain = parse_subdomain($this->httpHost);
        }

        if (! is_array($subdomains)) {
            $subdomains = [$subdomains];
        }

        
        
        if (! empty($this->currentSubdomain) && in_array('*', $subdomains, true)) {
            return true;
        }

        return in_array($this->currentSubdomain, $subdomains, true);
    }

    
    public function resetRoutes()
    {
        $this->routes = $this->routesNames = ['*' => []];

        foreach ($this->defaultHTTPMethods as $verb) {
            $this->routes[$verb]      = [];
            $this->routesNames[$verb] = [];
        }

        $this->routesOptions = [];

        $this->prioritizeDetected = false;
        $this->didDiscover        = false;
    }

    
    protected function loadRoutesOptions(?string $verb = null): array
    {
        $verb ??= $this->getHTTPVerb();

        $options = $this->routesOptions[$verb] ?? [];

        if (isset($this->routesOptions['*'])) {
            foreach ($this->routesOptions['*'] as $key => $val) {
                if (isset($options[$key])) {
                    $extraOptions  = array_diff_key($val, $options[$key]);
                    $options[$key] = array_merge($options[$key], $extraOptions);
                } else {
                    $options[$key] = $val;
                }
            }
        }

        return $options;
    }

    
    public function setPrioritize(bool $enabled = true)
    {
        $this->prioritize = $enabled;

        return $this;
    }

    
    public function getRegisteredControllers(?string $verb = '*'): array
    {
        if ($verb !== '*' && $verb === strtolower($verb)) {
            @trigger_error(
                'Passing lowercase HTTP method "' . $verb . '" is deprecated.'
                . ' Use uppercase HTTP method like "' . strtoupper($verb) . '".',
                E_USER_DEPRECATED,
            );
        }

        
        $verb = strtoupper($verb);

        $controllers = [];

        if ($verb === '*') {
            foreach ($this->defaultHTTPMethods as $tmpVerb) {
                foreach ($this->routes[$tmpVerb] as $route) {
                    $controller = $this->getControllerName($route['handler']);
                    if ($controller !== null) {
                        $controllers[] = $controller;
                    }
                }
            }
        } else {
            $routes = $this->getRoutes($verb);

            foreach ($routes as $handler) {
                $controller = $this->getControllerName($handler);
                if ($controller !== null) {
                    $controllers[] = $controller;
                }
            }
        }

        return array_unique($controllers);
    }

    
    private function getControllerName($handler)
    {
        if (! is_string($handler)) {
            return null;
        }

        [$controller] = explode('::', $handler, 2);

        return $controller;
    }

    
    public function useSupportedLocalesOnly(bool $useOnly): self
    {
        $this->useSupportedLocalesOnly = $useOnly;

        return $this;
    }

    
    public function shouldUseSupportedLocalesOnly(): bool
    {
        return $this->useSupportedLocalesOnly;
    }
}
