<?php

declare(strict_types=1);



namespace Kint\Value;

use Kint\Value\Context\ContextInterface;
use SimpleXMLElement;

class SimpleXMLElementValue extends InstanceValue
{
    
    protected ?string $text_content;

    
    public function __construct(
        ContextInterface $context,
        SimpleXMLElement $element,
        array $children,
        ?string $text_content
    ) {
        parent::__construct($context, \get_class($element), \spl_object_hash($element), \spl_object_id($element));

        $this->children = $children;
        $this->text_content = $text_content;
    }

    public function getHint(): string
    {
        return parent::getHint() ?? 'simplexml_element';
    }

    public function getDisplaySize(): ?string
    {
        if ((bool) $this->children) {
            return (string) \count($this->children);
        }

        if (null !== $this->text_content) {
            return (string) \strlen($this->text_content);
        }

        return null;
    }

    public function getDisplayValue(): ?string
    {
        if ((bool) $this->children) {
            return parent::getDisplayValue();
        }

        if (null !== $this->text_content) {
            return '"'.$this->text_content.'"';
        }

        return null;
    }
}
