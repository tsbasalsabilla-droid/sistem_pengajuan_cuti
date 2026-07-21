<?php

declare(strict_types=1);



namespace CodeIgniter\Database;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Exceptions\InvalidArgumentException;
use Config\Database;
use Faker\Factory;
use Faker\Generator;


class Seeder
{
    
    protected $DBGroup;

    
    protected $seedPath;

    
    protected $config;

    
    protected $db;

    
    protected $forge;

    
    protected $silent = false;

    
    private static ?Generator $faker = null;

    
    public function __construct(Database $config, ?BaseConnection $db = null)
    {
        $this->seedPath = $config->filesPath ?? APPPATH . 'Database/';

        if ($this->seedPath === '') {
            throw new InvalidArgumentException('Invalid filesPath set in the Config\Database.');
        }

        $this->seedPath = rtrim($this->seedPath, '\\/') . '/Seeds/';

        if (! is_dir($this->seedPath)) {
            throw new InvalidArgumentException('Unable to locate the seeds directory. Please check Config\Database::filesPath');
        }

        $this->config = &$config;

        if (isset($this->DBGroup)) {
            $this->db    = Database::connect($this->DBGroup);
            $this->forge = Database::forge($this->DBGroup);
        } elseif ($db instanceof BaseConnection) {
            $this->db    = $db;
            $this->forge = Database::forge($db);
        } else {
            $this->db    = Database::connect($config->defaultGroup);
            $this->forge = Database::forge($config->defaultGroup);
        }
    }

    
    public static function faker(): ?Generator
    {
        if (! self::$faker instanceof Generator && class_exists(Factory::class)) {
            self::$faker = Factory::create();
        }

        return self::$faker;
    }

    
    public function call(string $class)
    {
        $class = trim($class);

        if ($class === '') {
            throw new InvalidArgumentException('No seeder was specified.');
        }

        if (! str_contains($class, '\\')) {
            $path = $this->seedPath . str_replace('.php', '', $class) . '.php';

            if (! is_file($path)) {
                throw new InvalidArgumentException('The specified seeder is not a valid file: ' . $path);
            }

            
            
            $class = APP_NAMESPACE . '\Database\Seeds\\' . $class;

            if (! class_exists($class, false)) {
                require_once $path;
            }
            
        }

        
        $seeder = new $class($this->config, $this->db);
        $seeder->setSilent($this->silent)->run();

        unset($seeder);

        if (is_cli() && ! $this->silent) {
            CLI::write("Seeded: {$class}", 'green');
        }
    }

    
    public function setPath(string $path)
    {
        $this->seedPath = rtrim($path, '\\/') . '/';

        return $this;
    }

    
    public function setSilent(bool $silent)
    {
        $this->silent = $silent;

        return $this;
    }

    
    public function run()
    {
    }
}
