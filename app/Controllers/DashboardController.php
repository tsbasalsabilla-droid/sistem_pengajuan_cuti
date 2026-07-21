<?php

namespace App\Controllers;

use App\Models\PegawaiModel;
use App\Models\PengajuanCutiModel;

class DashboardController extends BaseController
{
    protected $pegawaiModel;
    protected $pengajuanModel;

    public function __construct()
    {

        $this->pegawaiModel = new PegawaiModel();
        $this->pengajuanModel = new PengajuanCutiModel();
    }

    public function index()
    {

        $this->pengajuanModel->updateBatalOtomatis();


        $userId = session()->get('user')['id'];


        $saldo = $this->pegawaiModel->find($userId);


        $pengajuanTerakhir = $this->pengajuanModel
            ->where('pegawai_id', $userId)
            ->orderBy('id', 'DESC')
            ->first();

        $pengajuanTerakhirList = $this->pengajuanModel
            ->where('pegawai_id', $userId)
            ->orderBy('id', 'DESC')
            ->limit(5)
            ->findAll();

        $statusAktif = $this->pengajuanModel
            ->where('pegawai_id', $userId)
            ->whereIn('status', ['pending_teman_sejawat', 'pending_spv', 'pending_hrd', 'pending_direktur'])
            ->orderBy('id', 'DESC')
            ->first();


        $jumlahCuti = $this->pengajuanModel
            ->selectSum('total_hari')
            ->where('pegawai_id', $userId)
            ->where('status', 'approved')
            ->where('tanggal_mulai >=', date('Y-01-01'))
            ->where('tanggal_selesai <=', date('Y-12-31'))
            ->first();

        $data = [
            'saldo'                 => $saldo,
            'pengajuanTerakhir'     => $pengajuanTerakhir,
            'pengajuanTerakhirList' => $pengajuanTerakhirList,
            'statusAktif'           => $statusAktif,
            'jumlahCuti'            => $jumlahCuti
        ];

        return view('pegawai/dashboard/index', $data);
    }
}
