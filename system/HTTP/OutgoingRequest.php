<?php

declare(strict_types=1);



namespace CodeIgniter\HTTP;


class OutgoingRequest extends Message implements OutgoingRequestInterface
{
    
    protected $method;

    
    protected $uri;

    
    public function __construct(
        string $method,
        ?URI $uri = null,
        array $headers = [],
        $body = null,
        string $version = '1.1',
    ) {
        $this->method = $method;
        $this->uri    = $uri;

        foreach ($headers as $header => $value) {
            $this->setHeader($header, $value);
        }

        $this->body            = $body;
        $this->protocolVersion = $version;

        if (! $this->hasHeader('Host') && $this->uri->getHost() !== '') {
            $this->setHeader('Host', $this->getHostFromUri($this->uri));
        }
    }

    private function getHostFromUri(URI $uri): string
    {
        $host = $uri->getHost();

        return $host . ($uri->getPort() > 0 ? ':' . $uri->getPort() : '');
    }

    
    public function getMethod(): string
    {
        return $this->method;
    }

    
    public function setMethod(string $method)
    {
        $this->method = $method;

        return $this;
    }

    
    public function withMethod($method)
    {
        $request         = clone $this;
        $request->method = $method;

        return $request;
    }

    
    public function getUri()
    {
        return $this->uri;
    }

    
    public function withUri(URI $uri, $preserveHost = false)
    {
        $request      = clone $this;
        $request->uri = $uri;

        if ($preserveHost) {
            if ($this->isHostHeaderMissingOrEmpty() && $uri->getHost() !== '') {
                $request->setHeader('Host', $this->getHostFromUri($uri));

                return $request;
            }

            if ($this->isHostHeaderMissingOrEmpty() && $uri->getHost() === '') {
                return $request;
            }

            if (! $this->isHostHeaderMissingOrEmpty()) {
                return $request;
            }
        }

        if ($uri->getHost() !== '') {
            $request->setHeader('Host', $this->getHostFromUri($uri));
        }

        return $request;
    }

    private function isHostHeaderMissingOrEmpty(): bool
    {
        if (! $this->hasHeader('Host')) {
            return true;
        }

        return $this->header('Host')->getValue() === '';
    }
}
