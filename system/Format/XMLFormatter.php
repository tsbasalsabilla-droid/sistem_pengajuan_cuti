<?php

declare(strict_types=1);



namespace CodeIgniter\Format;

use CodeIgniter\Format\Exceptions\FormatException;
use Config\Format;
use SimpleXMLElement;


class XMLFormatter implements FormatterInterface
{
    
    public function format($data)
    {
        $config = new Format();

        
        
        if (! extension_loaded('simplexml')) {
            throw FormatException::forMissingExtension(); 
        }

        $options = $config->formatterOptions['application/xml'] ?? 0;
        $output  = new SimpleXMLElement('<?xml version="1.0"?><response></response>', $options);

        $this->arrayToXML((array) $data, $output);

        return $output->asXML();
    }

    
    protected function arrayToXML(array $data, &$output)
    {
        foreach ($data as $key => $value) {
            $key = $this->normalizeXMLTag($key);

            if (is_array($value)) {
                $subnode = $output->addChild("{$key}");
                $this->arrayToXML($value, $subnode);
            } else {
                $output->addChild("{$key}", htmlspecialchars("{$value}"));
            }
        }
    }

    
    protected function normalizeXMLTag($key)
    {
        $startChar = 'A-Z_a-z' .
            '\\x{C0}-\\x{D6}\\x{D8}-\\x{F6}\\x{F8}-\\x{2FF}\\x{370}-\\x{37D}' .
            '\\x{37F}-\\x{1FFF}\\x{200C}-\\x{200D}\\x{2070}-\\x{218F}' .
            '\\x{2C00}-\\x{2FEF}\\x{3001}-\\x{D7FF}\\x{F900}-\\x{FDCF}' .
            '\\x{FDF0}-\\x{FFFD}\\x{10000}-\\x{EFFFF}';
        $validName = $startChar . '\\.\\d\\x{B7}\\x{300}-\\x{36F}\\x{203F}-\\x{2040}';

        $key = (string) $key;

        $key = trim($key);
        $key = preg_replace("/[^{$validName}-]+/u", '', $key);
        $key = preg_replace("/^[^{$startChar}]+/u", 'item$0', $key);

        return preg_replace('/^(xml).*/iu', 'item$0', $key); 
    }
}
