<?php

namespace App\Controllers;

use App\Models\PengajuanCutiModel;
use App\Models\PegawaiModel;
use App\Models\DetailStatusCutiModel;

class CutiHrdController extends BaseController
{
    protected $pengajuanModel;
    protected $pegawaiModel;
    protected $detailModel;

    public function __construct()
    {
        $this->pengajuanModel = new PengajuanCutiModel();
        $this->pegawaiModel   = new PegawaiModel();
        $this->detailModel    = new DetailStatusCutiModel();
    }

    public function index()
    {
        $userId = session()->get('user')['id'];

        $data['cuti'] = $this->pengajuanModel
            ->where('pegawai_id', $userId)
            ->findAll();

        return view('hrd/cuti/index', $data);
    }

    public function create()
    {
        return view('hrd/cuti/create');
    }

    public function store()
    {
        $tanggalMulai = $this->request->getPost('tanggal_mulai');
        $tanggalSelesai = $this->request->getPost('tanggal_selesai');
        $pegawaiId = session()->get('user')['id'] ?? $this->request->getPost('pegawai_id');

        if (empty($pegawaiId)) {
            return redirect()->back()->with('error', 'Identitas pegawai tidak ditemukan.');
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
            return redirect()->back()->with('error', 'Tanggal yang dipilih merupakan hari libur/cuti bersama.');
        }

        $pegawai = $this->pegawaiModel->find($pegawaiId);

        if (!$pegawai) {
            return redirect()->back()->with('error', 'Data pegawai tidak ditemukan.');
        }

        if ($pegawai['saldo_cuti'] < $totalHari) {
            return redirect()->back()->with('error', 'Saldo cuti tidak cukup. Sisa saldo Anda: ' . $pegawai['saldo_cuti'] . ' hari.');
        }

        $this->pengajuanModel->insert([
            'pegawai_id'      => $pegawaiId,
            'tanggal_mulai'   => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
            'total_hari'      => $totalHari,
            'alasan'          => $this->request->getPost('alasan') ?? '-',
            'status'          => 'pending_direktur'
        ]);


        $idBaru = $this->pengajuanModel->insertID();

        $this->detailModel->insert([
            'pengajuan_id'   => $idBaru,
            'approved_by'    => null,
            'level_approval' => 'direktur',
            'status'         => 'pending',
            'catatan'        => 'Menunggu persetujuan Direktur'
        ]);

        $this->detailModel->insert([
            'pengajuan_id'   => $idBaru,
            'approved_by'    => $pegawaiId,
            'level_approval' => 'spv',
            'status'         => 'approved',
            'catatan'        => 'Otomatis Disetujui (Pengajuan oleh HRD)'
        ]);

        $this->detailModel->insert([
            'pengajuan_id'   => $idBaru,
            'approved_by'    => $pegawaiId,
            'level_approval' => 'hrd',
            'status'         => 'approved',
            'catatan'        => 'Disetujui HRD'
        ]);

        return redirect()->to('/hrd/cuti')->with('success', 'Pengajuan berhasil dan otomatis disetujui hingga tingkat HRD!');
    }

    public function detail($id)
    {
        $data['cuti'] = $this->pengajuanModel->find($id);

        $data['tracking'] = $this->detailModel
            ->select('detail_status_cuti.*, pegawai.nama')
            ->join('pegawai', 'pegawai.id = detail_status_cuti.approved_by AND detail_status_cuti.approved_by IS NOT NULL', 'left')
            ->where('detail_status_cuti.pengajuan_id', $id)
            ->where('detail_status_cuti.level_approval', 'direktur')
            ->findAll();

        return view('hrd/cuti/detail', $data);
    }
}
