<?php

declare(strict_types=1);



namespace Kint\Value\Representation;

interface RepresentationInterface
{
    public function getLabel(): string;

    public function getName(): string;

    public function getHint(): ?string;

    public function labelIsImplicit(): bool;
}
