<?php

declare(strict_types=1);



namespace Kint\Value\Context;


interface ContextInterface
{
    
    public function getName();

    public function getDepth(): int;

    public function isRef(): bool;

    
    public function isAccessible(?string $scope): bool;

    public function getAccessPath(): ?string;

    
    public function getOperator(): ?string;
}
