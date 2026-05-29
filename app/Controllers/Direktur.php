<?php

namespace App\Controllers;

use App\Models\CutiModel;

class Direktur extends BaseController
{
    protected $cutiModel;

    public function __construct()
    {
        $this->cutiModel = new CutiModel();
    }

    // Approval Direktur
    public function index()
    {
        $data['cuti'] = $this->cutiModel
            ->where('current_step', 'direktur')
            ->findAll();

        return view('direktur/index', $data);
    }

    // Approve pengajuan
    public function approve($id)
    {
        // update status cuti
        $this->cutiModel->update($id, [
            'status' => 'approved',
            'current_step' => 'selesai'
        ]);

        // insert approval log
        $approvalModel = new \App\Models\ApprovalModel();
        $approvalModel->save([
            'cuti_id' => $id,
            'role' => 'direktur',
            'action' => 'approve'
        ]);

        return redirect()->to('/direktur')->with('success', 'Pengajuan cuti disetujui!');
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
            'role' => 'direktur',
            'action' => 'reject'
        ]);

        return redirect()->to('/direktur')->with('success', 'Pengajuan cuti ditolak!');
    }

    // dashboard Direktur
    public function dashboard()
    {
        $pending = $this->cutiModel->where('current_step', 'direktur')->countAllResults();
        $approved = $this->cutiModel->where('status', 'approved')->countAllResults();
        $rejected = $this->cutiModel->where('status', 'rejected')->countAllResults();
        $approvedCuti = $this->cutiModel
            ->where('status', 'approved')
            ->findAll();

        $data = [
            'cuti' => $approvedCuti,
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected
        ];

        return view('direktur/dashboard', $data);
    }
}
