<?php

declare(strict_types=1);



namespace CodeIgniter\Log\Handlers;

use CodeIgniter\HTTP\ResponseInterface;


class ChromeLoggerHandler extends BaseHandler
{
    
    public const VERSION = 1.0;

    
    protected $backtraceLevel = 0;

    
    protected $json = [
        'version' => self::VERSION,
        'columns' => [
            'log',
            'backtrace',
            'type',
        ],
        'rows' => [],
    ];

    
    protected $header = 'X-ChromeLogger-Data';

    
    protected $levels = [
        'emergency' => 'error',
        'alert'     => 'error',
        'critical'  => 'error',
        'error'     => 'error',
        'warning'   => 'warn',
        'notice'    => 'warn',
        'info'      => 'info',
        'debug'     => 'info',
    ];

    
    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $this->json['request_uri'] = current_url();
    }

    
    public function handle($level, $message): bool
    {
        $message = $this->format($message);

        $backtrace = debug_backtrace(0, $this->backtraceLevel);
        $backtrace = end($backtrace);

        $backtraceMessage = 'unknown';
        if (isset($backtrace['file'], $backtrace['line'])) {
            $backtraceMessage = $backtrace['file'] . ':' . $backtrace['line'];
        }

        
        $type = '';

        if (array_key_exists($level, $this->levels)) {
            $type = $this->levels[$level];
        }

        $this->json['rows'][] = [[$message], $backtraceMessage, $type];

        $this->sendLogs();

        return true;
    }

    
    protected function format($object)
    {
        if (! is_object($object)) {
            return $object;
        }

        
        $objectArray = (array) $object;

        $objectArray['___class_name'] = $object::class;

        return $objectArray;
    }

    
    public function sendLogs(?ResponseInterface &$response = null)
    {
        if (! $response instanceof ResponseInterface) {
            $response = service('response', null, true);
        }

        $data = base64_encode(
            mb_convert_encoding(json_encode($this->json), 'UTF-8', mb_list_encodings()),
        );

        $response->setHeader($this->header, $data);
    }
}
