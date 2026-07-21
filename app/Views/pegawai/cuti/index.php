<?= $this->extend('pegawai/layout/sidebar'); ?>

<?= $this->section('content'); ?>

<style>
    .card-table {
        background: #fffaf5;
        border-radius: 20px;
        padding: 30px;
        border: 1px solid #f1e2d2;
        box-shadow: 0 5px 10px rgba(0, 0, 0, 0.4);
        overflow: hidden;
    }

    .card-table h1 {
        color: #7b573d;
        font-size: 30px;
        font-weight: 700;
        margin-bottom: 25px;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
    }

    .table th {
        background: #f3e8dc;
        color: #6f4e37;
        font-weight: 600;
        border: none;
        padding: 15px;
        text-align: left;
    }

    .table td {
        padding: 15px;
        color: #495057;
        border-color: #ead7c4;
        background-color: transparent;
    }

    .table tbody tr:hover,
    .table tbody tr:hover>td {
        background-color: #f3e8dc !important;
        color: #3e2f20 !important;
    }

    .btn-action {
        display: inline-block;
        background: #8b6b52;
        color: #fff;
        text-decoration: none;
        padding: 8px 16px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 13px;
        border: none;
        transition: 0.3s ease;
    }

    .btn-action:hover {
        background: #6f4e37;
        color: #fff;
        transform: translateY(-1px);
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
    }

    .pagination a.active {
        background: #7b573d;
        color: #fff;
        border-color: #7b573d;
    }

    .btn-batal {
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 10px;
    padding: 8px 14px;
    font-size: 13px;
    font-weight: 700;
    transition: .2s;
}

.btn-batal:hover {
    background: #c82333;
}

.modal-content {
    background: #fffaf5;
    border-radius: 18px;
    border: 1px solid #ead7c4;
    box-shadow: 0 15px 40px rgba(0,0,0,.2);
}

.modal-header {
    border-bottom: 1px solid #ead7c4;
    padding: 18px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-body {
    padding: 22px 24px;
}

.modal-footer {
    border-top: 1px solid #ead7c4;
    padding: 18px 24px;
}

.modal-title {
    color: #7b573d;
    font-weight: 700;
}

.close-btn {
    border: none;
    background: transparent;
    color: #8b6b52;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close-btn:hover {
    color: #6f4e37;
}

.form-control {
    border-radius: 10px;
    border: 1px solid #ead7c4;
}

.form-control:focus {
    border-color: #7b573d;
    box-shadow: 0 0 0 .2rem rgba(123,87,61,.15);
}

.modal-footer .btn-secondary {
    background: #b6b6b6;
    border: none;
}

.modal-footer .btn-secondary:hover {
    background: #9b9b9b;
}

.modal-footer .btn-danger {
    background: #dc3545;
    border: none;
}

.modal-footer .btn-danger:hover {
    background: #c82333;
}
</style>

<div class="card-table">
    <h1>History Pengajuan Cuti</h1>

    <table class="table">

        <tr>
            <th>Tanggal Mulai</th>
            <th>Tanggal Selesai</th>
            <th>Total Hari</th>
            <th>Alasan Cuti</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>

        <?php foreach ($cuti as $c): ?>

            <tr>

<td>
    <?= formatTanggalIndonesia($c['tanggal_mulai'] ?? ''); ?>
</td>

<td>
    <?= formatTanggalIndonesia($c['tanggal_selesai'] ?? ''); ?>
</td>

<td>
    <?= $c['total_hari']; ?> Hari
</td>

<td>
    <?= esc($c['alasan']); ?>
</td>

                <td>
                    <?php
                    $status = trim($c['status'] ?? '');
                    if ($status === 'approve') $status = 'approved';
                    if ($status === '') {
                        $approvalModel = new \App\Models\ApprovalModel();
                        $log = $approvalModel->where('cuti_id', $c['id'])->where('status', 'approved')->first();
                        $status = $log ? 'approved' : 'pending';
                    }
                    $statusLabel = match ($status) {
                        'pending' => 'Menunggu',
                        'pending_spv' => 'Menunggu SPV',
                        'pending_hrd' => 'Menunggu HRD',
                        'pending_direktur' => 'Menunggu Direktur',
                        'pending_teman', 'pending_teman_sejawat' => 'Menunggu Teman Sejawat',
                        'batal', 'dibatalkan' => 'Dibatalkan',
                        default => ucwords(str_replace('_', ' ', $status))
                    };
                
                    $bolehBatal = in_array($status, [
                        'pending',
                        'pending_spv',
                        'pending_teman_sejawat',
                        'pending_hrd',
                        'pending_direktur'
                    ]);
                
                    echo $statusLabel;
                    ?>
                </td>

                <td>
                    <a href="/pegawai/cuti/detail/<?= $c['id']; ?>" class="btn-action"> Detail </a>

                    <?php if ($bolehBatal): ?>

                        <br>
                        <br>
                        <button type="button" class="btn-batal btn-cancel-modal" data-id="<?= $c['id']; ?>">Batalkan</button>
                    <?php endif; ?>
                </td>

            </tr>

        <?php endforeach; ?>

    </table>

    <?php if (!empty($total) && $total > 0) : ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++) : ?>
                <a href="/pegawai/cuti?page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formCancel" method="post">
                <?= csrf_field(); ?>

                <div class="modal-header">
                    <h5 class="modal-title">Alasan Pembatalan Cuti</h5>
                    <button type="button" class="close-btn" data-bs-dismiss="modal">&times;</button>
                </div>

                <div class="modal-body">
                    <label class="form-label"> Berikan alasan pembatalan: </label>

                    <textarea class="form-control" name="alasan_batal" rows="4" placeholder="Tulis alasan pembatalan..." required></textarea>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"> Batal </button>

                    <button type="submit" class="btn btn-danger"> Batalkan Pengajuan </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const modalElement = document.getElementById('cancelModal');
    const cancelModal = new bootstrap.Modal(modalElement);
    const formCancel = document.getElementById('formCancel');

    document.querySelectorAll('.btn-cancel-modal').forEach(button => {
        button.addEventListener('click', function () {

            const id = this.dataset.id;

            formCancel.action = "<?= base_url('pegawai/cuti/batal/') ?>/" + id;

            formCancel.reset();

            cancelModal.show();
        });
    });

    modalElement.addEventListener('hidden.bs.modal', function () {
        formCancel.reset();
    });

});
</script>

<?= $this->endSection(); ?>