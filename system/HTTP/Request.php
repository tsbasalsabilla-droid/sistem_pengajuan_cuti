<?php

declare(strict_types=1);



namespace CodeIgniter\HTTP;

use Config\App;


class Request extends OutgoingRequest implements RequestInterface
{
    use RequestTrait;

    
    public function __construct($config = null)
    {
        $this->config = $config ?? config(App::class);

        if (empty($this->method)) {
            $this->method = $this->getServer('REQUEST_METHOD') ?? Method::GET;
        }

        if (empty($this->uri)) {
            $this->uri = new URI();
        }
    }

    
    public function setMethod(string $method)
    {
        $this->method = $method;

        return $this;
    }

    
    public function withMethod($method)
    {
        $request = clone $this;

        $request->method = $method;

        return $request;
    }

    
    public function getUri()
    {
        return $this->uri;
    }
}
