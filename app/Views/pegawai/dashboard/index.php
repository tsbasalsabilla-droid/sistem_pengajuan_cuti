<?= $this->extend('pegawai/layout/sidebar'); ?>

<?= $this->section('content'); ?>

<h1 style="margin-bottom:30px;">
    Dashboard Pegawai
</h1>

<div class="card-wrapper">

    <div class="card">

        <h3>Sisa Saldo Cuti</h3>

        <h1>
            <?= $saldo['saldo_cuti'] ?? 0; ?>
        </h1>

    </div>

    <div class="card">

        <h3>Jumlah Cuti Tahun Ini</h3>

        <h1>
            <?= $jumlahCuti['total_hari'] ?? 0; ?>
        </h1>

    </div>

    <div class="card">

        <h3>Status Pengajuan Aktif</h3>

        <h1 style="font-size:18px;">
            <?= $statusAktif['status'] ?? 'Tidak Ada'; ?>
        </h1>

    </div>

    <div class="card">

        <h3>Pengajuan Terakhir</h3>

        <h1 style="font-size:16px;">
            <?= $pengajuanTerakhir['tanggal_mulai'] ?? '-'; ?>
        </h1>

    </div>

</div>

<?= $this->endSection(); ?>