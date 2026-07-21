<?php

namespace App\Models;

use CodeIgniter\Model;

class DetailStatusCutiModel extends Model
{
    protected $table = 'detail_status_cuti';

    protected $allowedFields = [
        'pengajuan_id',
        'approved_by',
        'level_approval',
        'status',
        'catatan',
        'approved_at'
    ];
}
