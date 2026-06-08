<?php

namespace App\Models;

use CodeIgniter\Model;

class SaldoCutiModel extends Model
{
    protected $table = 'saldo_cuti';

    protected $allowedFields = [
        'pegawai_id',
        'tahun',
        'total_cuti',
        'cuti_terpakai',
        'sisa_cuti'
    ];
}