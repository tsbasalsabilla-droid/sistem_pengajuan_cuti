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
        $userId = session()->get('user')['id'];

        $data['cuti'] = $this->pengajuanModel
            ->where('pegawai_id', $userId)
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

        if (!$saldo || $saldo['sisa_cuti'] < $totalHari) {
            return redirect()->back()->with('error', 'Saldo cuti tidak cukup');
        }

        $this->pengajuanModel->insert([
            'pegawai_id' => $userId,
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
            'total_hari' => $totalHari,
            'alasan' => $this->request->getPost('alasan'),
            'status' => 'pending'
        ]);

        $pengajuanId = $this->pengajuanModel->getInsertID();

        $this->detailModel->insert([
            'pengajuan_id' => $pengajuanId,
            'level_approval' => 'spv',
            'status' => 'pending'
        ]);

        return redirect()->to('/pegawai/cuti')
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

        return view('pegawai/cuti/detail', $data);
    }

    public function approve($id)
    {
        $cuti = $this->pengajuanModel->find($id);
        if ($cuti && $cuti['pegawai_id'] == session()->get('user')['id']) {
            return redirect()->back()->with('error', 'Anda tidak bisa menyetujui pengajuan sendiri.');
        }

        $role = session()->get('role');

        $nextStatus = 'approve';

        if ($role == 'direktur') {
            $nextStatus = 'approve';
            $saldo = $this->saldoModel
                ->where('user_id', $cuti['pegawai_id'])
                ->first();

            if ($saldo) {
                $this->saldoModel->update($saldo['id'], [
                    'cuti_terpakai' => $saldo['cuti_terpakai'] + $cuti['total_hari'],
                    'sisa_cuti' => $saldo['sisa_cuti'] - $cuti['total_hari']
                ]);
            }
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
        $cuti = $this->pengajuanModel->find($id);
        if ($cuti && $cuti['pegawai_id'] == session()->get('user')['id']) {
            return redirect()->back()->with('error', 'Anda tidak bisa menolak pengajuan sendiri.');
        }

        $this->pengajuanModel->update($id, [
            'status' => 'rejected'
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
