<?php

declare(strict_types=1);



use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\SiteURI;
use CodeIgniter\HTTP\URI;
use CodeIgniter\Router\Exceptions\RouterException;
use Config\App;
use Config\Hostnames;



if (! function_exists('site_url')) {
    
    function site_url($relativePath = '', ?string $scheme = null, ?App $config = null): string
    {
        $currentURI = service('request')->getUri();

        assert($currentURI instanceof SiteURI);

        return $currentURI->siteUrl($relativePath, $scheme, $config);
    }
}

if (! function_exists('base_url')) {
    
    function base_url($relativePath = '', ?string $scheme = null): string
    {
        $currentURI = service('request')->getUri();

        assert($currentURI instanceof SiteURI);

        return $currentURI->baseUrl($relativePath, $scheme);
    }
}

if (! function_exists('current_url')) {
    
    function current_url(bool $returnObject = false, ?IncomingRequest $request = null)
    {
        $request ??= service('request');
        
        $uri = $request->getUri();

        return $returnObject ? $uri : URI::createURIString($uri->getScheme(), $uri->getAuthority(), $uri->getPath());
    }
}

if (! function_exists('previous_url')) {
    
    function previous_url(bool $returnObject = false)
    {
        
        
        if (isset($_SESSION)) {
            $referer = session('_ci_previous_url');
        }

        
        $referer ??= request()->getServer('HTTP_REFERER', FILTER_SANITIZE_URL) ?? site_url('/');

        return $returnObject ? new URI($referer) : $referer;
    }
}

if (! function_exists('uri_string')) {
    
    function uri_string(): string
    {
        
        
        $uri = service('request')->getUri();

        $path = $uri instanceof SiteURI ? $uri->getRoutePath() : $uri->getPath();

        return ltrim($path, '/');
    }
}

if (! function_exists('index_page')) {
    
    function index_page(?App $altConfig = null): string
    {
        
        $config = $altConfig ?? config(App::class);

        return $config->indexPage;
    }
}

if (! function_exists('anchor')) {
    
    function anchor($uri = '', string $title = '', $attributes = '', ?App $altConfig = null): string
    {
        
        $config = $altConfig ?? config(App::class);

        $siteUrl = is_array($uri) ? site_url($uri, null, $config) : (preg_match('#^(\w+:)?//#i', $uri) ? $uri : site_url($uri, null, $config));
        
        $siteUrl = rtrim($siteUrl, '/');

        if ($title === '') {
            $title = $siteUrl;
        }

        if ($attributes !== '') {
            $attributes = stringify_attributes($attributes);
        }

        return '<a href="' . $siteUrl . '"' . $attributes . '>' . $title . '</a>';
    }
}

if (! function_exists('anchor_popup')) {
    
    function anchor_popup($uri = '', string $title = '', $attributes = false, ?App $altConfig = null): string
    {
        
        $config = $altConfig ?? config(App::class);

        $siteUrl = preg_match('#^(\w+:)?//#i', $uri) ? $uri : site_url($uri, null, $config);
        $siteUrl = rtrim($siteUrl, '/');

        if ($title === '') {
            $title = $siteUrl;
        }

        if ($attributes === false) {
            return '<a href="' . $siteUrl . '" onclick="window.open(\'' . $siteUrl . "', '_blank'); return false;\">" . $title . '</a>';
        }

        if (! is_array($attributes)) {
            $attributes = [$attributes];

            
            $windowName = '_blank';
        } elseif (! empty($attributes['window_name'])) {
            $windowName = $attributes['window_name'];
            unset($attributes['window_name']);
        } else {
            $windowName = '_blank';
        }

        $atts = [];

        foreach (['width' => '800', 'height' => '600', 'scrollbars' => 'yes', 'menubar' => 'no', 'status' => 'yes', 'resizable' => 'yes', 'screenx' => '0', 'screeny' => '0'] as $key => $val) {
            $atts[$key] = $attributes[$key] ?? $val;
            unset($attributes[$key]);
        }

        $attributes = stringify_attributes($attributes);

        return '<a href="' . $siteUrl
                . '" onclick="window.open(\'' . $siteUrl . "', '" . $windowName . "', '" . stringify_attributes($atts, true) . "'); return false;\""
                . $attributes . '>' . $title . '</a>';
    }
}

