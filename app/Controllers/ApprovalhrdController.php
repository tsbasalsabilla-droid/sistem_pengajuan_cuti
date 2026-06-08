<?php

namespace App\Controllers;

use App\Models\ApprovalModel;
use App\Models\PengajuanCutiModel;

class ApprovalhrdController extends BaseController
{
    protected $cutiModel;
    protected $approvalModel;

    public function __construct()
    {
        $this->cutiModel = new PengajuanCutiModel();
        $this->approvalModel = new ApprovalModel();
    }

    public function index()
    {
        $data['cuti'] = $this->cutiModel
            ->where('status', 'pending')
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
            'status' => 'approve'
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
