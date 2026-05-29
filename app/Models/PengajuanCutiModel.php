<?php

namespace App\Models;

use CodeIgniter\Model;

class PengajuanCutiModel extends Model
{
    protected $table = 'pengajuan_cuti';

    protected $allowedFields = [
        'user_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'total_hari',
        'tujuan_cuti',
        'status'
    ];
}