<?php

declare(strict_types=1);



namespace CodeIgniter\Images;

use CodeIgniter\Files\File;
use CodeIgniter\Images\Exceptions\ImageException;


class Image extends File
{
    
    public $origWidth;

    
    public $origHeight;

    
    public $imageType;

    
    public $sizeStr;

    
    public $mime;

    
    public function copy(string $targetPath, ?string $targetName = null, int $perms = 0644): bool
    {
        $targetPath = rtrim($targetPath, '/ ') . '/';

        $targetName ??= $this->getFilename();

        if (empty($targetName)) {
            throw ImageException::forInvalidFile($targetName);
        }

        if (! is_dir($targetPath)) {
            mkdir($targetPath, 0755, true);
        }

        if (! copy($this->getPathname(), "{$targetPath}{$targetName}")) {
            throw ImageException::forCopyError($targetPath);
        }

        chmod("{$targetPath}/{$targetName}", $perms);

        return true;
    }

    
    public function getProperties(bool $return = false)
    {
        $path = $this->getPathname();
        $vals = getimagesize($path);

        if ($vals === false) {
            throw ImageException::forFileNotSupported();
        }

        $types = [
            IMAGETYPE_GIF  => 'gif',
            IMAGETYPE_JPEG => 'jpeg',
            IMAGETYPE_PNG  => 'png',
            IMAGETYPE_WEBP => 'webp',
        ];

        $mime = 'image/' . ($types[$vals[2]] ?? 'jpg');

        if ($return) {
            return [
                'width'      => $vals[0],
                'height'     => $vals[1],
                'image_type' => $vals[2],
                'size_str'   => $vals[3],
                'mime_type'  => $mime,
            ];
        }

        $this->origWidth  = $vals[0];
        $this->origHeight = $vals[1];
        $this->imageType  = $vals[2];
        $this->sizeStr    = $vals[3];
        $this->mime       = $mime;

        return true;
    }
}
