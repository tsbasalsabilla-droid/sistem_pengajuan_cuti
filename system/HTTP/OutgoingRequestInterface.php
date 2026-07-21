<?php

declare(strict_types=1);



namespace CodeIgniter\HTTP;

use CodeIgniter\Exceptions\InvalidArgumentException;


interface OutgoingRequestInterface extends MessageInterface
{
    
    public function getMethod(): string;

    
    public function withMethod($method);

    
    public function getUri();

    
    public function withUri(URI $uri, $preserveHost = false);
}
