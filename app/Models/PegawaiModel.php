<?php

namespace App\Models;

use CodeIgniter\Model;

class PegawaiModel extends Model
{
    protected $table = 'pegawai';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'nama',
        'email',
        'nip',
        'password',
        'role',
        'status_aktif',
        'saldo_cuti'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function getTemanSejawat($userId)
    {
        $db = \Config\Database::connect();

        $reviewer = $db->table('pegawai')
            ->select('id_divisi')
            ->where('id', $userId)
            ->get()
            ->getRowArray();

        if (!$reviewer || empty($reviewer['id_divisi'])) {
            return [];
        }

        return $db->table('pengajuan_cuti')
            ->select('pengajuan_cuti.*, pegawai.nama AS pegawai_nama')
            ->join('pegawai', 'pegawai.id = pengajuan_cuti.pegawai_id')
            ->where('pengajuan_cuti.status', 'pending_teman_sejawat')
            ->where('pengajuan_cuti.pegawai_id !=', $userId)
            ->where('pegawai.id_divisi', $reviewer['id_divisi'])
            ->where("
                pengajuan_cuti.id NOT IN (
                    SELECT pengajuan_id
                    FROM detail_status_cuti
                    WHERE approved_by = " . $db->escape($userId) . "
                    AND level_approval = 'teman'
                )
            ", null, false)
            ->get()
            ->getResultArray();
    }

    public function countTemanSejawat($userId)
    {
        $db = \Config\Database::connect();

        $pegawai = $db->table('pegawai')
            ->select('id_divisi')
            ->where('id', $userId)
            ->get()
            ->getRowArray();

        if (!$pegawai || empty($pegawai['id_divisi'])) {
            return 0;
        }

        
        return $db->table('pegawai')
            ->where('id_divisi', $pegawai['id_divisi'])
            ->where('id !=', $userId)
            ->where('status_aktif', 1)
            ->countAllResults();
    }

    public function getPengajuanWithPegawai()
    {
        $db = \Config\Database::connect();

        return $db->table('pengajuan_cuti')
            ->select('pengajuan_cuti.*, pegawai.nama AS pegawai_nama')
            ->join('pegawai', 'pegawai.id = pengajuan_cuti.pegawai_id')
            ->get()
            ->getResultArray();
    }

    public function getDetailPengajuan($id)
    {
        $db = \Config\Database::connect();

        return $db->table('pengajuan_cuti')
            ->select('pengajuan_cuti.*, pegawai.nama AS pegawai_nama')
            ->join('pegawai', 'pegawai.id = pengajuan_cuti.pegawai_id')
            ->where('pengajuan_cuti.id', $id)
            ->get()
            ->getRowArray();
    }
}
