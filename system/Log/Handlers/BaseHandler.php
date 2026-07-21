<?php

declare(strict_types=1);



namespace CodeIgniter\Log\Handlers;


abstract class BaseHandler implements HandlerInterface
{
    
    protected $handles;

    
    protected $dateFormat = 'Y-m-d H:i:s';

    
    public function __construct(array $config)
    {
        $this->handles = $config['handles'] ?? [];
    }

    
    public function canHandle(string $level): bool
    {
        return in_array($level, $this->handles, true);
    }

    
    public function setDateFormat(string $format): HandlerInterface
    {
        $this->dateFormat = $format;

        return $this;
    }
}
