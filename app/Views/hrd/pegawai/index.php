<?= $this->include('layout/header') ?>
<?= $this->include('layout/sidebarhrd') ?>
<style>
    .foto-pegawai {
        width: 100px;
        height: 150px;
        object-fit: cover;
        border-radius: 10px;
    }


    .card-table {
        background: #fffaf5;
        border-radius: 20px;
        padding: 30px;
        border: 1px solid #f1e2d2;
        box-shadow: 0 5px 10px rgba(0, 0, 0, 0.4);
        overflow: hidden;


    }

    .card-table h2 {
        color: #7b573d;
        font-size: 30px;
        font-weight: 700;
        margin-bottom: 25px;

    }

    .table thead th {
        background: #f3e8dc;
        color: #6f4e37;
        font-weight: 600;
        border: none;
        padding: 15px;

        text-align: center;
        vertical-align: middle;
    }

    .table {
        border-radius: 15px;
        overflow: hidden;
    }

    .table tbody td {
        padding: 15px;
        color: #495057;
        border-color: #ead7c4;
    }
</style>


<div class="card-table">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Daftar Pegawai</h2>
        <a href="/pegawai/create" class="btn btn-primary">Tambah pegawai</a>
    </div>
    <table class="table table-striped align-middle">
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
                    <td><?= $i++ ?></td>
                    <td><img src="/img/<?= $p['foto'] ?>" alt="" class="foto-pegawai"></td>
                    <td><?= $p['nama'] ?></td>
                    <td><?= $p['nip'] ?></td>
                    <td><?= $p['email'] ?></td>
                    <td><?= $p['no_hp'] ?></td>
                    <td><?= $p['jabatan'] ?></td>
                    <td><?= $p['nama_divisi'] ?></td>
                    <td><?= $p['alamat'] ?></td>
                    <td><?= $p['saldo_cuti'] ?></td>
                    <td>
                        <div class="d-flex flex-column gap-2">
                            <a href="/pegawai/edit/<?= $p['id']; ?>" class="btn btn-warning">Edit</a>
                            <a href="/pegawai/delete/<?= $p['id']; ?>" class="btn btn-danger" onclick="return confirm('apakah anda yakin');">Delete</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= $this->include('layout/footerhrd') ?>