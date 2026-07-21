<?php

declare(strict_types=1);



namespace CodeIgniter\Test;

use CodeIgniter\Log\Logger;
use Stringable;


class TestLogger extends Logger
{
    
    protected static $op_logs = [];

    
    public function log($level, string|Stringable $message, array $context = []): void
    {
        
        
        $logMessage = $this->interpolate($message, $context);

        
        
        $trace = debug_backtrace();
        $file  = null;

        foreach ($trace as $row) {
            if (! in_array($row['function'], ['log', 'log_message'], true)) {
                $file = basename($row['file'] ?? '');
                break;
            }
        }

        self::$op_logs[] = [
            'level'   => $level,
            'message' => $logMessage,
            'file'    => $file,
        ];

        
        parent::log($level, $message, $context);
    }

    
    public static function didLog(string $level, $message, bool $useExactComparison = true)
    {
        $lowerLevel = strtolower($level);

        foreach (self::$op_logs as $log) {
            if (strtolower($log['level']) !== $lowerLevel) {
                continue;
            }

            if ($useExactComparison) {
                if ($log['message'] === $message) {
                    return true;
                }

                continue;
            }

            if (str_contains($log['message'], $message)) {
                return true;
            }
        }

        return false;
    }
}
