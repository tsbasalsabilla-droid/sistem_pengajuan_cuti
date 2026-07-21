<?php

declare(strict_types=1);



namespace CodeIgniter\CLI;

use Closure;


trait SignalTrait
{
    
    private bool $running = true;

    
    private bool $signalsBlocked = false;

    
    private array $registeredSignals = [];

    
    private array $signalMethodMap = [];

    
    private static ?bool $isPcntlAvailable = null;

    
    private static ?bool $isPosixAvailable = null;

    
    protected function isPcntlAvailable(): bool
    {
        if (self::$isPcntlAvailable === null) {
            if (is_windows()) {
                self::$isPcntlAvailable = false;
            } else {
                self::$isPcntlAvailable = extension_loaded('pcntl');
                if (! self::$isPcntlAvailable) {
                    CLI::write(lang('CLI.signals.noPcntlExtension'), 'yellow');
                }
            }
        }

        return self::$isPcntlAvailable;
    }

    
    protected function isPosixAvailable(): bool
    {
        if (self::$isPosixAvailable === null) {
            self::$isPosixAvailable = is_windows() ? false : extension_loaded('posix');
        }

        return self::$isPosixAvailable;
    }

    
    protected function registerSignals(
        array $signals = [],
        array $methodMap = [],
    ): void {
        if (! $this->isPcntlAvailable()) {
            return;
        }

        if ($signals === []) {
            $signals = [SIGTERM, SIGINT, SIGHUP, SIGQUIT];
        }

        if (! $this->isPosixAvailable() && (in_array(SIGTSTP, $signals, true) || in_array(SIGCONT, $signals, true))) {
            CLI::write(lang('CLI.signals.noPosixExtension'), 'yellow');
            $signals = array_diff($signals, [SIGTSTP, SIGCONT]);

            
            unset($methodMap[SIGTSTP], $methodMap[SIGCONT]);

            if ($signals === []) {
                return;
            }
        }

        
        pcntl_async_signals(true);

        $this->signalMethodMap = $methodMap;

        foreach ($signals as $signal) {
            if (pcntl_signal($signal, [$this, 'handleSignal'])) {
                $this->registeredSignals[] = $signal;
            } else {
                $signal = $this->getSignalName($signal);
                CLI::write(lang('CLI.signals.failedSignal', [$signal]), 'red');
            }
        }
    }

    
    protected function handleSignal(int $signal): void
    {
        $this->callCustomHandler($signal);

        
        switch ($signal) {
            case SIGTERM:
            case SIGINT:
            case SIGQUIT:
            case SIGHUP:
                $this->running = false;
                break;

            case SIGTSTP:
                
                pcntl_signal(SIGTSTP, SIG_DFL);
                posix_kill(posix_getpid(), SIGTSTP);
                break;

            case SIGCONT:
                
                pcntl_signal(SIGTSTP, [$this, 'handleSignal']);
                break;
        }
    }

    
    private function callCustomHandler(int $signal): void
    {
        
        $method = $this->signalMethodMap[$signal] ?? null;

        if ($method !== null && method_exists($this, $method)) {
            $this->{$method}($signal);

            return;
        }

        
        if (method_exists($this, 'onInterruption')) {
            $this->onInterruption($signal);
        }
    }

    
    protected function shouldTerminate(): bool
    {
        return ! $this->running;
    }

    
    protected function isRunning(): bool
    {
        return $this->running;
    }

    
    protected function requestTermination(): void
    {
        $this->running = false;
    }

    
    protected function resetState(): void
    {
        $this->running = true;

        
        if ($this->signalsBlocked) {
            $this->unblockSignals();
        }
    }

    
    protected function withSignalsBlocked(Closure $operation)
    {
        $this->blockSignals();

        try {
            return $operation();
        } finally {
            $this->unblockSignals();
        }
    }

    
    protected function blockSignals(): void
    {
        if (! $this->signalsBlocked && $this->isPcntlAvailable()) {
            
            pcntl_sigprocmask(SIG_BLOCK, [
                SIGTERM, SIGINT, SIGHUP, SIGQUIT, 
                SIGTSTP, SIGCONT,                 
                SIGUSR1, SIGUSR2,                 
                SIGPIPE, SIGALRM,                 
            ]);
            $this->signalsBlocked = true;
        }
    }

    
    protected function unblockSignals(): void
    {
        if ($this->signalsBlocked && $this->isPcntlAvailable()) {
            
            pcntl_sigprocmask(SIG_UNBLOCK, [
                SIGTERM, SIGINT, SIGHUP, SIGQUIT, 
                SIGTSTP, SIGCONT,                 
                SIGUSR1, SIGUSR2,                 
                SIGPIPE, SIGALRM,                 
            ]);
            $this->signalsBlocked = false;
        }
    }

    
    protected function signalsBlocked(): bool
    {
        return $this->signalsBlocked;
    }

    
    protected function mapSignal(int $signal, string $method): void
    {
        $this->signalMethodMap[$signal] = $method;
    }

    
    protected function getSignalName(int $signal): string
    {
        return match ($signal) {
            SIGTERM => 'SIGTERM',
            SIGINT  => 'SIGINT',
            SIGHUP  => 'SIGHUP',
            SIGQUIT => 'SIGQUIT',
            SIGUSR1 => 'SIGUSR1',
            SIGUSR2 => 'SIGUSR2',
            SIGPIPE => 'SIGPIPE',
            SIGALRM => 'SIGALRM',
            SIGTSTP => 'SIGTSTP',
            SIGCONT => 'SIGCONT',
            default => "Signal {$signal}",
        };
    }

    
    protected function unregisterSignals(): void
    {
        if (! $this->isPcntlAvailable()) {
            return;
        }

        foreach ($this->registeredSignals as $signal) {
            pcntl_signal($signal, SIG_DFL);
        }

        $this->registeredSignals = [];
        $this->signalMethodMap   = [];
    }

    
    protected function hasSignals(): bool
    {
        return $this->registeredSignals !== [];
    }

    
    protected function getSignals(): array
    {
        return $this->registeredSignals;
    }

    
    protected function getProcessState(): array
    {
        $pid   = getmypid();
        $state = [
            
            'pid'     => $pid,
            'running' => $this->running,

            
            'pcntl_available'          => $this->isPcntlAvailable(),
            'registered_signals'       => count($this->registeredSignals),
            'registered_signals_names' => array_map([$this, 'getSignalName'], $this->registeredSignals),
            'signals_blocked'          => $this->signalsBlocked,
            'explicit_mappings'        => count($this->signalMethodMap),

            
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'memory_peak_mb'  => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        ];

        
        if ($this->isPosixAvailable()) {
            $state['session_id']               = posix_getsid($pid);
            $state['process_group']            = posix_getpgid($pid);
            $state['has_controlling_terminal'] = posix_isatty(STDIN);
        }

        return $state;
    }
}
