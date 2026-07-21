<?php

declare(strict_types=1);



namespace Kint\Parser;

use Kint\Value\AbstractValue;
use Kint\Value\Context\ContextInterface;


class ProxyPlugin implements PluginBeginInterface, PluginCompleteInterface
{
    protected array $types;
    
    protected int $triggers;
    
    protected $callback;
    private ?Parser $parser = null;

    
    public function __construct(array $types, int $triggers, $callback)
    {
        $this->types = $types;
        $this->triggers = $triggers;
        $this->callback = $callback;
    }

    public function setParser(Parser $p): void
    {
        $this->parser = $p;
    }

    public function getTypes(): array
    {
        return $this->types;
    }

    public function getTriggers(): int
    {
        return $this->triggers;
    }

    public function parseBegin(&$var, ContextInterface $c): ?AbstractValue
    {
        return \call_user_func_array($this->callback, [
            &$var,
            $c,
            Parser::TRIGGER_BEGIN,
            $this->parser,
        ]);
    }

    public function parseComplete(&$var, AbstractValue $v, int $trigger): AbstractValue
    {
        return \call_user_func_array($this->callback, [
            &$var,
            $v,
            $trigger,
            $this->parser,
        ]);
    }
}
