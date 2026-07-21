<?php

declare(strict_types=1);



namespace CodeIgniter\Filters;

use CodeIgniter\HTTP\Exceptions\RedirectException;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\App;


class ForceHTTPS implements FilterInterface
{
    
    public function before(RequestInterface $request, $arguments = null)
    {
        $config = config(App::class);

        if ($config->forceGlobalSecureRequests !== true) {
            return null;
        }

        $response = service('response');

        try {
            force_https(YEAR, $request, $response);
        } catch (RedirectException $e) {
            return $e->getResponse();
        }

        return null;
    }

    
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
