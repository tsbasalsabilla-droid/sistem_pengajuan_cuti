<?php

declare(strict_types=1);



namespace Kint\Value;

use DomainException;
use Kint\Value\Context\ContextInterface;


class StringValue extends AbstractValue
{
    
    protected string $value;

    
    protected $encoding;

    
    protected int $length;

    
    public function __construct(ContextInterface $context, string $value, $encoding = false)
    {
        parent::__construct($context, 'string');
        $this->value = $value;
        $this->encoding = $encoding;
        $this->length = \strlen($value);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getValueUtf8(): string
    {
        if (false === $this->encoding) {
            throw new DomainException('StringValue with no encoding can\'t be converted to UTF-8');
        }

        if ('ASCII' === $this->encoding || 'UTF-8' === $this->encoding) {
            return $this->value;
        }

        
        $encoded = \mb_convert_encoding($this->value, 'UTF-8', $this->encoding);

        return $encoded;
    }

    
    public function getLength(): int
    {
        return $this->length;
    }

    
    public function getEncoding()
    {
        return $this->encoding;
    }

    public function getDisplayType(): string
    {
        if (false === $this->encoding) {
            return 'binary '.$this->type;
        }

        if ('ASCII' === $this->encoding) {
            return $this->type;
        }

        return $this->encoding.' '.$this->type;
    }

    public function getDisplaySize(): string
    {
        return (string) $this->length;
    }

    public function getDisplayValue(): string
    {
        return '"'.$this->value.'"';
    }
}
