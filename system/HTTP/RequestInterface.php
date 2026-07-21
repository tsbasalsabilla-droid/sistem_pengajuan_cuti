<?php

declare(strict_types=1);



namespace CodeIgniter\HTTP;


interface RequestInterface extends OutgoingRequestInterface
{
    
    public function getIPAddress(): string;

    
    public function getServer($index = null, $filter = null);
}
