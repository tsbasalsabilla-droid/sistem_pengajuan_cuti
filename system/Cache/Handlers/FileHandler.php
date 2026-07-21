<?php

declare(strict_types=1);



namespace CodeIgniter\Cache\Handlers;

use CodeIgniter\Cache\Exceptions\CacheException;
use CodeIgniter\I18n\Time;
use Config\Cache;
use Throwable;


class FileHandler extends BaseHandler
{
    
    public const MAX_KEY_LENGTH = 255;

    
    protected $path;

    
    protected $mode;

    
    public function __construct(Cache $config)
    {
        $options = [
            ...['storePath' => WRITEPATH . 'cache', 'mode' => 0640],
            ...$config->file,
        ];

        $this->path = $options['storePath'] !== '' ? $options['storePath'] : WRITEPATH . 'cache';
        $this->path = rtrim($this->path, '\\/') . '/';

        if (! is_really_writable($this->path)) {
            throw CacheException::forUnableToWrite($this->path);
        }

        $this->mode   = $options['mode'];
        $this->prefix = $config->prefix;

        helper('filesystem');
    }

    public function initialize(): void
    {
    }

    public function get(string $key): mixed
    {
        $key  = static::validateKey($key, $this->prefix);
        $data = $this->getItem($key);

        return is_array($data) ? $data['data'] : null;
    }

    public function save(string $key, mixed $value, int $ttl = 60): bool
    {
        $key = static::validateKey($key, $this->prefix);

        $contents = [
            'time' => Time::now()->getTimestamp(),
            'ttl'  => $ttl,
            'data' => $value,
        ];

        if (write_file($this->path . $key, serialize($contents))) {
            try {
                chmod($this->path . $key, $this->mode);

                
            } catch (Throwable $e) {
                log_message('debug', 'Failed to set mode on cache file: ' . $e);
                
            }

            return true;
        }

        return false;
    }

    public function delete(string $key): bool
    {
        $key = static::validateKey($key, $this->prefix);

        return is_file($this->path . $key) && unlink($this->path . $key);
    }

