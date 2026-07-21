<?php

declare(strict_types=1);



namespace CodeIgniter\Test\Mock;

use Config\App;

class MockCLIConfig extends App
{
    public string $baseURL        = 'http://example.com/';
    public string $uriProtocol    = 'REQUEST_URI';
    public array $proxyIPs        = [];
    public string $CSRFTokenName  = 'csrf_test_name';
    public string $CSRFCookieName = 'csrf_cookie_name';
    public int $CSRFExpire        = 7200;
    public bool $CSRFRegenerate   = true;

    
    public array $CSRFExcludeURIs = ['http://example.com'];

    public string $CSRFSameSite    = 'Lax';
    public bool $CSPEnabled        = false;
    public string $defaultLocale   = 'en';
    public bool $negotiateLocale   = false;
    public array $supportedLocales = [
        'en',
        'es',
    ];
}
