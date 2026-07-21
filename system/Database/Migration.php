<?php

declare(strict_types=1);



namespace CodeIgniter\Database;

use Config\Database;


abstract class Migration
{
    
    protected $DBGroup;

    
    protected $db;

    
    protected $forge;

    public function __construct(?Forge $forge = null)
    {
        if (isset($this->DBGroup)) {
            $this->forge = Database::forge($this->DBGroup);
        } elseif ($forge instanceof Forge) {
            $this->forge = $forge;
        } else {
            $this->forge = Database::forge(config(Database::class)->defaultGroup);
        }

        $this->db = $this->forge->getConnection();
    }

    
    public function getDBGroup(): ?string
    {
        return $this->DBGroup;
    }

    
    abstract public function up();

    
    abstract public function down();
}
