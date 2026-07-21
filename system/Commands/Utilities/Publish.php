<?php

declare(strict_types=1);



namespace CodeIgniter\Commands\Utilities;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Publisher\Publisher;


class Publish extends BaseCommand
{
    
    protected $group = 'CodeIgniter';

    
    protected $name = 'publish';

    
    protected $description = 'Discovers and executes all predefined Publisher classes.';

    
    protected $usage = 'publish [<directory>]';

    
    protected $arguments = [
        'directory' => '[Optional] The directory to scan within each namespace. Default: "Publishers".',
    ];

    
    protected $options = [
        '--namespace' => 'The namespace from which to search for files to publish. By default, all namespaces are analysed.',
    ];

    
    public function run(array $params)
    {
        $directory = $params[0] ?? 'Publishers';
        $namespace = $params['namespace'] ?? '';

        if ([] === $publishers = Publisher::discover($directory, $namespace)) {
            if ($namespace === '') {
                CLI::write(lang('Publisher.publishMissing', [$directory]));
            } else {
                CLI::write(lang('Publisher.publishMissingNamespace', [$directory, $namespace]));
            }

            return;
        }

        foreach ($publishers as $publisher) {
            if ($publisher->publish()) {
                CLI::write(lang('Publisher.publishSuccess', [
                    $publisher::class,
                    count($publisher->getPublished()),
                    $publisher->getDestination(),
                ]), 'green');
            } else {
                CLI::error(lang('Publisher.publishFailure', [
                    $publisher::class,
                    $publisher->getDestination(),
                ]), 'light_gray', 'red');

                foreach ($publisher->getErrors() as $file => $exception) {
                    CLI::write($file);
                    CLI::error($exception->getMessage());
                    CLI::newLine();
                }
            }
        }
    }
}
