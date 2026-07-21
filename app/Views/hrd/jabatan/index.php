<?= $this->include('layout/header') ?>
<?= $this->include('layout/sidebarhrd') ?>

<style>
    .card-table {
        background: #fffaf5;
        border-radius: 20px;
        padding: 30px;
        border: 1px solid #f1e2d2;
        box-shadow: 0 5px 10px rgba(0, 0, 0, 0.4);
        overflow: hidden;

        width: 100%;
        max-width: none;
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
        <h2>Daftar Jabatan</h2>
        <a href="/jabatan/create" class="btn btn-primary">Tambah jabatan</a>
    </div>

    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th scope="col-no">#</th>
                <th scope="col">Nama Jabatan</th>
                <th scope="col">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = ($page - 1) * $perPage + 1; ?>
            <?php foreach ($jabatan as $j) : ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= $j['jabatan'] ?></td>
                    <td>
                        <a href="/jabatan/edit/<?= $j['id']; ?>" class="btn btn-warning">Edit</a>
                        <a href="/jabatan/delete/<?= $j['id']; ?>" class="btn btn-danger" onclick="return confirm('apakah anda yakin');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($total > 0) : ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++) : ?>
                <a href="/hrd/jabatan?page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>
<?= $this->include('layout/footerhrd') ?>