<?php

declare(strict_types=1);



namespace Kint\Parser;

use Kint\Value\AbstractValue;
use Kint\Value\Context\BaseContext;
use Kint\Value\InstanceValue;
use ReflectionClass;

class ClassStringsPlugin extends AbstractPlugin implements PluginCompleteInterface
{
    public static array $blacklist = [];

    protected ClassMethodsPlugin $methods_plugin;
    protected ClassStaticsPlugin $statics_plugin;

    public function __construct(Parser $parser)
    {
        parent::__construct($parser);

        $this->methods_plugin = new ClassMethodsPlugin($parser);
        $this->statics_plugin = new ClassStaticsPlugin($parser);
    }

    public function setParser(Parser $p): void
    {
        parent::setParser($p);

        $this->methods_plugin->setParser($p);
        $this->statics_plugin->setParser($p);
    }

    public function getTypes(): array
    {
        return ['string'];
    }

    public function getTriggers(): int
    {
        return Parser::TRIGGER_SUCCESS;
    }

    public function parseComplete(&$var, AbstractValue $v, int $trigger): AbstractValue
    {
        $c = $v->getContext();

        if ($c->getDepth() > 0) {
            return $v;
        }

        if (!\class_exists($var, true)) {
            return $v;
        }

        if (\in_array($var, self::$blacklist, true)) {
            return $v;
        }

        $r = new ReflectionClass($var);

        $fakeC = new BaseContext($c->getName());
        $fakeC->access_path = null;
        $fakeV = new InstanceValue($fakeC, $r->getName(), 'badhash', -1);
        $fakeVar = null;

        $fakeV = $this->methods_plugin->parseComplete($fakeVar, $fakeV, Parser::TRIGGER_SUCCESS);
        $fakeV = $this->statics_plugin->parseComplete($fakeVar, $fakeV, Parser::TRIGGER_SUCCESS);

        foreach (['methods', 'static_methods', 'statics', 'constants'] as $rep) {
            if ($rep = $fakeV->getRepresentation($rep)) {
                $v->addRepresentation($rep);
            }
        }

        return $v;
    }
}
