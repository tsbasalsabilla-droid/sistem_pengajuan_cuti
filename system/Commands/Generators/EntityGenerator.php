<?php

declare(strict_types=1);



namespace CodeIgniter\Commands\Generators;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\GeneratorTrait;


class EntityGenerator extends BaseCommand
{
    use GeneratorTrait;

    
    protected $group = 'Generators';

    
    protected $name = 'make:entity';

    
    protected $description = 'Generates a new entity file.';

    
    protected $usage = 'make:entity <name> [options]';

    
    protected $arguments = [
        'name' => 'The entity class name.',
    ];

    
    protected $options = [
        '--namespace' => 'Set root namespace. Default: "APP_NAMESPACE".',
        '--suffix'    => 'Append the component title to the class name (e.g. User => UserEntity).',
        '--force'     => 'Force overwrite existing file.',
    ];

    
    public function run(array $params)
    {
        $this->component = 'Entity';
        $this->directory = 'Entities';
        $this->template  = 'entity.tpl.php';

        $this->classNameLang = 'CLI.generator.className.entity';
        $this->generateClass($params);
    }
}
