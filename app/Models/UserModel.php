<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table          = 'pegawai';
    protected $allowedFields  = [
        'nama',
        'nip',
        'email',
        'id_jabatan',
        'id_divisi',
        'no_hp',
        'alamat',
        'foto',
        'saldo_cuti',
    ];

    public function getPegawai($id = false)
    {
        $builder = $this->db->table('pegawai')
            ->join('jabatan', 'jabatan.id = pegawai.id_jabatan')
            ->join('divisi', 'divisi.id = pegawai.id_divisi')
            ->select('pegawai.*, jabatan.jabatan, divisi.nama_divisi');

        if ($id === false) {
            return $builder->get()->getResultArray();
        }

        return $builder->where('pegawai.id', $id)->get()->getRowArray();
    }

    public function countPegawai()
    {
        return $this->countAllResults();
    }
}
