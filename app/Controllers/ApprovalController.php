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
        $currentUser = $this->pegawaiModel->find($userId);
        $userDivisi = $currentUser['id_divisi'] ?? null;

        $query = $this->cutiModel
            ->select('pengajuan_cuti.*, pegawai.nama AS nama_pegawai')
            ->join('pegawai', 'pegawai.id = pengajuan_cuti.pegawai_id', 'left');

        if ($role == 'pegawai') {
            $query = $query
                ->where('pengajuan_cuti.status', 'pending_teman_sejawat')
                ->where('pengajuan_cuti.pegawai_id !=', $userId);

            if ($userDivisi) {
                $query = $query->where('pegawai.id_divisi', $userDivisi);
            } else {
                $query = $query->where('pengajuan_cuti.id', 0);
            }
        } elseif ($role == 'spv') {
            $query = $query
                ->where('pengajuan_cuti.status', 'pending_spv');
        } elseif ($role == 'hrd') {
            $query = $query
                ->where('pengajuan_cuti.status', 'pending_hrd')
                ->where('pengajuan_cuti.pegawai_id !=', $userId);
        } elseif ($role == 'direktur') {
            $query = $query
                ->where('pengajuan_cuti.status', 'pending_direktur')
                ->where('pengajuan_cuti.pegawai_id !=', $userId);
        }

        if (in_array($role, ['pegawai', 'spv', 'hrd', 'direktur'])) {
            $data['cuti'] = $query
                ->orderBy('pengajuan_cuti.id', 'DESC')
                ->paginate(10);
            $data['pager'] = $this->cutiModel->pager;
        } else {
            $data['cuti'] = [];
            $data['pager'] = null;
        }

        $data['title'] = 'Approval Teman';

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
        return $this->approveByRole($id, 'hrd', '/hrd/approvalhrd/indexhrd');
    }

    public function rejectHrd($id)
    {
        return $this->rejectByRole($id, 'hrd', '/hrd/approvalhrd/indexhrd');
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
        $user = $this->pegawaiModel->find($userId);
        $cuti = $this->pengajuanModel->find($id);

        if ($cuti && $cuti['pegawai_id'] == $userId) {
            return redirect()->back()->with('error', 'Anda tidak bisa menyetujui pengajuan sendiri.');
        }

        if (! $cuti) {
            return redirect()->back()->with('error', 'Pengajuan tidak ditemukan.');
        }

        $pemohon = $this->pegawaiModel->find($cuti['pegawai_id']);
        if (! $pemohon || empty($pemohon['id_divisi']) || empty($user['id_divisi']) || $pemohon['id_divisi'] != $user['id_divisi']) {
            return redirect()->back()->with('error', 'Hanya teman sejawat dari divisi yang sama yang dapat menyetujui pengajuan ini.');
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

        
        $db = \Config\Database::connect();
        $approvedCount = $db->table('detail_status_cuti')
            ->select('approved_by')
            ->where('pengajuan_id', $id)
            ->where('level_approval', 'teman')
            ->where('status', 'approved')
            ->where('approved_by IS NOT NULL', null, false)
            ->groupBy('approved_by')
            ->get()
            ->getNumRows();

        
        $requiredApprovals = $this->pegawaiModel->countTemanSejawat($cuti['pegawai_id']);

        if ($requiredApprovals <= 0 || $approvedCount >= $requiredApprovals) {
            
            
            if (in_array($cuti['status'], ['pending_teman_sejawat', 'pending_teman'])) {
                $this->pengajuanModel->update($id, ['status' => 'pending_spv']);
            }
            return redirect()->back()->with('success', 'Pengajuan disetujui oleh teman sejawat divisi yang sama, berlanjut ke SPV.');
        }

        $remaining = max(0, $requiredApprovals - $approvedCount);
        return redirect()->back()->with('success', 'Anda menyetujui pengajuan.');
    }

    public function rejectTeman($id_pengajuan)
    {
        $userId = session()->get('user')['id'];
        $user = $this->pegawaiModel->find($userId);

        $cuti = $this->pengajuanModel->find($id_pengajuan);
        if ($cuti && $cuti['pegawai_id'] == $userId) {
            return redirect()->back()->with('error', 'Anda tidak bisa menolak pengajuan sendiri.');
        }

        if (! $cuti) {
            return redirect()->back()->with('error', 'Pengajuan tidak ditemukan.');
        }

        $pemohon = $this->pegawaiModel->find($cuti['pegawai_id']);
        if (! $pemohon || empty($pemohon['id_divisi']) || empty($user['id_divisi']) || $pemohon['id_divisi'] != $user['id_divisi']) {
            return redirect()->back()->with('error', 'Hanya teman sejawat dari divisi yang sama yang dapat menolak pengajuan ini.');
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

        $this->gugurkanSemuaLevel($id_pengajuan, 'Ditolak oleh teman sejawat divisi yang sama.');

        return redirect()->back()->with('success', 'Pengajuan ditolak oleh teman sejawat divisi yang sama.');
    }


    private function gugurkanSemuaLevel($id, $catatan)
    {
        
        
        $this->pengajuanModel->update($id, ['status' => 'rejected']);
    }

    private function approveByRole($id, $role, $redirect)
    {
        $cuti = $this->cutiModel->find($id);
        if (!$cuti) {
            return redirect()->to(base_url($redirect))->with('error', 'Data tidak ditemukan.');
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

        return redirect()->to(base_url($redirect))->with('success', 'Pengajuan cuti berhasil disetujui!');
    }

    private function rejectByRole($id, $role, $redirect = '/approval')
    {
        $cuti = $this->cutiModel->find($id);
        if (!$cuti) {
            return redirect()->to(base_url($redirect))->with('error', 'Data pengajuan cuti tidak ditemukan.');
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

        return redirect()->to(base_url($redirect))->with('success', 'Pengajuan cuti berhasil ditolak.');
    }
}