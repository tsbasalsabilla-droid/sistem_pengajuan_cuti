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

    public function getPegawai($perPage = 10, $id = false, $page = null)
    {
        $this->select('pegawai.*, jabatan.jabatan, divisi.nama_divisi')
            ->join('jabatan', 'jabatan.id = pegawai.id_jabatan', 'left')
            ->join('divisi', 'divisi.id = pegawai.id_divisi', 'left');

        if ($id !== false && $id !== null) {
            return $this->where('pegawai.id', $id)->first();
        }

        return $this->paginate($perPage, 'default', $page);
    }

    public function getPegawaiById($id)
    {
        return $this->select('pegawai.*, jabatan.jabatan, divisi.nama_divisi')
            ->join('jabatan', 'jabatan.id = pegawai.id_jabatan', 'left')
            ->join('divisi', 'divisi.id = pegawai.id_divisi', 'left')
            ->where('pegawai.id', $id)
            ->first();
    }

    public function countPegawai()
    {
        return $this->countAllResults();
    }
}
