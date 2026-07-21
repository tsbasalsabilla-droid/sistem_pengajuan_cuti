<?php

declare(strict_types=1);



namespace CodeIgniter\Commands;

use CodeIgniter\CLI\BaseCommand;


class Help extends BaseCommand
{
    
    protected $group = 'CodeIgniter';

    
    protected $name = 'help';

    
    protected $description = 'Displays basic usage information.';

    
    protected $usage = 'help [<command_name>]';

    
    protected $arguments = [
        'command_name' => 'The command name [default: "help"]',
    ];

    
    protected $options = [];

    
    public function run(array $params)
    {
        $command = array_shift($params);
        $command ??= 'help';
        $commands = $this->commands->getCommands();

        if (! $this->commands->verifyCommand($command, $commands)) {
            return;
        }

        $class = new $commands[$command]['class']($this->logger, $this->commands);
        $class->showHelp();
    }
}
