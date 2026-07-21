<?php

declare(strict_types=1);



namespace CodeIgniter\Commands\Utilities;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Config\DotEnv;
use Config\Paths;


final class Environment extends BaseCommand
{
    
    protected $group = 'CodeIgniter';

    
    protected $name = 'env';

    
    protected $description = 'Retrieves the current environment, or set a new one.';

    
    protected $usage = 'env [<environment>]';

    
    protected $arguments = [
        'environment' => '[Optional] The new environment to set. If none is provided, this will print the current environment.',
    ];

    
    protected $options = [];

    
    private static array $knownTypes = [
        'production',
        'development',
    ];

    
    public function run(array $params)
    {
        if ($params === []) {
            CLI::write(sprintf('Your environment is currently set as %s.', CLI::color(service('superglobals')->server('CI_ENVIRONMENT', ENVIRONMENT), 'green')));
            CLI::newLine();

            return EXIT_ERROR;
        }

        $env = strtolower(array_shift($params));

        if ($env === 'testing') {
            CLI::error('The "testing" environment is reserved for PHPUnit testing.', 'light_gray', 'red');
            CLI::error('You will not be able to run spark under a "testing" environment.', 'light_gray', 'red');
            CLI::newLine();

            return EXIT_ERROR;
        }

        if (! in_array($env, self::$knownTypes, true)) {
            CLI::error(sprintf('Invalid environment type "%s". Expected one of "%s".', $env, implode('" and "', self::$knownTypes)), 'light_gray', 'red');
            CLI::newLine();

            return EXIT_ERROR;
        }

        if (! $this->writeNewEnvironmentToEnvFile($env)) {
            CLI::error('Error in writing new environment to .env file.', 'light_gray', 'red');
            CLI::newLine();

            return EXIT_ERROR;
        }

        
        
        putenv('CI_ENVIRONMENT');
        unset($_ENV['CI_ENVIRONMENT']);
        service('superglobals')->unsetServer('CI_ENVIRONMENT');
        (new DotEnv((new Paths())->envDirectory ?? ROOTPATH))->load();

        CLI::write(sprintf('Environment is successfully changed to "%s".', $env), 'green');
        CLI::write('The ENVIRONMENT constant will be changed in the next script execution.');
        CLI::newLine();

        return EXIT_SUCCESS;
    }

    
    private function writeNewEnvironmentToEnvFile(string $newEnv): bool
    {
        $baseEnv = ROOTPATH . 'env';
        $envFile = ((new Paths())->envDirectory ?? ROOTPATH) . '.env';

        if (! is_file($envFile)) {
            if (! is_file($baseEnv)) {
                CLI::write('Both default shipped `env` file and custom `.env` are missing.', 'yellow');
                CLI::write('It is impossible to write the new environment type.', 'yellow');
                CLI::newLine();

                return false;
            }

            copy($baseEnv, $envFile);
        }

        $pattern = preg_quote(service('superglobals')->server('CI_ENVIRONMENT', ENVIRONMENT), '/');
        $pattern = sprintf('/^[#\s]*CI_ENVIRONMENT[=\s]+%s$/m', $pattern);

        return file_put_contents(
            $envFile,
            preg_replace($pattern, "\nCI_ENVIRONMENT = {$newEnv}", file_get_contents($envFile), -1, $count),
        ) !== false && $count > 0;
    }
}
