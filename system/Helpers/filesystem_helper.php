<?php

declare(strict_types=1);



use CodeIgniter\Exceptions\InvalidArgumentException;



if (! function_exists('directory_map')) {
    
    function directory_map(string $sourceDir, int $directoryDepth = 0, bool $hidden = false): array
    {
        try {
            $fp = opendir($sourceDir);

            $fileData  = [];
            $newDepth  = $directoryDepth - 1;
            $sourceDir = rtrim($sourceDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

            while (false !== ($file = readdir($fp))) {
                
                if ($file === '.' || $file === '..' || ($hidden === false && $file[0] === '.')) {
                    continue;
                }

                if (is_dir($sourceDir . $file)) {
                    $file .= DIRECTORY_SEPARATOR;
                }

                if (($directoryDepth < 1 || $newDepth > 0) && is_dir($sourceDir . $file)) {
                    $fileData[$file] = directory_map($sourceDir . $file, $newDepth, $hidden);
                } else {
                    $fileData[] = $file;
                }
            }

            closedir($fp);

            return $fileData;
        } catch (Throwable) {
            return [];
        }
    }
}

if (! function_exists('directory_mirror')) {
    
    function directory_mirror(string $originDir, string $targetDir, bool $overwrite = true): void
    {
        if (! is_dir($originDir = rtrim($originDir, '\\/'))) {
            throw new InvalidArgumentException(sprintf('The origin directory "%s" was not found.', $originDir));
        }

        if (! is_dir($targetDir = rtrim($targetDir, '\\/'))) {
            @mkdir($targetDir, 0755, true);
        }

        $dirLen = strlen($originDir);

        
        foreach (new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($originDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        ) as $file) {
            $origin = $file->getPathname();
            $target = $targetDir . substr($origin, $dirLen);

            if ($file->isDir()) {
                if (! is_dir($target)) {
                    mkdir($target, 0755);
                }
            } elseif ($overwrite || ! is_file($target)) {
                copy($origin, $target);
            }
        }
    }
}

if (! function_exists('write_file')) {
    
    function write_file(string $path, string $data, string $mode = 'wb'): bool
    {
        try {
            $fp = fopen($path, $mode);

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
        } catch (Throwable) {
            return false;
        }
    }
}

if (! function_exists('delete_files')) {
    
    function delete_files(string $path, bool $delDir = false, bool $htdocs = false, bool $hidden = false): bool
    {
        $path = realpath($path) ?: $path;
        $path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        try {
            foreach (new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST,
            ) as $object) {
                $filename = $object->getFilename();
                if (! $hidden && $filename[0] === '.') {
                    continue;
                }

                if (! $htdocs || preg_match('/^(\.htaccess|index\.(html|htm|php)|web\.config)$/i', $filename) !== 1) {
                    $isDir = $object->isDir();
                    if ($isDir && $delDir) {
                        rmdir($object->getPathname());

                        continue;
                    }
                    if (! $isDir) {
                        unlink($object->getPathname());
                    }
                }
            }

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}

if (! function_exists('get_filenames')) {
    
    function get_filenames(
        string $sourceDir,
        ?bool $includePath = false,
        bool $hidden = false,
        bool $includeDir = true,
    ): array {
        $files = [];

        $sourceDir = realpath($sourceDir) ?: $sourceDir;
        $sourceDir = rtrim($sourceDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        try {
            foreach (new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS),
                RecursiveIteratorIterator::SELF_FIRST,
            ) as $name => $object) {
                $basename = pathinfo($name, PATHINFO_BASENAME);
                if (! $hidden && $basename[0] === '.') {
                    continue;
                }

                if ($includeDir || ! $object->isDir()) {
                    if ($includePath === false) {
                        $files[] = $basename;
                    } elseif ($includePath === null) {
                        $files[] = str_replace($sourceDir, '', $name);
                    } else {
                        $files[] = $name;
                    }
                }
            }
        } catch (Throwable) {
            return [];
        }

        sort($files);

        return $files;
    }
}

if (! function_exists('get_dir_file_info')) {
    
    function get_dir_file_info(string $sourceDir, bool $topLevelOnly = true, bool $recursion = false): array
    {
        static $fileData = [];
        $relativePath    = $sourceDir;

        try {
            $fp = opendir($sourceDir);

            
            if ($recursion === false) {
                $fileData  = [];
                $sourceDir = rtrim(realpath($sourceDir), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            }

            
            while (false !== ($file = readdir($fp))) {
                if (is_dir($sourceDir . $file) && $file[0] !== '.' && $topLevelOnly === false) {
                    get_dir_file_info($sourceDir . $file . DIRECTORY_SEPARATOR, $topLevelOnly, true);
                } elseif ($file[0] !== '.') {
                    $fileData[$file]                  = get_file_info($sourceDir . $file);
                    $fileData[$file]['relative_path'] = $relativePath;
                }
            }

            closedir($fp);

            return $fileData;
        } catch (Throwable) {
            return [];
        }
    }
}

if (! function_exists('get_file_info')) {
    
    function get_file_info(string $file, $returnedValues = ['name', 'server_path', 'size', 'date'])
    {
        if (! is_file($file)) {
            return null;
        }

        $fileInfo = [];

        if (is_string($returnedValues)) {
            $returnedValues = explode(',', $returnedValues);
        }

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
                    $fileInfo['writable'] = is_really_writable($file);
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

if (! function_exists('symbolic_permissions')) {
    
    function symbolic_permissions(int $perms): string
    {
        if (($perms & 0xC000) === 0xC000) {
            $symbolic = 's'; 
        } elseif (($perms & 0xA000) === 0xA000) {
            $symbolic = 'l'; 
        } elseif (($perms & 0x8000) === 0x8000) {
            $symbolic = '-'; 
        } elseif (($perms & 0x6000) === 0x6000) {
            $symbolic = 'b'; 
        } elseif (($perms & 0x4000) === 0x4000) {
            $symbolic = 'd'; 
        } elseif (($perms & 0x2000) === 0x2000) {
            $symbolic = 'c'; 
        } elseif (($perms & 0x1000) === 0x1000) {
            $symbolic = 'p'; 
        } else {
            $symbolic = 'u'; 
        }

        
        $symbolic .= ((($perms & 0x0100) !== 0) ? 'r' : '-')
                . ((($perms & 0x0080) !== 0) ? 'w' : '-')
                . ((($perms & 0x0040) !== 0) ? ((($perms & 0x0800) !== 0) ? 's' : 'x') : ((($perms & 0x0800) !== 0) ? 'S' : '-'));

        
        $symbolic .= ((($perms & 0x0020) !== 0) ? 'r' : '-')
                . ((($perms & 0x0010) !== 0) ? 'w' : '-')
                . ((($perms & 0x0008) !== 0) ? ((($perms & 0x0400) !== 0) ? 's' : 'x') : ((($perms & 0x0400) !== 0) ? 'S' : '-'));

        
        $symbolic .= ((($perms & 0x0004) !== 0) ? 'r' : '-')
                . ((($perms & 0x0002) !== 0) ? 'w' : '-')
                . ((($perms & 0x0001) !== 0) ? ((($perms & 0x0200) !== 0) ? 't' : 'x') : ((($perms & 0x0200) !== 0) ? 'T' : '-'));

        return $symbolic;
    }
}

if (! function_exists('octal_permissions')) {
    
    function octal_permissions(int $perms): string
    {
        return substr(sprintf('%o', $perms), -3);
    }
}

if (! function_exists('same_file')) {
    
    function same_file(string $file1, string $file2): bool
    {
        return is_file($file1) && is_file($file2) && md5_file($file1) === md5_file($file2);
    }
}

if (! function_exists('set_realpath')) {
    
    function set_realpath(string $path, bool $checkExistence = false): string
    {
        
        if (preg_match('#^(http:\/\/|https:\/\/|www\.|ftp)#i', $path) || filter_var($path, FILTER_VALIDATE_IP) === $path) {
            throw new InvalidArgumentException('The path you submitted must be a local server path, not a URL');
        }

        
        if (realpath($path) !== false) {
            $path = realpath($path);
        } elseif ($checkExistence && ! is_dir($path) && ! is_file($path)) {
            throw new InvalidArgumentException('Not a valid path: ' . $path);
        }

        
        return is_dir($path) ? rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : $path;
    }
}
