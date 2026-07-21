<?php

namespace App\Controllers;

use App\Models\LaporanModel;

class LaporanController extends BaseController
{
    protected $LaporanModel;

    public function __construct()
    {
        $this->LaporanModel = new LaporanModel();
    }

    public function index()
    {
        $saldoCutiModel = new \App\Models\SaldoCutiModel();
        $saldoCutiModel->syncSaldoCuti();

        $page = max(1, (int) ($this->request->getVar('page') ?? 1));
        $search = trim((string) ($this->request->getVar('search') ?? ''));
        $perPage = 10;
        $total = $this->LaporanModel->countLaporan($search);
        $totalPages = max(1, (int) ceil($total / $perPage));

        $laporan = $this->LaporanModel->getLaporan($perPage, $page, $search);

        foreach ($laporan as &$item) {
            $item['total_hari'] = $this->LaporanModel->calculateActualLeaveDays($item['tanggal_mulai'], $item['tanggal_selesai']);
            $item['total_hari_kalender'] = (new \DateTime($item['tanggal_selesai']))->diff(new \DateTime($item['tanggal_mulai']))->days + 1;
        }
        unset($item);

        $data = [
            'title' => 'Data Laporan',
            'laporan' => $laporan,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'totalPages' => $totalPages,
            'search' => $search,
        ];

        return view('hrd/laporan/index', $data);
    }


    public function exportExcel()
    {
        $search = trim((string) ($this->request->getVar('search') ?? ''));
        $laporan = $this->LaporanModel->getLaporan(null, null, $search);

        $filename = 'laporan-cuti-' . date('YmdHis') . '.xls';

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        echo '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
        echo '<style>';
        echo 'table { border-collapse: collapse; width: 100%; }';
        echo 'table th, table td { border: 1px solid #999; padding: 6px; }';
        echo 'table th { background: #f2f2f2; font-weight: bold; }';
        echo '.text-left { text-align: left; }';
        echo '.text-center { text-align: center; }';
        echo '</style></head><body>';

        echo '<table>';
        echo '<tr>';
        echo '<th class="text-center">No</th>';
        echo '<th class="text-left">Nama</th>';
        echo '<th class="text-left">NIP</th>';
        echo '<th class="text-center">Tanggal Mulai</th>';
        echo '<th class="text-center">Tanggal Selesai</th>';
        echo '<th class="text-left">Alasan</th>';
        echo '<th class="text-center">Total Hari (Kalender)</th>';
        echo '<th class="text-center">Total Hari (Dikurangi Cuti Bersama)</th>';
        echo '<th class="text-center">Status</th>';
        echo '</tr>';

        $no = 1;

        foreach ($laporan as $l) {
            $totalHariKalender = (new \DateTime($l['tanggal_selesai']))->diff(new \DateTime($l['tanggal_mulai']))->days + 1;
            $totalHariAktual = $this->LaporanModel->calculateActualLeaveDays($l['tanggal_mulai'], $l['tanggal_selesai']);

            echo '<tr>';
            echo '<td class="text-center">' . $no++ . '</td>';
            echo '<td class="text-left">' . htmlspecialchars($l['nama']) . '</td>';
            echo '<td class="text-left">' . htmlspecialchars($l['nip']) . '</td>';
            echo '<td class="text-center">' . htmlspecialchars($l['tanggal_mulai']) . '</td>';
            echo '<td class="text-center">' . htmlspecialchars($l['tanggal_selesai']) . '</td>';
            echo '<td class="text-left">' . htmlspecialchars($l['alasan']) . '</td>';
            echo '<td class="text-center">' . $totalHariKalender . '</td>';
            echo '<td class="text-center">' . $totalHariAktual . '</td>';
            echo '<td class="text-center">' . htmlspecialchars($l['status']) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        echo '</body></html>';
        exit;
    }


    public function delete($id)
    {
        $this->LaporanModel->delete($id);
        session()->setFlashdata('pesan', 'Data berhasil dihapus.');
        return redirect()->to('/hrd/laporan');
    }
}
