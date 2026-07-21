<?php

declare(strict_types=1);



namespace CodeIgniter\Config;

use Laminas\Escaper\Escaper;
use Laminas\Escaper\EscaperInterface;
use Laminas\Escaper\Exception\ExceptionInterface;
use Laminas\Escaper\Exception\InvalidArgumentException as EscaperInvalidArgumentException;
use Laminas\Escaper\Exception\RuntimeException;
use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;


class AutoloadConfig
{
    
    public $psr4 = [];

    
    public $classmap = [];

    
    public $files = [];

    
    protected $corePsr4 = [
        'CodeIgniter' => SYSTEMPATH,
        'Config'      => APPPATH . 'Config',
    ];

    
    protected $coreClassmap = [
        AbstractLogger::class                  => SYSTEMPATH . 'ThirdParty/PSR/Log/AbstractLogger.php',
        InvalidArgumentException::class        => SYSTEMPATH . 'ThirdParty/PSR/Log/InvalidArgumentException.php',
        LoggerAwareInterface::class            => SYSTEMPATH . 'ThirdParty/PSR/Log/LoggerAwareInterface.php',
        LoggerAwareTrait::class                => SYSTEMPATH . 'ThirdParty/PSR/Log/LoggerAwareTrait.php',
        LoggerInterface::class                 => SYSTEMPATH . 'ThirdParty/PSR/Log/LoggerInterface.php',
        LoggerTrait::class                     => SYSTEMPATH . 'ThirdParty/PSR/Log/LoggerTrait.php',
        LogLevel::class                        => SYSTEMPATH . 'ThirdParty/PSR/Log/LogLevel.php',
        NullLogger::class                      => SYSTEMPATH . 'ThirdParty/PSR/Log/NullLogger.php',
        ExceptionInterface::class              => SYSTEMPATH . 'ThirdParty/Escaper/Exception/ExceptionInterface.php',
        EscaperInvalidArgumentException::class => SYSTEMPATH . 'ThirdParty/Escaper/Exception/InvalidArgumentException.php',
        RuntimeException::class                => SYSTEMPATH . 'ThirdParty/Escaper/Exception/RuntimeException.php',
        EscaperInterface::class                => SYSTEMPATH . 'ThirdParty/Escaper/EscaperInterface.php',
        Escaper::class                         => SYSTEMPATH . 'ThirdParty/Escaper/Escaper.php',
    ];

    
    protected $coreFiles = [];

    
    public function __construct()
    {
        if (isset($_SERVER['CI_ENVIRONMENT']) && $_SERVER['CI_ENVIRONMENT'] === 'testing') {
            $this->psr4['Tests\Support']                  = SUPPORTPATH;
            $this->classmap['CodeIgniter\Log\TestLogger'] = SYSTEMPATH . 'Test/TestLogger.php';
            $this->classmap['CIDatabaseTestCase']         = SYSTEMPATH . 'Test/CIDatabaseTestCase.php';
        }

        $this->psr4     = array_merge($this->corePsr4, $this->psr4);
        $this->classmap = array_merge($this->coreClassmap, $this->classmap);
        $this->files    = [...$this->coreFiles, ...$this->files];
    }
}
