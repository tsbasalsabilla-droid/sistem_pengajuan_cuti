<?php

namespace App\Controllers;

use App\Models\ApprovalModel;
use App\Models\PengajuanCutiModel;
use App\Models\DetailStatusCutiModel;

class ApprovalhrdController extends BaseController
{
    protected $cutiModel;
    protected $approvalModel;
    protected $detailModel;

    public function __construct()
    {
        $this->cutiModel = new PengajuanCutiModel();
        $this->approvalModel = new ApprovalModel();
        $this->detailModel = new DetailStatusCutiModel();
    }

    public function index()
    {
        $data['cuti'] = $this->cutiModel
            ->select('pengajuan_cuti.*, pegawai.nama AS nama_pegawai')
            ->join('pegawai', 'pegawai.id = pengajuan_cuti.pegawai_id', 'left')
            ->whereIn('pengajuan_cuti.status', ['pending', 'pending_hrd'])
            ->orderBy('pengajuan_cuti.id', 'DESC')
            ->findAll();

        return view('hrd/approvalhrd/indexhrd', $data);
    }

    public function approve($id)
    {
        $cuti = $this->cutiModel->find($id);
        if ($cuti && $cuti['pegawai_id'] == session()->get('user')['id']) {
            return redirect()->back()->with('error', 'Anda tidak bisa menyetujui pengajuan sendiri.');
        }

        $this->cutiModel->update($id, [
            'status' => 'pending_direktur'
        ]);

        $this->detailModel->save([
            'pengajuan_id'   => $id,
            'approved_by'    => session()->get('user')['id'] ?? null,
            'level_approval' => 'hrd',
            'status'         => 'approved',
            'catatan'        => 'Disetujui HRD'
        ]);

        return redirect()->to(base_url('hrd/approvalhrd/indexhrd'));
    }
}