    public function deleteMatching(string $pattern): int
    {
        $deleted = 0;

        foreach (glob($this->path . $pattern, GLOB_NOSORT) as $filename) {
            if (is_file($filename) && @unlink($filename)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    public function increment(string $key, int $offset = 1): bool|int
    {
        $prefixedKey = static::validateKey($key, $this->prefix);
        $tmp         = $this->getItem($prefixedKey);

        if ($tmp === false) {
            $tmp = ['data' => 0, 'ttl' => 60];
        }

        ['data' => $value, 'ttl' => $ttl] = $tmp;

        if (! is_int($value)) {
            return false;
        }

        $value += $offset;

        return $this->save($key, $value, $ttl) ? $value : false;
    }

    public function decrement(string $key, int $offset = 1): bool|int
    {
        return $this->increment($key, -$offset);
    }

    public function clean(): bool
    {
        return delete_files($this->path, false, true);
    }

    public function getCacheInfo(): array
    {
        return get_dir_file_info($this->path);
    }

    public function getMetaData(string $key): ?array
    {
        $key = static::validateKey($key, $this->prefix);

        if (false === $data = $this->getItem($key)) {
            return null;
        }

        return [
            'expire' => $data['ttl'] > 0 ? $data['time'] + $data['ttl'] : null,
            'mtime'  => filemtime($this->path . $key),
            'data'   => $data['data'],
        ];
    }

    public function isSupported(): bool
    {
        return is_writable($this->path);
    }

    
    protected function getItem(string $filename): array|false
    {
        if (! is_file($this->path . $filename)) {
            return false;
        }

        $content = @file_get_contents($this->path . $filename);

        if ($content === false) {
            return false;
        }

        try {
            $data = unserialize($content);
        } catch (Throwable) {
            return false;
        }

        if (! is_array($data)) {
            return false;
        }

        if (! isset($data['ttl']) || ! is_int($data['ttl'])) {
            return false;
        }

        if (! isset($data['time']) || ! is_int($data['time'])) {
            return false;
        }

        if ($data['ttl'] > 0 && Time::now()->getTimestamp() > $data['time'] + $data['ttl']) {
            @unlink($this->path . $filename);

            return false;
        }

        return $data;
    }

    
    protected function writeFile($path, $data, $mode = 'wb'): bool
    {
        if (($fp = @fopen($path, $mode)) === false) {
            return false;
        }

        flock($fp, LOCK_EX);

        $result = 0;

        for ($written = 0, $length = strlen($data); $written < $length; $written += $result) {
            if (($result = fwrite($fp, substr($data, $written))) === false) {
                break;
            }
        }

        flock($fp, LOCK_UN);
        fclose($fp);

        return is_int($result);
    }

    
    protected function deleteFiles(string $path, bool $delDir = false, bool $htdocs = false, int $_level = 0): bool
    {
        
        $path = rtrim($path, '/\\');

        if (! $currentDir = @opendir($path)) {
            return false;
        }

        while (false !== ($filename = @readdir($currentDir))) {
            if ($filename !== '.' && $filename !== '..') {
                if (is_dir($path . DIRECTORY_SEPARATOR . $filename) && $filename[0] !== '.') {
                    $this->deleteFiles($path . DIRECTORY_SEPARATOR . $filename, $delDir, $htdocs, $_level + 1);
                } elseif (! $htdocs || preg_match('/^(\.htaccess|index\.(html|htm|php)|web\.config)$/i', $filename) !== 1) {
                    @unlink($path . DIRECTORY_SEPARATOR . $filename);
                }
            }
        }

        closedir($currentDir);

        return ($delDir && $_level > 0) ? @rmdir($path) : true;
    }

    
    protected function getDirFileInfo(string $sourceDir, bool $topLevelOnly = true, bool $_recursion = false): array|false
    {
        static $filedata = [];

        $relativePath = $sourceDir;
        $filePointer  = @opendir($sourceDir);

        if (! is_bool($filePointer)) {
            
            if ($_recursion === false) {
                $filedata = [];

                $resolvedSrc = realpath($sourceDir);
                $resolvedSrc = $resolvedSrc === false ? $sourceDir : $resolvedSrc;

                $sourceDir = rtrim($resolvedSrc, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            }

            
            while (false !== $file = readdir($filePointer)) {
                if (is_dir($sourceDir . $file) && $file[0] !== '.' && $topLevelOnly === false) {
                    $this->getDirFileInfo($sourceDir . $file . DIRECTORY_SEPARATOR, $topLevelOnly, true);
                } elseif (! is_dir($sourceDir . $file) && $file[0] !== '.') {
                    $filedata[$file] = $this->getFileInfo($sourceDir . $file);

                    $filedata[$file]['relative_path'] = $relativePath;
                }
            }

            closedir($filePointer);

            return $filedata;
        }

        return false;
    }

    
    protected function getFileInfo(string $file, $returnedValues = ['name', 'server_path', 'size', 'date']): array|false
    {
        if (! is_file($file)) {
            return false;
        }

        if (is_string($returnedValues)) {
            $returnedValues = explode(',', $returnedValues);
        }

        $fileInfo = [];

        foreach ($returnedValues as $key) {
            switch ($key) {
                case 'name':
                    $fileInfo['name'] = basename($file);
                    break;

                case 'server_path':
                    $fileInfo['server_path'] = $file;
                    break;

                case 'size':
                    $fileInfo['size'] = filesize($file);
                    break;

                case 'date':
                    $fileInfo['date'] = filemtime($file);
                    break;

                case 'readable':
                    $fileInfo['readable'] = is_readable($file);
                    break;

                case 'writable':
                    $fileInfo['writable'] = is_writable($file);
                    break;

                case 'executable':
                    $fileInfo['executable'] = is_executable($file);
                    break;

                case 'fileperms':
                    $fileInfo['fileperms'] = fileperms($file);
                    break;
            }
        }

        return $fileInfo;
    }
}
