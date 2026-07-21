<?php

declare(strict_types=1);



namespace Kint\Value;

use Kint\Value\Context\ClassDeclaredContext;
use Kint\Value\Context\MethodContext;
use Kint\Value\Representation\CallableDefinitionRepresentation;

class MethodValue extends AbstractValue
{
    
    protected DeclaredCallableBag $callable_bag;
    
    protected ?CallableDefinitionRepresentation $definition_rep;

    public function __construct(MethodContext $c, DeclaredCallableBag $bag)
    {
        parent::__construct($c, 'method');

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

    public function getHint(): string
    {
        return parent::getHint() ?? 'callable';
    }

    public function getContext(): MethodContext
    {
        
        return $this->context;
    }

    public function getCallableBag(): DeclaredCallableBag
    {
        return $this->callable_bag;
    }

    
    public function getDefinitionRepresentation(): ?CallableDefinitionRepresentation
    {
        return $this->definition_rep;
    }

    public function getFullyQualifiedDisplayName(): string
    {
        $c = $this->getContext();

        return $c->owner_class.'::'.$c->getName().'('.$this->callable_bag->getParams().')';
    }

    public function getDisplayName(): string
    {
        $c = $this->getContext();

        if ($c->static || (ClassDeclaredContext::ACCESS_PRIVATE === $c->access && $c->inherited)) {
            return $this->getFullyQualifiedDisplayName();
        }

        return $c->getName().'('.$this->callable_bag->getParams().')';
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

        $c = $this->getContext();

        $class = \str_replace('\\', '-', \strtolower($c->owner_class));
        $funcname = \str_replace('_', '-', \strtolower($c->getName()));

        if (0 === \strpos($funcname, '--') && 0 !== \strpos($funcname, '-', 2)) {
            $funcname = (string) \substr($funcname, 2);
        }

        return 'https://www.php.net/'.$class.'.'.$funcname;
    }
}
