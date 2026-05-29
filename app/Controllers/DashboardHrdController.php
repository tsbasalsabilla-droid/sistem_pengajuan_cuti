<?php

namespace App\Controllers;

use App\Models\SaldoCutiModel;
use App\Models\PengajuanCutiModel;

class DashboardHrdController extends BaseController
{
    protected $saldoModel;
    protected $pengajuanModel;

    public function __construct()
    {
        $this->saldoModel = new SaldoCutiModel();
        $this->pengajuanModel = new PengajuanCutiModel();
    }

    public function index()
    {
        // user HRD
        $userId = 2;

        $saldo = $this->saldoModel
            ->where('user_id', $userId)
            ->first();

        $pengajuanTerakhir = $this->pengajuanModel
            ->where('user_id', $userId)
            ->orderBy('id', 'DESC')
            ->first();

        $statusAktif = $this->pengajuanModel
            ->where('user_id', $userId)
            ->where('status', 'pending_direktur')
            ->orderBy('id', 'DESC')
            ->first();

        $jumlahCuti = $this->pengajuanModel
            ->selectSum('total_hari')
            ->where('user_id', $userId)
            ->where('status', 'diterima')
            ->first();

        return view('hrd/dashboard/index', [
            'saldo' => $saldo,
            'pengajuanTerakhir' => $pengajuanTerakhir,
            'statusAktif' => $statusAktif,
            'jumlahCuti' => $jumlahCuti
        ]);
    }
}