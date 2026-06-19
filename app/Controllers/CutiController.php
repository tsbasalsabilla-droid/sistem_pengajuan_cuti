<?php

namespace App\Controllers;

use App\Models\PengajuanCutiModel;
use App\Models\PegawaiModel;
use App\Models\DetailStatusCutiModel;

class CutiController extends BaseController
{
    protected $pengajuanModel;
    protected $pegawaiModel;
    protected $detailModel;

    public function __construct()
    {
        $this->pengajuanModel = new PengajuanCutiModel();
        $this->pegawaiModel = new PegawaiModel();
        $this->detailModel = new DetailStatusCutiModel();
    }

    public function index()
    {
        $userId = session()->get('user')['id'];

        $data['cuti'] = $this->pengajuanModel
            ->where('pegawai_id', $userId)
            ->findAll();

        return view('pegawai/cuti/index', $data);
    }

    public function create()
    {
        return view('pegawai/cuti/create');
    }

    public function store()
    {
        $tanggalMulai = $this->request->getPost('tanggal_mulai');
        $tanggalSelesai = $this->request->getPost('tanggal_selesai');

        if ($tanggalSelesai < $tanggalMulai) {
            return redirect()->back()->with('error', 'Tanggal tidak valid');
        }


        $start = new \DateTime($tanggalMulai);
        $end   = new \DateTime($tanggalSelesai);
        $end->modify('+1 day');

        $interval  = new \DateInterval('P1D');
        $dateRange = new \DatePeriod($start, $interval, $end);


        $daftarLibur = [
            '2026-06-16',
        ];

        $totalHari = 0;

        foreach ($dateRange as $date) {
            $tanggalSekarang = $date->format('Y-m-d');
            $hariMingguSabtu = $date->format('w');

            if ($hariMingguSabtu == 0 || $hariMingguSabtu == 6) {
                continue;
            }


            if (in_array($tanggalSekarang, $daftarLibur)) {
                continue;
            }


            $totalHari++;
        }

        if ($totalHari <= 0) {
            return redirect()->back()->with('error', 'Tanggal yang Anda pilih adalah hari libur/cuti bersama');
        }

        $userId = session()->get('user')['id'] ?? null;
        if (!$userId) {
            return redirect()->back()->with('error', 'Sesi pengguna tidak ditemukan');
        }

        $pegawai = $this->pegawaiModel->find($userId);


        if (!$pegawai || ($pegawai['saldo_cuti'] ?? $pegawai['sisa_cuti']) < $totalHari) {
            return redirect()->back()->with('error', 'Saldo cuti tidak cukup');
        }

        $this->pengajuanModel->insert([
            'pegawai_id' => $userId,
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
            'total_hari' => $totalHari,
            'alasan' => $this->request->getPost('alasan'),
            'status' => 'pending_teman_sejawat'
        ]);

        $pengajuanId = $this->pengajuanModel->getInsertID();

        $this->detailModel->insert([
            'pengajuan_id' => $pengajuanId,
            'level_approval' => 'teman',
            'status' => 'pending'
        ]);

        return redirect()->to('/pegawai/cuti')->with('success', 'Pengajuan berhasil');
    }

    public function detail($id)
    {
        $data['cuti'] = $this->pengajuanModel->find($id);

        $data['tracking'] = $this->detailModel
            ->select('detail_status_cuti.*, pegawai.nama')
            ->join('pegawai', 'pegawai.id = detail_status_cuti.approved_by', 'left')
            ->where('pengajuan_id', $id)
            ->where('detail_status_cuti.approved_by !=', null)
            ->findAll();

        return view('pegawai/cuti/detail', $data);
    }

    public function approve($id)
    {
        $cuti = $this->pengajuanModel->find($id);
        if ($cuti && $cuti['pegawai_id'] == session()->get('user')['id']) {
            return redirect()->back()->with('error', 'Anda tidak bisa menyetujui pengajuan sendiri.');
        }

        $role = session()->get('user')['role'];
        $nextStatus = 'approved';

        if ($role == 'teman') {
            $nextStatus = 'pending_spv';
        } elseif ($role == 'spv') {
            $nextStatus = 'pending_hrd';
        } elseif ($role == 'hrd') {
            $nextStatus = 'pending_direktur';
        } elseif ($role == 'direktur') {
            $nextStatus = 'approved';

            $pegawai = $this->pegawaiModel->find($cuti['pegawai_id']);
            if ($pegawai) {
                $this->pegawaiModel->update($pegawai['id'], [
                    'cuti_terpakai' => $pegawai['cuti_terpakai'] + $cuti['total_hari'],
                    'sisa_cuti'     => $pegawai['sisa_cuti'] - $cuti['total_hari']
                ]);
            }
        }

        $this->pengajuanModel->update($id, [
            'status' => $nextStatus
        ]);

        $this->detailModel->insert([
            'pengajuan_id' => $id,
            'approved_by' => session()->get('user')['id'],
            'level_approval' => $role,
            'status' => 'approved',
            'approved_at' => date('Y-m-d H:i:s')
        ]);

        return redirect()->back()->with('success', 'Pengajuan disetujui');
    }

    public function reject($id)
    {
        $cuti = $this->pengajuanModel->find($id);
        if ($cuti && $cuti['pegawai_id'] == session()->get('user')['id']) {
            return redirect()->back()->with('error', 'Anda tidak bisa menolak pengajuan sendiri.');
        }

        $role = session()->get('user')['role'];


        $nextStatus = 'rejected';

        $this->pengajuanModel->update($id, [
            'status' => $nextStatus
        ]);

        $this->detailModel->insert([
            'pengajuan_id' => $id,
            'approved_by' => session()->get('user')['id'],
            'level_approval' => $role,
            'status' => 'rejected',
            'catatan' => $this->request->getPost('catatan'),
            'approved_at' => date('Y-m-d H:i:s')
        ]);

        return redirect()->back()->with('success', 'Pengajuan ditolak');
    }

    public function teman()
    {
        $userId = session()->get('user')['id'];

        $data['teman'] = $this->pengajuanModel
            ->select('pengajuan_cuti.*, pegawai.nama AS pegawai_nama')
            ->join('pegawai', 'pegawai.id = pengajuan_cuti.pegawai_id')
            ->where('pengajuan_cuti.status', 'pending_teman_sejawat')
            ->where('pengajuan_cuti.pegawai_id !=', $userId)
            ->where("pengajuan_cuti.id NOT IN (
                SELECT pengajuan_id FROM detail_status_cuti 
                WHERE approved_by = " . \Config\Database::connect()->escape($userId) . " AND level_approval = 'teman'
            )", NULL, false)
            ->findAll();

        $data['cuti'] = $data['teman'];

        return view('pegawai/cuti/teman', $data);
    }

    public function approveTeman($id)
    {
        $approval = new \App\Controllers\ApprovalController();
        return $approval->approveTeman($id);
    }

    public function rejectTeman($id)
    {
        $approval = new \App\Controllers\ApprovalController();
        return $approval->rejectTeman($id);
    }
}
