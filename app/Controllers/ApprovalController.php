<?php

namespace App\Controllers;

use App\Models\ApprovalModel;
use App\Models\PegawaiModel;
use App\Models\PengajuanCutiModel;
use App\Models\DetailStatusCutiModel;

class ApprovalController extends BaseController
{
    protected $cutiModel;
    protected $pengajuanModel;
    protected $approvalModel;
    protected $pegawaiModel;
    protected $detailstatuscutiModel;

    public function __construct()
    {
        $this->cutiModel = new PengajuanCutiModel();
        $this->pengajuanModel = new PengajuanCutiModel();
        $this->approvalModel = new ApprovalModel();
        $this->pegawaiModel = new PegawaiModel();
        $this->detailstatuscutiModel = new DetailStatusCutiModel();
    }

    public function index()
    {
        $role = session()->get('user')['role'];
        $userId = session()->get('user')['id'];

        if ($role == 'pegawai') {
            $data['cuti'] = $this->cutiModel
                ->select('pengajuan_cuti.*, pegawai.nama AS nama_pegawai')
                ->join('pegawai', 'pegawai.id = pengajuan_cuti.pegawai_id', 'left')
                ->where('pengajuan_cuti.status', 'pending_teman_sejawat')
                ->where('pengajuan_cuti.pegawai_id !=', $userId)
                ->findAll();
        } elseif ($role == 'spv') {
            $data['cuti'] = $this->cutiModel
                ->select('pengajuan_cuti.*, pegawai.nama AS nama_pegawai')
                ->join('pegawai', 'pegawai.id = pengajuan_cuti.pegawai_id', 'left')
                ->where('status', 'pending_spv')
                ->findAll();
        } elseif ($role == 'hrd') {
            $data['cuti'] = $this->cutiModel
                ->select('pengajuan_cuti.*, pegawai.nama AS nama_pegawai')
                ->join('pegawai', 'pegawai.id = pengajuan_cuti.pegawai_id', 'left')
                ->where('status', 'pending_hrd')
                ->where('pegawai_id !=', $userId)
                ->findAll();
        } elseif ($role == 'direktur') {
            $data['cuti'] = $this->cutiModel
                ->select('pengajuan_cuti.*, pegawai.nama AS nama_pegawai')
                ->join('pegawai', 'pegawai.id = pengajuan_cuti.pegawai_id', 'left')
                ->where('status', 'pending_direktur')
                ->where('pegawai_id !=', $userId)
                ->findAll();
        } else {
            $data['cuti'] = [];
        }

        return view('approval/index', $data);
    }

    public function approveSpv($id)
    {
        return $this->approveByRole($id, 'spv', '/approval');
    }

    public function rejectSpv($id)
    {
        return $this->rejectByRole($id, 'spv', '/approval');
    }

    public function approveHrd($id)
    {
        return $this->approveByRole($id, 'hrd', '/approval');
    }

    public function rejectHrd($id)
    {
        return $this->rejectByRole($id, 'hrd', '/approval');
    }

    public function approveDirektur($id)
    {
        return $this->approveByRole($id, 'direktur', '/direktur');
    }

    public function rejectDirektur($id)
    {
        return $this->rejectByRole($id, 'direktur', '/direktur');
    }

    public function approveTeman($id)
    {
        $userId = session()->get('user')['id'];

        $cuti = $this->pengajuanModel->find($id);
        if ($cuti && $cuti['pegawai_id'] == $userId) {
            return redirect()->back()->with('error', 'Anda tidak bisa menyetujui pengajuan sendiri.');
        }

        $sudahPernahKlik = $this->detailstatuscutiModel
            ->where('pengajuan_id', $id)
            ->where('approved_by', $userId)
            ->where('level_approval', 'teman')
            ->first();

        if ($sudahPernahKlik) {
            return redirect()->back()->with('error', 'Anda sudah memberikan penilaian (Approve/Reject) untuk pengajuan ini.');
        }

        $this->detailstatuscutiModel->insert([
            'pengajuan_id'   => $id,
            'approved_by'    => $userId,
            'level_approval' => 'teman',
            'status'         => 'approved',
            'catatan'        => 'Disetujui Teman Sejawat',
            'approved_at'    => date('Y-m-d H:i:s')
        ]);

        $jumlahApprove = $this->detailstatuscutiModel
            ->where('pengajuan_id', $id)
            ->where('level_approval', 'teman')
            ->where('status', 'approved')
            ->countAllResults();

        $jumlahReject = $this->detailstatuscutiModel
            ->where('pengajuan_id', $id)
            ->where('level_approval', 'teman')
            ->where('status', 'rejected')
            ->countAllResults();

        $totalSuaraMasuk = $jumlahApprove + $jumlahReject;

        if ($totalSuaraMasuk >= 3) {
            if ($jumlahApprove > $jumlahReject) {
                $this->pengajuanModel->update($id, ['status' => 'pending_spv']);
                $pesan = 'Pengajuan disetujui berdasarkan mayoritas suara, berlanjut ke SPV.';
            } else {
                $this->gugurkanSemuaLevel($id, 'Ditolak otomatis karena voting Teman Sejawat menghasilkan keputusan ditolak.');
                $pesan = 'Pengajuan ditolak berdasarkan mayoritas suara.';
            }
        } else {
            $pesan = 'Persetujuan berhasil disimpan. Menunggu teman lainnya menyelesaikan voting (' . $totalSuaraMasuk . '/3).';
        }

        return redirect()->back()->with('success', $pesan);
    }

