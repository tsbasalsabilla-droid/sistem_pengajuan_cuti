<?php

declare(strict_types=1);



namespace Kint\Value\Representation;

class ProfileRepresentation extends AbstractRepresentation
{
    
    public int $complexity;
    public ?int $instance_counts = null;
    public ?int $instance_complexity = null;

    public function __construct(int $complexity)
    {
        parent::__construct('Performance profile', 'profiling', false);
        $this->complexity = $complexity;
    }

    public function getHint(): string
    {
        return 'profiling';
    }
}
