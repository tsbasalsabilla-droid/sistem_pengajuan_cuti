<?php

namespace App\Controllers;

use App\Models\PengajuanCutiModel;
use App\Models\DetailStatusCutiModel;

class Spv extends BaseController
{
    protected $cutiModel;
    protected $detailStatusModel;

    public function __construct()
    {
        $this->cutiModel = new PengajuanCutiModel();
        $this->detailStatusModel = new DetailStatusCutiModel();
    }

    public function index()
    {
        $userId = session()->get('user')['id'];

        $page = max(1, (int) $this->request->getGet('page'));
        $perPage = 10;

        $query = $this->cutiModel
            ->select('pengajuan_cuti.*, pegawai.nama AS nama_pegawai')
            ->join('pegawai', 'pegawai.id = pengajuan_cuti.pegawai_id', 'left')
            ->where('status', 'pending_spv')
            ->where('pengajuan_cuti.pegawai_id !=', $userId);

        
        $total = $query->countAllResults(false);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);

        
        $cuti = $query->orderBy('pengajuan_cuti.id', 'DESC')
            ->findAll($perPage, ($page - 1) * $perPage);

        $data = [
            'cuti'       => $cuti,
            'page'       => $page,
            'perPage'    => $perPage,
            'total'      => $total,
            'totalPages' => $totalPages,
        ];

        return view('spv/index', $data);
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

        $this->detailStatusModel->save([
            'pengajuan_id'   => $id,
            'approved_by'    => session()->get('user')['id'] ?? null,
            'level_approval' => 'spv',
            'status'         => 'approved',
            'catatan'        => null,
            'approved_at'    => date('Y-m-d H:i:s')
        ]);

        $approvalModel = new \App\Models\ApprovalModel();
        $approvalModel->save([
            'cuti_id'       => $id,
            'approver_id'   => session()->get('user')['id'] ?? null,
            'role_approver' => 'spv',
            'status'        => 'approved',
            'catatan'       => null
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

        $rejectReason = $this->request->getPost('catatan') ?? null;

        $this->detailStatusModel->save([
            'pengajuan_id'   => $id,
            'approved_by'    => session()->get('user')['id'] ?? null,
            'level_approval' => 'spv',
            'status'         => 'rejected',
            'catatan'        => $rejectReason,
            'approved_at'    => date('Y-m-d H:i:s')
        ]);

        $approvalModel = new \App\Models\ApprovalModel();
        $approvalModel->save([
            'cuti_id'       => $id,
            'approver_id'   => session()->get('user')['id'] ?? null,
            'role_approver' => 'spv',
            'status'        => 'rejected',
            'catatan'       => $rejectReason
        ]);

        return redirect()->to('/spv')->with('success', 'Pengajuan cuti ditolak!');
    }

    public function dashboard()
    {
        $userId = (int) session()->get('user')['id'];

        if (empty($userId)) {
            return redirect()->to('/auth/login')->with('error', 'Sesi Anda telah habis, silakan login kembali.');
        }

        $page = max(1, (int) $this->request->getGet('page'));
        $perPage = 10;

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

        $approvedQuery = $this->cutiModel
            ->select('pengajuan_cuti.*, pegawai.nama AS nama_pegawai')
            ->join('pegawai', 'pegawai.id = pengajuan_cuti.pegawai_id', 'left')
            ->where('pengajuan_cuti.pegawai_id !=', $userId)
            ->whereIn('pengajuan_cuti.status', ['pending_hrd', 'pending_direktur', 'approved']);

        $total = $approvedQuery->countAllResults(false);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);

        $approvedCuti = $approvedQuery
            ->orderBy('pengajuan_cuti.id', 'DESC')
            ->findAll($perPage, ($page - 1) * $perPage);

        $data = [
            'cuti'       => $approvedCuti,
            'pending'    => $pending,
            'approved'   => $approved,
            'rejected'   => $rejected,
            'page'       => $page,
            'perPage'    => $perPage,
            'total'      => $total,
            'totalPages' => $totalPages
        ];

        return view('spv/dashboard', $data);
    }
}
