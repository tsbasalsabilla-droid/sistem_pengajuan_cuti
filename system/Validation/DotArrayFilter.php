<?php

declare(strict_types=1);



namespace CodeIgniter\Validation;


final class DotArrayFilter
{
    
    public static function run(array $indexes, array $array): array
    {
        $result = [];

        foreach ($indexes as $index) {
            $segments = preg_split('/(?<!\\\\)\./', $index, -1, PREG_SPLIT_NO_EMPTY);
            $segments = array_map(static fn ($key): string => str_replace('\.', '.', $key), $segments);

            $filteredArray = self::filter($segments, $array);

            if ($filteredArray !== []) {
                $result = array_replace_recursive($result, $filteredArray);
            }
        }

        return $result;
    }

    
    private static function filter(array $indexes, array $array): array
    {
        
        if ($indexes === []) {
            return [];
        }

        
        $currentIndex = array_shift($indexes);

        
        if (! isset($array[$currentIndex]) && $currentIndex !== '*') {
            return [];
        }

        
        if ($currentIndex === '*') {
            $result = [];

            
            foreach ($array as $key => $value) {
                if ($indexes === []) {
                    
                    $result[$key] = $value;
                } elseif (is_array($value)) {
                    
                    $filtered = self::filter($indexes, $value);
                    if ($filtered !== []) {
                        $result[$key] = $filtered;
                    }
                }
            }

            return $result;
        }

        
        if ($indexes === []) {
            return [$currentIndex => $array[$currentIndex] ?? []];
        }

        
        if (is_array($array[$currentIndex])) {
            $filtered = self::filter($indexes, $array[$currentIndex]);

            if ($filtered !== []) {
                return [$currentIndex => $filtered];
            }
        }

        return [];
    }
}
