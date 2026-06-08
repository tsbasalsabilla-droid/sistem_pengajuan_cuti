<?php

namespace App\Controllers;

use App\Models\SaldoCutiModel;
use App\Models\PengajuanCutiModel;

class DashboardSpvController extends BaseController
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
        // user SPV
        $userId = 3;

        $saldo = $this->saldoModel
            ->where('user_id', $userId)
            ->first();

        $pengajuanTerakhir = $this->pengajuanModel
            ->where('pegawai_id', $userId)
            ->orderBy('id', 'DESC')
            ->first();

        $statusAktif = $this->pengajuanModel
            ->where('pegawai_id', $userId)
            ->whereIn('status', [
                'pending_hrd',
                'pending_direktur'
            ])
            ->orderBy('id', 'DESC')
            ->first();

        $jumlahCuti = $this->pengajuanModel
            ->selectSum('total_hari')
            ->where('pegawai_id', $userId)
            ->where('status', 'diterima')
            ->first();

        return view('spv/dashboard/index', [
            'saldo' => $saldo,
            'pengajuanTerakhir' => $pengajuanTerakhir,
            'statusAktif' => $statusAktif,
            'jumlahCuti' => $jumlahCuti
        ]);
    }
}
