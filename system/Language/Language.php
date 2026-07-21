<?php

declare(strict_types=1);



namespace CodeIgniter\Language;

use IntlException;
use MessageFormatter;


class Language
{
    
    protected $language = [];

    
    protected $locale;

    
    protected $intlSupport = false;

    
    protected $loadedFiles = [];

    
    public function __construct(string $locale)
    {
        $this->locale = $locale;

        if (class_exists(MessageFormatter::class)) {
            $this->intlSupport = true;
        }
    }

    
    public function setLocale(?string $locale = null)
    {
        if ($locale !== null) {
            $this->locale = $locale;
        }

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    
    public function getLine(string $line, array $args = [])
    {
        
        if (! str_contains($line, '.')) {
            return $this->formatMessage($line, $args);
        }

        
        [$file, $parsedLine] = $this->parseLine($line, $this->locale);

        $output = $this->getTranslationOutput($this->locale, $file, $parsedLine);

        
        if ($output === null && str_contains($this->locale, '-')) {
            [$locale] = explode('-', $this->locale, 2);

            [$file, $parsedLine] = $this->parseLine($line, $locale);

            $output = $this->getTranslationOutput($locale, $file, $parsedLine);
        }

        
        if ($output === null) {
            [$file, $parsedLine] = $this->parseLine($line, 'en');

            $output = $this->getTranslationOutput('en', $file, $parsedLine);
        }

        
        $output ??= $line;

        return $this->formatMessage($output, $args);
    }

    
    protected function getTranslationOutput(string $locale, string $file, string $parsedLine)
    {
        $output = $this->language[$locale][$file][$parsedLine] ?? null;

        if ($output !== null) {
            return $output;
        }

        
        $current = $this->language[$locale][$file] ?? null;

        if (is_array($current)) {
            foreach (explode('.', $parsedLine) as $segment) {
                $output = $current[$segment] ?? null;

                if ($output === null) {
                    break;
                }

                if (is_array($output)) {
                    $current = $output;
                }
            }

            if ($output !== null && ! is_array($output)) {
                return $output;
            }
        }

        
        [$first, $rest] = explode('.', $parsedLine, 2) + ['', ''];

        return $this->language[$locale][$file][$first][$rest] ?? null;
    }

    
    protected function parseLine(string $line, string $locale): array
    {
        [$file, $line] = explode('.', $line, 2);

        if (! isset($this->language[$locale][$file]) || ! array_key_exists($line, $this->language[$locale][$file])) {
            $this->load($file, $locale);
        }

        return [$file, $line];
    }

    
    protected function formatMessage($message, array $args = [])
    {
        if (! $this->intlSupport || $args === []) {
            return $message;
        }

        if (is_array($message)) {
            foreach ($message as $index => $value) {
                $message[$index] = $this->formatMessage($value, $args);
            }

            return $message;
        }

        $formatted = MessageFormatter::formatMessage($this->locale, $message, $args);

        if ($formatted === false) {
            
            try {
                $formatter = new MessageFormatter($this->locale, $message);
                $formatted = $formatter->format($args);
                $fmtError  = sprintf('"%s" (%d)', $formatter->getErrorMessage(), $formatter->getErrorCode());
            } catch (IntlException $e) {
                $fmtError = sprintf('"%s" (%d)', $e->getMessage(), $e->getCode());
            }

            $argsAsString   = sprintf('"%s"', implode('", "', $args));
            $urlEncodedArgs = sprintf('"%s"', implode('", "', array_map(rawurlencode(...), $args)));

            log_message('error', sprintf(
                'Invalid message format: $message: "%s", $args: %s (urlencoded: %s), MessageFormatter Error: %s',
                $message,
                $argsAsString,
                $urlEncodedArgs,
                $fmtError,
            ));

            return $message . "\n【Warning】Also, invalid string(s) was passed to the Language class. See log file for details.";
        }

        return $formatted;
    }

    
    protected function load(string $file, string $locale, bool $return = false)
    {
        if (! array_key_exists($locale, $this->loadedFiles)) {
            $this->loadedFiles[$locale] = [];
        }

        if (in_array($file, $this->loadedFiles[$locale], true)) {
            
            return [];
        }

        if (! array_key_exists($locale, $this->language)) {
            $this->language[$locale] = [];
        }

        if (! array_key_exists($file, $this->language[$locale])) {
            $this->language[$locale][$file] = [];
        }

        $path = "Language/{$locale}/{$file}.php";

        $lang = $this->requireFile($path);

        if ($return) {
            return $lang;
        }

        $this->loadedFiles[$locale][] = $file;

        
        $this->language[$locale][$file] = $lang;

        return null;
    }

    
    protected function requireFile(string $path): array
    {
        $files   = service('locator')->search($path, 'php', false);
        $strings = [];

        foreach ($files as $file) {
            if (is_file($file)) {
                
                
                $loadedStrings = require $file;

                if (is_array($loadedStrings)) {
                    
                    $strings[] = $loadedStrings;
                }
            }
        }

        $count = count($strings);

        if ($count > 1) {
            $base = array_shift($strings);

            $strings = array_replace_recursive($base, ...$strings);
        } elseif ($count === 1) {
            $strings = $strings[0];
        }

        return $strings;
    }
}
