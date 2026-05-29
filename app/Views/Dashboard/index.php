<?= $this->include('layout/header') ?>
<?= $this->include('layout/sidebarhrd') ?>

<div style="padding: 1.5rem;">
    <h1>Dashboard</h1>
    <div style="display:flex; flex-wrap:wrap; gap:1rem; margin-top:1rem;">
        <div style="flex:1 1 250px; border:1px solid #ddd; border-radius:8px; padding:1.25rem; background:#fff; box-shadow:0 1px 4px rgba(0,0,0,0.05);">
            <h2 style="margin:0 0 .5rem; font-size:2.25rem;"><?= $totalPegawai ?></h2>
            <p style="margin:0; color:#555;">Jumlah karyawan</p>
        </div>
        <div style="flex:1 1 250px; border:1px solid #ddd; border-radius:8px; padding:1.25rem; background:#fff; box-shadow:0 1px 4px rgba(0,0,0,0.05);">
            <h2 style="margin:0 0 .5rem; font-size:2.25rem;"><?= $totalLaporan ?></h2>
            <p style="margin:0; color:#555;">Jumlah laporan</p>
        </div>
        <div style="flex:1 1 250px; border:1px solid #ddd; border-radius:8px; padding:1.25rem; background:#fff; box-shadow:0 1px 4px rgba(0,0,0,0.05);">
            <h2 style="margin:0 0 .5rem; font-size:2.25rem;"><?= $cutiBulanIni ?></h2>
            <p style="margin:0; color:#555;">Cuti bulan ini</p>
        </div>
    </div>

    <div style="margin-top:2rem;">
        <div style="background:#fff; border:1px solid #ddd; border-radius:8px; box-shadow:0 1px 4px rgba(0,0,0,0.05); overflow:hidden;">
            <div style="padding:1.25rem; border-bottom:1px solid #eee; background:#fafafa;">
                <h2 style="margin:0; font-size:1.1rem;">Laporan Terbaru</h2>
                <p style="margin:0.5rem 0 0; color:#666;">5 laporan terakhir yang masuk.</p>
            </div>
            <div style="overflow-x:auto;">
                <table class="table mb-0" style="min-width:720px; margin-bottom:0;">
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
                                    <th scope="row"><?= $no++ ?></th>
                                    <td><?= esc($laporan['nama']) ?></td>
                                    <td><?= esc($laporan['nip']) ?></td>
                                    <td><?= esc($laporan['tanggal_mulai']) ?></td>
                                    <td><?= esc($laporan['tanggal_selesai']) ?></td>
                                    <td><?= esc($totalHari) ?></td>
                                    <td><?= esc($laporan['status']) ?></td>
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
    </div>
</div>
<?= $this->include('layout/footer') ?>