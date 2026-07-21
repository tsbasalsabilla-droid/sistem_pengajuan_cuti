<?php

declare(strict_types=1);



namespace CodeIgniter\Images;


interface ImageHandlerInterface
{
    
    public function resize(int $width, int $height, bool $maintainRatio = false, string $masterDim = 'auto');

    
    public function crop(?int $width = null, ?int $height = null, ?int $x = null, ?int $y = null, bool $maintainRatio = false, string $masterDim = 'auto');

    
    public function convert(int $imageType);

    
    public function rotate(float $angle);

    
    public function flatten(int $red = 255, int $green = 255, int $blue = 255);

    
    public function reorient();

    
    public function getEXIF(?string $key = null);

    
    public function flip(string $dir = 'vertical');

    
    public function fit(int $width, int $height, string $position);

    
    public function text(string $text, array $options = []);

    
    public function save(?string $target = null, int $quality = 90);

    
    public function clearMetadata(): static;
}
