<?php

declare(strict_types=1);



namespace CodeIgniter\Publisher;

use CodeIgniter\Autoloader\FileLocatorInterface;
use CodeIgniter\Exceptions\RuntimeException;
use CodeIgniter\Files\FileCollection;
use CodeIgniter\HTTP\URI;
use CodeIgniter\Publisher\Exceptions\PublisherException;
use Config\Publisher as PublisherConfig;
use Throwable;


class Publisher extends FileCollection
{
    
    private static array $discovered = [];

    
    private ?string $scratch = null;

    
    private array $errors = [];

    
    private array $published = [];

    
    private  array $restrictions;

    private  ContentReplacer $replacer;

    
    protected $source = ROOTPATH;

    
    protected $destination = FCPATH;

    
    
    

    
    final public static function discover(string $directory = 'Publishers', string $namespace = ''): array
    {
        $key = implode('.', [$namespace, $directory]);

        if (isset(self::$discovered[$key])) {
            return self::$discovered[$key];
        }

        self::$discovered[$key] = [];

        
        $locator = service('locator');

        $files = $namespace === ''
            ? $locator->listFiles($directory)
            : $locator->listNamespaceFiles($namespace, $directory);

        if ([] === $files) {
            return [];
        }

        
        foreach (array_unique($files) as $file) {
            $className = $locator->findQualifiedNameFromPath($file);

            if ($className !== false && class_exists($className) && is_a($className, self::class, true)) {
                
                self::$discovered[$key][] = new $className();
            }
        }

        sort(self::$discovered[$key]);

        return self::$discovered[$key];
    }

    
    private static function wipeDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            
            $attempts = 10;

            while ((bool) $attempts && ! delete_files($directory, true, false, true)) {
                
                $attempts--;
                usleep(100000); 
                
            }

            @rmdir($directory);
        }
    }

    
    
    

    
    public function __construct(?string $source = null, ?string $destination = null)
    {
        helper(['filesystem']);

        $this->source      = self::resolveDirectory($source ?? $this->source);
        $this->destination = self::resolveDirectory($destination ?? $this->destination);

        $this->replacer = new ContentReplacer();

        
        $this->restrictions = config(PublisherConfig::class)->restrictions;

        
        foreach (array_keys($this->restrictions) as $directory) {
            if (str_starts_with($this->destination, $directory)) {
                return;
            }
        }

        throw PublisherException::forDestinationNotAllowed($this->destination);
    }

    
    public function __destruct()
    {
        if (isset($this->scratch)) {
            self::wipeDirectory($this->scratch);

            $this->scratch = null;
        }
    }

    
    public function publish(): bool
    {
        
        if ($this->source === ROOTPATH && $this->destination === FCPATH) {
            throw new RuntimeException('Child classes of Publisher should provide their own publish method or a source and destination.');
        }

        return $this->addPath('/')->merge(true);
    }

    
    
    

    
    final public function getSource(): string
    {
        return $this->source;
    }

    
    final public function getDestination(): string
    {
        return $this->destination;
    }

    
    final public function getScratch(): string
    {
        if ($this->scratch === null) {
            $this->scratch = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . bin2hex(random_bytes(6)) . DIRECTORY_SEPARATOR;
            mkdir($this->scratch, 0700);
            $this->scratch = realpath($this->scratch) ? realpath($this->scratch) . DIRECTORY_SEPARATOR
                : $this->scratch;
        }

        return $this->scratch;
    }

    
    final public function getErrors(): array
    {
        return $this->errors;
    }

    
    final public function getPublished(): array
    {
        return $this->published;
    }

    
    
    

    
    final public function addPaths(array $paths, bool $recursive = true)
    {
        foreach ($paths as $path) {
            $this->addPath($path, $recursive);
        }

        return $this;
    }

    
    final public function addPath(string $path, bool $recursive = true)
    {
        $this->add($this->source . $path, $recursive);

        return $this;
    }

    
    final public function addUris(array $uris)
    {
        foreach ($uris as $uri) {
            $this->addUri($uri);
        }

        return $this;
    }

    
    final public function addUri(string $uri)
    {
        
        $file = $this->getScratch() . basename((new URI($uri))->getPath());

        
        write_file($file, service('curlrequest')->get($uri)->getBody());

        return $this->addFile($file);
    }

    
    
    

    
    final public function wipe()
    {
        self::wipeDirectory($this->destination);

        return $this;
    }

    
    final public function copy(bool $replace = true): bool
    {
        $this->errors = $this->published = [];

        foreach ($this->get() as $file) {
            $to = $this->destination . basename($file);

            try {
                $this->safeCopyFile($file, $to, $replace);
                $this->published[] = $to;
            } catch (Throwable $e) {
                $this->errors[$file] = $e;
            }
        }

        return $this->errors === [];
    }

    
    final public function merge(bool $replace = true): bool
    {
        $this->errors = $this->published = [];

        
        $sourced = self::filterFiles($this->get(), $this->source);

        
        $this->files = array_diff($this->files, $sourced);
        $this->copy($replace);

        
        foreach ($sourced as $file) {
            
            $to = $this->destination . substr($file, strlen($this->source));

            try {
                $this->safeCopyFile($file, $to, $replace);
                $this->published[] = $to;
            } catch (Throwable $e) {
                $this->errors[$file] = $e;
            }
        }

        return $this->errors === [];
    }

    
    public function replace(string $file, array $replaces): bool
    {
        $this->verifyAllowed($file, $file);

        $content = file_get_contents($file);

        $newContent = $this->replacer->replace($content, $replaces);

        $return = file_put_contents($file, $newContent);

        return $return !== false;
    }

    
    public function addLineAfter(string $file, string $line, string $after): bool
    {
        $this->verifyAllowed($file, $file);

        $content = file_get_contents($file);

        $result = $this->replacer->addAfter($content, $line, $after);

        if ($result !== null) {
            $return = file_put_contents($file, $result);

            return $return !== false;
        }

        return false;
    }

    
    public function addLineBefore(string $file, string $line, string $before): bool
    {
        $this->verifyAllowed($file, $file);

        $content = file_get_contents($file);

        $result = $this->replacer->addBefore($content, $line, $before);

        if ($result !== null) {
            $return = file_put_contents($file, $result);

            return $return !== false;
        }

        return false;
    }

    
    private function verifyAllowed(string $from, string $to): void
    {
        
        foreach ($this->restrictions as $directory => $pattern) {
            if (str_starts_with($to, $directory) && self::matchFiles([$to], $pattern) === []) {
                throw PublisherException::forFileNotAllowed($from, $directory, $pattern);
            }
        }
    }

    
    private function safeCopyFile(string $from, string $to, bool $replace): void
    {
        
        $this->verifyAllowed($from, $to);

        
        if (file_exists($to)) {
            
            if (! $replace || same_file($from, $to)) {
                return;
            }

            
            if (is_dir($to)) {
                throw PublisherException::forCollision($from, $to);
            }

            
            unlink($to);
        }

        
        if (! is_dir($directory = pathinfo($to, PATHINFO_DIRNAME))) {
            mkdir($directory, 0775, true);
        }

        
        copy($from, $to);
    }
}
