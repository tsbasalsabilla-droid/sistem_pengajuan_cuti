<?php

declare(strict_types=1);



namespace CodeIgniter\HTTP;

use CodeIgniter\Cookie\Cookie;
use CodeIgniter\Cookie\CookieStore;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use Config\App;
use Config\Cookie as CookieConfig;


class Response extends Message implements ResponseInterface
{
    use ResponseTrait;

    
    protected static $statusCodes = [
        
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing', 
        103 => 'Early Hints', 
        
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information', 
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status', 
        208 => 'Already Reported', 
        226 => 'IM Used', 
        
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found', 
        303 => 'See Other', 
        304 => 'Not Modified',
        305 => 'Use Proxy', 
        306 => 'Switch Proxy', 
        307 => 'Temporary Redirect', 
        308 => 'Permanent Redirect', 
        
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Content Too Large', 
        414 => 'URI Too Long', 
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => "I'm a teapot", 
        
        421 => 'Misdirected Request', 
        422 => 'Unprocessable Content', 
        423 => 'Locked', 
        424 => 'Failed Dependency', 
        425 => 'Too Early', 
        426 => 'Upgrade Required',
        428 => 'Precondition Required', 
        429 => 'Too Many Requests', 
        431 => 'Request Header Fields Too Large', 
        451 => 'Unavailable For Legal Reasons', 
        499 => 'Client Closed Request', 
        
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates', 
        507 => 'Insufficient Storage', 
        508 => 'Loop Detected', 
        510 => 'Not Extended', 
        511 => 'Network Authentication Required', 
        599 => 'Network Connect Timeout Error', 
    ];

    
    protected $reason = '';

    
    protected $statusCode = 200;

    
    protected $pretend = false;

    
    public function __construct($config) 
    {
        
        
        $this->noCache();

        
        $this->CSP = service('csp');

        $this->cookieStore = new CookieStore([]);

        $cookie = config(CookieConfig::class);

        Cookie::setDefaults($cookie);

        
        $this->setContentType('text/html');
    }

    
    public function pretend(bool $pretend = true)
    {
        $this->pretend = $pretend;

        return $this;
    }

    
    public function getStatusCode(): int
    {
        if (empty($this->statusCode)) {
            throw HTTPException::forMissingResponseStatus();
        }

        return $this->statusCode;
    }

    
    public function getReasonPhrase()
    {
        if ($this->reason === '') {
            return empty($this->statusCode) ? '' : static::$statusCodes[$this->statusCode];
        }

        return $this->reason;
    }
}
