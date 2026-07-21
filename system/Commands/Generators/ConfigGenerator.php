<?php

declare(strict_types=1);



namespace CodeIgniter\Commands\Generators;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\GeneratorTrait;


class ConfigGenerator extends BaseCommand
{
    use GeneratorTrait;

    
    protected $group = 'Generators';

    
    protected $name = 'make:config';

    
    protected $description = 'Generates a new config file.';

    
    protected $usage = 'make:config <name> [options]';

    
    protected $arguments = [
        'name' => 'The config class name.',
    ];

    
    protected $options = [
        '--namespace' => 'Set root namespace. Default: "APP_NAMESPACE".',
        '--suffix'    => 'Append the component title to the class name (e.g. User => UserConfig).',
        '--force'     => 'Force overwrite existing file.',
    ];

    
    public function run(array $params)
    {
        $this->component = 'Config';
        $this->directory = 'Config';
        $this->template  = 'config.tpl.php';

        $this->classNameLang = 'CLI.generator.className.config';
        $this->generateClass($params);
    }

    
    protected function prepare(string $class): string
    {
        $namespace = $this->getOption('namespace') ?? APP_NAMESPACE;

        if ($namespace === APP_NAMESPACE) {
            $class = substr($class, strlen($namespace . '\\'));
        }

        return $this->parseTemplate($class);
    }
}
