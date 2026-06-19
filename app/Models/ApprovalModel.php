<?php

namespace App\Models;

use CodeIgniter\Model;

class ApprovalModel extends Model
{
    protected $table = 'approval_logs';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'cuti_id',
        'approver_id',
        'role_approver',
        'status',
        'catatan'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';
}
