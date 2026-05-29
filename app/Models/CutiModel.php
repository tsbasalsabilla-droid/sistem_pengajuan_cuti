<?php

namespace App\Models;

use CodeIgniter\Model;

class CutiModel extends Model
{
    protected $table = 'cuti_requests';

    protected $primaryKey = 'id';

    protected $allowedFields = [
    'pegawai_id',
    'tanggal_mulai',
    'tanggal_selesai',
    'total_hari',
    'alasan',
    'status',
    'current_step',
    'teman_approve_count'
];
}