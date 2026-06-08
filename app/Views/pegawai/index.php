<?= $this->include('layout/header') ?>
<?= $this->include('layout/sidebarhrd') ?>

<a href="/hrd/pegawai/create" class="btn btn-primary">Tambah pegawai</a>
<h1>Daftar Pegawai</h1>

<table class="table">
    <thead>
        <tr>
            <th scope="col">#</th>
            <th scope="col">Foto</th>
            <th scope="col">Nama</th>
            <th scope="col">NIP</th>
            <th scope="col">Email</th>
            <th scope="col">No HP</th>
            <th scope="col">Jabatan</th>
            <th scope="col">Divisi</th>
            <th scope="col">Alamat</th>
            <th scope="col">Saldo cuti</th>
            <th scope="col">Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php $i = 1; ?>
        <?php foreach ($pegawai as $p) : ?>
            <tr>
                <th scope="row"><?= $i++ ?></th>
                <td><img src="/img/<?= $p['foto'] ?>" alt="" class="foto"></td>
                <td><?= $p['nama'] ?></td>
                <td><?= $p['nip'] ?></td>
                <td><?= $p['email'] ?></td>
                <td><?= $p['no_hp'] ?></td>
                <td><?= $p['jabatan'] ?></td>
                <td><?= $p['nama_divisi'] ?></td>
                <td><?= $p['alamat'] ?></td>
                <td><?= $p['saldo_cuti'] ?></td>
                <td>
                    <a href="/hrd/pegawai/edit/<?= $p['id']; ?>" class="btn btn-warning">Edit</a>
                    <a href="/hrd/pegawai/delete/<?= $p['id']; ?>" class="btn btn-danger" onclick="return confirm('apakah anda yakin');">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?= $this->include('layout/footer') ?>