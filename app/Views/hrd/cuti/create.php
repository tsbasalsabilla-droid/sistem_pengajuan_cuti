<?= $this->extend('layout/sidebarhrd'); ?>

<?= $this->section('content'); ?>

<h1>Pengajuan Cuti HRD</h1>

<form action="/hrd/cuti/store" method="post">

    <label>Tanggal Mulai</label>
    <input type="date" name="tanggal_mulai">

    <label>Tanggal Selesai</label>
    <input type="date" name="tanggal_selesai">

    <label>Alasan Cuti</label>
    <textarea name="alasan"></textarea>

    <button type="submit">
        Ajukan
    </button>

</form>

<?= $this->endSection(); ?>