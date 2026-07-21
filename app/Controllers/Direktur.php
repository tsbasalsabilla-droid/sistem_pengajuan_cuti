<?php

namespace App\Controllers;

use App\Models\PengajuanCutiModel;

class Direktur extends BaseController
{
    protected $cutiModel;

    public function __construct()
    {
        $this->cutiModel = new PengajuanCutiModel();
    }

    public function index()
    {
        $page = max(1, (int) $this->request->getGet('page'));
        $perPage = 10;

        $query = $this->cutiModel
            ->where('status', 'pending_direktur');

        $total = $query->countAllResults(false);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);

        $cuti = $query->orderBy('id', 'DESC')
            ->findAll($perPage, ($page - 1) * $perPage);

        $data = [
            'cuti' => $cuti,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'totalPages' => $totalPages,
        ];

        return view('direktur/index', $data);
    }

    public function approve($id)
    {
        $cuti = $this->cutiModel->find($id);
        if ($cuti && $cuti['pegawai_id'] == session()->get('user')['id']) {
            return redirect()->back()->with('error', 'Anda tidak bisa menyetujui pengajuan sendiri.');
        }

        $this->cutiModel->update($id, [
            'status' => 'approved'
        ]);

        $approvalModel = new \App\Models\ApprovalModel();
        $approvalModel->save([
            'cuti_id' => $id,
            'role' => 'direktur',
            'action' => 'approve'
        ]);

        return redirect()->to('/direktur')->with('success', 'Pengajuan cuti disetujui!');
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
            'role' => 'direktur',
            'action' => 'reject'
        ]);

        return redirect()->to('/direktur')->with('success', 'Pengajuan cuti ditolak!');
    }

    public function dashboard()
    {
        $cutiModel = new \App\Models\PengajuanCutiModel();

        $data['pending']  = $cutiModel->where('status', 'pending_direktur')->countAllResults();
        $data['approved'] = $cutiModel->where('status', 'approved')->countAllResults();
        $data['rejected'] = $cutiModel->where('status', 'rejected')->countAllResults();

        $page = max(1, (int) $this->request->getGet('page'));
        $perPage = 10;

        $query = $cutiModel
            ->select('pengajuan_cuti.*, pegawai.nama AS nama_pegawai')
            ->join('pegawai', 'pegawai.id = pengajuan_cuti.pegawai_id', 'left')
            ->where('pengajuan_cuti.status', 'approved');

        $total = $query->countAllResults(false);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);

        $cuti = $query->orderBy('pengajuan_cuti.id', 'DESC')
            ->findAll($perPage, ($page - 1) * $perPage);

        $data['cuti'] = $cuti;
        $data['page'] = $page;
        $data['perPage'] = $perPage;
        $data['total'] = $total;
        $data['totalPages'] = $totalPages;

        return view('direktur/dashboard', $data);
    }
}
