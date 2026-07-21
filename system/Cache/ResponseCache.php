<?php

declare(strict_types=1);



namespace CodeIgniter\Cache;

use CodeIgniter\Exceptions\RuntimeException;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\Header;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Cache as CacheConfig;


final class ResponseCache
{
    
    private array|bool $cacheQueryString = false;

    
    private int $ttl = 0;

    public function __construct(CacheConfig $config, private  CacheInterface $cache)
    {
        $this->cacheQueryString = $config->cacheQueryString;
    }

    public function setTtl(int $ttl): self
    {
        $this->ttl = $ttl;

        return $this;
    }

    
    public function generateCacheKey(CLIRequest|IncomingRequest $request): string
    {
        if ($request instanceof CLIRequest) {
            return md5($request->getPath());
        }

        $uri = clone $request->getUri();

        $query = (bool) $this->cacheQueryString
            ? $uri->getQuery(is_array($this->cacheQueryString) ? ['only' => $this->cacheQueryString] : [])
            : '';

        return md5($request->getMethod() . ':' . $uri->setFragment('')->setQuery($query));
    }

    
    public function make(CLIRequest|IncomingRequest $request, ResponseInterface $response): bool
    {
        if ($this->ttl === 0) {
            return true;
        }

        $headers = [];

        foreach ($response->headers() as $name => $value) {
            if ($value instanceof Header) {
                $headers[$name] = $value->getValueLine();
            } else {
                foreach ($value as $header) {
                    $headers[$name][] = $header->getValueLine();
                }
            }
        }

        return $this->cache->save(
            $this->generateCacheKey($request),
            serialize([
                'headers' => $headers,
                'output'  => $response->getBody(),
                'status'  => $response->getStatusCode(),
                'reason'  => $response->getReasonPhrase(),
            ]),
            $this->ttl,
        );
    }

    
    public function get(CLIRequest|IncomingRequest $request, ResponseInterface $response): ?ResponseInterface
    {
        $cachedResponse = $this->cache->get($this->generateCacheKey($request));

        if (is_string($cachedResponse) && $cachedResponse !== '') {
            $cachedResponse = unserialize($cachedResponse);

            if (
                ! is_array($cachedResponse)
                || ! isset($cachedResponse['output'])
                || ! isset($cachedResponse['headers'])
            ) {
                throw new RuntimeException('Error unserializing page cache');
            }

            $headers = $cachedResponse['headers'];
            $output  = $cachedResponse['output'];
            $status  = $cachedResponse['status'] ?? 200;
            $reason  = $cachedResponse['reason'] ?? '';

            
            foreach (array_keys($response->headers()) as $key) {
                $response->removeHeader($key);
            }

            
            foreach ($headers as $name => $value) {
                $response->setHeader($name, $value);
            }

            $response->setBody($output);

            $response->setStatusCode($status, $reason);

            return $response;
        }

        return null;
    }
}
