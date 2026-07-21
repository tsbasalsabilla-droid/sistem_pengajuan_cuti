<?php

declare(strict_types=1);



namespace CodeIgniter\HTTP;

use CodeIgniter\Cookie\CookieStore;
use CodeIgniter\HTTP\Exceptions\HTTPException;


class RedirectResponse extends Response
{
    
    public function to(string $uri, ?int $code = null, string $method = 'auto')
    {
        
        
        if (! str_starts_with($uri, 'http')) {
            $uri = site_url($uri);
        }

        return $this->redirect($uri, $method, $code);
    }

    
    public function route(string $route, array $params = [], ?int $code = null, string $method = 'auto')
    {
        $namedRoute = $route;

        $route = service('routes')->reverseRoute($route, ...$params);

        if (! $route) {
            throw HTTPException::forInvalidRedirectRoute($namedRoute);
        }

        return $this->redirect(site_url($route), $method, $code);
    }

    
    public function back(?int $code = null, string $method = 'auto')
    {
        service('session');

        return $this->redirect(previous_url(), $method, $code);
    }

    
    public function withInput()
    {
        $session = service('session');
        $session->setFlashdata('_ci_old_input', [
            'get'  => service('superglobals')->getGetArray(),
            'post' => service('superglobals')->getPostArray(),
        ]);

        $this->withErrors();

        return $this;
    }

    
    private function withErrors(): self
    {
        $validation = service('validation');

        if ($validation->getErrors() !== []) {
            service('session')->setFlashdata('_ci_validation_errors', $validation->getErrors());
        }

        return $this;
    }

    
    public function with(string $key, $message)
    {
        service('session')->setFlashdata($key, $message);

        return $this;
    }

    
    public function withCookies()
    {
        $this->cookieStore = new CookieStore(service('response')->getCookies());

        return $this;
    }

    
    public function withHeaders()
    {
        foreach (service('response')->headers() as $name => $value) {
            if ($value instanceof Header) {
                $this->setHeader($name, $value->getValue());
            } else {
                foreach ($value as $header) {
                    $this->addHeader($name, $header->getValue());
                }
            }
        }

        return $this;
    }
}
