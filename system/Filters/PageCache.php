<?php

declare(strict_types=1);



namespace CodeIgniter\Filters;

use CodeIgniter\Cache\ResponseCache;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Cache;


class PageCache implements FilterInterface
{
    private  ResponseCache $pageCache;

    
    private  array $cacheStatusCodes;

    public function __construct(?Cache $config = null)
    {
        $config ??= config('Cache');

        $this->pageCache        = service('responsecache');
        $this->cacheStatusCodes = $config->cacheStatusCodes ?? [];
    }

    
    public function before(RequestInterface $request, $arguments = null)
    {
        assert($request instanceof CLIRequest || $request instanceof IncomingRequest);

        $response = service('response');

        return $this->pageCache->get($request, $response);
    }

    
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        assert($request instanceof CLIRequest || $request instanceof IncomingRequest);

        if (
            ! $response instanceof DownloadResponse
            && ! $response instanceof RedirectResponse
            && ($this->cacheStatusCodes === [] || in_array($response->getStatusCode(), $this->cacheStatusCodes, true))
        ) {
            
            
            
            $this->pageCache->make($request, $response);

            return $response;
        }

        return null;
    }
}
