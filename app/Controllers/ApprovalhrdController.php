<?php

namespace App\Controllers;

use App\Models\ApprovalModel;
use App\Models\CutiModel;

class ApprovalhrdController extends BaseController
{
    protected $cutiModel;
    protected $approvalModel;

    public function __construct()
    {
        $this->cutiModel = new CutiModel();
        $this->approvalModel = new ApprovalModel();
    }

    public function index()
    {
        $data['cuti'] = $this->cutiModel
            ->where('current_step', 'hrd')
            ->findAll();

        return view('approval/index', $data);
    }

    public function approve($id)
    {
        $this->cutiModel->update($id, [
            'status' => 'approved',
            'current_step' => 'selesai'
        ]);

        $this->approvalModel->save([
            'cuti_id' => $id,
            'approver_id' => 6,
            'role_approver' => 'hrd',
            'status' => 'approved',
            'catatan' => 'Disetujui HRD'
        ]);

        return redirect()->to('/approvalhrd')->with('success', 'Pengajuan cuti disetujui!');
    }
}