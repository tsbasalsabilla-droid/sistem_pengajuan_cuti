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
    }

    .table tbody td {
        padding: 15px;
        color: #495057;
        border-color: #ead7c4;
    }

    .table th:nth-child(6),
    .table td:nth-child(6) {
        min-width: 250px;
    }

    .table th:last-child,
    .table td:last-child {
        width: 120px;
        text-align: center;
    }

    .pagination {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-top: 20px;
        flex-wrap: wrap;
    }

    .pagination a {
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

    .pagination a.active {
        background: #7b573d;
        color: #fff;
        border-color: #7b573d;
    }
</style>

<div class="card-table">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Daftar Laporan</h1>
        <a href="/laporan/exportExcel<?= !empty($search) ? '?search=' . urlencode($search) : '' ?>" class="btn btn-success">
            Download Excel
        </a>
    </div>

    <form method="get" action="/hrd/laporan" class="row g-2 mb-3">
        <div class="col-md-8">
            <input type="text" name="search" class="form-control" placeholder="Cari nama, nip, alasan, status, tanggal..." value="<?= esc($search ?? '') ?>">
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Cari</button>
            <?php if (!empty($search)) : ?>
                <a href="/hrd/laporan" class="btn btn-outline-secondary">Reset</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if (session()->getFlashdata('pesan')) : ?>
        <div class="alert alert-info" role="alert">
            <?= session()->getFlashdata('pesan') ?>
        </div>
    <?php endif; ?>

    <table class="table table-striped align-middle">
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
            <?php $i = ($page - 1) * $perPage + 1; ?>
            <?php foreach ($laporan as $l) : ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= esc($l['nama']) ?></td>
                    <td><?= esc($l['nip']) ?></td>
                    <td><?= formatTanggalIndonesia($l['tanggal_mulai']) ?></td>
                    <td><?= formatTanggalIndonesia($l['tanggal_selesai']) ?></td>
                    <td><?= esc($l['alasan']) ?></td>
                    <td><?= esc($l['total_hari']) ?></td>
                    <td><?= esc(str_replace('_', ' ', $l['status'])) ?></td>
                    <?php
                    $badgeClass = str_replace('_', '-', strtolower($l['status']));
                    ?>
                    <td>
                        <a href="/laporan/delete/<?= $l['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('apakah anda yakin');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($total > 0) : ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++) : ?>
                <a href="/hrd/laporan?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

    <?= $this->include('layout/footerhrd') ?>