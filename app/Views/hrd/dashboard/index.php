<?= $this->include('layout/header') ?>
<?= $this->include('layout/sidebarhrd') ?>

<style>
    .card-table {
        background: #fffaf5;
        border-radius: 20px;
        padding: 30px;
        border: 1px solid #f1e2d2;
        box-shadow: 0 5px 10px rgba(0, 0, 0, 0.4);
        overflow: hidden;
    }

    .card-table h2 {
        color: #7b573d;
        font-size: 30px;
        font-weight: 700;
        margin-bottom: 25px;

    }

    .table thead th {
        background: #f3e8dc;
        color: #6f4e37;
        font-weight: 600;
        border: none;
        padding: 15px;

        text-align: center;
        vertical-align: middle;
    }

    .table {
        border-radius: 15px;
        overflow: hidden;
        width: 100%;
    }

    .table tbody td {
        padding: 15px;
        color: #495057;
        border-color: #ead7c4;
        vertical-align: middle;
    }

    .table tbody th {
        text-align: center;
        vertical-align: middle;
    }

    .table th:first-child,
    .table td:first-child {
        width: 70px;
        text-align: center;
    }

    .table th,
    .table td {
        text-align: center;
        vertical-align: middle;
    }

    .table td:nth-child(2) {
        text-align: left;
    }

    .laporan-header {
        margin-top: 2rem;
        margin-bottom: 1rem;
    }

    .laporan-header h2 {
        color: #7b573d;
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 4px;
    }

    .laporan-header p {
        color: #777;
        font-size: 13px;
        margin: 0;
    }
</style>

<div class="card-table">
    <div>
        <h2 class="mb-4">Dashboard</h2>
        <div style="display:flex; flex-wrap:wrap; gap:1rem; margin-top:1rem;">
            <div style="flex:1 1 175px; border:1px solid #ddd; border-radius:12px; padding:1.25rem; background:#fff; box-shadow:0 1px 4px rgba(0,0,0,0.05);">
                <h2 style="margin:0 0 .5rem; font-size:2.25rem;"><?= $totalPegawai ?></h2>
                <p style="margin:0; color:#555;">Jumlah karyawan</p>
            </div>
            <div style="flex:1 1 175px; border:1px solid #ddd; border-radius:8px; padding:1.25rem; background:#fff; box-shadow:0 1px 4px rgba(0,0,0,0.05);">
                <h2 style="margin:0 0 .5rem; font-size:2.25rem;"><?= $totalLaporan ?></h2>
                <p style="margin:0; color:#555;">Jumlah laporan</p>
            </div>
            <div style="flex:1 1 175px; border:1px solid #ddd; border-radius:8px; padding:1.25rem; background:#fff; box-shadow:0 1px 4px rgba(0,0,0,0.05);">
                <h2 style="margin:0 0 .5rem; font-size:2.25rem;"><?= $cutiBulanIni ?></h2>
                <p style="margin:0; color:#555;">Cuti bulan ini</p>
            </div>
        </div>

        <div style="margin-top: 3rem; margin-bottom: 1.5rem;">
            <div class="laporan-header">
                <h2>Laporan Terbaru</h2>
                <p>5 laporan terakhir yang masuk.</p>
            </div>
        </div>
        <div style="overflow-x:auto;">
            <table class="table table-striped mb-0" style="min-width:720px; margin-bottom:0;">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama</th>
                        <th>NIP</th>
                        <th>Tanggal Mulai</th>
                        <th>Tanggal Selesai</th>
                        <th>Total Hari</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recentLaporan)) : ?>
                        <?php $no = 1; ?>
                        <?php foreach ($recentLaporan as $laporan) : ?>
                            <?php
                            $start = new \DateTime($laporan['tanggal_mulai']);
                            $end = new \DateTime($laporan['tanggal_selesai']);
                            $totalHari = $laporan['total_hari'] ?? $start->diff($end)->days + 1;
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= esc($laporan['nama']) ?></td>
                                <td><?= esc($laporan['nip']) ?></td>
                                <td><?= formatTanggalIndonesia($laporan['tanggal_mulai']) ?></td>
                                <td><?= formatTanggalIndonesia($laporan['tanggal_selesai']) ?></td>
                                <td><?= esc($totalHari) ?></td>
                                <td><?= esc(str_replace('_', ' ', $laporan['status'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="7" class="text-center">Belum ada laporan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?= $this->include('layout/footerhrd') ?>