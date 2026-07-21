<?php

declare(strict_types=1);



namespace Kint;

use Kint\Value\StringValue;
use Kint\Value\TraceFrameValue;
use ReflectionNamedType;
use ReflectionType;
use UnexpectedValueException;


final class Utils
{
    public const BT_STRUCTURE = [
        'function' => 'string',
        'line' => 'integer',
        'file' => 'string',
        'class' => 'string',
        'object' => 'object',
        'type' => 'string',
        'args' => 'array',
    ];

    public const BYTE_UNITS = ['B', 'KB', 'MB', 'GB', 'TB'];

    
    public static array $char_encodings = [
        'ASCII',
        'UTF-8',
    ];

    
    public static array $legacy_encodings = [];

    
    public static array $path_aliases = [];

    
    private function __construct()
    {
    }

    
    public static function getHumanReadableBytes(int $value): array
    {
        $negative = $value < 0;
        $value = \abs($value);

        if ($value < 1024) {
            $i = 0;
            $value = \floor($value);
        } elseif ($value < 0xFFFCCCCCCCCCCCC >> 40) {
            $i = 1;
        } elseif ($value < 0xFFFCCCCCCCCCCCC >> 30) {
            $i = 2;
        } elseif ($value < 0xFFFCCCCCCCCCCCC >> 20) {
            $i = 3;
        } else {
            $i = 4;
        }

        if ($i) {
            $value = $value / \pow(1024, $i);
        }

        if ($negative) {
            $value *= -1;
        }

        return [
            'value' => \round($value, 1),
            'unit' => self::BYTE_UNITS[$i],
        ];
    }

    
    public static function isSequential(array $array): bool
    {
        return \array_keys($array) === \range(0, \count($array) - 1);
    }

    
    public static function isAssoc(array $array): bool
    {
        return (bool) \count(\array_filter(\array_keys($array), 'is_string'));
    }

    
    public static function isTrace(array $trace): bool
    {
        if (!self::isSequential($trace)) {
            return false;
        }

        $file_found = false;

        foreach ($trace as $frame) {
            if (!\is_array($frame) || !isset($frame['function'])) {
                return false;
            }

            if (isset($frame['class']) && !\class_exists($frame['class'], false)) {
                return false;
            }

            foreach ($frame as $key => $val) {
                if (!isset(self::BT_STRUCTURE[$key])) {
                    return false;
                }

                if (\gettype($val) !== self::BT_STRUCTURE[$key]) {
                    return false;
                }

                if ('file' === $key) {
                    $file_found = true;
                }
            }
        }

        return $file_found;
    }

    
    public static function traceFrameIsListed(array $frame, array $matches): bool
    {
        if (isset($frame['class'])) {
            $called = [\strtolower($frame['class']), \strtolower($frame['function'])];
        } else {
            $called = \strtolower($frame['function']);
        }

        return \in_array($called, $matches, true);
    }

    
    public static function normalizeAliases(array $aliases): array
    {
        foreach ($aliases as $index => $alias) {
            if (\is_array($alias) && 2 === \count($alias)) {
                $alias = \array_values(\array_filter($alias, 'is_string'));

                if (2 === \count($alias) && self::isValidPhpName($alias[1]) && self::isValidPhpNamespace($alias[0])) {
                    $aliases[$index] = [
                        \strtolower(\ltrim($alias[0], '\\')),
                        \strtolower($alias[1]),
                    ];
                } else {
                    unset($aliases[$index]);
                    continue;
                }
            } elseif (\is_string($alias)) {
                if (self::isValidPhpNamespace($alias)) {
                    $alias = \explode('\\', \strtolower($alias));
                    $aliases[$index] = \end($alias);
                } else {
                    unset($aliases[$index]);
                    continue;
                }
            } else {
                unset($aliases[$index]);
            }
        }

        return \array_values($aliases);
    }

    
    public static function isValidPhpName(string $name): bool
    {
        return (bool) \preg_match('/^[a-zA-Z_\\x80-\\xff][a-zA-Z0-9_\\x80-\\xff]*$/', $name);
    }

    
    public static function isValidPhpNamespace(string $ns): bool
    {
        $parts = \explode('\\', $ns);
        if ('' === \reset($parts)) {
            \array_shift($parts);
        }

        if (!\count($parts)) {
            return false;
        }

        foreach ($parts as $part) {
            if (!self::isValidPhpName($part)) {
                return false;
            }
        }

        return true;
    }

    
    public static function errorSanitizeString(string $input): string
    {
        if (KINT_PHP82 || '' === $input) {
            return $input;
        }

        return (string) \strtok($input, "\0"); 
    }

    
    public static function getTypeString(ReflectionType $type): string
    {
        
        
        
        if (!KINT_PHP80) {
            if (!$type instanceof ReflectionNamedType) {
                throw new UnexpectedValueException('ReflectionType on PHP 7 must be ReflectionNamedType');
            }

            $name = $type->getName();
            if ($type->allowsNull() && 'mixed' !== $name && false === \strpos($name, '|')) {
                $name = '?'.$name;
            }

            return $name;
        }
        

        return (string) $type;
    }

    
    public static function truncateString(string $input, int $length = PHP_INT_MAX, string $end = '...', $encoding = false): string
    {
        $endlength = self::strlen($end);

        if ($endlength >= $length) {
            $endlength = 0;
            $end = '';
        }

        if (self::strlen($input, $encoding) > $length) {
            return self::substr($input, 0, $length - $endlength, $encoding).$end;
        }

        return $input;
    }

    
    public static function detectEncoding(string $string)
    {
        if (\function_exists('mb_detect_encoding')) {
            $ret = \mb_detect_encoding($string, self::$char_encodings, true);
            if (false !== $ret) {
                return $ret;
            }
        }

        
        
        
        if (\preg_match('/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F]/', $string)) {
            return false;
        }

        if (\function_exists('iconv')) {
            foreach (self::$legacy_encodings as $encoding) {
                
                
                
                
                if (@\iconv($encoding, $encoding, $string) === $string) {
                    return $encoding;
                }
            }
        } elseif (!\function_exists('mb_detect_encoding')) { 
            
            
            
            return 'ASCII'; 
        }

        return false;
    }

    
    public static function strlen(string $string, $encoding = false): int
    {
        if (\function_exists('mb_strlen')) {
            if (false === $encoding) {
                $encoding = self::detectEncoding($string);
            }

            if (false !== $encoding && 'ASCII' !== $encoding) {
                return \mb_strlen($string, $encoding);
            }
        }

        return \strlen($string);
    }

    
    public static function substr(string $string, int $start, ?int $length = null, $encoding = false): string
    {
        if (\function_exists('mb_substr')) {
            if (false === $encoding) {
                $encoding = self::detectEncoding($string);
            }

            if (false !== $encoding && 'ASCII' !== $encoding) {
                return \mb_substr($string, $start, $length, $encoding);
            }
        }

        
        if ('' === $string) {
            return '';
        }

        return (string) \substr($string, $start, $length ?? PHP_INT_MAX);
    }