if (! function_exists('mailto')) {
    
    function mailto(string $email, string $title = '', $attributes = ''): string
    {
        if (trim($title) === '') {
            $title = $email;
        }

        return '<a href="mailto:' . $email . '"' . stringify_attributes($attributes) . '>' . $title . '</a>';
    }
}

if (! function_exists('safe_mailto')) {
    
    function safe_mailto(string $email, string $title = '', $attributes = ''): string
    {
        $count = 0;
        if (trim($title) === '') {
            $title = $email;
        }

        $x = str_split('<a href="mailto:', 1);

        for ($i = 0, $l = strlen($email); $i < $l; $i++) {
            $x[] = '|' . ord($email[$i]);
        }

        $x[] = '"';

        if ($attributes !== '') {
            if (is_array($attributes)) {
                foreach ($attributes as $key => $val) {
                    $x[] = ' ' . $key . '="';

                    for ($i = 0, $l = strlen($val); $i < $l; $i++) {
                        $x[] = '|' . ord($val[$i]);
                    }

                    $x[] = '"';
                }
            } else {
                for ($i = 0, $l = mb_strlen($attributes); $i < $l; $i++) {
                    $x[] = mb_substr($attributes, $i, 1);
                }
            }
        }

        $x[] = '>';

        $temp = [];

        for ($i = 0, $l = strlen($title); $i < $l; $i++) {
            $ordinal = ord($title[$i]);

            if ($ordinal < 128) {
                $x[] = '|' . $ordinal;
            } else {
                if ($temp === []) {
                    $count = ($ordinal < 224) ? 2 : 3;
                }

                $temp[] = $ordinal;

                if (count($temp) === $count) {
                    $number = ($count === 3) ? (($temp[0] % 16) * 4096) + (($temp[1] % 64) * 64) + ($temp[2] % 64) : (($temp[0] % 32) * 64) + ($temp[1] % 64);
                    $x[]    = '|' . $number;
                    $count  = 1;
                    $temp   = [];
                }
            }
        }

        $x[] = '<';
        $x[] = '/';
        $x[] = 'a';
        $x[] = '>';

        $x = array_reverse($x);

        
        $cspNonce = csp_script_nonce();
        $cspNonce = $cspNonce !== '' ? ' ' . $cspNonce : $cspNonce;
        $output   = '<script' . $cspNonce . '>'
                . 'var l=new Array();';

        foreach ($x as $i => $value) {
            $output .= 'l[' . $i . "] = '" . $value . "';";
        }

        return $output . ('for (var i = l.length-1; i >= 0; i=i-1) {'
                . "if (l[i].substring(0, 1) === '|') document.write(\"&#\"+unescape(l[i].substring(1))+\";\");"
                . 'else document.write(unescape(l[i]));'
                . '}'
                . '</script>');
    }
}

