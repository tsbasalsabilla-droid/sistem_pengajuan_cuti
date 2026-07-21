<?php

declare(strict_types=1);



namespace CodeIgniter\Commands\Generators;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\CLI\GeneratorTrait;


class CommandGenerator extends BaseCommand
{
    use GeneratorTrait;

    
    protected $group = 'Generators';

    
    protected $name = 'make:command';

    
    protected $description = 'Generates a new spark command.';

    
    protected $usage = 'make:command <name> [options]';

    
    protected $arguments = [
        'name' => 'The command class name.',
    ];

    
    protected $options = [
        '--command'   => 'The command name. Default: "command:name"',
        '--type'      => 'The command type. Options [basic, generator]. Default: "basic".',
        '--group'     => 'The command group. Default: [basic -> "App", generator -> "Generators"].',
        '--namespace' => 'Set root namespace. Default: "APP_NAMESPACE".',
        '--suffix'    => 'Append the component title to the class name (e.g. User => UserCommand).',
        '--force'     => 'Force overwrite existing file.',
    ];

    
    public function run(array $params)
    {
        $this->component = 'Command';
        $this->directory = 'Commands';
        $this->template  = 'command.tpl.php';

        $this->classNameLang = 'CLI.generator.className.command';
        $this->generateClass($params);
    }

    
    protected function prepare(string $class): string
    {
        $command = $this->getOption('command');
        $group   = $this->getOption('group');
        $type    = $this->getOption('type');

        $command = is_string($command) ? $command : 'command:name';
        $type    = is_string($type) ? $type : 'basic';

        if (! in_array($type, ['basic', 'generator'], true)) {
            
            $type = CLI::prompt(lang('CLI.generator.commandType'), ['basic', 'generator'], 'required');
            CLI::newLine();
            
        }

        if (! is_string($group)) {
            $group = $type === 'generator' ? 'Generators' : 'App';
        }

        return $this->parseTemplate(
            $class,
            ['{group}', '{command}'],
            [$group, $command],
            ['type' => $type],
        );
    }
}
