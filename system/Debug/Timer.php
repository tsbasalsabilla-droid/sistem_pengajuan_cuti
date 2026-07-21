<?php

declare(strict_types=1);



namespace CodeIgniter\Debug;

use CodeIgniter\Exceptions\RuntimeException;


class Timer
{
    
    protected $timers = [];

    
    public function start(string $name, ?float $time = null)
    {
        $this->timers[strtolower($name)] = [
            'start' => empty($time) ? microtime(true) : $time,
            'end'   => null,
        ];

        return $this;
    }

    
    public function stop(string $name)
    {
        $name = strtolower($name);

        if (empty($this->timers[$name])) {
            throw new RuntimeException('Cannot stop timer: invalid name given.');
        }

        $this->timers[$name]['end'] = microtime(true);

        return $this;
    }

    
    public function getElapsedTime(string $name, int $decimals = 4)
    {
        $name = strtolower($name);

        if (empty($this->timers[$name])) {
            return null;
        }

        $timer = $this->timers[$name];

        if (empty($timer['end'])) {
            $timer['end'] = microtime(true);
        }

        return (float) number_format($timer['end'] - $timer['start'], $decimals, '.', '');
    }

    
    public function getTimers(int $decimals = 4): array
    {
        $timers = $this->timers;

        foreach ($timers as &$timer) {
            if (empty($timer['end'])) {
                $timer['end'] = microtime(true);
            }

            $timer['duration'] = (float) number_format($timer['end'] - $timer['start'], $decimals);
        }

        return $timers;
    }

    
    public function has(string $name): bool
    {
        return array_key_exists(strtolower($name), $this->timers);
    }

    
    public function record(string $name, callable $callable)
    {
        $this->start($name);
        $returnValue = $callable();
        $this->stop($name);

        return $returnValue;
    }
}
