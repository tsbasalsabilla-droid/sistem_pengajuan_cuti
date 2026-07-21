<?php

declare(strict_types=1);



namespace CodeIgniter\View;

use CodeIgniter\HTTP\URI;


class Plugins
{
    
    public static function currentURL()
    {
        return current_url();
    }

    
    public static function previousURL()
    {
        return previous_url();
    }

    
    public static function mailto(array $params = []): string
    {
        $email = $params['email'] ?? '';
        $title = $params['title'] ?? '';
        $attrs = $params['attributes'] ?? '';

        return mailto($email, $title, $attrs);
    }

    
    public static function safeMailto(array $params = []): string
    {
        $email = $params['email'] ?? '';
        $title = $params['title'] ?? '';
        $attrs = $params['attributes'] ?? '';

        return safe_mailto($email, $title, $attrs);
    }

    
    public static function lang(array $params = []): string
    {
        $line = array_shift($params);

        return lang($line, $params);
    }

    
    public static function validationErrors(array $params = []): string
    {
        $validator = service('validation');
        if ($params === []) {
            return $validator->listErrors();
        }

        return $validator->showError($params['field']);
    }

    
    public static function route(array $params = [])
    {
        return route_to(...$params);
    }

    
    public static function siteURL(array $params = []): string
    {
        return site_url(...$params);
    }

    
    public static function cspScriptNonce(): string
    {
        return csp_script_nonce();
    }

    
    public static function cspStyleNonce(): string
    {
        return csp_style_nonce();
    }
}
