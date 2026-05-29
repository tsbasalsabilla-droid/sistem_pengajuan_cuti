<?php

namespace App\Controllers;

use App\Models\CutiModel;

class Spv extends BaseController
{
    protected $cutiModel;

    public function __construct()
    {
        $this->cutiModel = new CutiModel();
    }

    // Approval SPV
    public function index()
    {
        $data['cuti'] = $this->cutiModel
            ->where('current_step', 'spv')
            ->findAll();

        return view('spv/index', $data);
    }

    // Approve pengajuan
    public function approve($id)
    {
        // update status cuti
        $this->cutiModel->update($id, [
            'status' => 'approved',
            'current_step' => 'teman'
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
        // update status cuti
        $this->cutiModel->update($id, [
            'status' => 'rejected',
            'current_step' => 'rejected'
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
        $pending = $this->cutiModel->where('current_step', 'spv')->countAllResults();
        $approved = $this->cutiModel->where('status', 'approved')->countAllResults();
        $rejected = $this->cutiModel->where('status', 'rejected')->countAllResults();
        $approvedCuti = $this->cutiModel
            ->where('current_step !=', 'spv')
            ->where('status !=', 'rejected')
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
