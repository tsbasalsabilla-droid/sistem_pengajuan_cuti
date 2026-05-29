<?php

namespace App\Controllers;

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
    }

    public function index()
    {
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
