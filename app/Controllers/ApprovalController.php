<?php

namespace App\Controllers;

use App\Models\ApprovalModel;
use App\Models\PengajuanCutiModel;

class ApprovalController extends BaseController
{
    protected $cutiModel;
    protected $pengajuanModel;
    protected $approvalModel;

    public function __construct()
    {
        $this->cutiModel = new PengajuanCutiModel();
        $this->pengajuanModel = new PengajuanCutiModel();
        $this->approvalModel = new ApprovalModel();
    }

    public function index()
    {
        $data['cuti'] = $this->cutiModel
            ->where('status', 'pending')
            ->findAll();

        return view('approval/index', $data);
    }

    public function approveHrd($id)
    {
        return $this->approveByRole($id, 'hrd', '/approvalhrd');
    }

    public function rejectHrd($id)
    {
        return $this->rejectByRole($id, 'hrd');
    }

    public function approveSpv($id)
    {
        return $this->approveByRole($id, 'spv', '/spv');
    }

    public function rejectSpv($id)
    {
        return $this->rejectByRole($id, 'spv');
    }

    public function approveDirektur($id)
    {
        return $this->approveByRole($id, 'direktur', '/direktur');
    }

    public function rejectDirektur($id)
    {
        return $this->rejectByRole($id, 'direktur');
    }

    public function approveTeman($id)
    {
        return $this->approveByRole($id, 'teman', '/teman');
    }

    public function rejectTeman($id)
    {
        return $this->rejectByRole($id, 'teman');
    }

    private function approveByRole($id, $role, $redirect)
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
            'approver_id' => session()->get('user')['id'] ?? null,
            'role_approver' => $role,
            'status' => 'approved',
            'catatan' => 'Disetujui ' . ucfirst($role)
        ]);

        return redirect()->to($redirect)->with('success', 'Pengajuan cuti disetujui!');
    }

    private function rejectByRole($id, $role)
    {
        $cuti = $this->cutiModel->find($id);
        if ($cuti && $cuti['pegawai_id'] == session()->get('user')['id']) {
            return redirect()->back()->with('error', 'Anda tidak bisa menolak pengajuan sendiri.');
        }

        $this->cutiModel->update($id, [
            'status' => 'rejected'
        ]);

        $this->approvalModel->save([
            'cuti_id' => $id,
            'approver_id' => session()->get('user')['id'] ?? null,
            'role_approver' => $role,
            'status' => 'rejected',
            'catatan' => 'Ditolak ' . ucfirst($role)
        ]);

        return redirect()->back()->with('success', 'Pengajuan cuti ditolak.');
    }
}
