<?php

declare(strict_types=1);



namespace CodeIgniter;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;


final class ComposerScripts
{
    
    private static string $path = __DIR__ . '/ThirdParty/';

    
    private static array $dependencies = [
        'kint-src' => [
            'license' => __DIR__ . '/../vendor/kint-php/kint/LICENSE',
            'from'    => __DIR__ . '/../vendor/kint-php/kint/src/',
            'to'      => __DIR__ . '/ThirdParty/Kint/',
        ],
        'kint-resources' => [
            'from' => __DIR__ . '/../vendor/kint-php/kint/resources/',
            'to'   => __DIR__ . '/ThirdParty/Kint/resources/',
        ],
        'escaper' => [
            'license' => __DIR__ . '/../vendor/laminas/laminas-escaper/LICENSE.md',
            'from'    => __DIR__ . '/../vendor/laminas/laminas-escaper/src/',
            'to'      => __DIR__ . '/ThirdParty/Escaper/',
        ],
        'psr-log' => [
            'license' => __DIR__ . '/../vendor/psr/log/LICENSE',
            'from'    => __DIR__ . '/../vendor/psr/log/src/',
            'to'      => __DIR__ . '/ThirdParty/PSR/Log/',
        ],
    ];

    
    public static function postUpdate(): void
    {
        self::recursiveDelete(self::$path);

        foreach (self::$dependencies as $key => $dependency) {
            
            if (! is_dir($dependency['from']) && str_starts_with($key, 'kint')) {
                continue;
            }

            self::recursiveMirror($dependency['from'], $dependency['to']);

            if (isset($dependency['license'])) {
                $license = basename($dependency['license']);
                copy($dependency['license'], $dependency['to'] . '/' . $license);
            }
        }

        self::copyKintInitFiles();
    }

    
    private static function recursiveDelete(string $directory): void
    {
        if (! is_dir($directory)) {
            echo sprintf('Cannot recursively delete "%s" as it does not exist.', $directory) . PHP_EOL;

            return;
        }

        
        foreach (new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(rtrim($directory, '\\/'), FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        ) as $file) {
            $path = $file->getPathname();

            if ($file->isDir()) {
                @rmdir($path);
            } else {
                @unlink($path);
            }
        }
    }

    
    private static function recursiveMirror(string $originDir, string $targetDir): void
    {
        $originDir = rtrim($originDir, '\\/');
        $targetDir = rtrim($targetDir, '\\/');

        if (! is_dir($originDir)) {
            echo sprintf('The origin directory "%s" was not found.', $originDir);

            exit(1);
        }

        if (is_dir($targetDir)) {
            echo sprintf('The target directory "%s" is existing. Run %s::recursiveDelete(\'%s\') first.', $targetDir, self::class, $targetDir);

            exit(1);
        }

        if (! @mkdir($targetDir, 0755, true)) {
            echo sprintf('Cannot create the target directory: "%s"', $targetDir) . PHP_EOL;

            exit(1);
        }

        $dirLen = strlen($originDir);

        
        foreach (new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($originDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        ) as $file) {
            $origin = $file->getPathname();
            $target = $targetDir . substr($origin, $dirLen);

            if ($file->isDir()) {
                @mkdir($target, 0755);
            } else {
                @copy($origin, $target);
            }
        }
    }

    
    private static function copyKintInitFiles(): void
    {
        $originDir = self::$dependencies['kint-src']['from'] . '../';
        $targetDir = self::$dependencies['kint-src']['to'];

        foreach (['init.php', 'init_helpers.php'] as $kintInit) {
            @copy($originDir . $kintInit, $targetDir . $kintInit);
        }
    }
}
