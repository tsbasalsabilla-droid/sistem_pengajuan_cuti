<?php

namespace App\Models;

use CodeIgniter\Model;

class LaporanModel extends Model
{
    protected $table = 'pengajuan_cuti';
    protected $allowedFields  = [
        'pegawai_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'alasan',
        'total_hari',
        'status',
    ];

public function getLaporan()
    {
        $builder = $this->db->table($this->table)
            ->join('pegawai', 'pegawai.id = ' . $this->table . '.pegawai_id')
            ->select($this->table . '.*, pegawai.nama, pegawai.nip');

        return $builder->get()->getResultArray();
    }

    public function getRecentLaporan(int $limit = 5)
    {
        $builder = $this->db->table($this->table)
            ->join('pegawai', 'pegawai.id = ' . $this->table . '.pegawai_id')
            ->select($this->table . '.*, pegawai.nama, pegawai.nip')
            ->orderBy($this->table . '.tanggal_mulai', 'DESC')
            ->limit($limit);

        return $builder->get()->getResultArray();
    }

    public function countLaporan()
    {
        return $this->countAllResults();
    }

    public function countCutiThisMonth()
    {
        $start = date('Y-m-01');
        $end = date('Y-m-t');

        return $this->builder()
            ->where('tanggal_mulai <=', $end)
            ->where('tanggal_selesai >=', $start)
            ->countAllResults();
    }
}

