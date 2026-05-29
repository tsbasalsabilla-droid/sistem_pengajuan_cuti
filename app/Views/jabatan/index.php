<?= $this->include('layout/header') ?>
<?= $this->include('layout/sidebarhrd') ?>

<a href="/jabatan/create" class="btn btn-primary">Tambah jabatan</a>
<h1>Daftar jabatan</h1>

<table class="table">
    <thead>
        <tr>
            <th scope="col">#</th>
            <th scope="col">Nama Jabatan</th>
            <th scope="col">Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php $i = 1; ?>
        <?php foreach ($jabatan as $j) : ?>
            <tr>
                <th scope="row"><?= $i++ ?></th>
                <td><?= $j['jabatan'] ?></td>
                <td>
                    <a href="/jabatan/edit/<?= $j['id']; ?>" class="btn btn-warning">Edit</a>
                    <a href="/jabatan/delete/<?= $j['id']; ?>" class="btn btn-danger" onclick="return confirm('apakah anda yakin');">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?= $this->include('layout/footer') ?>