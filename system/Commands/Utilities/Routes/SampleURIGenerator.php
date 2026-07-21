<?php

declare(strict_types=1);



namespace CodeIgniter\Commands\Utilities\Routes;

use CodeIgniter\Router\RouteCollection;
use Config\App;


final class SampleURIGenerator
{
    private  RouteCollection $routes;

    
    private array $samples = [
        'any'      => '123/abc',
        'segment'  => 'abc_123',
        'alphanum' => 'abc123',
        'num'      => '123',
        'alpha'    => 'abc',
        'hash'     => 'abc_123',
    ];

    public function __construct(?RouteCollection $routes = null)
    {
        $this->routes = $routes ?? service('routes');
    }

    
    public function get(string $routeKey): string
    {
        $sampleUri = $routeKey;

        if (str_contains($routeKey, '{locale}')) {
            $sampleUri = str_replace(
                '{locale}',
                config(App::class)->defaultLocale,
                $routeKey,
            );
        }

        foreach ($this->routes->getPlaceholders() as $placeholder => $regex) {
            $sample = $this->samples[$placeholder] ?? '::unknown::';

            $sampleUri = str_replace('(' . $regex . ')', $sample, $sampleUri);
        }

        
        return str_replace('[/...]', '/1/2/3/4/5', $sampleUri);
    }
}
