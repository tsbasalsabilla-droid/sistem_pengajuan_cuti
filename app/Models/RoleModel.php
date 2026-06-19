<?php

namespace Tests\Support\Models;

use CodeIgniter\Model;

class RoleModel extends Model
{
    protected $table          = 'role';
    protected $allowedFields  = [
        'role',
    ];
}
