<?php

declare(strict_types=1);



namespace CodeIgniter\Test;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Database\MigrationRunner;
use CodeIgniter\Database\Seeder;
use CodeIgniter\Test\Constraints\SeeInDatabase;
use Config\Database;
use Config\Migrations;
use PHPUnit\Framework\Attributes\AfterClass;


trait DatabaseTestTrait
{
    
    private static $doneMigration = false;

    
    private static $doneSeed = false;

    
    
    

    
    protected function setUpDatabase()
    {
        $this->loadDependencies();
        $this->setUpMigrate();
        $this->setUpSeed();
    }

    
    protected function tearDownDatabase()
    {
        $this->clearInsertCache();
    }

    
    public function loadDependencies()
    {
        if ($this->db === null) {
            $this->db = Database::connect($this->DBGroup);
            $this->db->initialize();
        }

        if ($this->migrations === null) {
            
            $config          = new Migrations();
            $config->enabled = true;

            $this->migrations = service('migrations', $config, $this->db, false);
            $this->migrations->setSilent(false);
        }

        if ($this->seeder === null) {
            $this->seeder = Database::seeder($this->DBGroup);
            $this->seeder->setSilent(true);
        }
    }

    
    
    

    
    protected function setUpMigrate()
    {
        if ($this->migrateOnce === false || self::$doneMigration === false) {
            if ($this->refresh === true) {
                $this->regressDatabase();

                
                Fabricator::resetCounts();
            }

            $this->migrateDatabase();
        }
    }

    
    protected function regressDatabase()
    {
        if ($this->migrate === false) {
            return;
        }

        
        if ($this->namespace === null) {
            $this->migrations->setNamespace(null);
            $this->migrations->regress(0, 'tests');
        }

        
        else {
            $namespaces = is_array($this->namespace) ? $this->namespace : [$this->namespace];

            foreach ($namespaces as $namespace) {
                $this->migrations->setNamespace($namespace);
                $this->migrations->regress(0, 'tests');
            }
        }
    }

    
    protected function migrateDatabase()
    {
        if ($this->migrate === false) {
            return;
        }

        
        if ($this->namespace === null) {
            $this->migrations->setNamespace(null);
            $this->migrations->latest('tests');
            self::$doneMigration = true;
        }
        
        else {
            $namespaces = is_array($this->namespace) ? $this->namespace : [$this->namespace];

            foreach ($namespaces as $namespace) {
                $this->migrations->setNamespace($namespace);
                $this->migrations->latest('tests');
                self::$doneMigration = true;
            }
        }
    }

    
    
    

    
    protected function setUpSeed()
    {
        if ($this->seedOnce === false || self::$doneSeed === false) {
            $this->runSeeds();
        }
    }

    
    protected function runSeeds()
    {
        if ($this->seed !== '') {
            if ($this->basePath !== '') {
                $this->seeder->setPath(rtrim($this->basePath, '/') . '/Seeds');
            }

            $seeds = is_array($this->seed) ? $this->seed : [$this->seed];

            foreach ($seeds as $seed) {
                $this->seed($seed);
            }
        }

        self::$doneSeed = true;
    }

    
    public function seed(string $name)
    {
        $this->seeder->call($name);
    }

    
    
    
    
    #[AfterClass]
    public static function resetMigrationSeedCount()
    {
        self::$doneMigration = false;
        self::$doneSeed      = false;
    }

    
    protected function clearInsertCache()
    {
        foreach ($this->insertCache as $row) {
            $this->db->table($row[0])
                ->where($row[1])
                ->delete();
        }
    }

    
    public function loadBuilder(string $tableName)
    {
        $builderClass = str_replace('Connection', 'Builder', $this->db::class);

        return new $builderClass($tableName, $this->db);
    }

    
    public function grabFromDatabase(string $table, string $column, array $where)
    {
        $query = $this->db->table($table)
            ->select($column)
            ->where($where)
            ->get();

        $query = $query->getRow();

        return $query->{$column} ?? false;
    }

    
    
    

    
    public function seeInDatabase(string $table, array $where)
    {
        $constraint = new SeeInDatabase($this->db, $where);
        static::assertThat($table, $constraint);
    }

    
    public function dontSeeInDatabase(string $table, array $where)
    {
        $count = $this->db->table($table)
            ->where($where)
            ->countAllResults();

        $this->assertTrue($count === 0, 'Row was found in database');
    }

    
    public function hasInDatabase(string $table, array $data)
    {
        $this->insertCache[] = [
            $table,
            $data,
        ];

        return $this->db->table($table)->insert($data);
    }

    
    public function seeNumRecords(int $expected, string $table, array $where)
    {
        $count = $this->db->table($table)
            ->where($where)
            ->countAllResults();

        $this->assertEquals($expected, $count, 'Wrong number of matching rows in database.');
    }

    
    protected function disableDBDebug(): void
    {
        $this->setPrivateProperty($this->db, 'DBDebug', false);
    }

    
    protected function enableDBDebug(): void
    {
        $this->setPrivateProperty($this->db, 'DBDebug', true);
    }
}
