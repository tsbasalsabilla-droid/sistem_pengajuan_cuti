<?php

declare(strict_types=1);



namespace CodeIgniter\Router\Attributes;

use Attribute;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;


#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Restrict implements RouteAttributeInterface
{
    public function __construct(
        public array|string|null $environment = null,
        public array|string|null $hostname = null,
        public array|string|null $subdomain = null,
    ) {
    }

    public function before(RequestInterface $request): RequestInterface|ResponseInterface|null
    {
        $this->checkEnvironment();
        $this->checkHostname($request);
        $this->checkSubdomain($request);

        return null; 
    }

    public function after(RequestInterface $request, ResponseInterface $response): ?ResponseInterface
    {
        return null; 
    }

    protected function checkEnvironment(): void
    {
        if ($this->environment === null || $this->environment === []) {
            return;
        }

        $currentEnv = ENVIRONMENT;
        $allowed    = [];
        $denied     = [];

        foreach ((array) $this->environment as $env) {
            if (str_starts_with($env, '!')) {
                $denied[] = substr($env, 1);
            } else {
                $allowed[] = $env;
            }
        }

        
        if ($denied !== [] && in_array($currentEnv, $denied, true)) {
            throw new PageNotFoundException('Access denied: Current environment is blocked.');
        }

        
        
        if ($allowed !== [] && ! in_array($currentEnv, $allowed, true)) {
            throw new PageNotFoundException('Access denied: Current environment is not allowed.');
        }
    }

    private function checkHostname(RequestInterface $request): void
    {
        if ($this->hostname === null || $this->hostname === []) {
            return;
        }

        $currentHost  = strtolower($request->getUri()->getHost());
        $allowedHosts = array_map(strtolower(...), (array) $this->hostname);

        if (! in_array($currentHost, $allowedHosts, true)) {
            throw new PageNotFoundException('Access denied: Host is not allowed.');
        }
    }

    private function checkSubdomain(RequestInterface $request): void
    {
        if ($this->subdomain === null || $this->subdomain === []) {
            return;
        }

        $currentSubdomain  = parse_subdomain($request->getUri()->getHost());
        $allowedSubdomains = array_map(strtolower(...), (array) $this->subdomain);

        
        if ($currentSubdomain === '') {
            throw new PageNotFoundException('Access denied: Subdomain required');
        }

        
        if (! in_array($currentSubdomain, $allowedSubdomains, true)) {
            throw new PageNotFoundException('Access denied: subdomain is blocked.');
        }
    }
}
