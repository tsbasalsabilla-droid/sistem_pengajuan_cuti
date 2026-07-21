<?php

declare(strict_types=1);



namespace CodeIgniter\Commands\Generators;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\CLI\GeneratorTrait;
use CodeIgniter\Controller;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\RESTful\ResourcePresenter;


class ControllerGenerator extends BaseCommand
{
    use GeneratorTrait;

    
    protected $group = 'Generators';

    
    protected $name = 'make:controller';

    
    protected $description = 'Generates a new controller file.';

    
    protected $usage = 'make:controller <name> [options]';

    
    protected $arguments = [
        'name' => 'The controller class name.',
    ];

    
    protected $options = [
        '--bare'      => 'Extends from CodeIgniter\Controller instead of BaseController.',
        '--restful'   => 'Extends from a RESTful resource, Options: [controller, presenter]. Default: "controller".',
        '--namespace' => 'Set root namespace. Default: "APP_NAMESPACE".',
        '--suffix'    => 'Append the component title to the class name (e.g. User => UserController).',
        '--force'     => 'Force overwrite existing file.',
    ];

    
    public function run(array $params)
    {
        $this->component = 'Controller';
        $this->directory = 'Controllers';
        $this->template  = 'controller.tpl.php';

        $this->classNameLang = 'CLI.generator.className.controller';
        $this->generateClass($params);
    }

    
    protected function prepare(string $class): string
    {
        $bare = $this->getOption('bare');
        $rest = $this->getOption('restful');

        $useStatement = trim(APP_NAMESPACE, '\\') . '\Controllers\BaseController';
        $extends      = 'BaseController';

        
        if ($bare || $rest) {
            if ($bare) {
                $useStatement = Controller::class;
                $extends      = 'Controller';
            } elseif ($rest) {
                $rest = is_string($rest) ? $rest : 'controller';

                if (! in_array($rest, ['controller', 'presenter'], true)) {
                    
                    $rest = CLI::prompt(lang('CLI.generator.parentClass'), ['controller', 'presenter'], 'required');
                    CLI::newLine();
                    
                }

                if ($rest === 'controller') {
                    $useStatement = ResourceController::class;
                    $extends      = 'ResourceController';
                } elseif ($rest === 'presenter') {
                    $useStatement = ResourcePresenter::class;
                    $extends      = 'ResourcePresenter';
                }
            }
        }

        return $this->parseTemplate(
            $class,
            ['{useStatement}', '{extends}'],
            [$useStatement, $extends],
            ['type' => $rest],
        );
    }
}
