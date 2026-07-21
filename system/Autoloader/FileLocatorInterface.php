<?php

declare(strict_types=1);



namespace CodeIgniter\Autoloader;


interface FileLocatorInterface
{
    
    public function locateFile(string $file, ?string $folder = null, string $ext = 'php');

    
    public function getClassname(string $file): string;

    
    public function search(string $path, string $ext = 'php', bool $prioritizeApp = true): array;

    
    public function findQualifiedNameFromPath(string $path);

    
    public function listFiles(string $path): array;

    
    public function listNamespaceFiles(string $prefix, string $path): array;
}
