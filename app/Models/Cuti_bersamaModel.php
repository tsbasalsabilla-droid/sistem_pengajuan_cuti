<?php

namespace App\Models;

use CodeIgniter\Model;

class Cuti_bersamaModel extends Model
{
    protected $table = 'cuti_bersama';
    protected $allowedFields  = [
        'tanggal',
        'keterangan',
    ];

    public function getcuti($id = false)
    {
        if ($id == false) {
            return $this->findAll();
        }

        return $this->where(['id' => $id])->first();
    }
}
