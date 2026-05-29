<?= $this->extend('spv/layout/sidebar'); ?>

<?= $this->section('content'); ?>

<h1>History Cuti SPV</h1>

<table>

    <tr>
        <th>Tanggal</th>
        <th>Total Hari</th>
        <th>Status</th>
        <th>Aksi</th>
    </tr>

    <?php foreach($cuti as $c): ?>

    <tr>

        <td>
            <?= $c['tanggal_mulai']; ?>
            s/d
            <?= $c['tanggal_selesai']; ?>
        </td>

        <td>
            <?= $c['total_hari']; ?> Hari
        </td>

        <td>
            <?= $c['status']; ?>
        </td>

        <td>
            <a href="/spv/cuti/detail/<?= $c['id']; ?>">
                Detail
            </a>
        </td>

    </tr>

    <?php endforeach; ?>

</table>

<?= $this->endSection(); ?>