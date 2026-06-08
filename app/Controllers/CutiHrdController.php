<?php

namespace App\Controllers;

use App\Models\PengajuanCutiModel;
use App\Models\SaldoCutiModel;
use App\Models\DetailStatusCutiModel;

class CutiHrdController extends BaseController
{
    protected $pengajuanModel;
    protected $saldoModel;
    protected $detailModel;

    public function __construct()
    {
        $this->pengajuanModel = new PengajuanCutiModel();
        $this->saldoModel = new SaldoCutiModel();
        $this->detailModel = new DetailStatusCutiModel();
    }

    public function index()
    {
        return $this->create();
    }

    public function history()
    {
        $data['cuti'] = $this->pengajuanModel
        ->select('pengajuan_cuti.*, pegawai.nama as nama_pegawai')
        ->join('pegawai', 'pegawai.id = pengajuan_cuti.pegawai_id', 'left')
        ->orderBy('pengajuan_cuti.id', 'DESC')
        ->findAll();

        return view('hrd/cuti/index', $data);
    }

    public function create()
    {
        return view('hrd/cuti/create');
    }

    public function store()
    {
        $tanggalMulai = $this->request->getPost('tanggal_mulai');
        $tanggalSelesai = $this->request->getPost('tanggal_selesai');

        $mulai = strtotime($tanggalMulai);
        $selesai = strtotime($tanggalSelesai);

        $totalHari = (($selesai - $mulai) / 86400) + 1;

        $saldo = $this->saldoModel
            ->where('pegawai_id', $userId)
            ->first();

        if (!$saldo) {
            return redirect()->back()
                ->with('error', 'Saldo cuti tidak ditemukan');
        }

        if ($saldo['sisa_cuti'] < $totalHari) {
            return redirect()->back()
                ->with('error', 'Saldo cuti tidak cukup');
        }

        // HRD langsung ke direktur
        $this->pengajuanModel->insert([
            'pegawai_id' => $userId,
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
            'total_hari' => $totalHari,
            'alasan' => $this->request->getPost('alasan'),
            'status' => 'pending_direktur'
        ]);

        return redirect()->to('/hrd/cuti')
            ->with('success', 'Pengajuan berhasil');
    }

    public function detail($id)
    {
        $data['cuti'] = $this->pengajuanModel->find($id);

       $data['tracking'] = $this->detailModel
        ->select('detail_status_cuti.*, pegawai.nama')
        ->join('pegawai', 'pegawai.id = detail_status_cuti.approved_by', 'left')
        ->where('pengajuan_id', $id)
        ->findAll();

        return view('hrd/cuti/detail', $data);
    }
}
