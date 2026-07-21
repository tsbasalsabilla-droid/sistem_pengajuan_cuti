<?php

declare(strict_types=1);



namespace Kint\Value;

use Closure;
use Kint\Utils;
use Kint\Value\Context\BaseContext;
use Kint\Value\Context\ContextInterface;
use ReflectionFunction;

class ClosureValue extends InstanceValue
{
    use ParameterHoldingTrait;

    
    protected ?string $filename;
    
    protected ?int $startline;

    public function __construct(ContextInterface $context, Closure $cl)
    {
        parent::__construct($context, \get_class($cl), \spl_object_hash($cl), \spl_object_id($cl));

        
        $closure = new ReflectionFunction($cl);

        if ($closure->isUserDefined()) {
            $this->filename = $closure->getFileName();
            $this->startline = $closure->getStartLine();
        } else {
            $this->filename = null;
            $this->startline = null;
        }

        $parameters = [];
        foreach ($closure->getParameters() as $param) {
            $parameters[] = new ParameterBag($param);
        }
        $this->parameters = $parameters;

        if (!$this->context instanceof BaseContext) {
            return;
        }

        if (0 !== $this->context->getDepth()) {
            return;
        }

        $ap = $this->context->getAccessPath();

        if (null === $ap) {
            return;
        }

        if (\preg_match('/^\\((function|fn)\\s*\\(/i', $ap, $match)) {
            $this->context->name = \strtolower($match[1]);
        }
    }

    
    public function getFileName(): ?string
    {
        return $this->filename;
    }

    
    public function getStartLine(): ?int
    {
        return $this->startline;
    }

    public function getDisplaySize(): ?string
    {
        return null;
    }

    public function getDisplayName(): string
    {
        return $this->context->getName().'('.$this->getParams().')';
    }

    public function getDisplayValue(): ?string
    {
        if (null !== $this->filename && null !== $this->startline) {
            return Utils::shortenPath($this->filename).':'.$this->startline;
        }

        return parent::getDisplayValue();
    }
}