if (! function_exists('auto_link')) {
    
    function auto_link(string $str, string $type = 'both', bool $popup = false): string
    {
        
        if (
            $type !== 'email'
            && preg_match_all(
                '#([a-z][a-z0-9+\-.]*://|www\.)[a-z0-9]+(-+[a-z0-9]+)*(\.[a-z0-9]+(-+[a-z0-9]+)*)+(/([^\s()<>;]+\w)?/?)?#i',
                $str,
                $matches,
                PREG_OFFSET_CAPTURE | PREG_SET_ORDER,
            ) >= 1
        ) {
            
            $target = ($popup) ? ' target="_blank"' : '';

            
            
            
            foreach (array_reverse($matches) as $match) {
                
                
                
                
                
                $a   = '<a href="' . (strpos($match[1][0], '/') ? '' : 'http://') . $match[0][0] . '"' . $target . '>' . $match[0][0] . '</a>';
                $str = substr_replace($str, $a, $match[0][1], strlen($match[0][0]));
            }
        }

        
        if (
            $type !== 'url'
            && preg_match_all(
                '#([\w\.\-\+]+@[a-z0-9\-]+\.[a-z0-9\-\.]+[^[:punct:]\s])#i',
                $str,
                $matches,
                PREG_OFFSET_CAPTURE,
            ) >= 1
        ) {
            foreach (array_reverse($matches[0]) as $match) {
                if (filter_var($match[0], FILTER_VALIDATE_EMAIL) !== false) {
                    $str = substr_replace($str, safe_mailto($match[0]), $match[1], strlen($match[0]));
                }
            }
        }

        return $str;
    }
}

if (! function_exists('prep_url')) {
    
    function prep_url(string $str = '', bool $secure = false): string
    {
        if (in_array($str, ['http://', 'https://', '//', ''], true)) {
            return '';
        }

        if (parse_url($str, PHP_URL_SCHEME) === null) {
            $str = 'http://' . ltrim($str, '/');
        }

        
        if ($secure) {
            $str = preg_replace('/^(?:http):/i', 'https:', $str);
        }

        return $str;
    }
}

if (! function_exists('url_title')) {
    
    function url_title(string $str, string $separator = '-', bool $lowercase = false): string
    {
        $qSeparator = preg_quote($separator, '#');

        $trans = [
            '&.+?;'                  => '',
            '[^\w\d\pL\pM _-]'       => '',
            '\s+'                    => $separator,
            '(' . $qSeparator . ')+' => $separator,
        ];

        $str = strip_tags($str);

        foreach ($trans as $key => $val) {
            $str = preg_replace('#' . $key . '#iu', $val, $str);
        }

        if ($lowercase) {
            $str = mb_strtolower($str);
        }

        return trim(trim($str, $separator));
    }
}

if (! function_exists('mb_url_title')) {
    
    function mb_url_title(string $str, string $separator = '-', bool $lowercase = false): string
    {
        helper('text');

        return url_title(convert_accented_characters($str), $separator, $lowercase);
    }
}

if (! function_exists('url_to')) {
    
    function url_to(string $controller, ...$args): string
    {
        if (! $route = route_to($controller, ...$args)) {
            $explode = explode('::', $controller);

            if (isset($explode[1])) {
                throw RouterException::forControllerNotFound($explode[0], $explode[1]);
            }

            throw RouterException::forInvalidRoute($controller);
        }

        return site_url($route);
    }
}

if (! function_exists('url_is')) {
    
    function url_is(string $path): bool
    {
        
        $path        = '/' . trim(str_replace('*', '(\S)*', $path), '/ ');
        $currentPath = '/' . trim(uri_string(), '/ ');

        return (bool) preg_match("|^{$path}$|", $currentPath, $matches);
    }
}

if (! function_exists('parse_subdomain')) {
    
    function parse_subdomain(?string $host = null): string
    {
        if ($host === null) {
            $host = service('request')->getUri()->getHost();
        }

        
        if ($host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
            return '';
        }

        $parts     = explode('.', $host);
        $partCount = count($parts);

        
        
        if ($partCount < 3) {
            return '';
        }

        
        $lastTwoParts = $parts[$partCount - 2] . '.' . $parts[$partCount - 1];

        if (in_array($lastTwoParts, Hostnames::TWO_PART_TLDS, true)) {
            
            
            if ($partCount < 4) {
                return ''; 
            }

            
            
            return implode('.', array_slice($parts, 0, $partCount - 3));
        }

        
        
        return implode('.', array_slice($parts, 0, $partCount - 2));
    }
}
