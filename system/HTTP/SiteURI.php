<?php

declare(strict_types=1);



namespace CodeIgniter\HTTP;

use CodeIgniter\Exceptions\BadMethodCallException;
use CodeIgniter\Exceptions\ConfigException;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use Config\App;


class SiteURI extends URI
{
    
    private  URI $baseURL;

    
    private string $basePathWithoutIndexPage;

    
    private  string $indexPage;

    
    private array $baseSegments;

    
    protected $segments;

    
    private string $routePath;

    
    public function __construct(
        App $configApp,
        string $relativePath = '',
        ?string $host = null,
        ?string $scheme = null,
    ) {
        $this->indexPage = $configApp->indexPage;

        $this->baseURL = $this->determineBaseURL($configApp, $host, $scheme);

        $this->setBasePath();

        
        [$routePath, $query, $fragment] = $this->parseRelativePath($relativePath);

        
        $indexPageRoutePath = $this->getIndexPageRoutePath($routePath);

        
        $uri = $this->baseURL . $indexPageRoutePath;

        
        $parts = parse_url($uri);
        if ($parts === false) {
            throw HTTPException::forUnableToParseURI($uri);
        }
        $parts['query']    = $query;
        $parts['fragment'] = $fragment;
        $this->applyParts($parts);

        $this->setRoutePath($routePath);
    }

    private function parseRelativePath(string $relativePath): array
    {
        $parts = parse_url('http://dummy/' . $relativePath);
        if ($parts === false) {
            throw HTTPException::forUnableToParseURI($relativePath);
        }

        $routePath = $relativePath === '/' ? '/' : ltrim($parts['path'], '/');

        $query    = $parts['query'] ?? '';
        $fragment = $parts['fragment'] ?? '';

        return [$routePath, $query, $fragment];
    }

    private function determineBaseURL(
        App $configApp,
        ?string $host,
        ?string $scheme,
    ): URI {
        $baseURL = $this->normalizeBaseURL($configApp);

        $uri = new URI($baseURL);

        
        if ($scheme !== null && $scheme !== '') {
            $uri->setScheme($scheme);
        } elseif ($configApp->forceGlobalSecureRequests) {
            $uri->setScheme('https');
        }

        
        if ($host !== null) {
            $uri->setHost($host);
        }

        return $uri;
    }

    private function getIndexPageRoutePath(string $routePath): string
    {
        
        if ($routePath !== '' && $routePath[0] === '/' && $routePath !== '/') {
            $routePath = ltrim($routePath, '/');
        }

        
        $indexPage = '';
        if ($this->indexPage !== '') {
            $indexPage = $this->indexPage;

            
            if ($routePath !== '' && $routePath[0] !== '/' && $routePath[0] !== '?') {
                $indexPage .= '/';
            }
        }

        $indexPageRoutePath = $indexPage . $routePath;

        if ($indexPageRoutePath === '/') {
            $indexPageRoutePath = '';
        }

        return $indexPageRoutePath;
    }

    private function normalizeBaseURL(App $configApp): string
    {
        
        
        $baseURL = rtrim($configApp->baseURL, '/ ') . '/';

        
        if (filter_var($baseURL, FILTER_VALIDATE_URL) === false) {
            throw new ConfigException(
                'Config\App::$baseURL "' . $baseURL . '" is not a valid URL.',
            );
        }

        return $baseURL;
    }

    
    private function setBasePath(): void
    {
        $this->basePathWithoutIndexPage = $this->baseURL->getPath();

        $this->baseSegments = $this->convertToSegments($this->basePathWithoutIndexPage);

        if ($this->indexPage !== '') {
            $this->baseSegments[] = $this->indexPage;
        }
    }

    
    public function setBaseURL(string $baseURL): void
    {
        throw new BadMethodCallException('Cannot use this method.');
    }

    
    public function setURI(?string $uri = null)
    {
        throw new BadMethodCallException('Cannot use this method.');
    }

    
    public function getBaseURL(): string
    {
        return (string) $this->baseURL;
    }

    
    public function getRoutePath(): string
    {
        return $this->routePath;
    }

    
    public function __toString(): string
    {
        return static::createURIString(
            $this->getScheme(),
            $this->getAuthority(),
            $this->getPath(),
            $this->getQuery(),
            $this->getFragment(),
        );
    }

    
    public function setPath(string $path)
    {
        $this->setRoutePath($path);

        return $this;
    }

    
    private function setRoutePath(string $routePath): void
    {
        $routePath = $this->filterPath($routePath);

        $indexPageRoutePath = $this->getIndexPageRoutePath($routePath);

        $this->path = $this->basePathWithoutIndexPage . $indexPageRoutePath;

        $this->routePath = ltrim($routePath, '/');

        $this->segments = $this->convertToSegments($this->routePath);
    }

    
    private function convertToSegments(string $path): array
    {
        $tempPath = trim($path, '/');

        return ($tempPath === '') ? [] : explode('/', $tempPath);
    }

    
    public function refreshPath()
    {
        $allSegments = array_merge($this->baseSegments, $this->segments);
        $this->path  = '/' . $this->filterPath(implode('/', $allSegments));

        if ($this->routePath === '/' && $this->path !== '/') {
            $this->path .= '/';
        }

        $this->routePath = $this->filterPath(implode('/', $this->segments));

        return $this;
    }

    
    protected function applyParts(array $parts): void
    {
        if (isset($parts['host']) && $parts['host'] !== '') {
            $this->host = $parts['host'];
        }

        if (isset($parts['user']) && $parts['user'] !== '') {
            $this->user = $parts['user'];
        }

        if (isset($parts['path']) && $parts['path'] !== '') {
            $this->path = $this->filterPath($parts['path']);
        }

        if (isset($parts['query']) && $parts['query'] !== '') {
            $this->setQuery($parts['query']);
        }

        if (isset($parts['fragment']) && $parts['fragment'] !== '') {
            $this->fragment = $parts['fragment'];
        }

        if (isset($parts['scheme'])) {
            $this->setScheme(rtrim($parts['scheme'], ':/'));
        } else {
            $this->setScheme('http');
        }

        if (isset($parts['port'])) {
            
            $this->port = $parts['port'];
        }

        if (isset($parts['pass'])) {
            $this->password = $parts['pass'];
        }
    }

    
    public function baseUrl($relativePath = '', ?string $scheme = null): string
    {
        $relativePath = $this->stringifyRelativePath($relativePath);

        $config            = clone config(App::class);
        $config->indexPage = '';

        $host = $this->getHost();

        $uri = new self($config, $relativePath, $host, $scheme);

        
        if ($scheme === '') {
            return substr((string) $uri, strlen($uri->getScheme()) + 1);
        }

        return (string) $uri;
    }

    
    private function stringifyRelativePath($relativePath): string
    {
        if (is_array($relativePath)) {
            $relativePath = implode('/', $relativePath);
        }

        return $relativePath;
    }

    
    public function siteUrl($relativePath = '', ?string $scheme = null, ?App $config = null): string
    {
        $relativePath = $this->stringifyRelativePath($relativePath);

        
        $host = $config instanceof App ? null : $this->getHost();

        $config ??= config(App::class);

        $uri = new self($config, $relativePath, $host, $scheme);

        
        if ($scheme === '') {
            return substr((string) $uri, strlen($uri->getScheme()) + 1);
        }

        return (string) $uri;
    }
}
