<?php

declare(strict_types=1);



namespace CodeIgniter\HTTP;

use CodeIgniter\Exceptions\InvalidArgumentException;


class Message implements MessageInterface
{
    use MessageTrait;

    
    protected $protocolVersion;

    
    protected $validProtocolVersions = [
        '1.0',
        '1.1',
        '2.0',
        '3.0',
    ];

    
    protected $body;

    
    public function getBody()
    {
        return $this->body;
    }

    
    public function getHeaders(): array
    {
        return $this->headers();
    }

    
    public function getHeader(string $name)
    {
        return $this->header($name);
    }

    
    public function hasHeader(string $name): bool
    {
        $origName = $this->getHeaderName($name);

        return isset($this->headers[$origName]);
    }

    
    public function getHeaderLine(string $name): string
    {
        if ($this->hasMultipleHeaders($name)) {
            throw new InvalidArgumentException(
                'The header "' . $name . '" already has multiple headers.'
                . ' You cannot use getHeaderLine().',
            );
        }

        $origName = $this->getHeaderName($name);

        if (! array_key_exists($origName, $this->headers)) {
            return '';
        }

        return $this->headers[$origName]->getValueLine();
    }

    
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion ?? '1.1';
    }
}
