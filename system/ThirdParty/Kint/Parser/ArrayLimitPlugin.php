<?php

declare(strict_types=1);



namespace Kint\Parser;

use InvalidArgumentException;
use Kint\Utils;
use Kint\Value\AbstractValue;
use Kint\Value\ArrayValue;
use Kint\Value\Context\BaseContext;
use Kint\Value\Context\ContextInterface;
use Kint\Value\Representation\ContainerRepresentation;
use Kint\Value\Representation\ProfileRepresentation;
use Kint\Value\Representation\ValueRepresentation;

class ArrayLimitPlugin extends AbstractPlugin implements PluginBeginInterface
{
    
    public static int $trigger = 1000;

    
    public static int $limit = 50;

    
    public static bool $numeric_only = true;

    public function __construct(Parser $p)
    {
        if (self::$limit < 0) {
            throw new InvalidArgumentException('ArrayLimitPlugin::$limit can not be lower than 0');
        }

        if (self::$limit >= self::$trigger) {
            throw new InvalidArgumentException('ArrayLimitPlugin::$limit can not be lower than ArrayLimitPlugin::$trigger');
        }

        parent::__construct($p);
    }

    public function getTypes(): array
    {
        return ['array'];
    }

    public function getTriggers(): int
    {
        return Parser::TRIGGER_BEGIN;
    }

    public function parseBegin(&$var, ContextInterface $c): ?AbstractValue
    {
        $parser = $this->getParser();
        $pdepth = $parser->getDepthLimit();

        if (!$pdepth) {
            return null;
        }

        $cdepth = $c->getDepth();

        if ($cdepth >= $pdepth - 1) {
            return null;
        }

        if (\count($var) < self::$trigger) {
            return null;
        }

        if (self::$numeric_only && Utils::isAssoc($var)) {
            return null;
        }

        $slice = \array_slice($var, 0, self::$limit, true);
        $array = $parser->parse($slice, $c);

        if (!$array instanceof ArrayValue) {
            return null;
        }

        $base = new BaseContext($c->getName());
        $base->depth = $pdepth - 1;
        $base->access_path = $c->getAccessPath();

        $slice = \array_slice($var, self::$limit, null, true);
        $slice = $parser->parse($slice, $base);

        if (!$slice instanceof ArrayValue) {
            return null;
        }

        foreach ($slice->getContents() as $child) {
            $this->replaceDepthLimit($child, $cdepth + 1);
        }

        $out = new ArrayValue($c, \count($var), \array_merge($array->getContents(), $slice->getContents()));
        $out->flags = $array->flags;

        
        $arrayp = $array->getRepresentation('profiling');
        $slicep = $slice->getRepresentation('profiling');
        if ($arrayp instanceof ProfileRepresentation && $slicep instanceof ProfileRepresentation) {
            $out->addRepresentation(new ProfileRepresentation($arrayp->complexity + $slicep->complexity));
        }

        
        if ($contents = $out->getContents()) {
            $out->addRepresentation(new ContainerRepresentation('Contents', $contents, null, true));
        }

        return $out;
    }

    protected function replaceDepthLimit(AbstractValue $v, int $depth): void
    {
        $c = $v->getContext();

        if ($c instanceof BaseContext) {
            $c->depth = $depth;
        }

        $pdepth = $this->getParser()->getDepthLimit();

        if (($v->flags & AbstractValue::FLAG_DEPTH_LIMIT) && $pdepth && $depth < $pdepth) {
            $v->flags = $v->flags & ~AbstractValue::FLAG_DEPTH_LIMIT | AbstractValue::FLAG_ARRAY_LIMIT;
        }

        $reps = $v->getRepresentations();

        foreach ($reps as $rep) {
            if ($rep instanceof ContainerRepresentation) {
                foreach ($rep->getContents() as $child) {
                    $this->replaceDepthLimit($child, $depth + 1);
                }
            } elseif ($rep instanceof ValueRepresentation) {
                $this->replaceDepthLimit($rep->getValue(), $depth + 1);
            }
        }
    }
}
