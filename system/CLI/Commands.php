<?php

declare(strict_types=1);



namespace CodeIgniter\CLI;

use CodeIgniter\Autoloader\FileLocatorInterface;
use CodeIgniter\Events\Events;
use CodeIgniter\Log\Logger;
use ReflectionClass;
use ReflectionException;


class Commands
{
    
    protected $commands = [];

    
    protected $logger;

    
    public function __construct($logger = null)
    {
        $this->logger = $logger ?? service('logger');
        $this->discoverCommands();
    }

    
    public function run(string $command, array $params)
    {
        if (! $this->verifyCommand($command, $this->commands)) {
            return EXIT_ERROR;
        }

        
        
        $className = $this->commands[$command]['class'];
        $class     = new $className($this->logger, $this);

        Events::trigger('pre_command');

        $exit = $class->run($params);

        Events::trigger('post_command');

        return $exit;
    }

    
    public function getCommands()
    {
        return $this->commands;
    }

    
    public function discoverCommands()
    {
        if ($this->commands !== []) {
            return;
        }

        
        $locator = service('locator');
        $files   = $locator->listFiles('Commands/');

        
        
        if ($files === []) {
            return; 
        }

        
        
        foreach ($files as $file) {
            
            $className = $locator->findQualifiedNameFromPath($file);

            if ($className === false || ! class_exists($className)) {
                continue;
            }

            try {
                $class = new ReflectionClass($className);

                if (! $class->isInstantiable() || ! $class->isSubclassOf(BaseCommand::class)) {
                    continue;
                }

                $class = new $className($this->logger, $this);

                if ($class->group !== null && ! isset($this->commands[$class->name])) {
                    $this->commands[$class->name] = [
                        'class'       => $className,
                        'file'        => $file,
                        'group'       => $class->group,
                        'description' => $class->description,
                    ];
                }

                unset($class);
            } catch (ReflectionException $e) {
                $this->logger->error($e->getMessage());
            }
        }

        asort($this->commands);
    }

    
    public function verifyCommand(string $command, array $commands): bool
    {
        if (isset($commands[$command])) {
            return true;
        }

        $message      = lang('CLI.commandNotFound', [$command]);
        $alternatives = $this->getCommandAlternatives($command, $commands);

        if ($alternatives !== []) {
            if (count($alternatives) === 1) {
                $message .= "\n\n" . lang('CLI.altCommandSingular') . "\n    ";
            } else {
                $message .= "\n\n" . lang('CLI.altCommandPlural') . "\n    ";
            }

            $message .= implode("\n    ", $alternatives);
        }

        CLI::error($message);
        CLI::newLine();

        return false;
    }

    
    protected function getCommandAlternatives(string $name, array $collection): array
    {
        
        $alternatives = [];

        
        foreach (array_keys($collection) as $commandName) {
            $lev = levenshtein($name, $commandName);

            if ($lev <= strlen($commandName) / 3 || str_contains($commandName, $name)) {
                $alternatives[$commandName] = $lev;
            }
        }

        ksort($alternatives, SORT_NATURAL | SORT_FLAG_CASE);

        return array_keys($alternatives);
    }
}
