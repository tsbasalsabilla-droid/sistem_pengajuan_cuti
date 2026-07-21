<?php

declare(strict_types=1);



namespace CodeIgniter\HTTP\Files;

use CodeIgniter\Files\File;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use Config\Mimes;
use Exception;


class UploadedFile extends File implements UploadedFileInterface
{
    
    protected $path;

    
    protected $clientPath;

    
    protected $originalName;

    
    protected $name;

    
    protected $originalMimeType;

    
    protected $error;

    
    protected $hasMoved = false;

    
    public function __construct(string $path, string $originalName, ?string $mimeType = null, ?int $size = null, ?int $error = null, ?string $clientPath = null)
    {
        $this->path             = $path;
        $this->name             = $originalName;
        $this->originalName     = $originalName;
        $this->originalMimeType = $mimeType;
        $this->size             = $size;
        $this->error            = $error;
        $this->clientPath       = $clientPath;

        parent::__construct($path, false);
    }

    
    public function move(string $targetPath, ?string $name = null, bool $overwrite = false)
    {
        $targetPath = rtrim($targetPath, '/') . '/';
        $targetPath = $this->setPath($targetPath); 

        if ($this->hasMoved) {
            throw HTTPException::forAlreadyMoved();
        }

        if (! $this->isValid()) {
            throw HTTPException::forInvalidFile();
        }

        $name ??= $this->getName();
        $destination = $overwrite ? $targetPath . $name : $this->getDestination($targetPath . $name);

        try {
            $this->hasMoved = move_uploaded_file($this->path, $destination);
        } catch (Exception) {
            $error   = error_get_last();
            $message = strip_tags($error['message'] ?? '');

            throw HTTPException::forMoveFailed(basename($this->path), $targetPath, $message);
        }

        if ($this->hasMoved === false) {
            $message = 'move_uploaded_file() returned false';

            throw HTTPException::forMoveFailed(basename($this->path), $targetPath, $message);
        }

        @chmod($targetPath, 0777 & ~umask());

        
        $this->path = $targetPath;
        $this->name = basename($destination);

        return true;
    }

    
    protected function setPath(string $path): string
    {
        if (! is_dir($path)) {
            mkdir($path, 0777, true);
            
            if (! is_file($path . 'index.html')) {
                $file = fopen($path . 'index.html', 'x+b');
                fclose($file);
            }
        }

        return $path;
    }

    
    public function hasMoved(): bool
    {
        return $this->hasMoved;
    }

    
    public function getError(): int
    {
        return $this->error ?? UPLOAD_ERR_OK;
    }

    
    public function getErrorString(): string
    {
        $errors = [
            UPLOAD_ERR_OK         => lang('HTTP.uploadErrOk'),
            UPLOAD_ERR_INI_SIZE   => lang('HTTP.uploadErrIniSize'),
            UPLOAD_ERR_FORM_SIZE  => lang('HTTP.uploadErrFormSize'),
            UPLOAD_ERR_PARTIAL    => lang('HTTP.uploadErrPartial'),
            UPLOAD_ERR_NO_FILE    => lang('HTTP.uploadErrNoFile'),
            UPLOAD_ERR_CANT_WRITE => lang('HTTP.uploadErrCantWrite'),
            UPLOAD_ERR_NO_TMP_DIR => lang('HTTP.uploadErrNoTmpDir'),
            UPLOAD_ERR_EXTENSION  => lang('HTTP.uploadErrExtension'),
        ];

        $error = $this->error ?? UPLOAD_ERR_OK;

        return sprintf($errors[$error] ?? lang('HTTP.uploadErrUnknown'), $this->getName());
    }

    
    public function getClientMimeType(): string
    {
        return $this->originalMimeType;
    }

    
    public function getName(): string
    {
        return $this->name;
    }

    
    public function getClientName(): string
    {
        return $this->originalName;
    }

    
    public function getClientPath(): ?string
    {
        return $this->clientPath;
    }

    
    public function getTempName(): string
    {
        return $this->path;
    }

    
    public function getExtension(): string
    {
        $guessExtension = $this->guessExtension();

        return $guessExtension !== '' ? $guessExtension : $this->getClientExtension();
    }

    
    public function guessExtension(): string
    {
        return Mimes::guessExtensionFromType($this->getMimeType(), $this->getClientExtension()) ?? '';
    }

    
    public function getClientExtension(): string
    {
        return pathinfo($this->originalName, PATHINFO_EXTENSION);
    }

    
    public function isValid(): bool
    {
        return is_uploaded_file($this->path) && $this->error === UPLOAD_ERR_OK;
    }

    
    public function store(?string $folderName = null, ?string $fileName = null): string
    {
        $folderName = rtrim($folderName ?? date('Ymd'), '/') . '/';
        $fileName ??= $this->getRandomName();

        
        $this->move(WRITEPATH . 'uploads/' . $folderName, $fileName);

        return $folderName . $this->name;
    }
}
