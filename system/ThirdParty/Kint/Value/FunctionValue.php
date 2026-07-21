<?php

declare(strict_types=1);



namespace Kint\Value;

use Kint\Value\Context\ContextInterface;
use Kint\Value\Representation\CallableDefinitionRepresentation;

class FunctionValue extends AbstractValue
{
    
    protected DeclaredCallableBag $callable_bag;
    
    protected ?CallableDefinitionRepresentation $definition_rep;

    public function __construct(ContextInterface $c, DeclaredCallableBag $bag)
    {
        parent::__construct($c, 'function');

        $this->callable_bag = $bag;

        if ($this->callable_bag->internal) {
            $this->definition_rep = null;

            return;
        }

        
        $this->definition_rep = new CallableDefinitionRepresentation(
            $this->callable_bag->filename,
            $this->callable_bag->startline,
            $this->callable_bag->docstring
        );
        $this->addRepresentation($this->definition_rep);
    }

    public function getCallableBag(): DeclaredCallableBag
    {
        return $this->callable_bag;
    }

    
    public function getDefinitionRepresentation(): ?CallableDefinitionRepresentation
    {
        return $this->definition_rep;
    }

    public function getDisplayName(): string
    {
        return $this->context->getName().'('.$this->callable_bag->getParams().')';
    }

    public function getDisplayValue(): ?string
    {
        if ($this->definition_rep instanceof CallableDefinitionRepresentation) {
            return $this->definition_rep->getDocstringFirstLine();
        }

        return parent::getDisplayValue();
    }

    public function getPhpDocUrl(): ?string
    {
        if (!$this->callable_bag->internal) {
            return null;
        }

        return 'https://www.php.net/function.'.\str_replace('_', '-', \strtolower((string) $this->context->getName()));
    }
}
