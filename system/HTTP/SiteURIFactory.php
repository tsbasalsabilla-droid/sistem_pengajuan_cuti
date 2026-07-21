<?php

declare(strict_types=1);



namespace CodeIgniter\HTTP;

use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\Superglobals;
use Config\App;


final  class SiteURIFactory
{
    public function __construct(private App $appConfig, private Superglobals $superglobals)
    {
    }

    
    public function createFromGlobals(): SiteURI
    {
        $routePath = $this->detectRoutePath();

        return $this->createURIFromRoutePath($routePath);
    }

    
    public function createFromString(string $uri): SiteURI
    {
        
        if (filter_var($uri, FILTER_VALIDATE_URL) === false) {
            throw HTTPException::forUnableToParseURI($uri);
        }

        $parts = parse_url($uri);

        if ($parts === false) {
            throw HTTPException::forUnableToParseURI($uri);
        }

        $query = $fragment = '';
        if (isset($parts['query'])) {
            $query = '?' . $parts['query'];
        }
        if (isset($parts['fragment'])) {
            $fragment = '#' . $parts['fragment'];
        }

        $relativePath = ($parts['path'] ?? '') . $query . $fragment;
        $host         = $this->getValidHost($parts['host']);

        return new SiteURI($this->appConfig, $relativePath, $host, $parts['scheme']);
    }

    
    public function detectRoutePath(string $protocol = ''): string
    {
        if ($protocol === '') {
            $protocol = $this->appConfig->uriProtocol;
        }

        $routePath = match ($protocol) {
            'REQUEST_URI'  => $this->parseRequestURI(),
            'QUERY_STRING' => $this->parseQueryString(),
            default        => $this->superglobals->server($protocol) ?? $this->parseRequestURI(),
        };

        return ($routePath === '/' || $routePath === '') ? '/' : ltrim($routePath, '/');
    }

    
    private function parseRequestURI(): string
    {
        if (
            $this->superglobals->server('REQUEST_URI') === null
            || $this->superglobals->server('SCRIPT_NAME') === null
        ) {
            return '';
        }

        
        
        
        
        $parts = parse_url('http://dummy' . $this->superglobals->server('REQUEST_URI'));
        $query = $parts['query'] ?? '';
        $path  = $parts['path'] ?? '';

        
        if (
            $path !== '' && $this->superglobals->server('SCRIPT_NAME') !== ''
            && pathinfo($this->superglobals->server('SCRIPT_NAME'), PATHINFO_EXTENSION) === 'php'
        ) {
            
            $segments = explode('/', rawurldecode($path));
            $keep     = explode('/', $path);

            foreach (explode('/', $this->superglobals->server('SCRIPT_NAME')) as $i => $segment) {
                
                if (! isset($segments[$i]) || $segment !== $segments[$i]) {
                    break;
                }

                array_shift($keep);
            }

            $path = implode('/', $keep);
        }

        
        if ($this->appConfig->indexPage !== '' && str_starts_with($path, $this->appConfig->indexPage)) {
            $remainingPath = substr($path, strlen($this->appConfig->indexPage));
            
            if ($remainingPath === '' || str_starts_with($remainingPath, '/')) {
                $path = ltrim($remainingPath, '/');
            }
        }

        
        
        
        if (trim($path, '/') === '' && str_starts_with($query, '/')) {
            $parts    = explode('?', $query, 2);
            $path     = $parts[0];
            $newQuery = $query[1] ?? '';

            $this->superglobals->setServer('QUERY_STRING', $newQuery);
        } else {
            $this->superglobals->setServer('QUERY_STRING', $query);
        }

        
        parse_str($this->superglobals->server('QUERY_STRING'), $get);
        $this->superglobals->setGetArray($get);

        return URI::removeDotSegments($path);
    }

    
    private function parseQueryString(): string
    {
        $query = $this->superglobals->server('QUERY_STRING') ?? (string) getenv('QUERY_STRING');

        if (trim($query, '/') === '') {
            return '/';
        }

        if (str_starts_with($query, '/')) {
            $parts    = explode('?', $query, 2);
            $path     = $parts[0];
            $newQuery = $parts[1] ?? '';

            $this->superglobals->setServer('QUERY_STRING', $newQuery);
        } else {
            $path = $query;
        }

        
        parse_str($this->superglobals->server('QUERY_STRING'), $get);
        $this->superglobals->setGetArray($get);

        return URI::removeDotSegments($path);
    }

    
    private function createURIFromRoutePath(string $routePath): SiteURI
    {
        $query = $this->superglobals->server('QUERY_STRING') ?? '';

        $relativePath = $query !== '' ? $routePath . '?' . $query : $routePath;

        return new SiteURI($this->appConfig, $relativePath, $this->getHost());
    }

    
    private function getHost(): ?string
    {
        $httpHostPort = $this->superglobals->server('HTTP_HOST') ?? null;

        if ($httpHostPort !== null) {
            [$httpHost] = explode(':', $httpHostPort, 2);

            return $this->getValidHost($httpHost);
        }

        return null;
    }

    
    private function getValidHost(string $host): ?string
    {
        if (in_array($host, $this->appConfig->allowedHostnames, true)) {
            return $host;
        }

        return null;
    }
}
