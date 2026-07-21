<?php

declare(strict_types=1);



namespace Kint\Value;

use Kint\Value\Context\ContextInterface;
use Kint\Value\Representation\RepresentationInterface;
use OutOfRangeException;


abstract class AbstractValue
{
    public const FLAG_NONE = 0;
    public const FLAG_GENERATED = 1 << 0;
    public const FLAG_BLACKLIST = 1 << 1;
    public const FLAG_RECURSION = 1 << 2;
    public const FLAG_DEPTH_LIMIT = 1 << 3;
    public const FLAG_ARRAY_LIMIT = 1 << 4;

    
    public int $flags = self::FLAG_NONE;

    
    protected ContextInterface $context;
    
    protected string $type;

    
    protected array $representations = [];

    public function __construct(ContextInterface $context, string $type)
    {
        $this->context = $context;
        $this->type = $type;
    }

    public function __clone()
    {
        $this->context = clone $this->context;
    }

    public function getContext(): ContextInterface
    {
        return $this->context;
    }

    public function getHint(): ?string
    {
        if (self::FLAG_NONE === $this->flags) {
            return null;
        }
        if ($this->flags & self::FLAG_BLACKLIST) {
            return 'blacklist';
        }
        if ($this->flags & self::FLAG_RECURSION) {
            return 'recursion';
        }
        if ($this->flags & self::FLAG_DEPTH_LIMIT) {
            return 'depth_limit';
        }
        if ($this->flags & self::FLAG_ARRAY_LIMIT) {
            return 'array_limit';
        }

        return null;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function addRepresentation(RepresentationInterface $rep, ?int $pos = null): void
    {
        if (isset($this->representations[$rep->getName()])) {
            throw new OutOfRangeException('Representation already exists');
        }

        if (null === $pos) {
            $this->representations[$rep->getName()] = $rep;
        } else {
            $this->representations = \array_merge(
                \array_slice($this->representations, 0, $pos),
                [$rep->getName() => $rep],
                \array_slice($this->representations, $pos)
            );
        }
    }

    public function replaceRepresentation(RepresentationInterface $rep, ?int $pos = null): void
    {
        if (null === $pos) {
            $this->representations[$rep->getName()] = $rep;
        } else {
            $this->removeRepresentation($rep);
            $this->addRepresentation($rep, $pos);
        }
    }

    
    public function removeRepresentation($rep): void
    {
        if ($rep instanceof RepresentationInterface) {
            unset($this->representations[$rep->getName()]);
        } else { 
            unset($this->representations[$rep]);
        }
    }

    public function getRepresentation(string $name): ?RepresentationInterface
    {
        return $this->representations[$name] ?? null;
    }

    
    public function getRepresentations(): array
    {
        return $this->representations;
    }

    
    public function appendRepresentations(array $reps): void
    {
        foreach ($reps as $rep) {
            $this->addRepresentation($rep);
        }
    }

    
    public function clearRepresentations(): void
    {
        $this->representations = [];
    }

    public function getDisplayType(): string
    {
        return $this->type;
    }

    public function getDisplayName(): string
    {
        return (string) $this->context->getName();
    }

    public function getDisplaySize(): ?string
    {
        return null;
    }

    public function getDisplayValue(): ?string
    {
        return null;
    }

    
    public function getDisplayChildren(): array
    {
        return [];
    }
}
