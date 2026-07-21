<?php

declare(strict_types=1);



namespace CodeIgniter\Commands\Generators;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\GeneratorTrait;


class ValidationGenerator extends BaseCommand
{
    use GeneratorTrait;

    
    protected $group = 'Generators';

    
    protected $name = 'make:validation';

    
    protected $description = 'Generates a new validation file.';

    
    protected $usage = 'make:validation <name> [options]';

    
    protected $arguments = [
        'name' => 'The validation class name.',
    ];

    
    protected $options = [
        '--namespace' => 'Set root namespace. Default: "APP_NAMESPACE".',
        '--suffix'    => 'Append the component title to the class name (e.g. User => UserValidation).',
        '--force'     => 'Force overwrite existing file.',
    ];

    
    public function run(array $params)
    {
        $this->component = 'Validation';
        $this->directory = 'Validation';
        $this->template  = 'validation.tpl.php';

        $this->classNameLang = 'CLI.generator.className.validation';
        $this->generateClass($params);
    }
}
