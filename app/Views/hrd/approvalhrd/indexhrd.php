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
    }

    .card-table h2,
    .card-table h1 {
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

    .btn-approve,
    .btn-reject {
        border: none;
        border-radius: 12px;
        padding: 8px 16px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        transition: 0.3s ease;
    }

    .btn-approve {
        background: #28a745;
        color: white;
    }

    .btn-approve:hover {
        background: #218838;
        color: white;
        transform: translateY(-2px);
    }

    .btn-reject {
        background: #dc3545;
        color: white;
        border: none;
    }

    .btn-reject:hover {
        background: #c82333;
        color: white;
        transform: translateY(-2px);
    }

    /* Gaya Khusus Modal Pop-up Form Alasan Reject */
    .modal-content {
        background: #fffaf5;
        border-radius: 20px;
        border: 1px solid #f1e2d2;
    }

    .modal-header {
        border-bottom: 1px solid #ead7c4;
        color: #7b573d;
    }

    .modal-footer {
        border-top: 1px solid #ead7c4;
    }

    .form-control {
        border-radius: 10px;
        border: 1px solid #ead7c4;
    }

    .form-control:focus {
        border-color: #7b573d;
        box-shadow: 0 0 0 0.25rem rgba(123, 87, 61, 0.25);
    }
</style>

<div class="card-table">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Approval HRD</h1>
    </div>

    <h2>Daftar Pengajuan</h2>

    <?php if (session()->getFlashdata('success')) : ?>
        <div class="alert alert-success" role="alert">
            <?= session()->getFlashdata('success') ?>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger" role="alert">
            <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th scope="col" style="width: 5%;">No</th>
                    <th scope="col">Nama Pegawai</th>
                    <th scope="col" class="text-center">Periode</th>
                    <th scope="col" class="text-center">Total Hari</th>
                    <th scope="col">Alasan</th>
                    <th scope="col" class="text-center">Status</th>
                    <th scope="col" class="text-center" style="width: 20%;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($cuti)): ?>
                    <?php $no = 1; ?>
                    <?php foreach ($cuti as $c): ?>
                        <tr>
                            <td class="text-center"><?= $no++; ?></td>
                            <td><?= esc($c['nama_pegawai'] ?? 'Nama Tidak Ditemukan'); ?></td>
                            <td class="text-center">
                                <?= date('d/m/Y', strtotime($c['tanggal_mulai'])); ?>
                                s/d
                                <?= date('d/m/Y', strtotime($c['tanggal_selesai'])); ?>
                            </td>
                            <td class="text-center"><?= $c['total_hari']; ?> hari</td>
                            <td><?= esc($c['alasan']); ?></td>
                            <td class="text-center">
                                <span style="color: #495057; font-weight: 500;">
                                    Menunggu HRD
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="<?= base_url('approval/approve-hrd/' . $c['id']); ?>" class="btn-approve">Approve</a>

                                <button type="button" class="btn-reject btn-reject-modal" data-id="<?= $c['id']; ?>">
                                    Tolak
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center" style="color: #9a7456; font-style: italic; padding: 30px;">
                            Tidak ada pengajuan cuti yang memerlukan persetujuan HRD saat ini.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formReject" action="" method="post">
                <?= csrf_field(); ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectModalLabel" style="font-weight: 700;">Alasan Penolakan Cuti (HRD)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="catatan" class="form-label" style="color: #6f4e37; font-weight: 600;">Berikan Alasan/Catatan Penolakan:</label>
                        <textarea class="form-control" id="catatan" name="catatan" rows="4" placeholder="Tulis alasan penolakan di sini agar pegawai tahu..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger rounded-3">Kirim & Tolak</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->include('layout/footerhrd') ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
        const formReject = document.getElementById('formReject');
        const rejectButtons = document.querySelectorAll('.btn-reject-modal');

        rejectButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                formReject.setAttribute('action', '/approval/reject-hrd/' + id);
                rejectModal.show();
            });
        });
    });
</script>