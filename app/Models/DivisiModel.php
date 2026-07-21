<?php

namespace App\Models;

use CodeIgniter\Model;

class DivisiModel extends Model
{
    protected $table = 'divisi';
    protected $allowedFields  = [
        'nama_divisi',
    ];

    public function getDivisi($id = false, $perPage = 10, $page = null)
    {
        if ($id == false) {
            return $this->paginate($perPage, 'default', $page);
        }

        return $this->where(['id' => $id])->first();
    }
}
