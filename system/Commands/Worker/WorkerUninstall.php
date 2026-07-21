<?php

declare(strict_types=1);



namespace CodeIgniter\Commands\Worker;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;


class WorkerUninstall extends BaseCommand
{
    protected $group       = 'Worker Mode';
    protected $name        = 'worker:uninstall';
    protected $description = 'Remove FrankenPHP worker mode configuration files';
    protected $usage       = 'worker:uninstall [options]';
    protected $options     = [
        '--force' => 'Skip confirmation prompt',
    ];

    
    private array $files = [
        'public/frankenphp-worker.php',
        'Caddyfile',
    ];

    public function run(array $params)
    {
        $force = array_key_exists('force', $params) || CLI::getOption('force');

        CLI::write('Uninstalling FrankenPHP Worker Mode', 'yellow');
        CLI::newLine();

        
        $existing = [];

        foreach ($this->files as $file) {
            $path = ROOTPATH . $file;
            if (is_file($path)) {
                $existing[] = $file;
            }
        }

        
        if ($existing === []) {
            CLI::write('No worker mode files found to remove.', 'yellow');
            CLI::newLine();

            return EXIT_SUCCESS;
        }

        
        CLI::write('The following files will be removed:', 'yellow');

        foreach ($existing as $file) {
            CLI::write('  - ' . $file, 'white');
        }
        CLI::newLine();

        
        if (! $force) {
            $confirm = CLI::prompt('Are you sure you want to remove these files?', ['y', 'n']);
            CLI::newLine();

            if ($confirm !== 'y') {
                CLI::write('Uninstall cancelled.', 'yellow');
                CLI::newLine();

                return EXIT_ERROR;
            }
        }

        $removed = [];

        
        foreach ($existing as $file) {
            $path = ROOTPATH . $file;

            if (! @unlink($path)) {
                CLI::error('Failed to remove file: ' . clean_path($path), 'light_gray', 'red');

                continue;
            }

            CLI::write('  File removed: ' . clean_path($path), 'green');
            $removed[] = $file;
        }

        
        CLI::newLine();
        if ($removed === []) {
            CLI::error('No files were removed.');
            CLI::newLine();

            return EXIT_ERROR;
        }

        CLI::write('Worker mode files removed successfully!', 'green');
        CLI::newLine();

        return EXIT_SUCCESS;
    }
}
