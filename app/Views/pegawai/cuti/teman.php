<?= $this->extend('pegawai/layout/sidebar'); ?>
<?= $this->section('content'); ?>

<style>
    .content-area {
        padding: 12px 16px;
        box-sizing: border-box;
        min-height: 100vh;
    }

    .topbar-section h1 {
        color: #7b573d;
        font-size: 36px;
        font-weight: 700;
        margin-bottom: 10px;
    }

    .topbar-section p {
        color: #9a7456;
        font-size: 16px;
        margin-bottom: 24px;
    }

    .card-table {
        background: #fffaf5;
        border-radius: 14px;
        padding: 24px;
        border: 1px solid #f1e2d2;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.04);
        overflow: hidden;
        margin-bottom: 30px;
    }

    .card-table h2 {
        color: #7b573d;
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 20px;
    }

    .table thead th {
        background: #f3e8dc;
        color: #6f4e37;
        font-weight: 600;
        border: none;
        padding: 15px;
    }

    .table tbody td {
        padding: 15px;
        color: #495057;
        border-color: #ead7c4;
    }

    .table tbody tr:hover {
        background: #fef8f3;
    }

    .pagination {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-top: 20px;
        flex-wrap: wrap;
        padding-left: 0;
        list-style: none;
    }

    .pagination li {
        display: inline-block;
    }

    .pagination a,
    .pagination span,
    .pagination .page-link {
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

    .pagination a:hover,
    .pagination span:hover,
    .pagination .page-link:hover {
        background: #f3e8dc;
        color: #5f402f;
        border-color: #d8b89b;
    }

    .pagination li.active a,
    .pagination li.active span,
    .pagination a.active,
    .pagination .page-link.active {
        background: #7b573d;
        color: #fff;
        border-color: #7b573d;
    }

    /* Styling Custom Modal */
    .modal-content {
        background: #fffaf5;
        border-radius: 20px;
        border: 1px solid #f1e2d2;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    .modal-header {
        border-bottom: 1px solid #ead7c4;
        color: #7b573d;
    }

    .modal-footer {
        border-top: 1px solid #ead7c4;
    }

    .form-control:focus {
        border-color: #7b573d;
        box-shadow: 0 0 0 0.25rem rgba(123, 87, 61, 0.25);
    }

    @media (max-width: 992px) {
        .content-area {
            padding: 25px 20px;
        }
    }
</style>

<div class="content-area">
    <?php if (session()->getFlashdata('success')) : ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('success'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('error'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="topbar-section">
        <h1>Approval Teman</h1>
        <p>Daftar pengajuan cuti yang menunggu persetujuan teman sejawat</p>
    </div>

    <div class="card-table">
        <h2>Daftar Pengajuan</h2>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Pegawai</th>
                        <th>Periode</th>
                        <th>Total Hari</th>
                        <th>Alasan Cuti</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($teman)): ?>
                        <?php $no = ! empty($pager) ? ($pager->getCurrentPage() - 1) * $pager->getPerPage() + 1 : 1; ?>
                        <?php foreach ($teman as $item): ?>
                            <?php if (empty($item['pegawai_nama']) || trim($item['pegawai_nama']) === ''): continue;
                            endif; ?>

                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= esc($item['pegawai_nama']); ?></td>
                                <td>
                                    <?= formatTanggalIndonesia($item['tanggal_mulai']); ?>
                                    s/d
                                    <?= formatTanggalIndonesia($item['tanggal_selesai']); ?>
                                </td>
                                <td><?= esc($item['total_hari']); ?> hari</td>
                                <td><?= esc($item['alasan']); ?></td>
                                <td>
                                    <span class="badge bg-warning text-dark">
                                        <?= esc(strtoupper(str_replace('_', ' ', $item['status']))); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= base_url('approval/approve-teman/' . esc($item['id'], 'url')); ?>" class="btn btn-success btn-sm rounded-3" onclick="return confirm('Apakah Anda yakin ingin menyetujui pengajuan ini?')">Approve</a>
                                    <button type="button" class="btn btn-danger btn-sm rounded-3 btn-reject-modal" data-id="<?= esc($item['id']); ?>">
                                        Tolak
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">Belum ada request approval teman.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if (! empty($pager)) : ?>
                <div class="pagination">
                    <?= $pager->links() ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="rejectModal" window-v-target tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formReject" action="" method="post">
                <?= csrf_field(); ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectModalLabel" style="font-weight: 700; color: #7b573d;">Alasan Penolakan Cuti</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="catatan" class="form-label" style="color: #6f4e37; font-weight: 600;">Berikan Alasan/Catatan Penolakan:</label>
                        <textarea class="form-control" id="catatan" name="catatan" rows="4" placeholder="Tulis alasan penolakan di sini..." required></textarea>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'), {
            backdrop: 'static',
            keyboard: false
        });
        
        const formReject = document.getElementById('formReject');
        const rejectButtons = document.querySelectorAll('.btn-reject-modal');

        rejectButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const id = this.getAttribute('data-id');
                formReject.setAttribute('action', '<?= base_url("approval/reject-teman/"); ?>' + id);
                rejectModal.show();
            });
        });
    });
</script>

<?= $this->endSection(); ?>