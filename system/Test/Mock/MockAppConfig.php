<?php

declare(strict_types=1);



namespace CodeIgniter\Test\Mock;

use Config\App;

class MockAppConfig extends App
{
    public string $baseURL         = 'http://example.com/';
    public string $uriProtocol     = 'REQUEST_URI';
    public array $proxyIPs         = [];
    public bool $CSPEnabled        = false;
    public string $defaultLocale   = 'en';
    public bool $negotiateLocale   = false;
    public array $supportedLocales = [
        'en',
        'es',
    ];
}
