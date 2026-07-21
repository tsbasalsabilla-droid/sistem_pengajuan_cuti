<?php

declare(strict_types=1);



namespace CodeIgniter\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;


class ListCommands extends BaseCommand
{
    
    protected $group = 'CodeIgniter';

    
    protected $name = 'list';

    
    protected $description = 'Lists the available commands.';

    
    protected $usage = 'list';

    
    protected $arguments = [];

    
    protected $options = [
        '--simple' => 'Prints a list of the commands with no other info',
    ];

    
    public function run(array $params)
    {
        $commands = $this->commands->getCommands();
        ksort($commands);

        
        return array_key_exists('simple', $params) || CLI::getOption('simple') === true
            ? $this->listSimple($commands)
            : $this->listFull($commands);
    }

    
    protected function listFull(array $commands)
    {
        
        $groups = [];

        foreach ($commands as $title => $command) {
            if (! isset($groups[$command['group']])) {
                $groups[$command['group']] = [];
            }

            $groups[$command['group']][$title] = $command;
        }

        $length = max(array_map(strlen(...), array_keys($commands)));

        ksort($groups);

        
        foreach ($groups as $group => $commands) {
            CLI::write($group, 'yellow');

            foreach ($commands as $name => $command) {
                $name   = $this->setPad($name, $length, 2, 2);
                $output = CLI::color($name, 'green');

                if (isset($command['description'])) {
                    $output .= CLI::wrap($command['description'], 125, strlen($name));
                }

                CLI::write($output);
            }

            if ($group !== array_key_last($groups)) {
                CLI::newLine();
            }
        }

        return EXIT_SUCCESS;
    }

    
    protected function listSimple(array $commands)
    {
        foreach (array_keys($commands) as $title) {
            CLI::write($title);
        }

        return EXIT_SUCCESS;
    }
}
