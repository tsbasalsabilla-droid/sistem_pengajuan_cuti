<?php

declare(strict_types=1);



namespace CodeIgniter\HotReloader;

use Config\Toolbar;
use RecursiveFilterIterator;
use RecursiveIterator;


final class IteratorFilter extends RecursiveFilterIterator implements RecursiveIterator
{
    private array $watchedExtensions = [];

    public function __construct(RecursiveIterator $iterator)
    {
        parent::__construct($iterator);

        $this->watchedExtensions = config(Toolbar::class)->watchedExtensions;
    }

    
    public function accept(): bool
    {
        if (! $this->current()->isFile()) {
            return true;
        }

        $filename = $this->current()->getFilename();

        
        if ($filename[0] === '.') {
            return false;
        }

        
        $ext = trim(strtolower($this->current()->getExtension()), '. ');

        return in_array($ext, $this->watchedExtensions, true);
    }
}
