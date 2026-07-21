<?php

declare(strict_types=1);



namespace CodeIgniter\Commands\Utilities;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Autoload;


class Namespaces extends BaseCommand
{
    
    protected $group = 'CodeIgniter';

    
    protected $name = 'namespaces';

    
    protected $description = 'Verifies your namespaces are setup correctly.';

    
    protected $usage = 'namespaces';

    
    protected $arguments = [];

    
    protected $options = [
        '-c' => 'Show only CodeIgniter config namespaces.',
        '-r' => 'Show raw path strings.',
        '-m' => 'Specify max length of the path strings to output. Default: 60.',
    ];

    
    public function run(array $params)
    {
        $params['m'] = (int) ($params['m'] ?? 60);

        $tbody = array_key_exists('c', $params) ? $this->outputCINamespaces($params) : $this->outputAllNamespaces($params);

        $thead = [
            'Namespace',
            'Path',
            'Found?',
        ];

        CLI::table($tbody, $thead);
    }

    private function outputAllNamespaces(array $params): array
    {
        $maxLength = $params['m'];

        $autoloader = service('autoloader');

        $tbody = [];

        foreach ($autoloader->getNamespace() as $ns => $paths) {
            foreach ($paths as $path) {
                if (array_key_exists('r', $params)) {
                    $pathOutput = $this->truncate($path, $maxLength);
                } else {
                    $pathOutput = $this->truncate(clean_path($path), $maxLength);
                }

                $tbody[] = [
                    $ns,
                    $pathOutput,
                    is_dir($path) ? 'Yes' : 'MISSING',
                ];
            }
        }

        return $tbody;
    }

    private function truncate(string $string, int $max): string
    {
        $length = mb_strlen($string);

        if ($length > $max) {
            return mb_substr($string, 0, $max - 3) . '...';
        }

        return $string;
    }

    private function outputCINamespaces(array $params): array
    {
        $maxLength = $params['m'];

        $config = new Autoload();

        $tbody = [];

        foreach ($config->psr4 as $ns => $paths) {
            foreach ((array) $paths as $path) {
                if (array_key_exists('r', $params)) {
                    $pathOutput = $this->truncate($path, $maxLength);
                } else {
                    $pathOutput = $this->truncate(clean_path($path), $maxLength);
                }

                $path = realpath($path) ?: $path;

                $tbody[] = [
                    $ns,
                    $pathOutput,
                    is_dir($path) ? 'Yes' : 'MISSING',
                ];
            }
        }

        return $tbody;
    }
}
