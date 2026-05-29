<?= $this->extend('spv/layout/sidebar'); ?>

<?= $this->section('content'); ?>

<h1>Pengajuan Cuti SPV</h1>

<form action="/spv/cuti/store" method="post">

    <label>Tanggal Mulai</label>
    <input type="date" name="tanggal_mulai">

    <label>Tanggal Selesai</label>
    <input type="date" name="tanggal_selesai">

    <label>Tujuan Cuti</label>
    <textarea name="tujuan_cuti"></textarea>

    <button type="submit">
        Ajukan
    </button>

</form>

<?= $this->endSection(); ?>