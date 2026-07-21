<?php

declare(strict_types=1);



namespace Kint\Parser;

use Kint\Value\AbstractValue;
use Kint\Value\Context\BaseContext;
use Kint\Value\Context\ContextInterface;
use Kint\Value\FixedWidthValue;
use Kint\Value\InstanceValue;
use Kint\Value\Representation\ContainerRepresentation;
use Kint\Value\Representation\ProfileRepresentation;


class ProfilePlugin extends AbstractPlugin implements PluginBeginInterface, PluginCompleteInterface
{
    protected array $instance_counts = [];
    protected array $instance_complexity = [];
    protected array $instance_count_stack = [];
    protected array $class_complexity = [];
    protected array $class_count_stack = [];

    public function getTypes(): array
    {
        return ['string', 'object', 'array', 'integer', 'double', 'resource'];
    }

    public function getTriggers(): int
    {
        return Parser::TRIGGER_BEGIN | Parser::TRIGGER_COMPLETE;
    }

    public function parseBegin(&$var, ContextInterface $c): ?AbstractValue
    {
        if (0 === $c->getDepth()) {
            $this->instance_counts = [];
            $this->instance_complexity = [];
            $this->instance_count_stack = [];
            $this->class_complexity = [];
            $this->class_count_stack = [];
        }

        if (\is_object($var)) {
            $hash = \spl_object_hash($var);
            $this->instance_counts[$hash] ??= 0;
            $this->instance_complexity[$hash] ??= 0;
            $this->instance_count_stack[$hash] ??= 0;

            if (0 === $this->instance_count_stack[$hash]) {
                
                foreach (\class_parents($var) as $class) {
                    $this->class_count_stack[$class] ??= 0;
                    ++$this->class_count_stack[$class];
                }

                
                foreach (\class_implements($var) as $iface) {
                    $this->class_count_stack[$iface] ??= 0;
                    ++$this->class_count_stack[$iface];
                }
            }

            ++$this->instance_count_stack[$hash];
        }

        return null;
    }

    public function parseComplete(&$var, AbstractValue $v, int $trigger): AbstractValue
    {
        if ($v instanceof InstanceValue) {
            --$this->instance_count_stack[$v->getSplObjectHash()];

            if (0 === $this->instance_count_stack[$v->getSplObjectHash()]) {
                
                foreach (\class_parents($var) as $class) {
                    --$this->class_count_stack[$class];
                }

                
                foreach (\class_implements($var) as $iface) {
                    --$this->class_count_stack[$iface];
                }
            }
        }

        
        if (~$trigger & Parser::TRIGGER_SUCCESS) {
            return $v;
        }

        $sub_complexity = 1;

        foreach ($v->getRepresentations() as $rep) {
            if ($rep instanceof ContainerRepresentation) {
                foreach ($rep->getContents() as $value) {
                    $profile = $value->getRepresentation('profiling');
                    $sub_complexity += $profile instanceof ProfileRepresentation ? $profile->complexity : 1;
                }
            } else {
                ++$sub_complexity;
            }
        }

        if ($v instanceof InstanceValue) {
            ++$this->instance_counts[$v->getSplObjectHash()];
            if (0 === $this->instance_count_stack[$v->getSplObjectHash()]) {
                $this->instance_complexity[$v->getSplObjectHash()] += $sub_complexity;

                $this->class_complexity[$v->getClassName()] ??= 0;
                $this->class_complexity[$v->getClassName()] += $sub_complexity;

                
                foreach (\class_parents($var) as $class) {
                    $this->class_complexity[$class] ??= 0;
                    if (0 === $this->class_count_stack[$class]) {
                        $this->class_complexity[$class] += $sub_complexity;
                    }
                }

                
                foreach (\class_implements($var) as $iface) {
                    $this->class_complexity[$iface] ??= 0;
                    if (0 === $this->class_count_stack[$iface]) {
                        $this->class_complexity[$iface] += $sub_complexity;
                    }
                }
            }
        }

        if (0 === $v->getContext()->getDepth()) {
            $contents = [];

            \arsort($this->class_complexity);

            foreach ($this->class_complexity as $name => $complexity) {
                $contents[] = new FixedWidthValue(new BaseContext($name), $complexity);
            }

            if ($contents) {
                $v->addRepresentation(new ContainerRepresentation('Class complexity', $contents), 0);
            }
        }

        $rep = new ProfileRepresentation($sub_complexity);
        
        if ($v instanceof InstanceValue) {
            $rep->instance_counts = &$this->instance_counts[$v->getSplObjectHash()];
            $rep->instance_complexity = &$this->instance_complexity[$v->getSplObjectHash()];
        }

        $v->addRepresentation($rep, 0);

        return $v;
    }
}
