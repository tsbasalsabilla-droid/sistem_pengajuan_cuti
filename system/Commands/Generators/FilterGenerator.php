<?php

declare(strict_types=1);



namespace CodeIgniter\Commands\Generators;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\GeneratorTrait;


class FilterGenerator extends BaseCommand
{
    use GeneratorTrait;

    
    protected $group = 'Generators';

    
    protected $name = 'make:filter';

    
    protected $description = 'Generates a new filter file.';

    
    protected $usage = 'make:filter <name> [options]';

    
    protected $arguments = [
        'name' => 'The filter class name.',
    ];

    
    protected $options = [
        '--namespace' => 'Set root namespace. Default: "APP_NAMESPACE".',
        '--suffix'    => 'Append the component title to the class name (e.g. User => UserFilter).',
        '--force'     => 'Force overwrite existing file.',
    ];

    
    public function run(array $params)
    {
        $this->component = 'Filter';
        $this->directory = 'Filters';
        $this->template  = 'filter.tpl.php';

        $this->classNameLang = 'CLI.generator.className.filter';
        $this->generateClass($params);
    }
}
