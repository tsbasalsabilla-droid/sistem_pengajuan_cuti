<?php

declare(strict_types=1);



namespace Kint\Parser;

use Kint\Value\AbstractValue;
use Kint\Value\Context\ArrayContext;
use Kint\Value\ResourceValue;
use Kint\Value\StreamValue;
use TypeError;

class StreamPlugin extends AbstractPlugin implements PluginCompleteInterface
{
    public function getTypes(): array
    {
        return ['resource'];
    }

    public function getTriggers(): int
    {
        return Parser::TRIGGER_SUCCESS;
    }

    public function parseComplete(&$var, AbstractValue $v, int $trigger): AbstractValue
    {
        if (!$v instanceof ResourceValue) {
            return $v;
        }

        
        if (!\is_resource($var)) {
            return $v;
        }

        try {
            $meta = \stream_get_meta_data($var);
        } catch (TypeError $e) {
            return $v;
        }

        $c = $v->getContext();

        $parser = $this->getParser();
        $parsed_meta = [];
        foreach ($meta as $key => $val) {
            $base = new ArrayContext($key);
            $base->depth = $c->getDepth() + 1;

            if (null !== ($ap = $c->getAccessPath())) {
                $base->access_path = 'stream_get_meta_data('.$ap.')['.\var_export($key, true).']';
            }

            $val = $parser->parse($val, $base);
            $val->flags |= AbstractValue::FLAG_GENERATED;
            $parsed_meta[] = $val;
        }

        $stream = new StreamValue($c, $parsed_meta, $meta['uri'] ?? null);
        $stream->flags = $v->flags;
        $stream->appendRepresentations($v->getRepresentations());

        return $stream;
    }
}
