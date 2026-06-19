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
        width: 100%;
        table-layout: fixed;
    }

    .table tbody td {
        padding: 15px;
        color: #495057;
        border-color: #ead7c4;
    }

    .table th:first-child,
    .table td:first-child {
        width: 80px;
        text-align: center;
    }

    .table th:last-child,
    .table td:last-child {
        width: 200px;
        text-align: center;
        white-space: nowrap;
    }

    .table th:nth-child(2),
    .table td:nth-child(2) {
        text-align: left;
    }
</style>

<div class="card-table">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Daftar Divisi</h1>
        <a href="/divisi/create" class="btn btn-primary">Tambah Divisi</a>
    </div>

    <table class="table table-striped align-middle">
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
                    <td><?= $i++ ?></td>
                    <td><?= $d['nama_divisi'] ?></td>
                    <td>
                        <a href="/divisi/edit/<?= $d['id']; ?>" class="btn btn-warning">Edit</a>
                        <a href="/divisi/delete/<?= $d['id']; ?>" class="btn btn-danger" onclick="return confirm('apakah anda yakin');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?= $this->include('layout/footerhrd') ?>