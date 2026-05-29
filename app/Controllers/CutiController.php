<?php

namespace App\Controllers;

use App\Models\PengajuanCutiModel;
use App\Models\SaldoCutiModel;
use App\Models\DetailStatusCutiModel;

class CutiController extends BaseController
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
        $userId = 1;

        $data['cuti'] = $this->pengajuanModel
            ->where('user_id', $userId)
            ->findAll();

        return view('pegawai/cuti/index', $data);
    }

    public function create()
    {
        return view('pegawai/cuti/create');
    }

    public function store()
    {
        $tanggalMulai = $this->request->getPost('tanggal_mulai');
        $tanggalSelesai = $this->request->getPost('tanggal_selesai');

        if ($tanggalSelesai < $tanggalMulai) {
            return redirect()->back()->with('error', 'Tanggal tidak valid');
        }

        $mulai = strtotime($tanggalMulai);
        $selesai = strtotime($tanggalSelesai);

        $totalHari = (($selesai - $mulai) / 86400) + 1;

        $userId = 1;
        
        $saldo = $this->saldoModel
            ->where('user_id', $userId)
            ->first();

        if ($saldo['sisa_cuti'] < $totalHari) {
            return redirect()->back()->with('error', 'Saldo cuti tidak cukup');
        }

        $this->pengajuanModel->insert([
            'user_id' => $userId,
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
            'total_hari' => $totalHari,
            'tujuan_cuti' => $this->request->getPost('tujuan_cuti'),
            'status' => 'pending_spv'
        ]);

        $pengajuanId = $this->pengajuanModel->getInsertID();

        $this->detailModel->insert([
            'pengajuan_id' => $pengajuanId,
            'level_approval' => 'spv',
            'status' => 'pending'
        ]);

        return redirect()->to('/cuti')
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

        return view('pegawai/cuti/detail', $data);
    }

    public function approve($id)
    {
        $cuti = $this->pengajuanModel->find($id);

        $role = session()->get('role');

        $nextStatus = '';

        if ($role == 'spv') {
            $nextStatus = 'pending_teman';
        } elseif ($role == 'teman') {
            $nextStatus = 'pending_hrd';
        } elseif ($role == 'hrd') {
            $nextStatus = 'pending_direktur';
        } elseif ($role == 'direktur') {
            $nextStatus = 'diterima';

            $saldo = $this->saldoModel
                ->where('user_id', $cuti['user_id'])
                ->first();

            $this->saldoModel->update($saldo['id'], [
                'cuti_terpakai' => $saldo['cuti_terpakai'] + $cuti['total_hari'],
                'sisa_cuti' => $saldo['sisa_cuti'] - $cuti['total_hari']
            ]);
        }

        $this->pengajuanModel->update($id, [
            'status' => $nextStatus
        ]);

        $this->detailModel->insert([
            'pengajuan_id' => $id,
            'approved_by' => 1,
            'level_approval' => $role,
            'status' => 'approved',
            'approved_at' => date('Y-m-d H:i:s')
        ]);

        return redirect()->back()
            ->with('success', 'Pengajuan disetujui');
    }

    public function reject($id)
    {
        $this->pengajuanModel->update($id, [
            'status' => 'ditolak'
        ]);

        $this->detailModel->insert([
            'pengajuan_id' => $id,
            'approved_by' => 1,
            'level_approval' => session()->get('role'),
            'status' => 'rejected',
            'catatan' => $this->request->getPost('catatan'),
            'approved_at' => date('Y-m-d H:i:s')
        ]);

        return redirect()->back()
            ->with('success', 'Pengajuan ditolak');
    }
}