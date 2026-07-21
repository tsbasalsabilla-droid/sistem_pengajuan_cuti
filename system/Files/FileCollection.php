<?php

declare(strict_types=1);



namespace CodeIgniter\Files;

use CodeIgniter\Exceptions\InvalidArgumentException;
use CodeIgniter\Files\Exceptions\FileException;
use CodeIgniter\Files\Exceptions\FileNotFoundException;
use Countable;
use Generator;
use IteratorAggregate;


class FileCollection implements Countable, IteratorAggregate
{
    
    protected $files = [];

    
    
    

    
    final protected static function resolveDirectory(string $directory): string
    {
        if (! is_dir($directory = set_realpath($directory))) {
            $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];

            throw FileException::forExpectedDirectory($caller['function']);
        }

        return $directory;
    }

    
    final protected static function resolveFile(string $file): string
    {
        if (! is_file($file = set_realpath($file))) {
            $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];

            throw FileException::forExpectedFile($caller['function']);
        }

        return $file;
    }

    
    final protected static function filterFiles(array $files, string $directory): array
    {
        $directory = self::resolveDirectory($directory);

        return array_filter($files, static fn (string $value): bool => str_starts_with($value, $directory));
    }

    
    final protected static function matchFiles(array $files, string $pattern): array
    {
        
        if (@preg_match($pattern, '') === false) {
            $pattern = str_replace(
                ['#', '.', '*', '?'],
                ['\#', '\.', '.*', '.'],
                $pattern,
            );
            $pattern = "#\\A{$pattern}\\z#";
        }

        return array_filter($files, static fn ($value): bool => (bool) preg_match($pattern, basename($value)));
    }

    
    
    

    
    public function __construct(array $files = [])
    {
        helper(['filesystem']);

        $this->add($files)->define();
    }

    
    protected function define(): void
    {
    }

    
    public function get(): array
    {
        $this->files = array_unique($this->files);
        sort($this->files, SORT_STRING);

        return $this->files;
    }

    
    public function set(array $files)
    {
        $this->files = [];

        return $this->addFiles($files);
    }

    
    public function add($paths, bool $recursive = true)
    {
        $paths = (array) $paths;

        foreach ($paths as $path) {
            if (! is_string($path)) {
                throw new InvalidArgumentException('FileCollection paths must be strings.');
            }

            try {
                
                self::resolveDirectory($path);
            } catch (FileException) {
                $this->addFile($path);

                continue;
            }

            $this->addDirectory($path, $recursive);
        }

        return $this;
    }

    
    
    

    
    public function addFiles(array $files)
    {
        foreach ($files as $file) {
            $this->addFile($file);
        }

        return $this;
    }

    
    public function addFile(string $file)
    {
        $this->files[] = self::resolveFile($file);

        return $this;
    }

    
    public function removeFiles(array $files)
    {
        $this->files = array_diff($this->files, $files);

        return $this;
    }

    
    public function removeFile(string $file)
    {
        return $this->removeFiles([$file]);
    }

    
    
    

    
    public function addDirectories(array $directories, bool $recursive = false)
    {
        foreach ($directories as $directory) {
            $this->addDirectory($directory, $recursive);
        }

        return $this;
    }

    
    public function addDirectory(string $directory, bool $recursive = false)
    {
        $directory = self::resolveDirectory($directory);

        
        foreach (directory_map($directory, 2, true) as $key => $path) {
            if (is_string($path)) {
                $this->addFile($directory . $path);
            } elseif ($recursive && is_array($path)) {
                $this->addDirectory($directory . $key, true);
            }
        }

        return $this;
    }

    
    
    

    
    public function removePattern(string $pattern, ?string $scope = null)
    {
        if ($pattern === '') {
            return $this;
        }

        
        $files = $scope === null ? $this->files : self::filterFiles($this->files, $scope);

        
        return $this->removeFiles(self::matchFiles($files, $pattern));
    }

    
    public function retainPattern(string $pattern, ?string $scope = null)
    {
        if ($pattern === '') {
            return $this;
        }

        
        $files = $scope === null ? $this->files : self::filterFiles($this->files, $scope);

        
        return $this->removeFiles(array_diff($files, self::matchFiles($files, $pattern)));
    }

    
    public function retainMultiplePatterns(array $patterns, ?string $scope = null)
    {
        if ($patterns === []) {
            return $this;
        }

        if (count($patterns) === 1 && $patterns[0] === '') {
            return $this;
        }

        
        $files = $scope === null ? $this->files : self::filterFiles($this->files, $scope);

        
        $filesToRetain = [];

        foreach ($patterns as $pattern) {
            if ($pattern === '') {
                continue;
            }

            
            $filesToRetain = array_merge($filesToRetain, self::matchFiles($files, $pattern));
        }

        
        return $this->removeFiles(array_diff($files, $filesToRetain));
    }

    
    
    

    
    public function count(): int
    {
        return count($this->files);
    }

    
    public function getIterator(): Generator
    {
        foreach ($this->get() as $file) {
            yield new File($file, true);
        }
    }
}