    public function rejectTeman($id_pengajuan)
    {
        $userId = session()->get('user')['id'];

        $cuti = $this->pengajuanModel->find($id_pengajuan);
        if ($cuti && $cuti['pegawai_id'] == $userId) {
            return redirect()->back()->with('error', 'Anda tidak bisa menolak pengajuan sendiri.');
        }

        $sudahPernahKlik = $this->detailstatuscutiModel
            ->where('pengajuan_id', $id_pengajuan)
            ->where('approved_by', $userId)
            ->where('level_approval', 'teman')
            ->first();

        if ($sudahPernahKlik) {
            return redirect()->back()->with('error', 'Anda sudah memberikan penilaian (Approve/Reject) untuk pengajuan ini.');
        }

        $this->detailstatuscutiModel->insert([
            'pengajuan_id'   => $id_pengajuan,
            'approved_by'    => $userId,
            'level_approval' => 'teman',
            'status'         => 'rejected',
            'catatan'        => $this->request->getPost('catatan') ?? 'Ditolak Teman Sejawat',
            'approved_at'    => date('Y-m-d H:i:s')
        ]);

        $jumlahApprove = $this->detailstatuscutiModel
            ->where('pengajuan_id', $id_pengajuan)
            ->where('level_approval', 'teman')
            ->where('status', 'approved')
            ->countAllResults();

        $jumlahReject = $this->detailstatuscutiModel
            ->where('pengajuan_id', $id_pengajuan)
            ->where('level_approval', 'teman')
            ->where('status', 'rejected')
            ->countAllResults();

        $totalSuaraMasuk = $jumlahApprove + $jumlahReject;

        if ($totalSuaraMasuk >= 3) {
            if ($jumlahReject > $jumlahApprove) {
                $this->gugurkanSemuaLevel($id_pengajuan, 'Ditolak otomatis karena mayoritas voting Teman Sejawat memilih Reject.');
                $pesan = 'Pengajuan ditolak berdasarkan mayoritas suara.';
            } else {
                $this->pengajuanModel->update($id_pengajuan, ['status' => 'pending_spv']);
                $pesan = 'Pengajuan lolos ke SPV berdasarkan mayoritas suara setuju.';
            }
        } else {
            if ($jumlahReject >= 2) {
                $this->gugurkanSemuaLevel($id_pengajuan, 'Pengajuan resmi ditolak karena suara penolakan sudah mencapai mayoritas (2/3).');
                $pesan = 'Pengajuan resmi ditolak karena suara penolakan sudah mencapai mayoritas (2/3).';
            } else {
                $pesan = 'Suara penolakan Anda berhasil dicatat. Menunggu teman lainnya menyelesaikan voting (' . $totalSuaraMasuk . '/3).';
            }
        }

        return redirect()->back()->with('success', $pesan);
    }


    private function gugurkanSemuaLevel($id, $catatan)
    {

        $this->pengajuanModel->update($id, ['status' => 'rejected']);


        $levels = ['spv', 'hrd', 'direktur'];
        foreach ($levels as $lvl) {
            $this->detailstatuscutiModel->insert([
                'pengajuan_id'   => $id,
                'approved_by'    => null,
                'level_approval' => $lvl,
                'status'         => 'rejected',
                'catatan'        => $catatan,
                'approved_at'    => date('Y-m-d H:i:s')
            ]);

            try {
                $this->approvalModel->save([
                    'cuti_id'       => $id,
                    'approver_id'   => null,
                    'role_approver' => $lvl,
                    'status'        => 'rejected',
                    'catatan'       => $catatan
                ]);
            } catch (\Exception $e) {
            }
        }
    }

