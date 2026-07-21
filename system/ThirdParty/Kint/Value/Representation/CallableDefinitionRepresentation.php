<?php

declare(strict_types=1);



namespace Kint\Value\Representation;

use InvalidArgumentException;

class CallableDefinitionRepresentation extends AbstractRepresentation
{
    
    protected string $filename;
    
    protected int $line;
    
    protected ?string $docstring;

    
    public function __construct(string $filename, int $line, ?string $docstring)
    {
        if (null !== $docstring && !\preg_match('%^/\\*\\*.+\\*/$%s', $docstring)) {
            throw new InvalidArgumentException('Docstring is invalid');
        }

        parent::__construct('Callable definition', null, true);

        $this->filename = $filename;
        $this->line = $line;
        $this->docstring = $docstring;
    }

    public function getHint(): string
    {
        return 'callable';
    }

    public function getFileName(): string
    {
        return $this->filename;
    }

    public function getLine(): int
    {
        return $this->line;
    }

    
    public function getDocstring(): ?string
    {
        return $this->docstring;
    }

    
    public function getDocstringWithoutComments(): ?string
    {
        if (null === ($ds = $this->getDocstring())) {
            return null;
        }

        $string = (string) \substr($ds, 3, -2);
        
        $string = \preg_replace('/^\\s*\\*\\s*?(\\S|$)/m', '\\1', $string);

        return \trim($string);
    }

    public function getDocstringFirstLine(): ?string
    {
        $ds = $this->getDocstringWithoutComments();

        if (null === $ds) {
            return null;
        }

        $ds = \explode("\n", $ds);

        $out = '';

        foreach ($ds as $line) {
            if (0 === \strlen(\trim($line)) || '@' === $line[0]) {
                break;
            }

            $out .= $line.' ';
        }

        if (\strlen($out)) {
            return \rtrim($out);
        }

        return null;
    }

    public function getDocstringTrimmed(): ?string
    {
        if (null === ($ds = $this->getDocstring())) {
            return null;
        }

        $docstring = [];
        foreach (\explode("\n", $ds) as $line) {
            $line = \trim($line);
            if (($line[0] ?? null) === '*') {
                $line = ' '.$line;
            }
            $docstring[] = $line;
        }

        return \implode("\n", $docstring);
    }
}
