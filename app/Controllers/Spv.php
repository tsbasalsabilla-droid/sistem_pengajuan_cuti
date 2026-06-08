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

    // Approval SPV
    public function index()
    {
        $data['cuti'] = $this->cutiModel
            ->where('status', 'pending_spv')
            ->findAll();

        return view('spv/index', $data);
    }

    // Approve pengajuan
    public function approve($id)
    {
        $cuti = $this->cutiModel->find($id);
        if ($cuti && $cuti['pegawai_id'] == session()->get('user')['id']) {
            return redirect()->back()->with('error', 'Anda tidak bisa menyetujui pengajuan sendiri.');
        }

        // update status cuti
        $this->cutiModel->update($id, [
            'status' => 'approve'
        ]);

        // insert approval log
        $approvalModel = new \App\Models\ApprovalModel();
        $approvalModel->save([
            'cuti_id' => $id,
            'role' => 'spv',
            'action' => 'approve'
        ]);

        return redirect()->to('/spv')->with('success', 'Pengajuan cuti disetujui!');
    }

    // Reject pengajuan
    public function reject($id)
    {
        $cuti = $this->cutiModel->find($id);
        if ($cuti && $cuti['pegawai_id'] == session()->get('user')['id']) {
            return redirect()->back()->with('error', 'Anda tidak bisa menolak pengajuan sendiri.');
        }

        // update status cuti
        $this->cutiModel->update($id, [
            'status' => 'rejected'
        ]);

        // insert approval log
        $approvalModel = new \App\Models\ApprovalModel();
        $approvalModel->save([
            'cuti_id' => $id,
            'role' => 'spv',
            'action' => 'reject'
        ]);

        return redirect()->to('/spv')->with('success', 'Pengajuan cuti ditolak!');
    }

    // dashboard SPV
    public function dashboard()
    {
        $pending = $this->cutiModel->where('status', 'pending')->countAllResults();
        $approved = $this->cutiModel->where('status', 'approve')->countAllResults();
        $rejected = $this->cutiModel->where('status', 'rejected')->countAllResults();
        $approvedCuti = $this->cutiModel
            ->where('status', 'approve')
            ->findAll();

        $data = [
            'cuti' => $approvedCuti,
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected
        ];

        return view('spv/dashboard', $data);
    }
}
