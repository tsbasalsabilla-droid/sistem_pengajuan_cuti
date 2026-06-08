<?= $this->include('layout/header') ?>
<?= $this->include('layout/sidebarhrd') ?>

<h1>Daftar Laporan</h1>

<a href="/hrd/laporan/exportExcel" class="btn btn-success mb-3">
    Download Excel
</a>

<table class="table">
    <thead>
        <tr>
            <th scope="col">#</th>
            <th scope="col">Nama</th>
            <th scope="col">Nip</th>
            <th scope="col">Tanggal Mulai</th>
            <th scope="col">Tanggal Selesai</th>
            <th scope="col">Alasan</th>
            <th scope="col">Total Hari</th>
            <th scope="col">Status</th>
            <th scope="col">Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php $i = 1; ?>
        <?php foreach ($laporan as $l) : ?>
            <tr>
                <th scope="row"><?= $i++ ?></th>
                <td><?= $l['nama'] ?></td>
                <td><?= $l['nip'] ?></td>
                <td><?= $l['tanggal_mulai'] ?></td>
                <td><?= $l['tanggal_selesai'] ?></td>
                <td><?= $l['alasan'] ?></td>
                <td><?= $l['total_hari'] ?></td>
                <td><?= $l['status'] ?></td>
                <td>
                    <a href="/hrd/laporan/delete/<?= $l['id']; ?>" class="btn btn-danger" onclick="return confirm('apakah anda yakin');">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?= $this->include('layout/footer') ?>