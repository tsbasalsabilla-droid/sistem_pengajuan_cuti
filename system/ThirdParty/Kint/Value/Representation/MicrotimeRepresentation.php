<?php

declare(strict_types=1);



namespace Kint\Value\Representation;

use DateTimeImmutable;
use DateTimeInterface;

class MicrotimeRepresentation extends AbstractRepresentation
{
    
    protected int $seconds;
    
    protected int $microseconds;
    
    protected string $group;
    
    protected ?float $lap_time;
    
    protected ?float $total_time;
    protected ?float $avg_time = null;
    
    protected int $mem;
    
    protected int $mem_real;
    
    protected int $mem_peak;
    
    protected int $mem_peak_real;

    public function __construct(int $seconds, int $microseconds, string $group, ?float $lap_time = null, ?float $total_time = null, int $i = 0)
    {
        parent::__construct('Microtime', null, true);

        $this->seconds = $seconds;
        $this->microseconds = $microseconds;

        $this->group = $group;
        $this->lap_time = $lap_time;
        $this->total_time = $total_time;

        if ($i > 0) {
            $this->avg_time = $total_time / $i;
        }

        $this->mem = \memory_get_usage();
        $this->mem_real = \memory_get_usage(true);
        $this->mem_peak = \memory_get_peak_usage();
        $this->mem_peak_real = \memory_get_peak_usage(true);
    }

    public function getHint(): string
    {
        return 'microtime';
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    public function getLapTime(): ?float
    {
        return $this->lap_time;
    }

    public function getTotalTime(): ?float
    {
        return $this->total_time;
    }

    public function getAverageTime(): ?float
    {
        return $this->avg_time;
    }

    public function getMemoryUsage(): int
    {
        return $this->mem;
    }

    public function getMemoryUsageReal(): int
    {
        return $this->mem_real;
    }

    public function getMemoryPeakUsage(): int
    {
        return $this->mem_peak;
    }

    public function getMemoryPeakUsageReal(): int
    {
        return $this->mem_peak_real;
    }

    public function getDateTime(): ?DateTimeInterface
    {
        return DateTimeImmutable::createFromFormat('U u', $this->seconds.' '.\str_pad((string) $this->microseconds, 6, '0', STR_PAD_LEFT)) ?: null;
    }
}
