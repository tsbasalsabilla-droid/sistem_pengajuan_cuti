<?php

declare(strict_types=1);



use CodeIgniter\Cookie\Cookie;
use Config\Cookie as CookieConfig;





if (! function_exists('set_cookie')) {
    
    function set_cookie(
        $name,
        string $value = '',
        int $expire = 0,
        string $domain = '',
        string $path = '/',
        string $prefix = '',
        ?bool $secure = null,
        ?bool $httpOnly = null,
        ?string $sameSite = null,
    ): void {
        $response = service('response');
        $response->setCookie($name, $value, $expire, $domain, $path, $prefix, $secure, $httpOnly, $sameSite);
    }
}

if (! function_exists('get_cookie')) {
    
    function get_cookie($index, bool $xssClean = false, ?string $prefix = '')
    {
        if ($prefix === '') {
            $cookie = config(CookieConfig::class);

            $prefix = $cookie->prefix;
        }

        $request = service('request');
        $filter  = $xssClean ? FILTER_SANITIZE_FULL_SPECIAL_CHARS : FILTER_UNSAFE_RAW;

        return $request->getCookie($prefix . $index, $filter);
    }
}

if (! function_exists('delete_cookie')) {
    
    function delete_cookie($name, string $domain = '', string $path = '/', string $prefix = ''): void
    {
        service('response')->deleteCookie($name, $domain, $path, $prefix);
    }
}

if (! function_exists('has_cookie')) {
    
    function has_cookie(string $name, ?string $value = null, string $prefix = ''): bool
    {
        return service('response')->hasCookie($name, $value, $prefix);
    }
}
