<?php

declare(strict_types=1);



namespace CodeIgniter\Commands\Worker;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;


class WorkerInstall extends BaseCommand
{
    protected $group       = 'Worker Mode';
    protected $name        = 'worker:install';
    protected $description = 'Install FrankenPHP worker mode by creating necessary configuration files';
    protected $usage       = 'worker:install [options]';
    protected $options     = [
        '--force' => 'Overwrite existing files',
    ];

    
    private array $templates = [
        'frankenphp-worker.php.tpl' => 'public/frankenphp-worker.php',
        'Caddyfile.tpl'             => 'Caddyfile',
    ];

    public function run(array $params)
    {
        $force = array_key_exists('force', $params) || CLI::getOption('force');

        CLI::write('Setting up FrankenPHP Worker Mode', 'yellow');
        CLI::newLine();

        helper('filesystem');

        $created = [];

        
        foreach ($this->templates as $template => $destination) {
            $source = SYSTEMPATH . 'Commands/Worker/Views/' . $template;
            $target = ROOTPATH . $destination;

            $isFile = is_file($target);

            
            if (! $force && $isFile) {
                continue;
            }

            
            $content = file_get_contents($source);
            if ($content === false) {
                CLI::error(
                    "Failed to read template: {$template}",
                    'light_gray',
                    'red',
                );
                CLI::newLine();

                return EXIT_ERROR;
            }

            
            if (! write_file($target, $content)) {
                CLI::error(
                    'Failed to create file: ' . clean_path($target),
                    'light_gray',
                    'red',
                );
                CLI::newLine();

                return EXIT_ERROR;
            }

            if ($force && $isFile) {
                CLI::write('  File overwritten: ' . clean_path($target), 'yellow');
            } else {
                CLI::write('  File created: ' . clean_path($target), 'green');
            }

            $created[] = $destination;
        }

        
        if ($created === []) {
            CLI::newLine();
            CLI::write('Worker mode files already exist.', 'yellow');
            CLI::write('Use --force to overwrite existing files.', 'yellow');
            CLI::newLine();

            return EXIT_ERROR;
        }

        
        CLI::newLine();
        CLI::write('Worker mode files created successfully!', 'green');
        CLI::newLine();

        $this->showNextSteps();

        return EXIT_SUCCESS;
    }

    
    protected function showNextSteps(): void
    {
        CLI::write('Next Steps:', 'yellow');
        CLI::newLine();

        CLI::write('1. Start FrankenPHP:', 'white');
        CLI::write('   frankenphp run', 'green');
        CLI::newLine();

        CLI::write('2. Test your application:', 'white');
        CLI::write('   curl http://localhost:8080/', 'green');
        CLI::newLine();
    }
}
