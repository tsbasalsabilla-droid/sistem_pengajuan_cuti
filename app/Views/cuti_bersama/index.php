<?= $this->include('layout/header') ?>
<?= $this->include('layout/sidebarhrd') ?>

<a href="/cuti_bersama/create" class="btn btn-primary">Tambah Cuti Bersama</a>
<h1>Daftar Cuti Bersama</h1>

<table class="table">
    <thead>
        <tr>
            <th scope="col">#</th>
            <th scope="col">Tanggal</th>
            <th scope="col">Keterangan</th>
            <th scope="col">Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php $i = 1; ?>
        <?php foreach ($cuti_bersama as $c) : ?>
            <tr>
                <th scope="row"><?= $i++ ?></th>
                <td><?= $c['tanggal'] ?></td>
                <td><?= $c['keterangan'] ?></td>
                <td>
                    <a href="/cuti_bersama/edit/<?= $c['id']; ?>" class="btn btn-warning">Edit</a>
                    <a href="/cuti_bersama/delete/<?= $c['id']; ?>" class="btn btn-danger" onclick="return confirm('apakah anda yakin');">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?= $this->include('layout/footer') ?>