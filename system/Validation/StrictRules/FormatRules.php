<?php

declare(strict_types=1);



namespace CodeIgniter\Validation\StrictRules;

use CodeIgniter\Validation\FormatRules as NonStrictFormatRules;


class FormatRules
{
    private  NonStrictFormatRules $nonStrictFormatRules;

    public function __construct()
    {
        $this->nonStrictFormatRules = new NonStrictFormatRules();
    }

    
    public function alpha($str = null): bool
    {
        if (! is_string($str)) {
            return false;
        }

        return $this->nonStrictFormatRules->alpha($str);
    }

    
    public function alpha_space($value = null): bool
    {
        if (! is_string($value)) {
            return false;
        }

        return $this->nonStrictFormatRules->alpha_space($value);
    }

    
    public function alpha_dash($str = null): bool
    {
        if (is_int($str)) {
            $str = (string) $str;
        }

        if (! is_string($str)) {
            return false;
        }

        return $this->nonStrictFormatRules->alpha_dash($str);
    }

    
    public function alpha_numeric_punct($str)
    {
        if (is_int($str) || is_float($str)) {
            $str = (string) $str;
        }

        if (! is_string($str)) {
            return false;
        }

        return $this->nonStrictFormatRules->alpha_numeric_punct($str);
    }

    
    public function alpha_numeric($str = null): bool
    {
        if (is_int($str)) {
            $str = (string) $str;
        }

        if (! is_string($str)) {
            return false;
        }

        return $this->nonStrictFormatRules->alpha_numeric($str);
    }

    
    public function alpha_numeric_space($str = null): bool
    {
        if (is_int($str)) {
            $str = (string) $str;
        }

        if (! is_string($str)) {
            return false;
        }

        return $this->nonStrictFormatRules->alpha_numeric_space($str);
    }

    
    public function string($str = null): bool
    {
        return $this->nonStrictFormatRules->string($str);
    }

    
    public function decimal($str = null): bool
    {
        if (is_int($str) || is_float($str)) {
            $str = (string) $str;
        }

        if (! is_string($str)) {
            return false;
        }

        return $this->nonStrictFormatRules->decimal($str);
    }

    
    public function hex($str = null): bool
    {
        if (is_int($str)) {
            $str = (string) $str;
        }

        if (! is_string($str)) {
            return false;
        }

        return $this->nonStrictFormatRules->hex($str);
    }

    
    public function integer($str = null): bool
    {
        if (is_int($str)) {
            $str = (string) $str;
        }

        if (! is_string($str)) {
            return false;
        }

        return $this->nonStrictFormatRules->integer($str);
    }

    
    public function is_natural($str = null): bool
    {
        if (is_int($str)) {
            $str = (string) $str;
        }

        if (! is_string($str)) {
            return false;
        }

        return $this->nonStrictFormatRules->is_natural($str);
    }

    
    public function is_natural_no_zero($str = null): bool
    {
        if (is_int($str)) {
            $str = (string) $str;
        }

        if (! is_string($str)) {
            return false;
        }

        return $this->nonStrictFormatRules->is_natural_no_zero($str);
    }

    
    public function numeric($str = null): bool
    {
        if (is_int($str) || is_float($str)) {
            $str = (string) $str;
        }

        if (! is_string($str)) {
            return false;
        }

        return $this->nonStrictFormatRules->numeric($str);
    }

    
    public function regex_match($str, string $pattern): bool
    {
        if (! is_string($str)) {
            return false;
        }

        return $this->nonStrictFormatRules->regex_match($str, $pattern);
    }

    
    public function timezone($str = null): bool
    {
        if (! is_string($str)) {
            return false;
        }

        return $this->nonStrictFormatRules->timezone($str);
    }

    
    public function valid_base64($str = null): bool
    {
        if (! is_string($str)) {
            return false;
        }

        return $this->nonStrictFormatRules->valid_base64($str);
    }

    
    public function valid_json($str = null): bool
    {
        if (! is_string($str)) {
            return false;
        }

        return $this->nonStrictFormatRules->valid_json($str);
    }

    
    public function valid_email($str = null): bool
    {
        if (! is_string($str)) {
            return false;
        }

        return $this->nonStrictFormatRules->valid_email($str);
    }

    
    public function valid_emails($str = null): bool
    {
        if (! is_string($str)) {
            return false;
        }

        return $this->nonStrictFormatRules->valid_emails($str);
    }

    
    public function valid_ip($ip = null, ?string $which = null): bool
    {
        if (! is_string($ip)) {
            return false;
        }

        return $this->nonStrictFormatRules->valid_ip($ip, $which);
    }

    
    public function valid_url($str = null): bool
    {
        if (! is_string($str)) {
            return false;
        }

        return $this->nonStrictFormatRules->valid_url($str);
    }

    
    public function valid_url_strict($str = null, ?string $validSchemes = null): bool
    {
        if (! is_string($str)) {
            return false;
        }

        return $this->nonStrictFormatRules->valid_url_strict($str, $validSchemes);
    }

    
    public function valid_date($str = null, ?string $format = null): bool
    {
        if (! is_string($str)) {
            return false;
        }

        return $this->nonStrictFormatRules->valid_date($str, $format);
    }
}
