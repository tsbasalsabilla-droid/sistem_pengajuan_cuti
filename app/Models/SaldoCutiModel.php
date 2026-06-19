<?php

namespace App\Models;

use CodeIgniter\Model;

class SaldoCutiModel extends Model
{
    protected $table = 'pengajuan_cuti';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'pegawai_id',
        'tahun',
        'total_cuti',
        'cuti_terpakai',
        'sisa_cuti'
    ];

    public function syncSaldoCuti()
    {
        return true;
    }
}
