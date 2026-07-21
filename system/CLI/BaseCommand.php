<?php

declare(strict_types=1);



namespace CodeIgniter\CLI;

use Config\Exceptions;
use Psr\Log\LoggerInterface;
use ReflectionException;
use Throwable;


abstract class BaseCommand
{
    
    protected $group;

    
    protected $name;

    
    protected $usage;

    
    protected $description;

    
    protected $options = [];

    
    protected $arguments = [];

    
    protected $logger;

    
    protected $commands;

    public function __construct(LoggerInterface $logger, Commands $commands)
    {
        $this->logger   = $logger;
        $this->commands = $commands;
    }

    
    abstract public function run(array $params);

    
    protected function call(string $command, array $params = [])
    {
        return $this->commands->run($command, $params);
    }

    
    protected function showError(Throwable $e)
    {
        $exception = $e;
        $message   = $e->getMessage();
        $config    = config(Exceptions::class);

        require $config->errorViewPath . '/cli/error_exception.php';
    }

    
    public function showHelp()
    {
        CLI::write(lang('CLI.helpUsage'), 'yellow');

        if ($this->usage !== null) {
            $usage = $this->usage;
        } else {
            $usage = $this->name;

            if ($this->arguments !== []) {
                $usage .= ' [arguments]';
            }
        }

        CLI::write($this->setPad($usage, 0, 0, 2));

        if ($this->description !== null) {
            CLI::newLine();
            CLI::write(lang('CLI.helpDescription'), 'yellow');
            CLI::write($this->setPad($this->description, 0, 0, 2));
        }

        if ($this->arguments !== []) {
            CLI::newLine();
            CLI::write(lang('CLI.helpArguments'), 'yellow');
            $length = max(array_map(strlen(...), array_keys($this->arguments)));

            foreach ($this->arguments as $argument => $description) {
                CLI::write(CLI::color($this->setPad($argument, $length, 2, 2), 'green') . $description);
            }
        }

        if ($this->options !== []) {
            CLI::newLine();
            CLI::write(lang('CLI.helpOptions'), 'yellow');
            $length = max(array_map(strlen(...), array_keys($this->options)));

            foreach ($this->options as $option => $description) {
                CLI::write(CLI::color($this->setPad($option, $length, 2, 2), 'green') . $description);
            }
        }
    }

    
    public function setPad(string $item, int $max, int $extra = 2, int $indent = 0): string
    {
        $max += $extra + $indent;

        return str_pad(str_repeat(' ', $indent) . $item, $max);
    }

    
    public function getPad(array $array, int $pad): int
    {
        $max = 0;

        foreach (array_keys($array) as $key) {
            $max = max($max, strlen($key));
        }

        return $max + $pad;
    }

    
    public function __get(string $key)
    {
        return $this->{$key} ?? null;
    }

    
    public function __isset(string $key): bool
    {
        return isset($this->{$key});
    }
}
