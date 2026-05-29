<?php

namespace App\Controllers;

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
    }

    public function index()
    {
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