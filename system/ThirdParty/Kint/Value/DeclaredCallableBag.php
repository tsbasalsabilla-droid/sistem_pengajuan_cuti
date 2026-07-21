<?php

declare(strict_types=1);



namespace Kint\Value;

use Kint\Utils;
use ReflectionFunctionAbstract;


final class DeclaredCallableBag
{
    use ParameterHoldingTrait;

    
    public bool $internal;
    
    public ?string $filename;
    
    public ?int $startline;
    
    public ?int $endline;
    
    public ?string $docstring;
    
    public bool $return_reference;
    
    public ?string $returntype = null;

    public function __construct(ReflectionFunctionAbstract $callable)
    {
        $this->internal = $callable->isInternal();
        $t = $callable->getFileName();
        $this->filename = false === $t ? null : $t;
        $t = $callable->getStartLine();
        $this->startline = false === $t ? null : $t;
        $t = $callable->getEndLine();
        $this->endline = false === $t ? null : $t;
        $t = $callable->getDocComment();
        $this->docstring = false === $t ? null : $t;
        $this->return_reference = $callable->returnsReference();

        $rt = $callable->getReturnType();
        if ($rt) {
            $this->returntype = Utils::getTypeString($rt);
        }

        $parameters = [];
        foreach ($callable->getParameters() as $param) {
            $parameters[] = new ParameterBag($param);
        }
        $this->parameters = $parameters;
    }
}
