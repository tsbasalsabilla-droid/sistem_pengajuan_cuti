<?php

declare(strict_types=1);



namespace CodeIgniter\HTTP\Files;

use RecursiveArrayIterator;
use RecursiveIteratorIterator;


class FileCollection
{
    
    protected $files;

    
    public function all()
    {
        $this->populateFiles();

        return $this->files;
    }

    
    public function getFile(string $name)
    {
        $this->populateFiles();

        if ($this->hasFile($name)) {
            if (str_contains($name, '.')) {
                $name         = explode('.', $name);
                $uploadedFile = $this->getValueDotNotationSyntax($name, $this->files);

                return $uploadedFile instanceof UploadedFile ? $uploadedFile : null;
            }

            if (array_key_exists($name, $this->files)) {
                $uploadedFile = $this->files[$name];

                return $uploadedFile instanceof UploadedFile ? $uploadedFile : null;
            }
        }

        return null;
    }

    
    public function getFileMultiple(string $name)
    {
        $this->populateFiles();

        if ($this->hasFile($name)) {
            if (str_contains($name, '.')) {
                $name         = explode('.', $name);
                $uploadedFile = $this->getValueDotNotationSyntax($name, $this->files);

                return (is_array($uploadedFile) && ($uploadedFile[array_key_first($uploadedFile)] instanceof UploadedFile)) ?
                    $uploadedFile : null;
            }

            if (array_key_exists($name, $this->files)) {
                $uploadedFile = $this->files[$name];

                return (is_array($uploadedFile) && ($uploadedFile[array_key_first($uploadedFile)] instanceof UploadedFile)) ?
                    $uploadedFile : null;
            }
        }

        return null;
    }

    
    public function hasFile(string $fileID): bool
    {
        $this->populateFiles();

        if (str_contains($fileID, '.')) {
            $segments = explode('.', $fileID);

            $el = $this->files;

            foreach ($segments as $segment) {
                if (! array_key_exists($segment, $el)) {
                    return false;
                }

                $el = $el[$segment];
            }

            return true;
        }

        return isset($this->files[$fileID]);
    }

    
    protected function populateFiles()
    {
        if (is_array($this->files)) {
            return;
        }

        $this->files = [];

        $files = service('superglobals')->getFilesArray();

        if ($files === []) {
            return;
        }

        $files = $this->fixFilesArray($files);

        foreach ($files as $name => $file) {
            $this->files[$name] = $this->createFileObject($file);
        }
    }

    
    protected function createFileObject(array $array)
    {
        if (! isset($array['name'])) {
            $output = [];

            foreach ($array as $key => $values) {
                if (! is_array($values)) {
                    continue;
                }

                $output[$key] = $this->createFileObject($values);
            }

            return $output;
        }

        return new UploadedFile(
            $array['tmp_name'] ?? null,
            $array['name'] ?? null,
            $array['type'] ?? null,
            ($array['size'] ?? null) === null ? null : (int) $array['size'],
            $array['error'] ?? null,
            $array['full_path'] ?? null,
        );
    }

    
    protected function fixFilesArray(array $data): array
    {
        $output = [];

        foreach ($data as $name => $array) {
            foreach ($array as $field => $value) {
                $pointer = &$output[$name];

                if (! is_array($value)) {
                    $pointer[$field] = $value;

                    continue;
                }

                $stack    = [&$pointer];
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveArrayIterator($value),
                    RecursiveIteratorIterator::SELF_FIRST,
                );

                foreach ($iterator as $key => $val) {
                    array_splice($stack, $iterator->getDepth() + 1);
                    $pointer = &$stack[count($stack) - 1];
                    $pointer = &$pointer[$key];
                    $stack[] = &$pointer;

                    
                    
                    
                    if (! $iterator->hasChildren()) {
                        $pointer[$field] = $val;
                    }
                }
            }
        }

        return $output;
    }

    
    protected function getValueDotNotationSyntax(array $index, array $value)
    {
        $currentIndex = array_shift($index);

        if (isset($currentIndex) && $index !== [] && array_key_exists($currentIndex, $value) && is_array($value[$currentIndex])) {
            return $this->getValueDotNotationSyntax($index, $value[$currentIndex]);
        }

        return $value[$currentIndex] ?? null;
    }
}
