<?php

declare(strict_types=1);



namespace CodeIgniter\Session\Handlers;

use CodeIgniter\I18n\Time;
use CodeIgniter\Session\Exceptions\SessionException;
use Config\Session as SessionConfig;


class FileHandler extends BaseHandler
{
    
    protected $savePath;

    
    protected $fileHandle;

    
    protected $filePath;

    
    protected $fileNew;

    
    protected $matchIP = false;

    
    protected $sessionIDRegex = '';

    public function __construct(SessionConfig $config, string $ipAddress)
    {
        parent::__construct($config, $ipAddress);

        if ($this->savePath !== '') {
            $this->savePath = rtrim($this->savePath, '/\\');
            ini_set('session.save_path', $this->savePath);
        } else {
            $sessionPath = rtrim(ini_get('session.save_path'), '/\\');

            if ($sessionPath === '') {
                $sessionPath = WRITEPATH . 'session';
            }

            $this->savePath = $sessionPath;
        }

        $this->configureSessionIDRegex();
    }

    
    public function open($path, $name): bool
    {
        if (! is_dir($path) && ! mkdir($path, 0700, true)) {
            throw SessionException::forInvalidSavePath($this->savePath);
        }

        if (! is_writable($path)) {
            throw SessionException::forWriteProtectedSavePath($this->savePath);
        }

        $this->savePath = $path;

        
        $this->filePath = $this->savePath . '/' . $name . ($this->matchIP ? md5($this->ipAddress) : '');

        return true;
    }

    
    public function read($id): false|string
    {
        
        
        if ($this->fileHandle === null) {
            $this->fileNew = ! is_file($this->filePath . $id);

            if (($this->fileHandle = fopen($this->filePath . $id, 'c+b')) === false) {
                $this->logger->error("Session: Unable to open file '" . $this->filePath . $id . "'.");

                return false;
            }

            if (flock($this->fileHandle, LOCK_EX) === false) {
                $this->logger->error("Session: Unable to obtain lock for file '" . $this->filePath . $id . "'.");
                fclose($this->fileHandle);
                $this->fileHandle = null;

                return false;
            }

            if (! isset($this->sessionID)) {
                $this->sessionID = $id;
            }

            if ($this->fileNew) {
                chmod($this->filePath . $id, 0600);
                $this->fingerprint = md5('');

                return '';
            }
        } else {
            rewind($this->fileHandle);
        }

        $data   = '';
        $buffer = 0;
        clearstatcache(); 

        for ($read = 0, $length = filesize($this->filePath . $id); $read < $length; $read += strlen($buffer)) {
            if (($buffer = fread($this->fileHandle, $length - $read)) === false) {
                break;
            }

            $data .= $buffer;
        }

        $this->fingerprint = md5($data);

        return $data;
    }

    
    public function write($id, $data): bool
    {
        
        if ($id !== $this->sessionID) {
            $this->sessionID = $id;
        }

        if (! is_resource($this->fileHandle)) {
            return false;
        }

        if ($this->fingerprint === md5($data)) {
            return ($this->fileNew) ? true : touch($this->filePath . $id);
        }

        if (! $this->fileNew) {
            ftruncate($this->fileHandle, 0);
            rewind($this->fileHandle);
        }

        if (($length = strlen($data)) > 0) {
            $result = null;

            $written = 0;

            for (; $written < $length; $written += $result) {
                if (($result = fwrite($this->fileHandle, substr($data, $written))) === false) {
                    break;
                }
            }

            if (! is_int($result)) {
                $this->fingerprint = md5(substr($data, 0, $written));
                $this->logger->error('Session: Unable to write data.');

                return false;
            }
        }

        $this->fingerprint = md5($data);

        return true;
    }

    
    public function close(): bool
    {
        if (is_resource($this->fileHandle)) {
            flock($this->fileHandle, LOCK_UN);
            fclose($this->fileHandle);

            $this->fileHandle = null;
            $this->fileNew    = false;
        }

        return true;
    }

    
    public function destroy($id): bool
    {
        if ($this->close()) {
            return is_file($this->filePath . $id)
                ? (unlink($this->filePath . $id) && $this->destroyCookie())
                : true;
        }

        if ($this->filePath !== null) {
            clearstatcache();

            return is_file($this->filePath . $id)
                ? (unlink($this->filePath . $id) && $this->destroyCookie())
                : true;
        }

        return false;
    }

    
    public function gc($max_lifetime): false|int
    {
        if (! is_dir($this->savePath) || ($directory = opendir($this->savePath)) === false) {
            $this->logger->debug("Session: Garbage collector couldn't list files under directory '" . $this->savePath . "'.");

            return false;
        }

        $ts = Time::now()->getTimestamp() - $max_lifetime;

        $pattern = $this->matchIP === true ? '[0-9a-f]{32}' : '';

        $pattern = sprintf(
            '#\A%s' . $pattern . $this->sessionIDRegex . '\z#',
            preg_quote($this->cookieName, '#'),
        );

        $collected = 0;

        while (($file = readdir($directory)) !== false) {
            
            if (preg_match($pattern, $file) !== 1
                || ! is_file($this->savePath . DIRECTORY_SEPARATOR . $file)
                || ($mtime = filemtime($this->savePath . DIRECTORY_SEPARATOR . $file)) === false
                || $mtime > $ts
            ) {
                continue;
            }

            unlink($this->savePath . DIRECTORY_SEPARATOR . $file);
            $collected++;
        }

        closedir($directory);

        return $collected;
    }

    
    protected function configureSessionIDRegex()
    {
        $bitsPerCharacter = (int) ini_get('session.sid_bits_per_character');
        $sidLength        = (int) ini_get('session.sid_length');

        
        if (PHP_VERSION_ID < 90000) {
            if ($bitsPerCharacter !== 4) {
                ini_set('session.sid_bits_per_character', '4');
            }
            if ($sidLength !== 32) {
                ini_set('session.sid_length', '32');
            }
        }

        $this->sessionIDRegex = '[0-9a-f]{32}';
    }
}
