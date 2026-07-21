<?php

declare(strict_types=1);



namespace CodeIgniter\Validation;

use DateTime;


class FormatRules
{
    
    public function alpha($str = null): bool
    {
        if (! is_string($str)) {
            $str = (string) $str;
        }

        return ctype_alpha($str);
    }

    
    public function alpha_space($value = null): bool
    {
        if ($value === null) {
            return true;
        }

        if (! is_string($value)) {
            $value = (string) $value;
        }

        
        return (bool) preg_match('/\A[A-Z ]+\z/i', $value);
    }

    
    public function alpha_dash($str = null): bool
    {
        if ($str === null) {
            return false;
        }

        if (! is_string($str)) {
            $str = (string) $str;
        }

        return preg_match('/\A[a-z0-9_-]+\z/i', $str) === 1;
    }

    
    public function alpha_numeric_punct($str)
    {
        if ($str === null) {
            return false;
        }

        if (! is_string($str)) {
            $str = (string) $str;
        }

        return preg_match('/\A[A-Z0-9 ~!#$%\&\*\-_+=|:.]+\z/i', $str) === 1;
    }

    
    public function alpha_numeric($str = null): bool
    {
        if (! is_string($str)) {
            $str = (string) $str;
        }

        return ctype_alnum($str);
    }

    
    public function alpha_numeric_space($str = null): bool
    {
        if (! is_string($str)) {
            $str = (string) $str;
        }

        
        return (bool) preg_match('/\A[A-Z0-9 ]+\z/i', $str);
    }

    
    public function string($str = null): bool
    {
        return is_string($str);
    }

    
    public function decimal($str = null): bool
    {
        if (! is_string($str)) {
            $str = (string) $str;
        }

        
        return (bool) preg_match('/\A[-+]?\d{0,}\.?\d+\z/', $str);
    }

    
    public function hex($str = null): bool
    {
        if (! is_string($str)) {
            $str = (string) $str;
        }

        return ctype_xdigit($str);
    }

    
    public function integer($str = null): bool
    {
        if (! is_string($str)) {
            $str = (string) $str;
        }

        return (bool) preg_match('/\A[\-+]?\d+\z/', $str);
    }

    
    public function is_natural($str = null): bool
    {
        if (! is_string($str)) {
            $str = (string) $str;
        }

        return ctype_digit($str);
    }

    
    public function is_natural_no_zero($str = null): bool
    {
        if (! is_string($str)) {
            $str = (string) $str;
        }

        return $str !== '0' && ctype_digit($str);
    }

    
    public function numeric($str = null): bool
    {
        if (! is_string($str)) {
            $str = (string) $str;
        }

        
        return (bool) preg_match('/\A[\-+]?\d*\.?\d+\z/', $str);
    }

    
    public function regex_match($str, string $pattern): bool
    {
        if (! is_string($str)) {
            $str = (string) $str;
        }

        if (! str_starts_with($pattern, '/')) {
            $pattern = "/{$pattern}/";
        }

        return (bool) preg_match($pattern, $str);
    }

    
    public function timezone($str = null): bool
    {
        if (! is_string($str)) {
            $str = (string) $str;
        }

        return in_array($str, timezone_identifiers_list(), true);
    }

    
    public function valid_base64($str = null): bool
    {
        if ($str === null) {
            return false;
        }

        if (! is_string($str)) {
            $str = (string) $str;
        }

        $decoded = base64_decode($str, true);

        if ($decoded === false) {
            return false;
        }

        return base64_encode($decoded) === $str;
    }

    
    public function valid_json($str = null): bool
    {
        if (! is_string($str)) {
            $str = (string) $str;
        }

        json_decode($str);

        return json_last_error() === JSON_ERROR_NONE;
    }

    
    public function valid_email($str = null): bool
    {
        if (! is_string($str)) {
            $str = (string) $str;
        }

        
        if (function_exists('idn_to_ascii') && defined('INTL_IDNA_VARIANT_UTS46') && preg_match('#\A([^@]+)@(.+)\z#', $str, $matches)) {
            $str = $matches[1] . '@' . idn_to_ascii($matches[2], 0, INTL_IDNA_VARIANT_UTS46);
        }

        return (bool) filter_var($str, FILTER_VALIDATE_EMAIL);
    }

    
    public function valid_emails($str = null): bool
    {
        if (! is_string($str)) {
            $str = (string) $str;
        }

        foreach (explode(',', $str) as $email) {
            $email = trim($email);

            if ($email === '') {
                return false;
            }

            if ($this->valid_email($email) === false) {
                return false;
            }
        }

        return true;
    }

    
    public function valid_ip($ip = null, ?string $which = null): bool
    {
        if (! is_string($ip)) {
            $ip = (string) $ip;
        }

        if ($ip === '') {
            return false;
        }

        $option = match (strtolower($which ?? '')) {
            'ipv4'  => FILTER_FLAG_IPV4,
            'ipv6'  => FILTER_FLAG_IPV6,
            default => 0,
        };

        return filter_var($ip, FILTER_VALIDATE_IP, $option) !== false
            || (! ctype_print($ip) && filter_var(inet_ntop($ip), FILTER_VALIDATE_IP, $option) !== false);
    }

    
    public function valid_url($str = null): bool
    {
        if ($str === null || $str === '') {
            return false;
        }

        if (! is_string($str)) {
            $str = (string) $str;
        }

        if (preg_match('/\A(?:([^:]*)\:)?\/\/(.+)\z/', $str, $matches)) {
            if (! in_array($matches[1], ['http', 'https'], true)) {
                return false;
            }

            $str = $matches[2];
        }

        $str = 'http://' . $str;

        return filter_var($str, FILTER_VALIDATE_URL) !== false;
    }

    
    public function valid_url_strict($str = null, ?string $validSchemes = null): bool
    {
        if (in_array($str, [null, '', '0'], true)) {
            return false;
        }

        if (! is_string($str)) {
            $str = (string) $str;
        }

        
        $scheme       = strtolower((string) parse_url($str, PHP_URL_SCHEME));
        $validSchemes = explode(
            ',',
            strtolower($validSchemes ?? 'http,https'),
        );

        return in_array($scheme, $validSchemes, true)
            && filter_var($str, FILTER_VALIDATE_URL) !== false;
    }

    
    public function valid_date($str = null, ?string $format = null): bool
    {
        if (! is_string($str)) {
            $str = (string) $str;
        }

        if ($str === '') {
            return false;
        }

        if ($format === null || $format === '') {
            return strtotime($str) !== false;
        }

        $date   = DateTime::createFromFormat($format, $str);
        $errors = DateTime::getLastErrors();

        if ($date === false) {
            return false;
        }

        
        if ($errors === false) {
            return true;
        }

        return $errors['warning_count'] === 0 && $errors['error_count'] === 0;
    }
}