    private function approveByRole($id, $role, $redirect)
    {
        $cuti = $this->cutiModel->find($id);
        if (!$cuti) {
            return redirect()->to($redirect)->with('error', 'Data tidak ditemukan.');
        }

        if ($cuti['pegawai_id'] == session()->get('user')['id']) {
            return redirect()->back()->with('error', 'Anda tidak bisa menyetujui pengajuan sendiri.');
        }

        $nextStatus = 'approved';
        if ($role === 'spv') {
            $nextStatus = 'pending_hrd';
        } elseif ($role === 'hrd') {
            $nextStatus = 'pending_direktur';
        }

        $this->cutiModel->update($id, [
            'status' => $nextStatus
        ]);

        $this->detailstatuscutiModel->insert([
            'pengajuan_id'   => $id,
            'approved_by'    => session()->get('user')['id'],
            'level_approval' => $role,
            'status'         => 'approved',
            'approved_at'    => date('Y-m-d H:i:s')
        ]);

        if ($role == 'direktur') {
            $pegawai = $this->pegawaiModel->find($cuti['pegawai_id']);

            if ($pegawai) {
                $newCutiTerpakai = ($pegawai['cuti_terpakai'] ?? 0) + $cuti['total_hari'];
                $newSisa = ($pegawai['saldo_cuti'] ?? 12) - $cuti['total_hari'];

                $this->pegawaiModel->update($cuti['pegawai_id'], [
                    'cuti_terpakai' => $newCutiTerpakai,
                    'saldo_cuti'    => max(0, $newSisa)
                ]);
            }
        }

        try {
            $this->approvalModel->save([
                'cuti_id'       => $id,
                'approver_id'   => session()->get('user')['id'] ?? null,
                'role_approver' => $role,
                'status'        => 'approved',
                'catatan'       => 'Disetujui oleh ' . strtoupper($role)
            ]);
        } catch (\Exception $e) {
        }

        return redirect()->to($redirect)->with('success', 'Pengajuan cuti berhasil disetujui!');
    }

    private function rejectByRole($id, $role, $redirect = '/approval')
    {
        $cuti = $this->cutiModel->find($id);
        if (!$cuti) {
            return redirect()->to($redirect)->with('error', 'Data pengajuan cuti tidak ditemukan.');
        }

        $alasanRejectInput = $this->request->getPost('catatan') ?? '';
        $alasanReject = !empty($alasanRejectInput) ? $alasanRejectInput : 'Ditolak oleh ' . strtoupper($role) . ' tanpa alasan spesifik.';


        $this->cutiModel->update($id, [
            'status' => 'rejected'
        ]);

        $this->detailstatuscutiModel->insert([
            'pengajuan_id'   => $id,
            'approved_by'    => session()->get('user')['id'],
            'level_approval' => $role,
            'status'         => 'rejected',
            'catatan'        => $alasanReject,
            'approved_at'    => date('Y-m-d H:i:s')
        ]);

        try {
            $this->approvalModel->save([
                'cuti_id'       => $id,
                'approver_id'   => session()->get('user')['id'] ?? null,
                'role_approver' => $role,
                'status'        => 'rejected',
                'catatan'       => $alasanReject
            ]);
        } catch (\Exception $e) {
        }

        $allLevels = ['spv', 'hrd', 'direktur'];
        $currentKey = array_search($role, $allLevels);

        if ($currentKey !== false) {
            $upperLevels = array_slice($allLevels, $currentKey + 1);

            foreach ($upperLevels as $lvl) {
                $catatanSistem = 'Ditolak otomatis karena tidak lolos verifikasi di tingkat ' . strtoupper($role) . '.';

                $this->detailstatuscutiModel->insert([
                    'pengajuan_id'   => $id,
                    'approved_by'    => null,
                    'level_approval' => $lvl,
                    'status'         => 'rejected',
                    'catatan'        => $catatanSistem,
                    'approved_at'    => date('Y-m-d H:i:s')
                ]);

                try {
                    $this->approvalModel->save([
                        'cuti_id'       => $id,
                        'approver_id'   => null,
                        'role_approver' => $lvl,
                        'status'        => 'rejected',
                        'catatan'       => $catatanSistem
                    ]);
                } catch (\Exception $e) {
                }
            }
        }

        return redirect()->to($redirect)->with('success', 'Pengajuan cuti berhasil ditolak.');
    }
}