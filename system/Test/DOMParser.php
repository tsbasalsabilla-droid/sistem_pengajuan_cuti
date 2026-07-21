<?php

declare(strict_types=1);



namespace CodeIgniter\Test;

use CodeIgniter\Exceptions\BadMethodCallException;
use CodeIgniter\Exceptions\InvalidArgumentException;
use DOMDocument;
use DOMNodeList;
use DOMXPath;


class DOMParser
{
    
    protected $dom;

    
    public function __construct()
    {
        if (! extension_loaded('DOM')) {
            throw new BadMethodCallException('DOM extension is required, but not currently loaded.'); 
        }

        $this->dom = new DOMDocument('1.0', 'utf-8');
    }

    
    public function getBody(): string
    {
        return $this->dom->saveHTML();
    }

    
    public function withString(string $content)
    {
        
        
        
        
        $content = mb_encode_numericentity($content, [0x80, 0x10FFFF, 0, 0x1FFFFF], 'UTF-8');

        
        libxml_use_internal_errors(true);

        if (! $this->dom->loadHTML($content)) {
            
            
            libxml_clear_errors();

            throw new BadMethodCallException('Invalid HTML');
            
        }

        
        $this->dom->preserveWhiteSpace = false;

        return $this;
    }

    
    public function withFile(string $path)
    {
        if (! is_file($path)) {
            throw new InvalidArgumentException(basename($path) . ' is not a valid file.');
        }

        $content = file_get_contents($path);

        return $this->withString($content);
    }

    
    public function see(?string $search = null, ?string $element = null): bool
    {
        
        if ($element === null) {
            $content = $this->dom->saveHTML($this->dom->documentElement);

            return mb_strpos($content, $search) !== false;
        }

        $result = $this->doXPath($search, $element);

        return (bool) $result->length;
    }

    
    public function dontSee(?string $search = null, ?string $element = null): bool
    {
        return ! $this->see($search, $element);
    }

    
    public function seeElement(string $element): bool
    {
        return $this->see(null, $element);
    }

    
    public function dontSeeElement(string $element): bool
    {
        return $this->dontSee(null, $element);
    }

    
    public function seeLink(string $text, ?string $details = null): bool
    {
        return $this->see($text, 'a' . $details);
    }

    
    public function seeInField(string $field, string $value): bool
    {
        $result = $this->doXPath(null, 'input', ["[@value=\"{$value}\"][@name=\"{$field}\"]"]);

        return (bool) $result->length;
    }

    
    public function seeCheckboxIsChecked(string $element): bool
    {
        $result = $this->doXPath(null, 'input' . $element, [
            '[@type="checkbox"]',
            '[@checked="checked"]',
        ]);

        return (bool) $result->length;
    }

    
    public function seeXPath(string $path): bool
    {
        $xpath = new DOMXPath($this->dom);

        return (bool) $xpath->query($path)->length;
    }

    
    public function dontSeeXPath(string $path): bool
    {
        return ! $this->seeXPath($path);
    }

    
    protected function doXPath(?string $search, string $element, array $paths = [])
    {
        
        
        $selector = $this->parseSelector($element);

        $path = '';

        
        if (isset($selector['id'])) {
            $path = ($selector['tag'] === '')
                ? "id(\"{$selector['id']}\")"
                : "//{$selector['tag']}[@id=\"{$selector['id']}\"]";
        }
        
        elseif (isset($selector['class'])) {
            $path = ($selector['tag'] === '')
                ? "//*[@class=\"{$selector['class']}\"]"
                : "//{$selector['tag']}[@class=\"{$selector['class']}\"]";
        }
        
        elseif ($selector['tag'] !== '') {
            $path = "//{$selector['tag']}";
        }

        if (isset($selector['attr'])) {
            foreach ($selector['attr'] as $key => $value) {
                $path .= "[@{$key}=\"{$value}\"]";
            }
        }

        
        
        foreach ($paths as $extra) {
            $path .= $extra;
        }

        if ($search !== null) {
            $path .= "[contains(., \"{$search}\")]";
        }

        $xpath = new DOMXPath($this->dom);

        return $xpath->query($path);
    }

    
    public function parseSelector(string $selector)
    {
        $id    = null;
        $class = null;
        $attr  = null;

        
        if (str_contains($selector, '#')) {
            [$tag, $id] = explode('#', $selector);
        }
        
        elseif (str_contains($selector, '[') && str_contains($selector, ']')) {
            $open  = strpos($selector, '[');
            $close = strpos($selector, ']');

            $tag  = substr($selector, 0, $open);
            $text = substr($selector, $open + 1, $close - 2);

            
            $text = explode(',', $text);
            $text = trim(array_shift($text));

            [$name, $value] = explode('=', $text);

            $name  = trim($name);
            $value = trim($value);
            $attr  = [$name => trim($value, '] ')];
        }
        
        elseif (str_contains($selector, '.')) {
            [$tag, $class] = explode('.', $selector);
        }
        
        else {
            $tag = $selector;
        }

        return [
            'tag'   => $tag,
            'id'    => $id,
            'class' => $class,
            'attr'  => $attr,
        ];
    }
}
