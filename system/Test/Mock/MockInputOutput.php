<?php

declare(strict_types=1);



namespace CodeIgniter\Test\Mock;

use CodeIgniter\CLI\InputOutput;
use CodeIgniter\Exceptions\InvalidArgumentException;
use CodeIgniter\Exceptions\LogicException;
use CodeIgniter\Test\Filters\CITestStreamFilter;
use CodeIgniter\Test\PhpStreamWrapper;

final class MockInputOutput extends InputOutput
{
    
    private array $inputs = [];

    
    private array $outputs = [];

    
    public function setInputs(array $inputs): void
    {
        $this->inputs = $inputs;
    }

    
    public function getOutput(?int $index = null): string
    {
        if ($index === null) {
            return implode('', $this->outputs);
        }

        if (array_key_exists($index, $this->outputs)) {
            return $this->outputs[$index];
        }

        if ($index < 0) {
            $i = count($this->outputs) + $index;

            if (array_key_exists($i, $this->outputs)) {
                return $this->outputs[$i];
            }
        }

        throw new InvalidArgumentException(
            'No such index in output: ' . $index . ', the last index is: '
            . (count($this->outputs) - 1),
        );
    }

    
    public function getOutputs(): array
    {
        return $this->outputs;
    }

    private function addStreamFilters(): void
    {
        CITestStreamFilter::registration();
        CITestStreamFilter::addOutputFilter();
        CITestStreamFilter::addErrorFilter();
    }

    private function removeStreamFilters(): void
    {
        CITestStreamFilter::removeOutputFilter();
        CITestStreamFilter::removeErrorFilter();
    }

    public function input(?string $prefix = null): string
    {
        if ($this->inputs === []) {
            throw new LogicException(
                'No input data. Specifiy input data with `MockInputOutput::setInputs()`.',
            );
        }

        $input = array_shift($this->inputs);

        $this->addStreamFilters();

        PhpStreamWrapper::register();
        PhpStreamWrapper::setContent($input);

        $userInput       = parent::input($prefix);
        $this->outputs[] = CITestStreamFilter::$buffer . $input . PHP_EOL;

        PhpStreamWrapper::restore();

        $this->removeStreamFilters();

        if ($input !== $userInput) {
            throw new LogicException($input . '!==' . $userInput);
        }

        return $input;
    }

    public function fwrite($handle, string $string): void
    {
        $this->addStreamFilters();

        parent::fwrite($handle, $string);
        $this->outputs[] = CITestStreamFilter::$buffer;

        $this->removeStreamFilters();
    }
}
