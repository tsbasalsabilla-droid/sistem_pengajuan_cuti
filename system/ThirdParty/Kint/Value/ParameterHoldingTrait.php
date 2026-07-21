<?php

declare(strict_types=1);



namespace Kint\Value;

trait ParameterHoldingTrait
{
    
    public array $parameters = [];

    public function getParams(): string
    {
        $out = [];

        foreach ($this->parameters as $p) {
            $out[] = (string) $p;
        }

        return \implode(', ', $out);
    }
}
