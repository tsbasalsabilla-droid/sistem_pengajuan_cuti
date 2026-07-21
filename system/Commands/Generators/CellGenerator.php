<?php

declare(strict_types=1);



namespace CodeIgniter\Commands\Generators;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\GeneratorTrait;
use Config\Generators;


class CellGenerator extends BaseCommand
{
    use GeneratorTrait;

    
    protected $group = 'Generators';

    
    protected $name = 'make:cell';

    
    protected $description = 'Generates a new Controlled Cell file and its view.';

    
    protected $usage = 'make:cell <name> [options]';

    
    protected $arguments = [
        'name' => 'The Controlled Cell class name.',
    ];

    
    protected $options = [
        '--namespace' => 'Set root namespace. Default: "APP_NAMESPACE".',
        '--force'     => 'Force overwrite existing file.',
    ];

    
    public function run(array $params)
    {
        $this->component = 'Cell';
        $this->directory = 'Cells';

        $params = array_merge($params, ['suffix' => null]);

        $this->templatePath  = config(Generators::class)->views[$this->name]['class'];
        $this->template      = 'cell.tpl.php';
        $this->classNameLang = 'CLI.generator.className.cell';

        $this->generateClass($params);

        $this->templatePath  = config(Generators::class)->views[$this->name]['view'];
        $this->template      = 'cell_view.tpl.php';
        $this->classNameLang = 'CLI.generator.viewName.cell';

        $className = $this->qualifyClassName();
        $viewName  = decamelize(class_basename($className));
        $viewName  = preg_replace(
            '/([a-z][a-z0-9_\/\\\\]+)(_cell)$/i',
            '$1',
            $viewName,
        ) ?? $viewName;
        $namespace = substr($className, 0, strrpos($className, '\\') + 1);

        $this->generateView($namespace . $viewName, $params);

        return 0;
    }
}
