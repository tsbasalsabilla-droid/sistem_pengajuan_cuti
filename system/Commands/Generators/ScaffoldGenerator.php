<?php

declare(strict_types=1);



namespace CodeIgniter\Commands\Generators;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\CLI\GeneratorTrait;


class ScaffoldGenerator extends BaseCommand
{
    use GeneratorTrait;

    
    protected $group = 'Generators';

    
    protected $name = 'make:scaffold';

    
    protected $description = 'Generates a complete set of scaffold files.';

    
    protected $usage = 'make:scaffold <name> [options]';

    
    protected $arguments = [
        'name' => 'The class name',
    ];

    
    protected $options = [
        '--bare'      => 'Add the "--bare" option to controller component.',
        '--restful'   => 'Add the "--restful" option to controller component.',
        '--table'     => 'Add the "--table" option to the model component.',
        '--dbgroup'   => 'Add the "--dbgroup" option to model component.',
        '--return'    => 'Add the "--return" option to the model component.',
        '--namespace' => 'Set root namespace. Default: "APP_NAMESPACE".',
        '--suffix'    => 'Append the component title to the class name.',
        '--force'     => 'Force overwrite existing file.',
    ];

    
    public function run(array $params)
    {
        $this->params = $params;

        $options = [];

        if ($this->getOption('namespace')) {
            $options['namespace'] = $this->getOption('namespace');
        }

        if ($this->getOption('suffix')) {
            $options['suffix'] = null;
        }

        if ($this->getOption('force')) {
            $options['force'] = null;
        }

        $controllerOpts = [];

        if ($this->getOption('bare')) {
            $controllerOpts['bare'] = null;
        } elseif ($this->getOption('restful')) {
            $controllerOpts['restful'] = $this->getOption('restful');
        }

        $modelOpts = [
            'table'   => $this->getOption('table'),
            'dbgroup' => $this->getOption('dbgroup'),
            'return'  => $this->getOption('return'),
        ];

        $class = $params[0] ?? CLI::getSegment(2);

        
        $this->call('make:controller', array_merge([$class], $controllerOpts, $options));
        $this->call('make:model', array_merge([$class], $modelOpts, $options));
        $this->call('make:migration', array_merge([$class], $options));
        $this->call('make:seeder', array_merge([$class], $options));
    }
}
