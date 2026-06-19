<?php

namespace App\Controllers;

use App\Models\SaldoCutiModel;
use App\Models\PengajuanCutiModel;
use App\Models\LaporanModel;
use App\Models\UserModel;

class DashboardHrdController extends BaseController
{
    protected $laporanModel;
    protected $userModel;
    protected $saldoCutiModel;
    protected $pengajuanCutiModel;

    public function __construct()
    {
        $this->laporanModel       = new LaporanModel();
        $this->userModel          = new UserModel();
        $this->saldoCutiModel     = new SaldoCutiModel();
        $this->pengajuanCutiModel = new PengajuanCutiModel();
    }

    public function index()
    {
        $this->saldoCutiModel->syncSaldoCuti();

        $data = [
            'title'         => 'Dashboard',
            'totalPegawai'  => $this->userModel->countPegawai(),
            'totalLaporan'  => $this->laporanModel->countLaporan(),
            'cutiBulanIni'  => $this->laporanModel->countCutiThisMonth(),
            'recentLaporan' => $this->laporanModel->getRecentLaporan(5),
        ];

        return view('hrd/Dashboard/index', $data);
    }

    public function dashboard()
    {

        $userId = session()->get('user')['id'] ?? null;


        $saldo = $this->saldoCutiModel
            ->where('pegawai_id', $userId)
            ->first();


        $pengajuanTerakhir = $this->pengajuanCutiModel
            ->where('pegawai_id', $userId)
            ->orderBy('id', 'DESC')
            ->first();

        $statusAktif = $this->pengajuanCutiModel
            ->where('pegawai_id', $userId)
            ->where('status', 'pending_direktur')
            ->orderBy('id', 'DESC')
            ->first();

        $jumlahCuti = $this->pengajuanCutiModel
            ->selectSum('total_hari')
            ->where('pegawai_id', $userId)
            ->where('status', 'diterima')
            ->first();

        $data = [
            'saldo'             => $saldo,
            'pengajuanTerakhir' => $pengajuanTerakhir,
            'statusAktif'       => $statusAktif,
            'jumlahCuti'        => $jumlahCuti,
            'title'             => 'Dashboard HRD',
            'totalPegawai'      => $this->userModel->countPegawai(),
            'totalLaporan'      => $this->laporanModel->countLaporan(),
            'cutiBulanIni'      => $this->laporanModel->countCutiThisMonth(),
            'recentLaporan'     => $this->laporanModel->getRecentLaporan(5),
        ];

        return view('hrd/Dashboard/index', $data);
    }
}
