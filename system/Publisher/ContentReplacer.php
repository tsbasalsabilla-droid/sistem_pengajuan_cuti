<?php

declare(strict_types=1);



namespace CodeIgniter\Publisher;

use CodeIgniter\Exceptions\RuntimeException;


class ContentReplacer
{
    
    public function replace(string $content, array $replaces): string
    {
        return strtr($content, $replaces);
    }

    
    private function add(string $content, string $text, string $pattern, string $replace): ?string
    {
        $return = preg_match('/' . preg_quote($text, '/') . '/u', $content);

        if ($return === false) {
            
            throw new RuntimeException('Regex error. PCRE error code: ' . preg_last_error());
        }

        if ($return === 1) {
            
            return null;
        }

        $return = preg_replace($pattern, $replace, $content);

        if ($return === null) {
            
            throw new RuntimeException('Regex error. PCRE error code: ' . preg_last_error());
        }

        return $return;
    }

    
    public function addAfter(string $content, string $line, string $after): ?string
    {
        $pattern = '/(.*)(\n[^\n]*?' . preg_quote($after, '/') . '[^\n]*?\n)/su';
        $replace = '$1$2' . $line . "\n";

        return $this->add($content, $line, $pattern, $replace);
    }

    
    public function addBefore(string $content, string $line, string $before): ?string
    {
        $pattern = '/(\n)([^\n]*?' . preg_quote($before, '/') . ')(.*)/su';
        $replace = '$1' . $line . "\n" . '$2$3';

        return $this->add($content, $line, $pattern, $replace);
    }
}
