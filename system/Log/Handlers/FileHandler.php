<?php

declare(strict_types=1);



namespace CodeIgniter\Log\Handlers;

use DateTime;
use Exception;


class FileHandler extends BaseHandler
{
    
    protected $path;

    
    protected $fileExtension;

    
    protected $filePermissions;

    
    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $defaults = ['path' => WRITEPATH . 'logs/', 'fileExtension' => 'log', 'filePermissions' => 0644];
        $config   = [...$defaults, ...$config];

        $this->path = $config['path'] === '' ? $defaults['path'] : $config['path'];

        $this->fileExtension = $config['fileExtension'] === ''
            ? $defaults['fileExtension']
            : ltrim($config['fileExtension'], '.');

        $this->filePermissions = $config['filePermissions'];
    }

    
    public function handle($level, $message): bool
    {
        $filepath = $this->path . 'log-' . date('Y-m-d') . '.' . $this->fileExtension;

        $msg = '';

        $newfile = false;
        if (! is_file($filepath)) {
            $newfile = true;

            
            if ($this->fileExtension === 'php') {
                $msg .= "<?php defined('SYSTEMPATH') || exit('No direct script access allowed'); ?>\n\n";
            }
        }

        if (! $fp = @fopen($filepath, 'ab')) {
            return false;
        }

        
        if (str_contains($this->dateFormat, 'u')) {
            $microtimeFull  = microtime(true);
            $microtimeShort = sprintf('%06d', ($microtimeFull - floor($microtimeFull)) * 1_000_000);
            $date           = new DateTime(date('Y-m-d H:i:s.' . $microtimeShort, (int) $microtimeFull));
            $date           = $date->format($this->dateFormat);
        } else {
            $date = date($this->dateFormat);
        }

        $msg .= strtoupper($level) . ' - ' . $date . ' --> ' . $message . "\n";

        flock($fp, LOCK_EX);

        $result = null;

        for ($written = 0, $length = strlen($msg); $written < $length; $written += $result) {
            if (($result = fwrite($fp, substr($msg, $written))) === false) {
                
                break; 
            }
        }

        flock($fp, LOCK_UN);
        fclose($fp);

        if ($newfile) {
            chmod($filepath, $this->filePermissions);
        }

        return is_int($result);
    }
}
