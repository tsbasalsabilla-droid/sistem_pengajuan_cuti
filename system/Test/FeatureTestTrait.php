<?php

declare(strict_types=1);



namespace CodeIgniter\Test;

use Closure;
use CodeIgniter\Events\Events;
use CodeIgniter\Exceptions\RuntimeException;
use CodeIgniter\HTTP\Exceptions\RedirectException;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\Method;
use CodeIgniter\HTTP\Request;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\SiteURI;
use CodeIgniter\HTTP\URI;
use CodeIgniter\Router\RouteCollection;
use Config\App;
use Config\Services;
use Exception;
use ReflectionException;


trait FeatureTestTrait
{
    
    protected function withRoutes(?array $routes = null)
    {
        $collection = service('routes');

        if ($routes !== null) {
            $collection->resetRoutes();

            foreach ($routes as $route) {
                if ($route[0] === strtolower($route[0])) {
                    @trigger_error(
                        'Passing lowercase HTTP method "' . $route[0] . '" is deprecated.'
                        . ' Use uppercase HTTP method like "' . strtoupper($route[0]) . '".',
                        E_USER_DEPRECATED,
                    );
                }

                
                if (! in_array(strtoupper($route[0]), ['ADD', 'CLI', ...Method::all()], true)) {
                    throw new RuntimeException(sprintf(
                        'Invalid HTTP method "%s" provided for route "%s".',
                        $route[0],
                        $route[1],
                    ));
                }

                $method = strtolower($route[0]); 

                if (isset($route[3])) {
                    $collection->{$method}($route[1], $route[2], $route[3]);
                } else {
                    $collection->{$method}($route[1], $route[2]);
                }
            }
        }

        $this->routes = $collection;

        return $this;
    }

    
    public function withSession(?array $values = null)
    {
        $this->session = $values ?? $_SESSION;

        return $this;
    }

    
    public function withHeaders(array $headers = [])
    {
        $this->headers = $headers;

        return $this;
    }

    
    public function withBodyFormat(string $format)
    {
        $this->bodyFormat = $format;

        return $this;
    }

    
    public function withBody($body)
    {
        $this->requestBody = $body;

        return $this;
    }

    
    public function skipEvents()
    {
        Events::simulate(true);

        return $this;
    }

    
    public function call(string $method, string $path, ?array $params = null)
    {
        if ($method === strtolower($method)) {
            @trigger_error(
                'Passing lowercase HTTP method "' . $method . '" is deprecated.'
                . ' Use uppercase HTTP method like "' . strtoupper($method) . '".',
                E_USER_DEPRECATED,
            );
        }

        
        $method = strtoupper($method);

        
        $_SESSION = [];
        service('superglobals')->setServer('REQUEST_METHOD', $method);

        $request = $this->setupRequest($method, $path);
        $request = $this->setupHeaders($request);
        $name    = strtolower($method);
        $request = $this->populateGlobals($name, $request, $params);
        $request = $this->setRequestBody($request, $params);

        
        $routes = $this->routes;

        if ($routes !== []) {
            $routes = service('routes')->loadRoutes();
        }

        $routes->setHTTPVerb($method);

        
        
        Services::injectMock('request', $request);

        
        Services::injectMock('filters', service('filters', null, false));

        
        Services::injectMock('validation', service('validation', null, false));

        $response = $this->app
            ->setContext('web')
            ->setRequest($request)
            ->run($routes, true);

        
        service('router')->setDirectory();

        return new TestResponse($response);
    }

    
    public function get(string $path, ?array $params = null)
    {
        return $this->call(Method::GET, $path, $params);
    }

    
    public function post(string $path, ?array $params = null)
    {
        return $this->call(Method::POST, $path, $params);
    }

    
    public function put(string $path, ?array $params = null)
    {
        return $this->call(Method::PUT, $path, $params);
    }

    
    public function patch(string $path, ?array $params = null)
    {
        return $this->call(Method::PATCH, $path, $params);
    }

    
    public function delete(string $path, ?array $params = null)
    {
        return $this->call(Method::DELETE, $path, $params);
    }

    
    public function options(string $path, ?array $params = null)
    {
        return $this->call(Method::OPTIONS, $path, $params);
    }

    
    protected function setupRequest(string $method, ?string $path = null): IncomingRequest
    {
        $config = config(App::class);
        $uri    = new SiteURI($config);

        
        $path  = URI::removeDotSegments($path);
        $parts = explode('?', $path);
        $path  = $parts[0];
        $query = $parts[1] ?? '';

        $superglobals = service('superglobals');
        $superglobals->setServer('QUERY_STRING', $query);

        $uri->setPath($path);
        $uri->setQuery($query);

        Services::injectMock('uri', $uri);

        $request = service('incomingrequest', $config, false);

        $request->setMethod($method);
        $request->setProtocolVersion('1.1');

        if ($config->forceGlobalSecureRequests) {
            $_SERVER['HTTPS'] = 'test';
            $server           = $request->getServer();
            $server['HTTPS']  = 'test';
            $request->setGlobal('server', $server);
        }

        return $request;
    }

    
    protected function setupHeaders(IncomingRequest $request)
    {
        if (! empty($this->headers)) {
            foreach ($this->headers as $name => $value) {
                $request->setHeader($name, $value);
            }
        }

        return $request;
    }

    
    protected function populateGlobals(string $name, Request $request, ?array $params = null)
    {
        
        
        $get = ($params !== null && $params !== [] && $name === 'get')
            ? $params
            : $this->getPrivateProperty($request->getUri(), 'query');

        $request->setGlobal('get', $get);

        if ($name === 'get') {
            $request->setGlobal('request', $request->fetchGlobal('get'));
        }

        if ($name === 'post') {
            $request->setGlobal($name, $params ?? []);
            $request->setGlobal(
                'request',
                (array) $request->fetchGlobal('post') + (array) $request->fetchGlobal('get'),
            );
        }

        $_SESSION = $this->session;

        return $request;
    }

    
    protected function setRequestBody(Request $request, ?array $params = null): Request
    {
        if ($this->requestBody !== '') {
            $request->setBody($this->requestBody);
        }

        if ($this->bodyFormat !== '') {
            $formatMime = '';
            if ($this->bodyFormat === 'json') {
                $formatMime = 'application/json';
            } elseif ($this->bodyFormat === 'xml') {
                $formatMime = 'application/xml';
            }

            if ($formatMime !== '') {
                $request->setHeader('Content-Type', $formatMime);
            }

            if ($params !== null && $formatMime !== '') {
                $formatted = service('format')->getFormatter($formatMime)->format($params);
                
                $request->setBody($formatted);
            }
        }

        return $request;
    }
}
