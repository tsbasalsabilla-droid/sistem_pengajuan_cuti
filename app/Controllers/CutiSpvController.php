<?php

namespace App\Controllers;

use App\Models\PengajuanCutiModel;
use App\Models\SaldoCutiModel;
use App\Models\DetailStatusCutiModel;

class CutiSpvController extends BaseController
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
        $userId = 3;

        $data['cuti'] = $this->pengajuanModel
            ->where('user_id', $userId)
            ->orderBy('id', 'DESC')
            ->findAll();

        return view('spv/cuti/index', $data);
    }

    public function create()
    {
        return view('spv/cuti/create');
    }

    public function store()
    {
        $tanggalMulai = $this->request->getPost('tanggal_mulai');
        $tanggalSelesai = $this->request->getPost('tanggal_selesai');

        if ($tanggalSelesai < $tanggalMulai) {
            return redirect()->back()
                ->with('error', 'Tanggal tidak valid');
        }

        $mulai = strtotime($tanggalMulai);
        $selesai = strtotime($tanggalSelesai);

        $totalHari = (($selesai - $mulai) / 86400) + 1;

        $userId = 3;

        $saldo = $this->saldoModel
            ->where('user_id', $userId)
            ->first();

        if (!$saldo) {
            return redirect()->back()
                ->with('error', 'Saldo cuti tidak ditemukan');
        }

        if ($saldo['sisa_cuti'] < $totalHari) {
            return redirect()->back()
                ->with('error', 'Saldo cuti tidak cukup');
        }

        // SPV langsung ke HRD
        $this->pengajuanModel->insert([
            'user_id' => $userId,
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
            'total_hari' => $totalHari,
            'tujuan_cuti' => $this->request->getPost('tujuan_cuti'),
            'status' => 'pending_hrd'
        ]);

        return redirect()->to('/spv/cuti')
            ->with('success', 'Pengajuan berhasil');
    }

    public function detail($id)
    {
        $data['cuti'] = $this->pengajuanModel->find($id);

        $data['tracking'] = $this->detailModel
            ->select('detail_status_cuti.*, users.nama')
            ->join('users', 'users.id = detail_status_cuti.approved_by', 'left')
            ->where('pengajuan_id', $id)
            ->findAll();

        return view('spv/cuti/detail', $data);
    }
}