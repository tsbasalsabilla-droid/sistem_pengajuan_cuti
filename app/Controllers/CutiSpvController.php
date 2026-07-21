<?php

namespace App\Controllers;

use App\Models\PengajuanCutiModel;
use App\Models\DetailStatusCutiModel;
use App\Models\PegawaiModel;

class CutiSpvController extends BaseController
{
    protected $pengajuanModel;
    protected $detailModel;
    protected $pegawaiModel;

    public function __construct()
    {
        $this->pengajuanModel = new PengajuanCutiModel();
        $this->detailModel = new DetailStatusCutiModel();
        $this->pegawaiModel = new PegawaiModel();
    }

    public function index()
    {
        $this->pengajuanModel->updateBatalOtomatis();

        $userId = session()->get('user')['id'];
        $page = max(1, (int) $this->request->getGet('page'));
        $perPage = 10;

        $query = $this->pengajuanModel
            ->where('pegawai_id', $userId);

        $total = $query->countAllResults(false);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);

        $data['cuti'] = $query
            ->orderBy('id', 'DESC')
            ->findAll($perPage, ($page - 1) * $perPage);

        $data['page'] = $page;
        $data['perPage'] = $perPage;
        $data['total'] = $total;
        $data['totalPages'] = $totalPages;

        return view('spv/cuti/index', $data);
    }

    public function create()
    {
        return view('spv/cuti/create');
    }

    public function store()
    {
        $userId = session()->get('user')['id'] ?? null;

        if (!$userId) {
            return redirect()->back()
                ->with('error', 'Sesi pengguna tidak ditemukan');
        }

        $tanggalMulai = $this->request->getPost('tanggal_mulai');
        $tanggalSelesai = $this->request->getPost('tanggal_selesai');

        if ($tanggalSelesai < $tanggalMulai) {
            return redirect()->back()
                ->with('error', 'Tanggal tidak valid');
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
            return redirect()->back()
                ->with('error', 'Tanggal yang Anda pilih adalah hari libur/cuti bersama.');
        }


        $pegawai = $this->pegawaiModel->find($userId);

        if (!$pegawai) {
            return redirect()->back()
                ->with('error', 'Data pegawai tidak ditemukan');
        }


        if ($pegawai['saldo_cuti'] < $totalHari) {
            return redirect()->back()
                ->with('error', 'Saldo cuti tidak cukup. Sisa saldo Anda: ' . $pegawai['saldo_cuti'] . ' hari.');
        }

        $data = [
            'pegawai_id' => $userId,
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
            'total_hari' => $totalHari,
            'alasan' => $this->request->getPost('alasan'),
            'status' => 'pending_hrd'
        ];

        $result = $this->pengajuanModel->insert($data);

        if ($result === false) {
            return redirect()->back()
                ->with('error', 'Gagal mengajukan cuti');
        }

        return redirect()->to('/spv/cuti')
            ->with('success', 'Pengajuan berhasil');
    }

    public function batal($id)
    {
        if (!$this->user) {
            return redirect()->to('/auth/login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $cuti = $this->pengajuanModel->where('pegawai_id', $this->user['id'])->find($id);

        if (!$cuti) {
            return redirect()->back()->with('error', 'Pengajuan tidak ditemukan.');
        }

        $data = [
            'status' => 'dibatalkan',
            'alasan_batal' => $this->request->getPost('alasan_batal')
        ];

        $this->pengajuanModel->update($id, $data);

        return redirect()->to('/spv/cuti')
            ->with('success', 'Pengajuan berhasil dibatalkan.');
    }

    public function detail($id)
    {
        $data['cuti'] = $this->pengajuanModel->find($id);

        $data['tracking'] = $this->detailModel
            ->select('detail_status_cuti.*, pegawai.nama')
            ->join('pegawai', 'pegawai.id = detail_status_cuti.approved_by', 'left')
            ->where('pengajuan_id', $id)
            ->findAll();

        return view('spv/cuti/detail', $data);
    }
}
