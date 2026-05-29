<?php

namespace App\Controllers;

<<<<<<< HEAD
use App\Models\SaldoCutiModel;
use App\Models\PengajuanCutiModel;

class DashboardController extends BaseController
{
    protected $saldoModel;
    protected $pengajuanModel;

    public function __construct()
    {
        $this->saldoModel = new SaldoCutiModel();
        $this->pengajuanModel = new PengajuanCutiModel();
=======
use App\Models\LaporanModel;
use App\Models\UserModel;

class DashboardController extends BaseController
{
    protected $userModel;
    protected $laporanModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->laporanModel = new LaporanModel();
>>>>>>> affbd2d3d63a4f9f5f53a92a1110c45dbd3682db
    }

    public function index()
    {
<<<<<<< HEAD
        // sementara hardcode
        $userId = 1;

        // SALDO CUTI
        
        $saldo = $this->saldoModel
            ->where('user_id', $userId)
            ->first();

        // PENGAJUAN TERAKHIR
        $pengajuanTerakhir = $this->pengajuanModel
            ->where('user_id', $userId)
            ->orderBy('id', 'DESC')
            ->first();

        // STATUS PENGAJUAN AKTIF
        $statusAktif = $this->pengajuanModel
            ->where('user_id', $userId)
            ->whereIn('status', [
                'pending_spv',
                'pending_teman',
                'pending_hrd',
                'pending_direktur'
            ])
            ->orderBy('id', 'DESC')
            ->first();

        // JUMLAH CUTI TAHUN INI
        $jumlahCuti = $this->pengajuanModel
            ->selectSum('total_hari')
            ->where('user_id', $userId)
            ->where('status', 'diterima')
            ->where('created_at >=', date('Y-01-01'))
            ->where('created_at <=', date('Y-12-31'))
            ->first();

        $data = [
            'saldo' => $saldo,
            'pengajuanTerakhir' => $pengajuanTerakhir,
            'statusAktif' => $statusAktif,
            'jumlahCuti' => $jumlahCuti
        ];

       return view('pegawai/dashboard/index', $data);
    }
}
=======
        $data = [
            'title' => 'Dashboard',
            'totalPegawai' => $this->userModel->countPegawai(),
            'totalLaporan' => $this->laporanModel->countLaporan(),
            'cutiBulanIni' => $this->laporanModel->countCutiThisMonth(),
            'recentLaporan' => $this->laporanModel->getRecentLaporan(5),
        ];

        return view('Dashboard/index', $data);
    }
}
>>>>>>> affbd2d3d63a4f9f5f53a92a1110c45dbd3682db