    public static function shortenPath(string $file): string
    {
        $split = \explode('/', \str_replace('\\', '/', $file));

        $longest_match = 0;
        $match = '';

        foreach (self::$path_aliases as $path => $alias) {
            $path = \explode('/', \str_replace('\\', '/', $path));

            if (\count($path) < 2) {
                continue;
            }

            if (\array_slice($split, 0, \count($path)) === $path && \count($path) > $longest_match) {
                $longest_match = \count($path);
                $match = $alias;
            }
        }

        if ($longest_match) {
            $suffix = \implode('/', \array_slice($split, $longest_match));

            if (\preg_match('%^/*$%', $suffix)) {
                return $match;
            }

            return $match.'/'.$suffix;
        }

        
        $kint = \explode('/', \str_replace('\\', '/', KINT_DIR));
        $had_real_path_part = false;

        foreach ($split as $i => $part) {
            if (!isset($kint[$i]) || $kint[$i] !== $part) {
                if (!$had_real_path_part) {
                    break;
                }

                $suffix = \implode('/', \array_slice($split, $i));

                if (\preg_match('%^/*$%', $suffix)) {
                    break;
                }

                $prefix = $i > 1 ? '.../' : '/';

                return $prefix.$suffix;
            }

            if ($i > 0 && \strlen($kint[$i])) {
                $had_real_path_part = true;
            }
        }

        return $file;
    }

    public static function composerGetExtras(string $key = 'kint'): array
    {
        if (0 === \strpos(KINT_DIR, 'phar://')) {
            
            return []; 
        }

        $extras = [];

        $folder = KINT_DIR.'/vendor';

        for ($i = 0; $i < 4; ++$i) {
            $installed = $folder.'/composer/installed.json';

            if (\file_exists($installed) && \is_readable($installed)) {
                $packages = \json_decode((string) \file_get_contents($installed), true);

                if (!\is_array($packages)) {
                    continue;
                }

                
                
                foreach ($packages['packages'] ?? $packages as $package) {
                    if (\is_array($package['extra'][$key] ?? null)) {
                        $extras = \array_replace($extras, $package['extra'][$key]);
                    }
                }

                $folder = \dirname($folder);

                if (\file_exists($folder.'/composer.json') && \is_readable($folder.'/composer.json')) {
                    $composer = \json_decode((string) \file_get_contents($folder.'/composer.json'), true);

                    if (\is_array($composer['extra'][$key] ?? null)) {
                        $extras = \array_replace($extras, $composer['extra'][$key]);
                    }
                }

                break;
            }

            $folder = \dirname($folder);
        }

        return $extras;
    }

    
    public static function composerSkipFlags(): void
    {
        if (\defined('KINT_SKIP_FACADE') && \defined('KINT_SKIP_HELPERS')) {
            return;
        }

        $extras = self::composerGetExtras();

        if (!empty($extras['disable-facade']) && !\defined('KINT_SKIP_FACADE')) {
            \define('KINT_SKIP_FACADE', true);
        }

        if (!empty($extras['disable-helpers']) && !\defined('KINT_SKIP_HELPERS')) {
            \define('KINT_SKIP_HELPERS', true);
        }
    }
}
