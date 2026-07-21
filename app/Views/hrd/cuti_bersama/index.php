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
        vertical-align: middle;
    }

    .table th:first-child,
    .table td:first-child {
        width: 80px;
        text-align: center;
    }

    .table th:nth-child(2),
    .table td:nth-child(2) {
        width: 180px;
    }

    .table tbody td:nth-child(3) {
        text-align: center;
    }

    .table th:last-child,
    .table td:last-child {
        width: 180px;
        text-align: center;
        white-space: nowrap;
    }

    .table tbody th {
        text-align: center;
        vertical-align: middle;
    }

    .pagination {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-top: 20px;
        flex-wrap: wrap;
    }

    .pagination a,
    .pagination span {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 42px;
        height: 42px;
        padding: 0 12px;
        border-radius: 4px;
        border: 1px solid #e5cdb5;
        background: #fff;
        color: #7b573d;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .pagination a:hover {
        background: #f3e8dc;
        color: #5f402f;
        border-color: #d8b89b;
    }

    .pagination a.active,
    .pagination a.active span {
        background: #7b573d;
        color: #fff;
        border-color: #7b573d;
    }
</style>

<div class="card-table">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Daftar Cuti Bersama</h1>
        <a href="/cuti_bersama/create" class="btn btn-primary">Tambah Cuti Bersama</a>
    </div>

    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">Tanggal</th>
                <th scope="col">Keterangan</th>
                <th scope="col">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = ($page - 1) * $perPage + 1; ?>
            <?php foreach ($cuti_bersama as $c) : ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= formatTanggalIndonesia($c['tanggal']) ?></td>
                    <td><?= $c['keterangan'] ?></td>
                    <td>
                        <a href="/cuti_bersama/edit/<?= $c['id']; ?>" class="btn btn-warning">Edit</a>
                        <a href="/cuti_bersama/delete/<?= $c['id']; ?>" class="btn btn-danger" onclick="return confirm('apakah anda yakin');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($total > 0) : ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++) : ?>
                <a href="/hrd/cuti_bersama?page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?= $this->include('layout/footerhrd') ?>