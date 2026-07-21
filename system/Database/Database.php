<?php

declare(strict_types=1);



namespace CodeIgniter\Database;

use CodeIgniter\Exceptions\ConfigException;
use CodeIgniter\Exceptions\CriticalError;
use CodeIgniter\Exceptions\InvalidArgumentException;


class Database
{
    
    protected $connections = [];

    
    public function load(array $params = [], string $alias = '')
    {
        if ($alias === '') {
            throw new InvalidArgumentException('You must supply the parameter: alias.');
        }

        if (! empty($params['DSN']) && str_contains($params['DSN'], '://')) {
            $params = $this->parseDSN($params);
        }

        if (empty($params['DBDriver'])) {
            throw new InvalidArgumentException('You have not selected a database type to connect to.');
        }

        assert($this->checkDbExtension($params['DBDriver']));

        $this->connections[$alias] = $this->initDriver($params['DBDriver'], 'Connection', $params);

        return $this->connections[$alias];
    }

    
    public function loadForge(ConnectionInterface $db): Forge
    {
        if ($db->connID === false) {
            $db->initialize();
        }

        return $this->initDriver($db->DBDriver, 'Forge', $db);
    }

    
    public function loadUtils(ConnectionInterface $db): BaseUtils
    {
        if ($db->connID === false) {
            $db->initialize();
        }

        return $this->initDriver($db->DBDriver, 'Utils', $db);
    }

    
    protected function parseDSN(array $params): array
    {
        $dsn = parse_url($params['DSN']);

        if (in_array($dsn, [0, '', '0', [], false, null], true)) {
            throw new InvalidArgumentException('Your DSN connection string is invalid.');
        }

        $dsnParams = [
            'DSN'      => '',
            'DBDriver' => $dsn['scheme'],
            'hostname' => isset($dsn['host']) ? rawurldecode($dsn['host']) : '',
            'port'     => isset($dsn['port']) ? rawurldecode((string) $dsn['port']) : '',
            'username' => isset($dsn['user']) ? rawurldecode($dsn['user']) : '',
            'password' => isset($dsn['pass']) ? rawurldecode($dsn['pass']) : '',
            'database' => isset($dsn['path']) ? rawurldecode(substr($dsn['path'], 1)) : '',
        ];

        if (isset($dsn['query']) && ($dsn['query'] !== '')) {
            parse_str($dsn['query'], $extra);

            foreach ($extra as $key => $val) {
                if (is_string($val) && in_array(strtolower($val), ['true', 'false', 'null'], true)) {
                    $val = $val === 'null' ? null : filter_var($val, FILTER_VALIDATE_BOOLEAN);
                }

                $dsnParams[$key] = $val;
            }
        }

        return array_merge($params, $dsnParams);
    }

    
    protected function initDriver(string $driver, string $class, $argument): object
    {
        $classname = str_contains($driver, '\\')
            ? $driver . '\\' . $class
            : "CodeIgniter\\Database\\{$driver}\\{$class}";

        return new $classname($argument);
    }

    
    private function checkDbExtension(string $driver): bool
    {
        if (str_contains($driver, '\\')) {
            
            return true;
        }

        $extensionMap = [
            
            'MySQLi'  => 'mysqli',
            'SQLite3' => 'sqlite3',
            'Postgre' => 'pgsql',
            'SQLSRV'  => 'sqlsrv',
            'OCI8'    => 'oci8',
        ];

        $extension = $extensionMap[$driver] ?? '';

        if ($extension === '') {
            $message = 'Invalid DBDriver name: "' . $driver . '"';

            throw new ConfigException($message);
        }

        if (extension_loaded($extension)) {
            return true;
        }

        $message = 'The required PHP extension "' . $extension . '" is not loaded.'
            . ' Install and enable it to use "' . $driver . '" driver.';

        throw new CriticalError($message);
    }
}
