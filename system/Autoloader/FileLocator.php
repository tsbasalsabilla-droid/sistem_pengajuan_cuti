<?php

declare(strict_types=1);



namespace CodeIgniter\Autoloader;


class FileLocator implements FileLocatorInterface
{
    
    protected $autoloader;

    
    private array $invalidClassnames = [];

    public function __construct(Autoloader $autoloader)
    {
        $this->autoloader = $autoloader;
    }

    
    public function locateFile(string $file, ?string $folder = null, string $ext = 'php')
    {
        $file = $this->ensureExt($file, $ext);

        
        if ($folder !== null && str_starts_with($file, $folder)) {
            $file = substr($file, strlen($folder . '/'));
        }

        
        if (! str_contains($file, '\\')) {
            return $this->legacyLocate($file, $folder);
        }

        
        $file = strtr($file, '/', '\\');
        $file = ltrim($file, '\\');

        $segments = explode('\\', $file);

        
        if ($segments[0] === '') {
            unset($segments[0]);
        }

        $paths    = [];
        $filename = '';

        
        $namespaces = $this->autoloader->getNamespace();

        foreach (array_keys($namespaces) as $namespace) {
            if (substr($file, 0, strlen($namespace) + 1) === $namespace . '\\') {
                $fileWithoutNamespace = substr($file, strlen($namespace));

                
                
                $paths    = $namespaces[$namespace];
                $filename = ltrim(str_replace('\\', '/', $fileWithoutNamespace), '/');
            }
        }

        
        if ($paths === []) {
            return false;
        }

        
        foreach ($paths as $path) {
            
            $path = rtrim($path, '/') . '/';

            
            
            
            if ($folder !== null && ! str_contains($path . $filename, '/' . $folder . '/')) {
                $path .= trim($folder, '/') . '/';
            }

            $path .= $filename;
            if (is_file($path)) {
                return $path;
            }
        }

        return false;
    }

    
    public function getClassname(string $file): string
    {
        if (is_dir($file)) {
            return '';
        }

        $php       = file_get_contents($file);
        $tokens    = token_get_all($php);
        $dlm       = false;
        $namespace = '';
        $className = '';

        foreach ($tokens as $i => $token) {
            if ($i < 2) {
                continue;
            }

            if ((isset($tokens[$i - 2][1]) && ($tokens[$i - 2][1] === 'phpnamespace' || $tokens[$i - 2][1] === 'namespace')) || ($dlm && $tokens[$i - 1][0] === T_NS_SEPARATOR && $token[0] === T_STRING)) {
                if (! $dlm) {
                    $namespace = '';
                }

                if (isset($token[1])) {
                    $namespace = $namespace !== '' ? $namespace . '\\' . $token[1] : $token[1];
                    $dlm       = true;
                }
            } elseif ($dlm && ($token[0] !== T_NS_SEPARATOR) && ($token[0] !== T_STRING)) {
                $dlm = false;
            }

            if (
                ($tokens[$i - 2][0] === T_CLASS || (isset($tokens[$i - 2][1]) && $tokens[$i - 2][1] === 'phpclass'))
                && $tokens[$i - 1][0] === T_WHITESPACE
                && $token[0] === T_STRING
            ) {
                $className = $token[1];
                break;
            }
        }

        if ($className === '') {
            return '';
        }

        return $namespace . '\\' . $className;
    }

    
    public function search(string $path, string $ext = 'php', bool $prioritizeApp = true): array
    {
        $path = $this->ensureExt($path, $ext);

        $foundPaths = [];
        $appPaths   = [];

        foreach ($this->getNamespaces() as $namespace) {
            if (isset($namespace['path']) && is_file($namespace['path'] . $path)) {
                $fullPath     = $namespace['path'] . $path;
                $resolvedPath = realpath($fullPath);
                $fullPath     = $resolvedPath !== false ? $resolvedPath : $fullPath;

                if ($prioritizeApp) {
                    $foundPaths[] = $fullPath;
                } elseif (str_starts_with($fullPath, APPPATH)) {
                    $appPaths[] = $fullPath;
                } else {
                    $foundPaths[] = $fullPath;
                }
            }
        }

        if (! $prioritizeApp && $appPaths !== []) {
            $foundPaths = [...$foundPaths, ...$appPaths];
        }

        return array_values(array_unique($foundPaths));
    }

    
    protected function ensureExt(string $path, string $ext): string
    {
        if ($ext !== '') {
            $ext = '.' . $ext;

            if (! str_ends_with($path, $ext)) {
                $path .= $ext;
            }
        }

        return $path;
    }

    
    protected function getNamespaces()
    {
        $namespaces = [];

        $system = [];

        foreach ($this->autoloader->getNamespace() as $prefix => $paths) {
            foreach ($paths as $path) {
                if ($prefix === 'CodeIgniter') {
                    $system[] = [
                        'prefix' => $prefix,
                        'path'   => rtrim($path, '\\/') . DIRECTORY_SEPARATOR,
                    ];

                    continue;
                }

                $namespaces[] = [
                    'prefix' => $prefix,
                    'path'   => rtrim($path, '\\/') . DIRECTORY_SEPARATOR,
                ];
            }
        }

        
        return array_merge($namespaces, $system);
    }

