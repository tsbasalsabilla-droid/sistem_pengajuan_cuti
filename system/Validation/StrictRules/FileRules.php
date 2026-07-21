<?php

declare(strict_types=1);



namespace CodeIgniter\Validation\StrictRules;

use CodeIgniter\Exceptions\InvalidArgumentException;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use Config\Mimes;


class FileRules
{
    
    protected $request;

    
    public function __construct(?RequestInterface $request = null)
    {
        if (! $request instanceof RequestInterface) {
            $request = service('request');
        }

        assert($request instanceof IncomingRequest || $request instanceof CLIRequest);

        $this->request = $request;
    }

    
    public function uploaded(?string $blank, string $name): bool
    {
        $files = $this->request->getFileMultiple($name);
        if ($files === null) {
            $files = [$this->request->getFile($name)];
        }

        foreach ($files as $file) {
            if ($file === null) {
                return false;
            }

            if (ENVIRONMENT === 'testing') {
                if ($file->getError() !== 0) {
                    return false;
                }
            } else {
                
                
                if (! $file->isValid()) {
                    return false;
                }
                
            }
        }

        return true;
    }

    
    public function max_size(?string $blank, string $params): bool
    {
        
        
        $paramArray = explode(',', $params);
        if (count($paramArray) !== 2) {
            throw new InvalidArgumentException('Invalid max_size parameter: "' . $params . '"');
        }
        $name = array_shift($paramArray);

        $files = $this->request->getFileMultiple($name);
        if ($files === null) {
            $files = [$this->request->getFile($name)];
        }

        foreach ($files as $file) {
            if ($file === null) {
                return false;
            }

            if ($file->getError() === UPLOAD_ERR_NO_FILE) {
                return true;
            }

            if ($file->getError() === UPLOAD_ERR_INI_SIZE) {
                return false;
            }

            if ($file->getSize() / 1024 > $paramArray[0]) {
                return false;
            }
        }

        return true;
    }

    
    public function is_image(?string $blank, string $params): bool
    {
        
        
        $params = explode(',', $params);
        $name   = array_shift($params);

        $files = $this->request->getFileMultiple($name);
        if ($files === null) {
            $files = [$this->request->getFile($name)];
        }

        foreach ($files as $file) {
            if ($file === null) {
                return false;
            }

            if ($file->getError() === UPLOAD_ERR_NO_FILE) {
                return true;
            }

            
            
            $type = Mimes::guessTypeFromExtension($file->getExtension()) ?? '';

            if (mb_strpos($type, 'image') !== 0) {
                return false;
            }
        }

        return true;
    }

    
    public function mime_in(?string $blank, string $params): bool
    {
        
        
        $params = explode(',', $params);
        $name   = array_shift($params);

        $files = $this->request->getFileMultiple($name);
        if ($files === null) {
            $files = [$this->request->getFile($name)];
        }

        foreach ($files as $file) {
            if ($file === null) {
                return false;
            }

            if ($file->getError() === UPLOAD_ERR_NO_FILE) {
                return true;
            }

            if (! in_array($file->getMimeType(), $params, true)) {
                return false;
            }
        }

        return true;
    }

    
    public function ext_in(?string $blank, string $params): bool
    {
        
        
        $params = explode(',', $params);
        $name   = array_shift($params);

        $files = $this->request->getFileMultiple($name);
        if ($files === null) {
            $files = [$this->request->getFile($name)];
        }

        foreach ($files as $file) {
            if ($file === null) {
                return false;
            }

            if ($file->getError() === UPLOAD_ERR_NO_FILE) {
                return true;
            }

            if (! in_array($file->guessExtension(), $params, true)) {
                return false;
            }
        }

        return true;
    }

    
    public function max_dims(?string $blank, string $params): bool
    {
        
        
        $params = explode(',', $params);
        $name   = array_shift($params);

        $files = $this->request->getFileMultiple($name);
        if ($files === null) {
            $files = [$this->request->getFile($name)];
        }

        foreach ($files as $file) {
            if ($file === null) {
                return false;
            }

            if ($file->getError() === UPLOAD_ERR_NO_FILE) {
                return true;
            }

            
            $allowedWidth  = $params[0] ?? 0;
            $allowedHeight = $params[1] ?? 0;

            
            $info = getimagesize($file->getTempName());

            if ($info === false) {
                
                return false;
            }

            $fileWidth  = $info[0];
            $fileHeight = $info[1];

            if ($fileWidth > $allowedWidth || $fileHeight > $allowedHeight) {
                return false;
            }
        }

        return true;
    }

    
    public function min_dims(?string $blank, string $params): bool
    {
        
        
        $params = explode(',', $params);
        $name   = array_shift($params);

        $files = $this->request->getFileMultiple($name);
        if ($files === null) {
            $files = [$this->request->getFile($name)];
        }

        foreach ($files as $file) {
            if ($file === null) {
                return false;
            }

            if ($file->getError() === UPLOAD_ERR_NO_FILE) {
                return true;
            }

            
            $minimumWidth  = $params[0] ?? 0;
            $minimumHeight = $params[1] ?? 0;

            
            $info = getimagesize($file->getTempName());

            if ($info === false) {
                
                return false;
            }

            $fileWidth  = $info[0];
            $fileHeight = $info[1];

            if ($fileWidth < $minimumWidth || $fileHeight < $minimumHeight) {
                return false;
            }
        }

        return true;
    }
}
