<?= $this->extend('pegawai/layout/sidebar'); ?>

<?= $this->section('content'); ?>

<h1 style="margin-bottom:30px; color:#7b573d;">
    Dashboard Pegawai
</h1>

<style>
    .table-dashboard {
        width: 100%;
        border-collapse: collapse;
        margin-top: 12px;
        border: none;
    }

    .table-dashboard thead th {
        background: #f3e8dc;
        color: #6f4e37;
        font-weight: 700;
        padding: 12px 16px;
        text-align: left;
        border-left: none;
        border-right: none;
        border-bottom: 1px solid #ead7c4;
    }

    .table-dashboard tbody td {
        padding: 12px 16px;
        color: #495057;
        border-left: none;
        border-right: none;
        border-bottom: 1px solid #ead7c4;
        vertical-align: middle;
    }

    .table-dashboard tbody tr:hover {
        background: #fef8f3;
    }

    .btn-action {
        display: inline-block;
        background: #8b6b52;
        color: #fff;
        text-decoration: none;
        padding: 6px 12px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 13px;
    }

    .btn-action:hover {
        background: #6f4e37;
    }

    .card h3 {
        font-size: 15px;
        font-weight: 600;
        color: #7b573d;
    }

    .card h1 {
        font-size: 20px;
        font-weight: 700;
        color: #7b573d;
        margin-top: 10px;
    }
</style>

<div class="card-wrapper">

    <div class="card">
        <h3>Sisa Saldo Cuti</h3>
        <h1><?= $saldo['saldo_cuti'] ?? 0; ?></h1>
    </div>

    <div class="card">
        <h3>Jumlah Cuti Tahun Ini</h3>
        <h1><?= $jumlahCuti['total_hari'] ?? 0; ?></h1>
    </div>

    <div class="card">
        <h3>Status Pengajuan Aktif</h3>
        <h1><?= isset($statusAktif['status']) ? ucwords(str_replace('_', ' ', $statusAktif['status'])) : 'Tidak Ada'; ?></h1>
    </div>

    <div class="card">
        <h3>Pengajuan Terakhir</h3>
        <h1><?= formatTanggalIndonesia($pengajuanTerakhir['tanggal_mulai'] ?? ''); ?></h1>
    </div>

</div>

<div style="margin-top:50px;"></div>

<h2 style="color:#7b573d; margin-bottom:24px;">
    Pengajuan Cuti Terakhir
</h2>

<table class="table-dashboard">

    <thead>
        <tr>
            <th>No</th>
            <th>Tanggal Mulai</th>
            <th>Tanggal Selesai</th>
            <th>Total Hari</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
    </thead>

    <tbody>

        <?php if (!empty($pengajuanTerakhirList)): ?>

            <?php $no = 1; ?>
            <?php foreach ($pengajuanTerakhirList as $cuti): ?>

                <?php
                $status = match ($cuti['status']) {
                    'pending_teman_sejawat' => 'Menunggu Teman Sejawat',
                    'pending_spv' => 'Menunggu SPV',
                    'pending_hrd' => 'Menunggu HRD',
                    'pending_direktur' => 'Menunggu Direktur',
                    'approved' => 'Disetujui',
                    'rejected' => 'Ditolak',
                    'dibatalkan' => 'Dibatalkan',
                    default => ucfirst($cuti['status'])
                };
                ?>

                <tr>

                    <td><?= $no++; ?></td>

                    <td>
    <?= formatTanggalIndonesia($cuti['tanggal_mulai'] ?? ''); ?>
</td>

<td>
    <?= formatTanggalIndonesia($cuti['tanggal_selesai'] ?? ''); ?>
</td>

                    <td><?= $cuti['total_hari']; ?> Hari</td>

                    <td><?= $status; ?></td>

                    <td>
                        <a class="btn-action" href="<?= base_url('pegawai/cuti/detail/' . $cuti['id']); ?>">Detail</a>
                    </td>

                </tr>

            <?php endforeach; ?>

        <?php else: ?>

            <tr>
                <td colspan="5" style="text-align:center;">
                    Belum ada pengajuan cuti.
                </td>
            </tr>

        <?php endif; ?>

    </tbody>

</table>

<?= $this->endSection(); ?>     