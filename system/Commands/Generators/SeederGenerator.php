<?php

declare(strict_types=1);



namespace CodeIgniter\Commands\Generators;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\GeneratorTrait;


class SeederGenerator extends BaseCommand
{
    use GeneratorTrait;

    
    protected $group = 'Generators';

    
    protected $name = 'make:seeder';

    
    protected $description = 'Generates a new seeder file.';

    
    protected $usage = 'make:seeder <name> [options]';

    
    protected $arguments = [
        'name' => 'The seeder class name.',
    ];

    
    protected $options = [
        '--namespace' => 'Set root namespace. Default: "APP_NAMESPACE".',
        '--suffix'    => 'Append the component title to the class name (e.g. User => UserSeeder).',
        '--force'     => 'Force overwrite existing file.',
    ];

    
    public function run(array $params)
    {
        $this->component = 'Seeder';
        $this->directory = 'Database\Seeds';
        $this->template  = 'seeder.tpl.php';

        $this->classNameLang = 'CLI.generator.className.seeder';
        $this->generateClass($params);
    }
}
