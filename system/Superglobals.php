<?php

declare(strict_types=1);



namespace CodeIgniter;

use CodeIgniter\Exceptions\InvalidArgumentException;


final class Superglobals
{
    
    private array $server = [];

    
    private array $get = [];

    
    private array $post = [];

    
    private array $cookie = [];

    
    private array $files = [];

    
    private array $request = [];

    
    public function __construct(
        ?array $server = null,
        ?array $get = null,
        ?array $post = null,
        ?array $cookie = null,
        ?array $files = null,
        ?array $request = null,
    ) {
        $this
            ->setServerArray($server ?? $_SERVER)
            ->setGetArray($get ?? $_GET)
            ->setPostArray($post ?? $_POST)
            ->setCookieArray($cookie ?? $_COOKIE)
            ->setFilesArray($files ?? $_FILES)
            ->setRequestArray($request ?? $_REQUEST);
    }

    
    public function server(string $key, mixed $default = null): array|float|int|string|null
    {
        return $this->server[$key] ?? $default;
    }

    
    public function setServer(string $key, array|float|int|string $value): self
    {
        $this->server[$key] = $value;
        $_SERVER[$key]      = $value;

        return $this;
    }

    
    public function unsetServer(string $key): self
    {
        unset($this->server[$key], $_SERVER[$key]);

        return $this;
    }

    
    public function getServerArray(): array
    {
        return $this->server;
    }

    
    public function setServerArray(array $array): self
    {
        $this->server = $array;
        $_SERVER      = $array;

        return $this;
    }

    
    public function get(string $key, mixed $default = null): array|string|null
    {
        return $this->get[$key] ?? $default;
    }

    
    public function setGet(string $key, array|string $value): self
    {
        $this->get[$key] = $value;
        $_GET[$key]      = $value;

        return $this;
    }

    
    public function unsetGet(string $key): self
    {
        unset($this->get[$key], $_GET[$key]);

        return $this;
    }

    
    public function getGetArray(): array
    {
        return $this->get;
    }

    
    public function setGetArray(array $array): self
    {
        $this->get = $array;
        $_GET      = $array;

        return $this;
    }

    
    public function post(string $key, mixed $default = null): array|string|null
    {
        return $this->post[$key] ?? $default;
    }

    
    public function setPost(string $key, array|string $value): self
    {
        $this->post[$key] = $value;
        $_POST[$key]      = $value;

        return $this;
    }

    
    public function unsetPost(string $key): self
    {
        unset($this->post[$key], $_POST[$key]);

        return $this;
    }

    
    public function getPostArray(): array
    {
        return $this->post;
    }

    
    public function setPostArray(array $array): self
    {
        $this->post = $array;
        $_POST      = $array;

        return $this;
    }

    
    public function cookie(string $key, mixed $default = null): array|string|null
    {
        return $this->cookie[$key] ?? $default;
    }

    
    public function setCookie(string $key, array|string $value): self
    {
        $this->cookie[$key] = $value;
        $_COOKIE[$key]      = $value;

        return $this;
    }

    
    public function unsetCookie(string $key): self
    {
        unset($this->cookie[$key], $_COOKIE[$key]);

        return $this;
    }

    
    public function getCookieArray(): array
    {
        return $this->cookie;
    }

    
    public function setCookieArray(array $array): self
    {
        $this->cookie = $array;
        $_COOKIE      = $array;

        return $this;
    }

    
    public function request(string $key, mixed $default = null): array|string|null
    {
        return $this->request[$key] ?? $default;
    }

    
    public function setRequest(string $key, array|string $value): self
    {
        $this->request[$key] = $value;
        $_REQUEST[$key]      = $value;

        return $this;
    }

    
    public function unsetRequest(string $key): self
    {
        unset($this->request[$key], $_REQUEST[$key]);

        return $this;
    }

    
    public function getRequestArray(): array
    {
        return $this->request;
    }

    
    public function setRequestArray(array $array): self
    {
        $this->request = $array;
        $_REQUEST      = $array;

        return $this;
    }

    
    public function getFilesArray(): array
    {
        return $this->files;
    }

    
    public function setFilesArray(array $array): self
    {
        $this->files = $array;
        $_FILES      = $array;

        return $this;
    }

    
    public function getGlobalArray(string $name): array
    {
        return match ($name) {
            'server'  => $this->server,
            'get'     => $this->get,
            'post'    => $this->post,
            'cookie'  => $this->cookie,
            'files'   => $this->files,
            'request' => $this->request,
            default   => throw new InvalidArgumentException(
                "Invalid superglobal name '{$name}'. Must be one of: server, get, post, cookie, files, request.",
            ),
        };
    }

    
    public function setGlobalArray(string $name, array $array): void
    {
        match ($name) {
            'server'  => $this->setServerArray($array),
            'get'     => $this->setGetArray($array),
            'post'    => $this->setPostArray($array),
            'cookie'  => $this->setCookieArray($array),
            'files'   => $this->setFilesArray($array),
            'request' => $this->setRequestArray($array),
            default   => throw new InvalidArgumentException(
                "Invalid superglobal name '{$name}'. Must be one of: server, get, post, cookie, files, request.",
            ),
        };
    }
}
