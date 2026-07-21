<?= $this->extend('pegawai/layout/sidebar'); ?>

<?= $this->section('content'); ?>

<h1 style="margin-bottom:20px; color:#7b573d;">
    Form Pengajuan Cuti
</h1>

<form action="/pegawai/cuti/store" method="post">

    <label>Tanggal Mulai</label>
    <input type="date" name="tanggal_mulai" required>

    <label>Tanggal Selesai</label>
    <input type="date" name="tanggal_selesai" required>

    <label>Alasan Cuti</label>
    <textarea name="alasan"></textarea>

    <button type="submit">
        Ajukan Cuti
    </button>
 
</form>

<?= $this->endSection(); ?>