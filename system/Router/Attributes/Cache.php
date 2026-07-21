<?php

declare(strict_types=1);



namespace CodeIgniter\Router\Attributes;

use Attribute;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\I18n\Time;


#[Attribute(Attribute::TARGET_METHOD)]
class Cache implements RouteAttributeInterface
{
    public function __construct(
        public int $for = 3600,
        public ?string $key = null,
    ) {
    }

    public function before(RequestInterface $request): RequestInterface|ResponseInterface|null
    {
        
        if ($request->getMethod() !== 'GET') {
            return null;
        }

        
        $cacheKey = $this->key ?? $this->generateCacheKey($request);

        $cached = cache($cacheKey);
        
        if ($cached !== null && (is_array($cached) && isset($cached['body'], $cached['headers'], $cached['status']))) {
            $response = service('response');
            $response->setBody($cached['body']);
            $response->setStatusCode($cached['status']);
            
            $response->setHeader('X-Cached-Response', 'true');

            
            foreach ($cached['headers'] as $name => $value) {
                $response->setHeader($name, $value);
            }
            $time = Time::now()->getTimestamp();
            $response->setHeader('Age', (string) ($time - ($cached['timestamp'] ?? $time)));

            return $response;
        }

        return null; 
    }

    public function after(RequestInterface $request, ResponseInterface $response): ?ResponseInterface
    {
        
        if ($response->hasHeader('X-Cached-Response')) {
            
            $response->removeHeader('X-Cached-Response');

            return null;
        }

        
        if ($request->getMethod() !== 'GET') {
            return null;
        }

        $cacheKey = $this->key ?? $this->generateCacheKey($request);

        
        $headers = [];

        foreach ($response->headers() as $name => $header) {
            
            if (is_array($header)) {
                
                $values = [];

                foreach ($header as $h) {
                    $values[] = $h->getValueLine();
                }
                $headers[$name] = implode(', ', $values);
            } else {
                
                $headers[$name] = $header->getValueLine();
            }
        }

        $data = [
            'body'      => $response->getBody(),
            'headers'   => $headers,
            'status'    => $response->getStatusCode(),
            'timestamp' => Time::now()->getTimestamp(),
        ];

        cache()->save($cacheKey, $data, $this->for);

        return $response;
    }

    protected function generateCacheKey(RequestInterface $request): string
    {
        return 'route_cache_' . hash(
            'xxh128',
            $request->getMethod() .
            $request->getUri()->getPath() .
            $request->getUri()->getQuery() .
            (function_exists('user_id') ? user_id() : ''),
        );
    }
}
