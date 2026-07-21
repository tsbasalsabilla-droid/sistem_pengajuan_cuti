<?php

declare(strict_types=1);



namespace CodeIgniter\Validation\StrictRules;

use CodeIgniter\Helpers\Array\ArrayHelper;
use CodeIgniter\Validation\Rules as NonStrictRules;


class Rules
{
    private  NonStrictRules $nonStrictRules;

    public function __construct()
    {
        $this->nonStrictRules = new NonStrictRules();
    }

    
    public function differs(
        $str,
        string $otherField,
        array $data,
        ?string $error = null,
        ?string $field = null,
    ): bool {
        if (str_contains($otherField, '.')) {
            return $str !== dot_array_search($otherField, $data);
        }

        if (! array_key_exists($otherField, $data)) {
            return false;
        }

        if (str_contains($field, '.')) {
            if (! ArrayHelper::dotKeyExists($field, $data)) {
                return false;
            }
        } elseif (! array_key_exists($field, $data)) {
            return false;
        }

        return $str !== ($data[$otherField] ?? null);
    }

    
    public function equals($str, string $val): bool
    {
        return $this->nonStrictRules->equals($str, $val);
    }

    
    public function exact_length($str, string $val): bool
    {
        if (is_int($str) || is_float($str)) {
            $str = (string) $str;
        }

        if (! is_string($str)) {
            return false;
        }

        return $this->nonStrictRules->exact_length($str, $val);
    }

    
    public function greater_than($str, string $min): bool
    {
        if (is_int($str) || is_float($str)) {
            $str = (string) $str;
        }

        if (! is_string($str)) {
            return false;
        }

        return $this->nonStrictRules->greater_than($str, $min);
    }

    
    public function greater_than_equal_to($str, string $min): bool
    {
        if (is_int($str) || is_float($str)) {
            $str = (string) $str;
        }

        if (! is_string($str)) {
            return false;
        }

        return $this->nonStrictRules->greater_than_equal_to($str, $min);
    }

    
    public function is_not_unique($str, string $field, array $data): bool
    {
        if (is_object($str) || is_array($str)) {
            return false;
        }

        return $this->nonStrictRules->is_not_unique($str, $field, $data);
    }

    
    public function in_list($value, string $list): bool
    {
        if (is_int($value) || is_float($value)) {
            $value = (string) $value;
        }

        if (! is_string($value)) {
            return false;
        }

        return $this->nonStrictRules->in_list($value, $list);
    }

    
    public function is_unique($str, string $field, array $data): bool
    {
        if (is_object($str) || is_array($str)) {
            return false;
        }

        return $this->nonStrictRules->is_unique($str, $field, $data);
    }

    
    public function less_than($str, string $max): bool
    {
        if (is_int($str) || is_float($str)) {
            $str = (string) $str;
        }

        if (! is_string($str)) {
            return false;
        }

        return $this->nonStrictRules->less_than($str, $max);
    }

    
    public function less_than_equal_to($str, string $max): bool
    {
        if (is_int($str) || is_float($str)) {
            $str = (string) $str;
        }

        if (! is_string($str)) {
            return false;
        }

        return $this->nonStrictRules->less_than_equal_to($str, $max);
    }

    
    public function matches(
        $str,
        string $otherField,
        array $data,
        ?string $error = null,
        ?string $field = null,
    ): bool {
        if (str_contains($otherField, '.')) {
            return $str === dot_array_search($otherField, $data);
        }

        if (! array_key_exists($otherField, $data)) {
            return false;
        }

        if (str_contains($field, '.')) {
            if (! ArrayHelper::dotKeyExists($field, $data)) {
                return false;
            }
        } elseif (! array_key_exists($field, $data)) {
            return false;
        }

        return $str === ($data[$otherField] ?? null);
    }

    
    public function max_length($str, string $val): bool
    {
        if (is_int($str) || is_float($str) || null === $str) {
            $str = (string) $str;
        }

        if (! is_string($str)) {
            return false;
        }

        return $this->nonStrictRules->max_length($str, $val);
    }

    
    public function min_length($str, string $val): bool
    {
        if (is_int($str) || is_float($str)) {
            $str = (string) $str;
        }

        if (! is_string($str)) {
            return false;
        }

        return $this->nonStrictRules->min_length($str, $val);
    }

    
    public function not_equals($str, string $val): bool
    {
        return $this->nonStrictRules->not_equals($str, $val);
    }

    
    public function not_in_list($value, string $list): bool
    {
        if (null === $value) {
            return true;
        }

        if (is_int($value) || is_float($value)) {
            $value = (string) $value;
        }

        if (! is_string($value)) {
            return false;
        }

        return $this->nonStrictRules->not_in_list($value, $list);
    }

    
    public function required($str = null): bool
    {
        return $this->nonStrictRules->required($str);
    }

    
    public function required_with($str = null, ?string $fields = null, array $data = []): bool
    {
        return $this->nonStrictRules->required_with($str, $fields, $data);
    }

    
    public function required_without(
        $str = null,
        ?string $otherFields = null,
        array $data = [],
        ?string $error = null,
        ?string $field = null,
    ): bool {
        return $this->nonStrictRules->required_without($str, $otherFields, $data, $error, $field);
    }

    
    public function field_exists(
        $value = null,
        ?string $param = null,
        array $data = [],
        ?string $error = null,
        ?string $field = null,
    ): bool {
        if (str_contains($field, '.')) {
            return ArrayHelper::dotKeyExists($field, $data);
        }

        return array_key_exists($field, $data);
    }
}
