<?php

declare(strict_types=1);



namespace CodeIgniter\Commands\Utilities;

use CodeIgniter\Cache\FactoriesCache;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Config\BaseConfig;
use Config\Optimize;
use Kint\Kint;


final class ConfigCheck extends BaseCommand
{
    
    protected $group = 'CodeIgniter';

    
    protected $name = 'config:check';

    
    protected $description = 'Check your Config values.';

    
    protected $usage = 'config:check <classname>';

    
    protected $arguments = [
        'classname' => 'The config classname to check. Short classname or FQCN.',
    ];

    
    protected $options = [];

    
    public function run(array $params)
    {
        if (! isset($params[0])) {
            CLI::error('You must specify a Config classname.');
            CLI::write('  Usage: ' . $this->usage);
            CLI::write('Example: config:check App');
            CLI::write('         config:check \'CodeIgniter\Shield\Config\Auth\'');

            return EXIT_ERROR;
        }

        
        $class = $params[0];

        
        $configCacheEnabled = class_exists(Optimize::class)
            && (new Optimize())->configCacheEnabled;
        if ($configCacheEnabled) {
            $factoriesCache = new FactoriesCache();
            $factoriesCache->load('config');
        }

        $config = config($class);

        if ($config === null) {
            CLI::error('No such Config class: ' . $class);

            return EXIT_ERROR;
        }

        if (defined('KINT_DIR') && Kint::$enabled_mode !== false) {
            CLI::write($this->getKintD($config));
        } else {
            CLI::write(
                CLI::color($this->getVarDump($config), 'cyan'),
            );
        }

        CLI::newLine();
        $state = CLI::color($configCacheEnabled ? 'Enabled' : 'Disabled', 'green');
        CLI::write('Config Caching: ' . $state);

        return EXIT_SUCCESS;
    }

    
    private function getKintD(object $config): string
    {
        ob_start();
        d($config);
        $output = ob_get_clean();

        $output = trim($output);

        $lines = explode("\n", $output);
        array_splice($lines, 0, 3);
        array_splice($lines, -3);

        return implode("\n", $lines);
    }

    
    private function getVarDump(object $config): string
    {
        ob_start();
        var_dump($config);
        $output = ob_get_clean();

        return preg_replace(
            '!.*system/Commands/Utilities/ConfigCheck.php.*\n!u',
            '',
            $output,
        );
    }
}
