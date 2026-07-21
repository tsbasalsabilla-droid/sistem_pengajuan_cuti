<?php

declare(strict_types=1);



namespace CodeIgniter\Filters;

use CodeIgniter\Honeypot\Exceptions\HoneypotException;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;


class Honeypot implements FilterInterface
{
    
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! $request instanceof IncomingRequest) {
            return null;
        }

        if (service('honeypot')->hasContent($request)) {
            throw HoneypotException::isBot();
        }

        return null;
    }

    
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        service('honeypot')->attachHoneypot($response);

        return null;
    }
}
