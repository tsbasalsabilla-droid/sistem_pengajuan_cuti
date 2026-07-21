<?php

declare(strict_types=1);



namespace CodeIgniter\Exceptions;

use Throwable;


trait DebugTraceableTrait
{
    
    final public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $trace = $this->getTrace()[0];

        if (isset($trace['class']) && $trace['class'] === static::class) {
            [
                'line' => $this->line,
                'file' => $this->file,
            ] = $trace;
        }
    }
}
