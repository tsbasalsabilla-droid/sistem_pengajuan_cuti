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
            ->where('pegawai_id', $userId)
            ->first();

        // PENGAJUAN TERAKHIR
        $pengajuanTerakhir = $this->pengajuanModel
            ->where('pegawai_id', $userId)
            ->orderBy('id', 'DESC')
            ->first();

        // STATUS PENGAJUAN AKTIF
        $statusAktif = $this->pengajuanModel
            ->where('pegawai_id', $userId)
            ->where('status', 'pending')
            ->orderBy('id', 'DESC')
            ->first();

        // JUMLAH CUTI TAHUN INI
        $jumlahCuti = $this->pengajuanModel
            ->selectSum('total_hari')
            ->where('pegawai_id', $userId)
            ->where('status', 'approve')
            ->where('tanggal_mulai >=', date('Y-01-01'))
            ->where('tanggal_selesai <=', date('Y-12-31'))
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
