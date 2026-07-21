<?php

declare(strict_types=1);



namespace CodeIgniter\Test\Interfaces;

use CodeIgniter\BaseModel;
use Faker\Generator;
use ReflectionException;


interface FabricatorModel
{
    
    public function find($id = null);

    
    public function insert($row = null, bool $returnID = true);

    

    
    

    
    
}
