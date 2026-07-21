<?php

namespace App\Models;

use CodeIgniter\Model;

class PengajuanCutiModel extends Model
{
    protected $table = 'pengajuan_cuti';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField   = 'updated_at';

    protected $allowedFields = [
        'pegawai_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'total_hari',
        'alasan',
        'status',
        'catatan',
        'alasan_batal'
    ];

    public function updateBatalOtomatis()
    {
        return $this->whereIn('status', [
            'pending',
            'pending_teman',
            'pending_teman_sejawat',
            'pending_spv',
            'pending_hrd',
            'pending_direktur'
        ])
            ->where('tanggal_mulai <=', date('Y-m-d'))
            ->set([
                'status'       => 'dibatalkan',
                'alasan_batal'  => 'Pengajuan otomatis dibatalkan karena belum mendapat persetujuan hingga tanggal mulai cuti.'
            ])
            ->update();
    }
}
