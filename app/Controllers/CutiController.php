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
        $this->pengajuanModel->updateBatalOtomatis();

        $user = $this->user;
        if (!$user) {
            return redirect()->to('/auth/login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $userId = $user['id'];
        $perPage = 10;
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));

        $data['cuti'] = $this->pengajuanModel
            ->where('pegawai_id', $userId)
            ->orderBy('id', 'DESC')
            ->paginate($perPage);

        $data['pager'] = $this->pengajuanModel->pager;
        $data['title'] = 'History Pengajuan Cuti';
        $data['perPage'] = $perPage;
        $data['page'] = $page;

        $total = $this->pengajuanModel->where('pegawai_id', $userId)->countAllResults();
        $data['total'] = $total;
        $data['totalPages'] = max(1, (int) ceil($total / $perPage));

        return view('pegawai/cuti/index', $data);
    }

    public function create()
    {
        if (!$this->user) {
            return redirect()->to('/auth/login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $data = [
            'title' => 'Form Pengajuan Cuti'
        ];

        return view('pegawai/cuti/create', $data);
    }

    public function store()
    {
        if (!$this->user) {
            return redirect()->to('/auth/login')->with('error', 'Silakan login terlebih dahulu.');
        }

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

        $userId = $this->user['id'];
        if (!$userId) {
            return redirect()->back()->with('error', 'Sesi pengguna tidak ditemukan');
        }

        $pegawai = $this->pegawaiModel->find($userId);


        if (!$pegawai || ($pegawai['saldo_cuti'] ?? $pegawai['sisa_cuti']) < $totalHari) {
            return redirect()->back()->with('error', 'Saldo cuti tidak cukup');
        }

        $user = $this->pegawaiModel->find($userId);
        $temanSejawat = $this->pegawaiModel->countTemanSejawat($userId);

        if ($temanSejawat <= 0) {
            $this->pengajuanModel->insert([
                'pegawai_id' => $userId,
                'tanggal_mulai' => $tanggalMulai,
                'tanggal_selesai' => $tanggalSelesai,
                'total_hari' => $totalHari,
                'alasan' => $this->request->getPost('alasan'),
                'status' => 'pending_spv'
            ]);

            return redirect()->to('/pegawai/cuti')->with('success', 'Pengajuan berhasil. Karena Anda satu-satunya anggota divisi ini, pengajuan langsung diteruskan ke SPV.');
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
        if (!$this->user) {
            return redirect()->to('/auth/login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $data['cuti'] = $this->pengajuanModel->find($id);

        $data['tracking'] = $this->detailModel
            ->select('detail_status_cuti.*, pegawai.nama')
            ->join('pegawai', 'pegawai.id = detail_status_cuti.approved_by', 'left')
            ->where('pengajuan_id', $id)
            ->where('detail_status_cuti.approved_by !=', null)
            ->findAll();

        $data['title'] = 'Detail Pengajuan Cuti';
        return view('pegawai/cuti/detail', $data);
    }

    public function approve($id)
    {
        $user = $this->user;
        if (!$user) {
            return redirect()->to('/auth/login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $cuti = $this->pengajuanModel->find($id);
        if ($cuti && $cuti['pegawai_id'] == $user['id']) {
            return redirect()->back()->with('error', 'Anda tidak bisa menyetujui pengajuan sendiri.');
        }

        $role = $user['role'];
        
        
        if ($role === 'teman') {
            $approval = new \App\Controllers\ApprovalController();
            return $approval->approveTeman($id);
        }

        $nextStatus = 'approved';
        if ($role == 'spv') {
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
            'approved_by' => $user['id'],
            'level_approval' => $role,
            'status' => 'approved',
            'approved_at' => date('Y-m-d H:i:s')
        ]);

        return redirect()->back()->with('success', 'Pengajuan disetujui');
    }

    public function reject($id)
    {
        $user = $this->user;
        if (!$user) {
            return redirect()->to('/auth/login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $cuti = $this->pengajuanModel->find($id);
        if ($cuti && $cuti['pegawai_id'] == $user['id']) {
            return redirect()->back()->with('error', 'Anda tidak bisa menolak pengajuan sendiri.');
        }

        $role = $user['role'];

        
        
        if ($role === 'teman') {
            $approval = new \App\Controllers\ApprovalController();
            return $approval->rejectTeman($id);
        }

        $nextStatus = 'rejected';

        $this->pengajuanModel->update($id, [
            'status' => $nextStatus
        ]);

        $this->detailModel->insert([
            'pengajuan_id' => $id,
            'approved_by' => $user['id'],
            'level_approval' => $role,
            'status' => 'rejected',
            'catatan' => $this->request->getPost('catatan'),
            'approved_at' => date('Y-m-d H:i:s')
        ]);

        return redirect()->back()->with('success', 'Pengajuan ditolak');
    }

    public function teman()
    {
        $user = $this->user;
        if (!$user) {
            return redirect()->to('/auth/login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $userId = $user['id'];
        $user = $this->pegawaiModel->find($userId);
        $userDivisi = $user['id_divisi'] ?? null;

        $query = $this->pengajuanModel
            ->select('pengajuan_cuti.*, pegawai.nama AS pegawai_nama')
            ->join('pegawai', 'pegawai.id = pengajuan_cuti.pegawai_id')
            ->where('pengajuan_cuti.status', 'pending_teman_sejawat')
            ->where('pengajuan_cuti.pegawai_id !=', $userId)
            ->where("pengajuan_cuti.id NOT IN (
                SELECT pengajuan_id FROM detail_status_cuti 
                WHERE approved_by = " . \Config\Database::connect()->escape($userId) . " AND level_approval = 'teman'
            )", NULL, false);

        if ($userDivisi) {
            $query = $query->where('pegawai.id_divisi', $userDivisi);
        } else {
            $query = $query->where('pengajuan_cuti.id', 0);
        }

        $data['teman'] = $query->paginate(10);
        $data['pager'] = $this->pengajuanModel->pager;
        $data['cuti'] = $data['teman'];
        $data['title'] = 'Approval Teman';

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

    public function batal($id)
    {
        if (!$this->user) {
            return redirect()->to('/auth/login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $cuti = $this->pengajuanModel->find($id);

        if (!$cuti) {
            return redirect()->back()->with('error', 'Data tidak ditemukan.');
        }

        $data = [
            'status' => 'dibatalkan',
            'alasan_batal' => $this->request->getPost('alasan_batal')
        ];

        if (!$this->pengajuanModel->update($id, $data)) {
            dd($this->pengajuanModel->errors());
        }

        return redirect()->to('/pegawai/cuti')
            ->with('success', 'Pengajuan berhasil dibatalkan.');
    }
}
