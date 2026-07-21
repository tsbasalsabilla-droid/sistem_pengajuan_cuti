<?php

declare(strict_types=1);



namespace CodeIgniter\Router;

use Closure;
use CodeIgniter\HTTP\ResponseInterface;


interface RouteCollectionInterface
{
    
    public function add(string $from, $to, ?array $options = null);

    
    public function addPlaceholder($placeholder, ?string $pattern = null);

    
    public function setDefaultNamespace(string $value);

    
    public function getDefaultNamespace(): string;

    
    public function setDefaultController(string $value);

    
    public function setDefaultMethod(string $value);

    
    public function setTranslateURIDashes(bool $value);

    
    public function setAutoRoute(bool $value): self;

    
    public function set404Override($callable = null): self;

    
    public function get404Override();

    
    public function getDefaultController();

    
    public function getDefaultMethod();

    
    public function shouldTranslateURIDashes();

    
    public function shouldAutoRoute();

    
    public function getRoutes(?string $verb = null, bool $includeWildcard = true): array;

    
    public function getRoutesOptions(?string $from = null, ?string $verb = null): array;

    
    public function setHTTPVerb(string $verb);

    
    public function getHTTPVerb();

    
    public function reverseRoute(string $search, ...$params);

    
    public function isRedirect(string $routeKey): bool;

    
    public function getRedirectCode(string $routeKey): int;

    
    public function shouldUseSupportedLocalesOnly(): bool;

    
    public function isFiltered(string $search, ?string $verb = null): bool;

    
    public function getFiltersForRoute(string $search, ?string $verb = null): array;
}
