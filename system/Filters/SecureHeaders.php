<?php

declare(strict_types=1);



namespace CodeIgniter\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;


class SecureHeaders implements FilterInterface
{
    
    protected $headers = [
        
        'X-Frame-Options' => 'SAMEORIGIN',

        
        'X-Content-Type-Options' => 'nosniff',

        
        'X-Download-Options' => 'noopen',

        
        'X-Permitted-Cross-Domain-Policies' => 'none',

        
        'Referrer-Policy' => 'same-origin',

        
        
        
        
    ];

    
    public function before(RequestInterface $request, $arguments = null)
    {
        return null;
    }

    
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        foreach ($this->headers as $header => $value) {
            $response->setHeader($header, $value);
        }

        return $response;
    }
}
