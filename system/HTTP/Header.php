<?php

declare(strict_types=1);



namespace CodeIgniter\HTTP;

use InvalidArgumentException;
use Stringable;


class Header implements Stringable
{
    
    protected $name;

    
    protected $value;

    
    public function __construct(string $name, $value = null)
    {
        $this->setName($name);
        $this->setValue($value);
    }

    
    public function getName(): string
    {
        return $this->name;
    }

    
    public function getValue()
    {
        return $this->value;
    }

    
    public function setName(string $name)
    {
        $this->validateName($name);
        $this->name = $name;

        return $this;
    }

    
    public function setValue($value = null)
    {
        $value = is_array($value) ? $value : (string) $value;

        $this->validateValue($value);

        $this->value = $value;

        return $this;
    }

    
    public function appendValue($value = null)
    {
        if ($value === null) {
            return $this;
        }

        $this->validateValue($value);

        if (! is_array($this->value)) {
            $this->value = [$this->value];
        }

        if (! in_array($value, $this->value, true)) {
            $this->value[] = is_array($value) ? $value : (string) $value;
        }

        return $this;
    }

    
    public function prependValue($value = null)
    {
        if ($value === null) {
            return $this;
        }

        $this->validateValue($value);

        if (! is_array($this->value)) {
            $this->value = [$this->value];
        }

        array_unshift($this->value, $value);

        return $this;
    }

    
    public function getValueLine(): string
    {
        if (is_string($this->value)) {
            return $this->value;
        }
        if (! is_array($this->value)) {
            return '';
        }

        $options = [];

        foreach ($this->value as $key => $value) {
            if (is_string($key) && ! is_array($value)) {
                $options[] = $key . '=' . $value;
            } elseif (is_array($value)) {
                $key       = key($value);
                $options[] = $key . '=' . $value[$key];
            } elseif (is_numeric($key)) {
                $options[] = $value;
            }
        }

        return implode(', ', $options);
    }

    
    public function __toString(): string
    {
        return $this->name . ': ' . $this->getValueLine();
    }

    
    private function validateName(string $name): void
    {
        if (preg_match('/^[a-zA-Z0-9\'`#$%&*+.^_|~!-]+$/D', $name) !== 1) {
            throw new InvalidArgumentException('The header name is not valid as per RFC 7230.');
        }
    }

    
    private function validateValue(array|int|string $value): void
    {
        if (is_int($value)) {
            return;
        }

        if (is_array($value)) {
            foreach ($value as $key => $val) {
                $this->validateValue($key);
                $this->validateValue($val);
            }

            return;
        }

        
        
        
        if (preg_match('/^[\x20\x09\x21-\x7E\x80-\xFF]*$/D', $value) !== 1) {
            throw new InvalidArgumentException('The header value is not valid as per RFC 7230.');
        }
    }
}
