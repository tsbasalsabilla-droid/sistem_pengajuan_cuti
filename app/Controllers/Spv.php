<?php

namespace App\Controllers;

use App\Models\PengajuanCutiModel;

class Spv extends BaseController
{
    protected $cutiModel;

    public function __construct()
    {
        $this->cutiModel = new PengajuanCutiModel();
    }

    public function index()
    {
        $userId = session()->get('user')['id'];


        $data['cuti'] = $this->cutiModel
            ->select('pengajuan_cuti.*, pegawai.nama AS nama_pegawai')
            ->join('pegawai', 'pegawai.id = pengajuan_cuti.pegawai_id', 'left')
            ->where('status', 'pending_spv')
            ->where('pengajuan_cuti.pegawai_id !=', $userId)
            ->findAll();

        return view('approval/index', $data);
    }

    public function approve($id)
    {
        $cuti = $this->cutiModel->find($id);
        if ($cuti && $cuti['pegawai_id'] == session()->get('user')['id']) {
            return redirect()->back()->with('error', 'Anda tidak bisa menyetujui pengajuan sendiri.');
        }

        $this->cutiModel->update($id, [
            'status' => 'pending_hrd'
        ]);

        $approvalModel = new \App\Models\ApprovalModel();
        $approvalModel->save([
            'cuti_id'       => $id,
            'approver_id'   => session()->get('user')['id'] ?? 0,
            'role_approver' => 'spv',
            'status'        => 'rejected',
            'catatan'       => 'Ditolak oleh SPV'
        ]);

        return redirect()->to('/spv')->with('success', 'Pengajuan cuti disetujui!');
    }

    public function reject($id)
    {
        $cuti = $this->cutiModel->find($id);
        if ($cuti && $cuti['pegawai_id'] == session()->get('user')['id']) {
            return redirect()->back()->with('error', 'Anda tidak bisa menolak pengajuan sendiri.');
        }

        $this->cutiModel->update($id, [
            'status' => 'rejected'
        ]);

        $approvalModel = new \App\Models\ApprovalModel();
        $approvalModel->save([
            'cuti_id' => $id,
            'role' => 'spv',
            'action' => 'reject'
        ]);

        return redirect()->to('/spv')->with('success', 'Pengajuan cuti ditolak!');
    }

    public function dashboard()
    {
        $userId = (int) session()->get('user')['id'];

        if (empty($userId)) {
            return redirect()->to('/auth/login')->with('error', 'Sesi Anda telah habis, silakan login kembali.');
        }

        $pending = $this->cutiModel
            ->where('pengajuan_cuti.pegawai_id !=', $userId)
            ->where('pengajuan_cuti.status', 'pending_spv')
            ->countAllResults();

        $approved = $this->cutiModel
            ->where('pengajuan_cuti.pegawai_id !=', $userId)
            ->where('pengajuan_cuti.status', 'approved')
            ->countAllResults();

        $rejected = $this->cutiModel
            ->where('pengajuan_cuti.pegawai_id !=', $userId)
            ->where('pengajuan_cuti.status', 'rejected')
            ->countAllResults();

        $approvedCuti = $this->cutiModel
            ->select('pengajuan_cuti.*, pegawai.nama AS nama_pegawai')
            ->join('pegawai', 'pegawai.id = pengajuan_cuti.pegawai_id', 'left')
            ->where('pengajuan_cuti.pegawai_id !=', $userId)
            ->whereIn('pengajuan_cuti.status', ['pending_hrd', 'pending_direktur', 'approved'])
            ->findAll();

        $data = [
            'cuti'     => $approvedCuti,
            'pending'  => $pending,
            'approved' => $approved,
            'rejected' => $rejected
        ];

        return view('spv/dashboard', $data);
    }
}
