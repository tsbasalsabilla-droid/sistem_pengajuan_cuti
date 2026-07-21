<?php

declare(strict_types=1);



namespace CodeIgniter\Validation;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Exceptions\InvalidArgumentException;
use CodeIgniter\Helpers\Array\ArrayHelper;
use Config\Database;


class Rules
{
    
    public function differs($str, string $field, array $data): bool
    {
        if (str_contains($field, '.')) {
            return $str !== dot_array_search($field, $data);
        }

        return array_key_exists($field, $data) && $str !== $data[$field];
    }

    
    public function equals($str, string $val): bool
    {
        if (! is_string($str) && $str !== null) {
            $str = (string) $str;
        }

        return $str === $val;
    }

    
    public function exact_length($str, string $val): bool
    {
        if (! is_string($str) && $str !== null) {
            $str = (string) $str;
        }

        $val = explode(',', $val);

        foreach ($val as $tmp) {
            if (is_numeric($tmp) && (int) $tmp === mb_strlen($str ?? '')) {
                return true;
            }
        }

        return false;
    }

    
    public function greater_than($str, string $min): bool
    {
        if (! is_string($str) && $str !== null) {
            $str = (string) $str;
        }

        return is_numeric($str) && $str > $min;
    }

    
    public function greater_than_equal_to($str, string $min): bool
    {
        if (! is_string($str) && $str !== null) {
            $str = (string) $str;
        }

        return is_numeric($str) && $str >= $min;
    }

    
    public function is_not_unique($str, string $field, array $data): bool
    {
        [$builder, $whereField, $whereValue] = $this->prepareUniqueQuery($str, $field, $data);

        if (
            $whereField !== null && $whereField !== ''
            && $whereValue !== null && $whereValue !== ''
            && preg_match('/^\{(\w+)\}$/', $whereValue) !== 1
        ) {
            $builder = $builder->where($whereField, $whereValue);
        }

        return $builder->get()->getRow() !== null;
    }

    
    public function in_list($value, string $list): bool
    {
        if (! is_string($value) && $value !== null) {
            $value = (string) $value;
        }

        $list = array_map(trim(...), explode(',', $list));

        return in_array($value, $list, true);
    }

    
    public function is_unique($str, string $field, array $data): bool
    {
        [$builder, $ignoreField, $ignoreValue] = $this->prepareUniqueQuery($str, $field, $data);

        if (
            $ignoreField !== null && $ignoreField !== ''
            && $ignoreValue !== null && $ignoreValue !== ''
            && preg_match('/^\{(\w+)\}$/', $ignoreValue) !== 1
        ) {
            $builder = $builder->where("{$ignoreField} !=", $ignoreValue);
        }

        return $builder->get()->getRow() === null;
    }

    
    private function prepareUniqueQuery($value, string $field, array $data): array
    {
        if (! is_string($value) && $value !== null) {
            $value = (string) $value;
        }

        
        [$field, $extraField, $extraValue] = array_pad(explode(',', $field), 3, null);

        
        $parts    = explode('.', $field, 3);
        $numParts = count($parts);

        if ($numParts === 3) {
            [$dbGroup, $table, $field] = $parts;
        } elseif ($numParts === 2) {
            [$table, $field] = $parts;
        } else {
            throw new InvalidArgumentException('The field must be in the format "table.field" or "dbGroup.table.field".');
        }

        
        $dbGroup ??= $data['DBGroup'] ?? null;
        $builder = Database::connect($dbGroup)
            ->table($table)
            ->select('1')
            ->where($field, $value)
            ->limit(1);

        return [$builder, $extraField, $extraValue];
    }

    
    public function less_than($str, string $max): bool
    {
        if (! is_string($str) && $str !== null) {
            $str = (string) $str;
        }

        return is_numeric($str) && $str < $max;
    }

    
    public function less_than_equal_to($str, string $max): bool
    {
        if (! is_string($str) && $str !== null) {
            $str = (string) $str;
        }

        return is_numeric($str) && $str <= $max;
    }

    
    public function matches($str, string $field, array $data): bool
    {
        if (str_contains($field, '.')) {
            return $str === dot_array_search($field, $data);
        }

        return isset($data[$field]) && $str === $data[$field];
    }

    
    public function max_length($str, string $val): bool
    {
        if (! is_string($str) && $str !== null) {
            $str = (string) $str;
        }

        return is_numeric($val) && $val >= mb_strlen($str ?? '');
    }

    
    public function min_length($str, string $val): bool
    {
        if (! is_string($str) && $str !== null) {
            $str = (string) $str;
        }

        return is_numeric($val) && $val <= mb_strlen($str ?? '');
    }

    
    public function not_equals($str, string $val): bool
    {
        if (! is_string($str) && $str !== null) {
            $str = (string) $str;
        }

        return $str !== $val;
    }

    
    public function not_in_list($value, string $list): bool
    {
        if (! is_string($value) && $value !== null) {
            $value = (string) $value;
        }

        return ! $this->in_list($value, $list);
    }

    
    public function required($str = null): bool
    {
        if ($str === null) {
            return false;
        }

        if (is_object($str)) {
            return true;
        }

        if (is_array($str)) {
            return $str !== [];
        }

        return trim((string) $str) !== '';
    }

    
    public function required_with($str = null, ?string $fields = null, array $data = []): bool
    {
        if ($fields === null || $data === []) {
            throw new InvalidArgumentException('You must supply the parameters: fields, data.');
        }

        
        
        
        $present = $this->required($str ?? '');

        if ($present) {
            return true;
        }

        
        
        
        $requiredFields = [];

        foreach (explode(',', $fields) as $field) {
            if (
                (array_key_exists($field, $data) && ! empty($data[$field]))
                || (str_contains($field, '.') && ! empty(dot_array_search($field, $data)))
            ) {
                $requiredFields[] = $field;
            }
        }

        return $requiredFields === [];
    }

    
    public function required_without(
        $str = null,
        ?string $otherFields = null,
        array $data = [],
        ?string $error = null,
        ?string $field = null,
    ): bool {
        if ($otherFields === null || $data === []) {
            throw new InvalidArgumentException('You must supply the parameters: otherFields, data.');
        }

        
        
        
        $present = $this->required($str ?? '');

        if ($present) {
            return true;
        }

        
        
        foreach (explode(',', $otherFields) as $otherField) {
            if (
                (! str_contains($otherField, '.'))
                && (! array_key_exists($otherField, $data)
                    || empty($data[$otherField]))
            ) {
                return false;
            }

            if (str_contains($otherField, '.')) {
                if ($field === null) {
                    throw new InvalidArgumentException('You must supply the parameters: field.');
                }

                $fieldData       = dot_array_search($otherField, $data);
                $fieldSplitArray = explode('.', $field);
                $fieldKey        = $fieldSplitArray[1];

                if (is_array($fieldData)) {
                    return ! empty(dot_array_search($otherField, $data)[$fieldKey]);
                }
                $nowField      = str_replace('*', $fieldKey, $otherField);
                $nowFieldVaule = dot_array_search($nowField, $data);

                return null !== $nowFieldVaule;
            }
        }

        return true;
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
