<?php

namespace App\Models;

use CodeIgniter\Model;

class JabatanModel extends Model
{
    protected $table = 'jabatan';
    protected $allowedFields  = [
        'jabatan',
    ];

    public function getJabatan($id = false, $perPage = 10, $page = null)
    {
        if ($id == false) {
            return $this->paginate($perPage, 'default', $page);
        }

        return $this->where(['id' => $id])->first();
    }
}
