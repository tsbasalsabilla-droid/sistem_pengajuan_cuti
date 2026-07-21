<?php

declare(strict_types=1);



namespace CodeIgniter\Helpers\Array;

use CodeIgniter\Exceptions\InvalidArgumentException;


final class ArrayHelper
{
    
    public static function dotSearch(string $index, array $array)
    {
        return self::arraySearchDot(self::convertToArray($index), $array);
    }

    
    private static function convertToArray(string $index): array
    {
        
        $segments = preg_split(
            '/(?<!\\\\)\./',
            rtrim($index, '* '),
            0,
            PREG_SPLIT_NO_EMPTY,
        );

        return array_map(
            static fn ($key): string => str_replace('\.', '.', $key),
            $segments,
        );
    }

    
    private static function arraySearchDot(array $indexes, array $array)
    {
        
        if ($indexes === []) {
            return null;
        }

        
        $currentIndex = array_shift($indexes);

        if (! isset($array[$currentIndex]) && $currentIndex !== '*') {
            return null;
        }

        
        if ($currentIndex === '*') {
            $answer = [];

            foreach ($array as $value) {
                if (! is_array($value)) {
                    return null;
                }

                $answer[] = self::arraySearchDot($indexes, $value);
            }

            $answer = array_filter($answer, static fn ($value): bool => $value !== null);

            if ($answer !== []) {
                
                return count($answer) === 1 ? current($answer) : $answer;
            }

            return null;
        }

        
        
        if ($indexes === []) {
            return $array[$currentIndex];
        }

        
        if (is_array($array[$currentIndex]) && $array[$currentIndex] !== []) {
            return self::arraySearchDot($indexes, $array[$currentIndex]);
        }

        
        return null;
    }

    
    public static function dotKeyExists(string $index, array $array): bool
    {
        if (str_ends_with($index, '*') || str_contains($index, '*.*')) {
            throw new InvalidArgumentException(
                'You must set key right after "*". Invalid index: "' . $index . '"',
            );
        }

        $indexes = self::convertToArray($index);

        
        if ($indexes === []) {
            return false;
        }

        $currentArray = $array;

        
        while ($currentIndex = array_shift($indexes)) {
            if ($currentIndex === '*') {
                $currentIndex = array_shift($indexes);

                foreach ($currentArray as $item) {
                    if (! array_key_exists($currentIndex, $item)) {
                        return false;
                    }
                }

                
                if ($indexes === []) {
                    return true;
                }

                $currentArray = self::dotSearch('*.' . $currentIndex, $currentArray);

                continue;
            }

            if (! array_key_exists($currentIndex, $currentArray)) {
                return false;
            }

            $currentArray = $currentArray[$currentIndex];
        }

        return true;
    }

    
    public static function groupBy(array $array, array $indexes, bool $includeEmpty = false): array
    {
        if ($indexes === []) {
            return $array;
        }

        $result = [];

        foreach ($array as $row) {
            $result = self::arrayAttachIndexedValue($result, $row, $indexes, $includeEmpty);
        }

        return $result;
    }

    
    private static function arrayAttachIndexedValue(
        array $result,
        array $row,
        array $indexes,
        bool $includeEmpty,
    ): array {
        if (($index = array_shift($indexes)) === null) {
            $result[] = $row;

            return $result;
        }

        $value = dot_array_search($index, $row);

        if (! is_scalar($value)) {
            $value = '';
        }

        if (is_bool($value)) {
            $value = (int) $value;
        }

        if (! $includeEmpty && $value === '') {
            return $result;
        }

        if (! array_key_exists($value, $result)) {
            $result[$value] = [];
        }

        $result[$value] = self::arrayAttachIndexedValue($result[$value], $row, $indexes, $includeEmpty);

        return $result;
    }

    
    public static function recursiveDiff(array $original, array $compareWith): array
    {
        $difference = [];

        if ($original === []) {
            return [];
        }

        if ($compareWith === []) {
            return $original;
        }

        foreach ($original as $originalKey => $originalValue) {
            if ($originalValue === []) {
                continue;
            }

            if (is_array($originalValue)) {
                $diffArrays = [];

                if (isset($compareWith[$originalKey]) && is_array($compareWith[$originalKey])) {
                    $diffArrays = self::recursiveDiff($originalValue, $compareWith[$originalKey]);
                } else {
                    $difference[$originalKey] = $originalValue;
                }

                if ($diffArrays !== []) {
                    $difference[$originalKey] = $diffArrays;
                }
            } elseif (is_string($originalValue) && ! array_key_exists($originalKey, $compareWith)) {
                $difference[$originalKey] = $originalValue;
            }
        }

        return $difference;
    }

    
    public static function recursiveCount(array $array, int $counter = 0): int
    {
        foreach ($array as $value) {
            if (is_array($value)) {
                $counter = self::recursiveCount($value, $counter);
            }

            $counter++;
        }

        return $counter;
    }

    
    public static function sortValuesByNatural(array &$array, $sortByIndex = null): bool
    {
        return usort($array, static function ($currentValue, $nextValue) use ($sortByIndex): int {
            if ($sortByIndex !== null) {
                return strnatcmp((string) $currentValue[$sortByIndex], (string) $nextValue[$sortByIndex]);
            }

            return strnatcmp((string) $currentValue, (string) $nextValue);
        });
    }
}
