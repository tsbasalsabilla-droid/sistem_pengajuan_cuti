<?= $this->include('layout/header') ?>
<?= $this->include('layout/sidebarhrd') ?>

<a href="/hrd/divisi/create" class="btn btn-primary">Tambah Divisi</a>
<h1>Daftar Divisi</h1>

<table class="table">
    <thead>
        <tr>
            <th scope="col">#</th>
            <th scope="col">Nama Divisi</th>
            <th scope="col">Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php $i = 1; ?>
        <?php foreach ($divisi as $d) : ?>
            <tr>
                <th scope="row"><?= $i++ ?></th>
                <td><?= $d['nama_divisi'] ?></td>
                <td>
                    <a href="/hrd/divisi/edit/<?= $d['id']; ?>" class="btn btn-warning">Edit</a>
                    <a href="/hrd/divisi/delete/<?= $d['id']; ?>" class="btn btn-danger" onclick="return confirm('apakah anda yakin');">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?= $this->include('layout/footer') ?>