<?php

namespace App\Controllers;

use App\Models\PengajuanCutiModel;
use App\Models\SaldoCutiModel;
use App\Models\DetailStatusCutiModel;

class CutiSpvController extends BaseController
{
    protected $pengajuanModel;
    protected $saldoModel;
    protected $detailModel;

    public function __construct()
    {
        $this->pengajuanModel = new PengajuanCutiModel();
        $this->saldoModel = new SaldoCutiModel();
        $this->detailModel = new DetailStatusCutiModel();
    }

    public function index()
    {
        $userId = session()->get('user')['id'];

        $data['cuti'] = $this->pengajuanModel
            ->where('pegawai_id', $userId)
            ->orderBy('id', 'DESC')
            ->findAll();

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

        $mulai = strtotime($tanggalMulai);
        $selesai = strtotime($tanggalSelesai);

        $totalHari = (($selesai - $mulai) / 86400) + 1;

        $saldo = $this->saldoModel
            ->where('pegawai_id', $userId)
            ->first();

        if (!$saldo) {
            $this->saldoModel->insert([
                'pegawai_id' => $userId,
                'tahun' => date('Y'),
                'total_cuti' => 12,
                'cuti_terpakai' => 0,
                'sisa_cuti' => 12,
            ]);

            $saldo = $this->saldoModel
                ->where('pegawai_id', $userId)
                ->first();
        }

        if (!$saldo) {
            return redirect()->back()
                ->with('error', 'Saldo cuti tidak ditemukan');
        }

        if ($saldo['sisa_cuti'] < $totalHari) {
            return redirect()->back()
                ->with('error', 'Saldo cuti tidak cukup');
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

    public function history()
    {
        $userId = session()->get('user')['id'];

        $data['cuti'] = $this->pengajuanModel
            ->where('pegawai_id', $userId)
            ->orderBy('id', 'DESC')
            ->findAll();

        return view('spv/cuti/index', $data);
    }
}