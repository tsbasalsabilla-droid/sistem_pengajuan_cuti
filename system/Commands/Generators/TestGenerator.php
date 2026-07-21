<?php

declare(strict_types=1);



namespace CodeIgniter\Commands\Generators;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\CLI\GeneratorTrait;


class TestGenerator extends BaseCommand
{
    use GeneratorTrait;

    
    protected $group = 'Generators';

    
    protected $name = 'make:test';

    
    protected $description = 'Generates a new test file.';

    
    protected $usage = 'make:test <name> [options]';

    
    protected $arguments = [
        'name' => 'The test class name.',
    ];

    
    protected $options = [
        '--namespace' => 'Set root namespace. Default: "Tests".',
        '--force'     => 'Force overwrite existing file.',
    ];

    
    public function run(array $params)
    {
        
        $params['suffix'] = null;

        $this->component = 'Test';
        $this->template  = 'test.tpl.php';

        $this->classNameLang = 'CLI.generator.className.test';

        $autoload = service('autoloader');
        $autoload->addNamespace('CodeIgniter', TESTPATH . 'system');
        $autoload->addNamespace('Tests', ROOTPATH . 'tests');

        $this->generateClass($params);
    }

    
    protected function getNamespace(): string
    {
        if ($this->namespace !== null) {
            return $this->namespace;
        }

        if ($this->getOption('namespace') !== null) {
            return trim(
                str_replace(
                    '/',
                    '\\',
                    $this->getOption('namespace'),
                ),
                '\\',
            );
        }

        $class      = $this->normalizeInputClassName();
        $classPaths = explode('\\', $class);

        $namespaces = service('autoloader')->getNamespace();

        while ($classPaths !== []) {
            array_pop($classPaths);
            $namespace = implode('\\', $classPaths);

            foreach (array_keys($namespaces) as $prefix) {
                if ($prefix === $namespace) {
                    
                    return $namespace;
                }
            }
        }

        return 'Tests';
    }

    
    protected function buildPath(string $class): string
    {
        $namespace = $this->getNamespace();

        $base = $this->searchTestFilePath($namespace);

        if ($base === null) {
            CLI::error(
                lang('CLI.namespaceNotDefined', [$namespace]),
                'light_gray',
                'red',
            );
            CLI::newLine();

            return '';
        }

        $realpath = realpath($base);
        $base     = ($realpath !== false) ? $realpath : $base;

        $file = $base . DIRECTORY_SEPARATOR
            . str_replace(
                '\\',
                DIRECTORY_SEPARATOR,
                trim(str_replace($namespace . '\\', '', $class), '\\'),
            ) . '.php';

        return implode(
            DIRECTORY_SEPARATOR,
            array_slice(
                explode(DIRECTORY_SEPARATOR, $file),
                0,
                -1,
            ),
        ) . DIRECTORY_SEPARATOR . $this->basename($file);
    }

    
    private function searchTestFilePath(string $testNamespace): ?string
    {
        
        $testPaths = service('autoloader')->getNamespace($testNamespace);

        foreach ($testPaths as $candidate) {
            if (str_contains($candidate, DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR)) {
                return $candidate;
            }
        }

        return null;
    }
}
