<?php

declare(strict_types=1);



namespace CodeIgniter\Log\Handlers;

use CodeIgniter\Log\Exceptions\LogException;


class ErrorlogHandler extends BaseHandler
{
    
    public const TYPE_OS = 0;

    
    public const TYPE_SAPI = 4;

    
    protected $messageType = 0;

    
    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $messageType = $config['messageType'] ?? self::TYPE_OS;

        if (! is_int($messageType) || ! in_array($messageType, [self::TYPE_OS, self::TYPE_SAPI], true)) {
            throw LogException::forInvalidMessageType(print_r($messageType, true));
        }

        $this->messageType = $messageType;
    }

    
    public function handle($level, $message): bool
    {
        $message = strtoupper($level) . ' --> ' . $message . "\n";

        return $this->errorLog($message, $this->messageType);
    }

    
    protected function errorLog(string $message, int $messageType): bool
    {
        return error_log($message, $messageType);
    }
}