    public function findQualifiedNameFromPath(string $path)
    {
        $resolvedPath = realpath($path);
        $path         = $resolvedPath !== false ? $resolvedPath : $path;

        if (! is_file($path)) {
            return false;
        }

        foreach ($this->getNamespaces() as $namespace) {
            $resolvedNamespacePath = realpath($namespace['path']);
            $namespace['path']     = $resolvedNamespacePath !== false ? $resolvedNamespacePath : $namespace['path'];

            if ($namespace['path'] === '') {
                continue;
            }

            if (mb_strpos($path, $namespace['path']) === 0) {
                $className = $namespace['prefix'] . '\\' .
                    ltrim(
                        str_replace(
                            '/',
                            '\\',
                            mb_substr($path, mb_strlen($namespace['path'])),
                        ),
                        '\\',
                    );

                
                
                $className = mb_substr($className, 0, -4);

                if (in_array($className, $this->invalidClassnames, true)) {
                    continue;
                }

                if (class_exists($className)) {
                    return $className;
                }

                $this->invalidClassnames[] = $className;
            }
        }

        return false;
    }

    
    public function listFiles(string $path): array
    {
        if ($path === '') {
            return [];
        }

        $files = [];
        helper('filesystem');

        foreach ($this->getNamespaces() as $namespace) {
            $fullPath     = $namespace['path'] . $path;
            $resolvedPath = realpath($fullPath);
            $fullPath     = $resolvedPath !== false ? $resolvedPath : $fullPath;

            if (! is_dir($fullPath)) {
                continue;
            }

            $tempFiles = get_filenames($fullPath, true, false, false);

            if ($tempFiles !== []) {
                $files = array_merge($files, $tempFiles);
            }
        }

        return $files;
    }

    
    public function listNamespaceFiles(string $prefix, string $path): array
    {
        if ($path === '' || $prefix === '') {
            return [];
        }

        $files = [];
        helper('filesystem');

        
        foreach ($this->autoloader->getNamespace($prefix) as $namespacePath) {
            $fullPath     = rtrim($namespacePath, '/') . '/' . $path;
            $resolvedPath = realpath($fullPath);
            $fullPath     = $resolvedPath !== false ? $resolvedPath : $fullPath;

            if (! is_dir($fullPath)) {
                continue;
            }

            $tempFiles = get_filenames($fullPath, true, false, false);

            if ($tempFiles !== []) {
                $files = array_merge($files, $tempFiles);
            }
        }

        return $files;
    }

    
    protected function legacyLocate(string $file, ?string $folder = null)
    {
        $path         = APPPATH . ($folder === null ? $file : $folder . '/' . $file);
        $resolvedPath = realpath($path);
        $path         = $resolvedPath !== false ? $resolvedPath : $path;

        if (is_file($path)) {
            return $path;
        }

        return false;
    }
}
