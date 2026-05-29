<?php

namespace App\Controllers;

use App\Models\CutiModel;
use App\Models\ApprovalModel;

class Cuti extends BaseController
{
    protected $cutiModel;

    public function __construct()
    {
        $this->cutiModel = new CutiModel();
    }

    // tampil form
    public function index()
    {
        return view('cuti/form');
    }

    // simpan pengajuan
    public function submit()
    {
        $mulai = $this->request->getPost('tanggal_mulai');
        $selesai = $this->request->getPost('tanggal_selesai');

        // hitung total hari
        $totalHari = (strtotime($selesai) - strtotime($mulai)) / 86400 + 1;

        $this->cutiModel->save([
            'pegawai_id' => 1, // sementara hardcode
            'tanggal_mulai' => $mulai,
            'tanggal_selesai' => $selesai,
            'total_hari' => $totalHari,
            'alasan' => $this->request->getPost('alasan'),
            'status' => 'pending',
            'current_step' => 'spv'
        ]);

        return redirect()->to('/cuti')->with('success', 'Pengajuan cuti berhasil!');
    }

    // history pengajuan
    public function history()
    {
        $data['cuti'] = $this->cutiModel->findAll();

        return view('cuti/history', $data);
    }


    // dashboard SPV
    public function spv()
    {
        $data['cuti'] = $this->cutiModel
            ->where('current_step', 'spv')
            ->findAll();

        return view('spv/index', $data);
    }

    // approve SPV
    public function approveSpv($id)
    {
        $approvalModel = new ApprovalModel();

        // update status cuti
        $this->cutiModel->update($id, [
            'current_step' => 'teman',
            'status' => 'diproses'
        ]);

        // simpan log approval
        $approvalModel->save([
            'cuti_id' => $id,
            'approver_id' => 2, // id SPV sementara
            'role_approver' => 'spv',
            'status' => 'approved',
            'catatan' => 'Disetujui SPV'
        ]);

        return redirect()->to('/spv');
    }

    // reject SPV
    public function rejectSpv($id)
    {
        $approvalModel = new ApprovalModel();

        // update cuti
        $this->cutiModel->update($id, [
            'status' => 'rejected',
            'current_step' => 'ditolak_spv'
        ]);

        // log approval
        $approvalModel->save([
            'cuti_id' => $id,
            'approver_id' => 2,
            'role_approver' => 'spv',
            'status' => 'rejected',
            'catatan' => 'Ditolak SPV'
        ]);

        return redirect()->to('/spv');
    }

    // dashboard teman sejawat
    public function teman()
    {
        $data['cuti'] = $this->cutiModel
            ->where('current_step', 'teman')
            ->findAll();

        return view('teman/index', $data);
    }

    // approve teman
    public function approveTeman($id)
    {
        $approvalModel = new ApprovalModel();

        // ambil data cuti
        $cuti = $this->cutiModel->find($id);

        // tambah approval count
        $jumlahApprove = $cuti['teman_approve_count'] + 1;

        // update count
        $dataUpdate = [
            'teman_approve_count' => $jumlahApprove
        ];

        // kalau sudah 3 approval lanjut HRD
        if ($jumlahApprove >= 3) {
            $dataUpdate['current_step'] = 'hrd';
        }

        $this->cutiModel->update($id, $dataUpdate);

        // simpan log
        $approvalModel->save([
            'cuti_id' => $id,
            'approver_id' => rand(3, 5), // simulasi teman
            'role_approver' => 'teman',
            'status' => 'approved',
            'catatan' => 'Approved teman sejawat'
        ]);

        return redirect()->to('/teman');
    }

    // reject teman
    public function rejectTeman($id)
    {
        $approvalModel = new ApprovalModel();

        $this->cutiModel->update($id, [
            'status' => 'rejected',
            'current_step' => 'ditolak_teman'
        ]);

        $approvalModel->save([
            'cuti_id' => $id,
            'approver_id' => rand(3, 5),
            'role_approver' => 'teman',
            'status' => 'rejected',
            'catatan' => 'Rejected teman sejawat'
        ]);

        return redirect()->to('/teman');
    }
    // dashboard HRD
    public function hrd()
    {
        $data['cuti'] = $this->cutiModel
            ->where('current_step', 'hrd')
            ->findAll();

        return view('hrd/index', $data);
    }

    // approve HRD
    public function approveHrd($id)
    {
        $approvalModel = new ApprovalModel();

        // update cuti
        $this->cutiModel->update($id, [
            'current_step' => 'direktur',
            'status' => 'diproses'
        ]);

        // simpan log
        $approvalModel->save([
            'cuti_id' => $id,
            'approver_id' => 6,
            'role_approver' => 'hrd',
            'status' => 'approved',
            'catatan' => 'Approved HRD'
        ]);

        return redirect()->to('/hrd');
    }

    // reject HRD
    public function rejectHrd($id)
    {
        $approvalModel = new ApprovalModel();

        $this->cutiModel->update($id, [
            'status' => 'rejected',
            'current_step' => 'ditolak_hrd'
        ]);

        $approvalModel->save([
            'cuti_id' => $id,
            'approver_id' => 6,
            'role_approver' => 'hrd',
            'status' => 'rejected',
            'catatan' => 'Rejected HRD'
        ]);

        return redirect()->to('/hrd');
    }
    // dashboard direktur
    public function direktur()
    {
        $data['cuti'] = $this->cutiModel
            ->where('current_step', 'direktur')
            ->findAll();

        return view('direktur/index', $data);
    }

    // approve direktur
    public function approveDirektur($id)
    {
        $approvalModel = new ApprovalModel();

        // final approval
        $this->cutiModel->update($id, [
            'status' => 'approved',
            'current_step' => 'selesai'
        ]);

        // simpan log
        $approvalModel->save([
            'cuti_id' => $id,
            'approver_id' => 7,
            'role_approver' => 'direktur',
            'status' => 'approved',
            'catatan' => 'Final Approved Direktur'
        ]);

        return redirect()->to('/direktur');
    }

    // reject direktur
    public function rejectDirektur($id)
    {
        $approvalModel = new ApprovalModel();

        $this->cutiModel->update($id, [
            'status' => 'rejected',
            'current_step' => 'ditolak_direktur'
        ]);

        $approvalModel->save([
            'cuti_id' => $id,
            'approver_id' => 7,
            'role_approver' => 'direktur',
            'status' => 'rejected',
            'catatan' => 'Rejected Direktur'
        ]);

        return redirect()->to('/direktur');
    }
}
