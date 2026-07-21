<?php

declare(strict_types=1);



namespace Kint\Parser;

abstract class AbstractPlugin implements ConstructablePluginInterface
{
    private Parser $parser;

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    public function setParser(Parser $p): void
    {
        $this->parser = $p;
    }

    protected function getParser(): Parser
    {
        return $this->parser;
    }
}
