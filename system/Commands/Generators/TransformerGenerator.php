<?php

declare(strict_types=1);



namespace CodeIgniter\Commands\Generators;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\GeneratorTrait;


class TransformerGenerator extends BaseCommand
{
    use GeneratorTrait;

    
    protected $group = 'Generators';

    
    protected $name = 'make:transformer';

    
    protected $description = 'Generates a new transformer file.';

    
    protected $usage = 'make:transformer <name> [options]';

    
    protected $arguments = [
        'name' => 'The transformer class name.',
    ];

    
    protected $options = [
        '--namespace' => 'Set root namespace. Default: "APP_NAMESPACE".',
        '--suffix'    => 'Append the component title to the class name (e.g. User => UserTransformer).',
        '--force'     => 'Force overwrite existing file.',
    ];

    
    public function run(array $params)
    {
        $this->component = 'Transformer';
        $this->directory = 'Transformers';
        $this->template  = 'transformer.tpl.php';

        $this->classNameLang = 'CLI.generator.className.transformer';
        $this->generateClass($params);
    }

    
    protected function prepare(string $class): string
    {
        return $this->parseTemplate($class);
    }
}
