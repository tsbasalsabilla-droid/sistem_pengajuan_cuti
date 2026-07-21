<?php

declare(strict_types=1);



namespace CodeIgniter\Filters;

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Security\Security;


class CSRF implements FilterInterface
{
    
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! $request instanceof IncomingRequest) {
            return null;
        }

        
        $security = service('security');

        try {
            $security->verify($request);
        } catch (SecurityException $e) {
            if ($security->shouldRedirect() && ! $request->isAJAX()) {
                return redirect()->back()->with('error', $e->getMessage());
            }

            throw $e;
        }

        return null;
    }

    
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
