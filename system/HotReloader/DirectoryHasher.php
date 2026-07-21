<?php

declare(strict_types=1);



namespace CodeIgniter\HotReloader;

use CodeIgniter\Exceptions\FrameworkException;
use Config\Toolbar;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;


final class DirectoryHasher
{
    
    public function hash(): string
    {
        return md5(implode('', $this->hashApp()));
    }

    
    public function hashApp(): array
    {
        $hashes = [];

        $watchedDirectories = config(Toolbar::class)->watchedDirectories;

        foreach ($watchedDirectories as $directory) {
            if (is_dir(ROOTPATH . $directory)) {
                $hashes[$directory] = $this->hashDirectory(ROOTPATH . $directory);
            }
        }

        return array_unique(array_filter($hashes));
    }

    
    public function hashDirectory(string $path): string
    {
        if (! is_dir($path)) {
            throw FrameworkException::forInvalidDirectory($path);
        }

        $directory = new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS);
        $filter    = new IteratorFilter($directory);
        $iterator  = new RecursiveIteratorIterator($filter);

        $hashes = [];

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $hashes[] = md5_file($file->getRealPath());
            }
        }

        return md5(implode('', $hashes));
    }
}
