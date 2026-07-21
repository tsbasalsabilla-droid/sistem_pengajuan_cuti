<?php

declare(strict_types=1);



namespace CodeIgniter\Files;

use CodeIgniter\Files\Exceptions\FileException;
use CodeIgniter\Files\Exceptions\FileNotFoundException;
use CodeIgniter\I18n\Time;
use Config\Mimes;
use RuntimeException;
use SplFileInfo;


class File extends SplFileInfo
{
    
    protected $size;

    
    protected $originalMimeType;

    
    public function __construct(string $path, bool $checkFile = false)
    {
        if ($checkFile && ! is_file($path)) {
            throw FileNotFoundException::forFileNotFound($path);
        }

        parent::__construct($path);
    }

    
    public function getSize(): false|int
    {
        return $this->size ?? ($this->size = parent::getSize());
    }

    
    public function getSizeByBinaryUnit(FileSizeUnit $unit = FileSizeUnit::B, int $precision = 3): int|string
    {
        return $this->getSizeByUnitInternal(1024, $unit, $precision);
    }

    
    public function getSizeByMetricUnit(FileSizeUnit $unit = FileSizeUnit::B, int $precision = 3): int|string
    {
        return $this->getSizeByUnitInternal(1000, $unit, $precision);
    }

    
    public function getSizeByUnit(string $unit = 'b')
    {
        return match (strtolower($unit)) {
            'kb'    => $this->getSizeByBinaryUnit(FileSizeUnit::KB),
            'mb'    => $this->getSizeByBinaryUnit(FileSizeUnit::MB),
            default => $this->getSize(),
        };
    }

    
    public function guessExtension(): ?string
    {
        
        $pathinfo = pathinfo($this->getRealPath() ?: $this->__toString()) + ['extension' => ''];

        $proposedExtension = $pathinfo['extension'];

        return Mimes::guessExtensionFromType($this->getMimeType(), $proposedExtension);
    }

    
    public function getMimeType(): string
    {
        if (! function_exists('finfo_open')) {
            return $this->originalMimeType ?? 'application/octet-stream'; 
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        return finfo_file($finfo, $this->getRealPath() ?: $this->__toString());
    }

    
    public function getRandomName(): string
    {
        $extension = $this->getExtension();
        $extension = empty($extension) ? '' : '.' . $extension;

        return Time::now()->getTimestamp() . '_' . bin2hex(random_bytes(10)) . $extension;
    }

    
    public function move(string $targetPath, ?string $name = null, bool $overwrite = false)
    {
        $targetPath = rtrim($targetPath, '/') . '/';
        $name ??= $this->getBasename();
        $destination = $overwrite ? $targetPath . $name : $this->getDestination($targetPath . $name);

        $oldName = $this->getRealPath() ?: $this->__toString();

        if (! @rename($oldName, $destination)) {
            $error = error_get_last();

            throw FileException::forUnableToMove($this->getBasename(), $targetPath, strip_tags($error['message']));
        }

        @chmod($destination, 0777 & ~umask());

        return new self($destination);
    }

    
    public function getDestination(string $destination, string $delimiter = '_', int $i = 0): string
    {
        if ($delimiter === '') {
            $delimiter = '_';
        }

        while (is_file($destination)) {
            $info      = pathinfo($destination);
            $extension = isset($info['extension']) ? '.' . $info['extension'] : '';

            if (str_contains($info['filename'], $delimiter)) {
                $parts = explode($delimiter, $info['filename']);

                if (is_numeric(end($parts))) {
                    $i = end($parts);
                    array_pop($parts);
                    $parts[]     = ++$i;
                    $destination = $info['dirname'] . DIRECTORY_SEPARATOR . implode($delimiter, $parts) . $extension;
                } else {
                    $destination = $info['dirname'] . DIRECTORY_SEPARATOR . $info['filename'] . $delimiter . ++$i . $extension;
                }
            } else {
                $destination = $info['dirname'] . DIRECTORY_SEPARATOR . $info['filename'] . $delimiter . ++$i . $extension;
            }
        }

        return $destination;
    }

    private function getSizeByUnitInternal(int $fileSizeBase, FileSizeUnit $unit, int $precision): int|string
    {
        $exponent = $unit->value;
        $divider  = $fileSizeBase ** $exponent;
        $size     = $this->getSize() / $divider;

        if ($unit !== FileSizeUnit::B) {
            $size = number_format($size, $precision);
        }

        return $size;
    }
}
