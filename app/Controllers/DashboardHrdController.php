<?php

namespace App\Controllers;

use App\Models\SaldoCutiModel;
use App\Models\PengajuanCutiModel;
use App\Models\LaporanModel;
use App\Models\UserModel;

class DashboardHrdController extends BaseController
{
    protected $saldoModel;
    protected $pengajuanModel;
    protected $laporanModel;
    protected $userModel;

    public function __construct()
    {
        $this->saldoModel = new SaldoCutiModel();
        $this->pengajuanModel = new PengajuanCutiModel();
        $this->laporanModel = new LaporanModel();
        $this->userModel = new UserModel();
    }

    public function index()
    {
        return $this->dashboard();
    }

    public function dashboard()
    {
        // user HRD
        $userId = 2;

        $saldo = $this->saldoModel
            ->where('user_id', $userId)
            ->first();

        $pengajuanTerakhir = $this->pengajuanModel
            ->where('pegawai_id', $userId)
            ->orderBy('id', 'DESC')
            ->first();

        $statusAktif = $this->pengajuanModel
            ->where('pegawai_id', $userId)
            ->where('status', 'pending_direktur')
            ->orderBy('id', 'DESC')
            ->first();

        $jumlahCuti = $this->pengajuanModel
            ->selectSum('total_hari')
            ->where('pegawai_id', $userId)
            ->where('status', 'diterima')
            ->first();

        $data = [
            'saldo' => $saldo,
            'pengajuanTerakhir' => $pengajuanTerakhir,
            'statusAktif' => $statusAktif,
            'jumlahCuti' => $jumlahCuti,
            'title' => 'Dashboard HRD',
            'totalPegawai' => $this->userModel->countPegawai(),
            'totalLaporan' => $this->laporanModel->countLaporan(),
            'cutiBulanIni' => $this->laporanModel->countCutiThisMonth(),
            'recentLaporan' => $this->laporanModel->getRecentLaporan(5),
        ];

        return view('hrd/Dashboard/index', $data);
    }
}
