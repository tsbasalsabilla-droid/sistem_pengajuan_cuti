<?php

declare(strict_types=1);



use CodeIgniter\Helpers\Array\ArrayHelper;



if (! function_exists('dot_array_search')) {
    
    function dot_array_search(string $index, array $array)
    {
        return ArrayHelper::dotSearch($index, $array);
    }
}

if (! function_exists('array_deep_search')) {
    
    function array_deep_search($key, array $array)
    {
        if (isset($array[$key])) {
            return $array[$key];
        }

        foreach ($array as $value) {
            if (is_array($value) && ($result = array_deep_search($key, $value))) {
                return $result;
            }
        }

        return null;
    }
}

if (! function_exists('array_sort_by_multiple_keys')) {
    
    function array_sort_by_multiple_keys(array &$array, array $sortColumns): bool
    {
        
        if ($sortColumns === [] || $array === []) {
            return false;
        }

        
        $tempArray = [];

        foreach ($sortColumns as $key => $sortFlag) {
            
            $carry = $array;

            
            foreach (explode('.', $key) as $keySegment) {
                
                if (is_object(reset($carry))) {
                    
                    foreach ($carry as $index => $object) {
                        $carry[$index] = $object->{$keySegment};
                    }

                    continue;
                }

                
                $carry = array_column($carry, $keySegment);
            }

            
            $tempArray[] = $carry;
            $tempArray[] = $sortFlag;
        }

        
        $tempArray[] = &$array;

        
        return array_multisort(...$tempArray);
    }
}

if (! function_exists('array_flatten_with_dots')) {
    
    function array_flatten_with_dots(iterable $array, string $id = ''): array
    {
        $flattened = [];

        foreach ($array as $key => $value) {
            $newKey = $id . $key;

            if (is_array($value) && $value !== []) {
                $flattened = array_merge($flattened, array_flatten_with_dots($value, $newKey . '.'));
            } else {
                $flattened[$newKey] = $value;
            }
        }

        return $flattened;
    }
}

if (! function_exists('array_group_by')) {
    
    function array_group_by(array $array, array $indexes, bool $includeEmpty = false): array
    {
        return ArrayHelper::groupBy($array, $indexes, $includeEmpty);
    }
}
