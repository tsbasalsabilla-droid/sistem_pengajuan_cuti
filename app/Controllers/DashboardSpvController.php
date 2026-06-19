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
        $userId = session()->get('user')['id'];


        $saldo = null;
        $modelPending  = new \App\Models\PengajuanCutiModel();
        $modelApproved = new \App\Models\PengajuanCutiModel();
        $modelRejected = new \App\Models\PengajuanCutiModel();
        $modelCuti     = new \App\Models\PengajuanCutiModel();

        $pending = $modelPending->where('status', 'pending_spv')->countAllResults();

        $approved = $modelApproved->whereIn('status', ['pending_hrd', 'pending_direktur', 'approve', 'approved', 'diterima'])->countAllResults();

        $rejected = $modelRejected->whereIn('status', ['rejected', 'ditolak'])->countAllResults();


        $cuti = $modelCuti->select('pengajuan_cuti.*, pegawai.nama as nama_pegawai')
            ->join('pegawai', 'pegawai.id = pengajuan_cuti.pegawai_id')
            ->whereIn('pengajuan_cuti.status', ['pending_hrd', 'pending_direktur', 'approve', 'approved', 'diterima'])
            ->orderBy('pengajuan_cuti.id', 'DESC')
            ->findAll(10);

        return view('spv/dashboard', [
            'saldo'    => $saldo,
            'pending'  => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
            'cuti'     => $cuti
        ]);
    }
}
