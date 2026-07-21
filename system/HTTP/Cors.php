<?php

declare(strict_types=1);



namespace CodeIgniter\HTTP;

use CodeIgniter\Exceptions\ConfigException;
use Config\Cors as CorsConfig;


class Cors
{
    
    private array $config = [
        'allowedOrigins'         => [],
        'allowedOriginsPatterns' => [],
        'supportsCredentials'    => false,
        'allowedHeaders'         => [],
        'exposedHeaders'         => [],
        'allowedMethods'         => [],
        'maxAge'                 => 7200,
    ];

    
    public function __construct($config = null)
    {
        $config ??= config(CorsConfig::class);
        if ($config instanceof CorsConfig) {
            $config = $config->default;
        }
        $this->config = array_merge($this->config, $config);
    }

    
    public static function factory(string $configName = 'default'): self
    {
        $config = config(CorsConfig::class)->{$configName};

        return new self($config);
    }

    
    public function isPreflightRequest(IncomingRequest $request): bool
    {
        return $request->is('OPTIONS')
            && $request->hasHeader('Access-Control-Request-Method');
    }

    
    public function handlePreflightRequest(RequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $response->setStatusCode(204);

        $this->setAllowOrigin($request, $response);

        if ($response->hasHeader('Access-Control-Allow-Origin')) {
            $this->setAllowHeaders($response);
            $this->setAllowMethods($response);
            $this->setAllowMaxAge($response);
            $this->setAllowCredentials($response);
        }

        return $response;
    }

    private function checkWildcard(string $name, int $count): void
    {
        if (in_array('*', $this->config[$name], true) && $count > 1) {
            throw new ConfigException(
                "If wildcard is specified, you must set `'{$name}' => ['*']`."
                . ' But using wildcard is not recommended.',
            );
        }
    }

    private function checkWildcardAndCredentials(string $name, string $header): void
    {
        if (
            $this->config[$name] === ['*']
            && $this->config['supportsCredentials']
        ) {
            throw new ConfigException(
                'When responding to a credentialed request, '
                . 'the server must not specify the "*" wildcard for the '
                . $header . ' response-header value.',
            );
        }
    }

    private function setAllowOrigin(RequestInterface $request, ResponseInterface $response): void
    {
        $originCount        = count($this->config['allowedOrigins']);
        $originPatternCount = count($this->config['allowedOriginsPatterns']);

        $this->checkWildcard('allowedOrigins', $originCount);
        $this->checkWildcardAndCredentials('allowedOrigins', 'Access-Control-Allow-Origin');

        
        if ($originCount === 1 && $originPatternCount === 0) {
            $response->setHeader('Access-Control-Allow-Origin', $this->config['allowedOrigins'][0]);

            return;
        }

        
        if (! $request->hasHeader('Origin')) {
            return;
        }

        $origin = $request->getHeaderLine('Origin');

        if ($originCount > 1 && in_array($origin, $this->config['allowedOrigins'], true)) {
            $response->setHeader('Access-Control-Allow-Origin', $origin);
            $response->appendHeader('Vary', 'Origin');

            return;
        }

        if ($originPatternCount > 0) {
            foreach ($this->config['allowedOriginsPatterns'] as $pattern) {
                $regex = '#\A' . $pattern . '\z#';

                if (preg_match($regex, $origin)) {
                    $response->setHeader('Access-Control-Allow-Origin', $origin);
                    $response->appendHeader('Vary', 'Origin');

                    return;
                }
            }
        }
    }

    private function setAllowHeaders(ResponseInterface $response): void
    {
        $this->checkWildcard('allowedHeaders', count($this->config['allowedHeaders']));
        $this->checkWildcardAndCredentials('allowedHeaders', 'Access-Control-Allow-Headers');

        $response->setHeader(
            'Access-Control-Allow-Headers',
            implode(', ', $this->config['allowedHeaders']),
        );
    }

    private function setAllowMethods(ResponseInterface $response): void
    {
        $this->checkWildcard('allowedMethods', count($this->config['allowedMethods']));
        $this->checkWildcardAndCredentials('allowedMethods', 'Access-Control-Allow-Methods');

        $response->setHeader(
            'Access-Control-Allow-Methods',
            implode(', ', $this->config['allowedMethods']),
        );
    }

    private function setAllowMaxAge(ResponseInterface $response): void
    {
        $response->setHeader('Access-Control-Max-Age', (string) $this->config['maxAge']);
    }

    private function setAllowCredentials(ResponseInterface $response): void
    {
        if ($this->config['supportsCredentials']) {
            $response->setHeader('Access-Control-Allow-Credentials', 'true');
        }
    }

    
    public function addResponseHeaders(RequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->setAllowOrigin($request, $response);

        if ($response->hasHeader('Access-Control-Allow-Origin')) {
            $this->setAllowCredentials($response);
            $this->setExposeHeaders($response);
        }

        return $response;
    }

    private function setExposeHeaders(ResponseInterface $response): void
    {
        if ($this->config['exposedHeaders'] !== []) {
            $response->setHeader(
                'Access-Control-Expose-Headers',
                implode(', ', $this->config['exposedHeaders']),
            );
        }
    }

    
    public function hasResponseHeaders(RequestInterface $request, ResponseInterface $response): bool
    {
        if (! $response->hasHeader('Access-Control-Allow-Origin')) {
            return false;
        }

        if ($this->config['supportsCredentials']
            && ! $response->hasHeader('Access-Control-Allow-Credentials')) {
            return false;
        }

        return ! ($this->config['exposedHeaders'] !== [] && (! $response->hasHeader('Access-Control-Expose-Headers') || ! str_contains(
            $response->getHeaderLine('Access-Control-Expose-Headers'),
            implode(', ', $this->config['exposedHeaders']),
        )));
    }
}
