<?php

declare(strict_types=1);



namespace Kint\Value;

use InvalidArgumentException;
use Kint\Value\Context\BaseContext;
use Kint\Value\Context\MethodContext;
use ReflectionFunction;
use ReflectionMethod;


class TraceFrameValue extends ArrayValue
{
    
    protected ?string $file;

    
    protected ?int $line;

    
    protected $callable;

    
    protected array $args;

    
    protected ?InstanceValue $object;

    
    public function __construct(ArrayValue $old, $raw_frame)
    {
        parent::__construct($old->getContext(), $old->getSize(), $old->getContents());

        $this->file = $raw_frame['file'] ?? null;
        $this->line = $raw_frame['line'] ?? null;

        if (isset($raw_frame['class']) && \method_exists($raw_frame['class'], $raw_frame['function'])) {
            $func = new ReflectionMethod($raw_frame['class'], $raw_frame['function']);
            $this->callable = new MethodValue(
                new MethodContext($func),
                new DeclaredCallableBag($func)
            );
        } elseif (!isset($raw_frame['class']) && \function_exists($raw_frame['function'])) {
            $func = new ReflectionFunction($raw_frame['function']);
            $this->callable = new FunctionValue(
                new BaseContext($raw_frame['function']),
                new DeclaredCallableBag($func)
            );
        } else {
            
            $this->callable = null;
        }

        foreach ($this->contents as $frame_prop) {
            $c = $frame_prop->getContext();

            if ('object' === $c->getName()) {
                if (!$frame_prop instanceof InstanceValue) {
                    throw new InvalidArgumentException('object key of TraceFrameValue must be parsed to InstanceValue');
                }

                $this->object = $frame_prop;
            }

            if ('args' === $c->getName()) {
                if (!$frame_prop instanceof ArrayValue) {
                    throw new InvalidArgumentException('args key of TraceFrameValue must be parsed to ArrayValue');
                }

                $args = \array_values($frame_prop->getContents());

                if ($this->callable) {
                    foreach ($this->callable->getCallableBag()->parameters as $param) {
                        if (!isset($args[$param->position])) {
                            break; 
                        }

                        $arg = $args[$param->position];

                        if ($arg->getContext() instanceof BaseContext) {
                            $arg = clone $arg;
                            $c = $arg->getContext();

                            if (!$c instanceof BaseContext) {
                                throw new InvalidArgumentException('TraceFrameValue expects arg contexts to be instanceof BaseContext');
                            }

                            $c->name = '$'.$param->name;

                            $args[$param->position] = $arg;
                        }
                    }
                }

                $this->args = $args;
            }
        }

        
        $this->args ??= [];
        $this->object ??= null;
    }

    public function getHint(): string
    {
        return parent::getHint() ?? 'trace_frame';
    }

    public function getFile(): ?string
    {
        return $this->file;
    }

    public function getLine(): ?int
    {
        return $this->line;
    }

    
    public function getCallable()
    {
        return $this->callable;
    }

    
    public function getArgs(): array
    {
        return $this->args;
    }

    public function getObject(): ?InstanceValue
    {
        return $this->object;
    }
}
