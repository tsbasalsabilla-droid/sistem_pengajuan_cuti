<?php

namespace App\Models;

use CodeIgniter\Model;

class PengajuanCutiModel extends Model
{
    protected $table = 'pengajuan_cuti';

    protected $allowedFields = [
        'pegawai_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'total_hari',
        'alasan',
        'status'
    ];
}