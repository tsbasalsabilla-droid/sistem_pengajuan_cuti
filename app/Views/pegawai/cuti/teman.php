<?= $this->extend('pegawai/layout/sidebar'); ?>
<?= $this->section('content'); ?>

<h1>Approval Teman</h1>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<table class="table table-bordered align-middle">
    <thead>
        <tr>
            <th>No</th>
            <th>Nama Pegawai</th>
            <th>Tanggal Mulai</th>
            <th>Tanggal Selesai</th>
            <th>Total Hari</th>
            <th>Alasan cuti</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php if (! empty($teman)): ?>
            <?php $no = 1; ?>
            <?php foreach ($teman as $item): ?>
                <?php if (empty($item['pegawai_nama']) || trim($item['pegawai_nama']) === ''): continue;
                endif; ?>

                <tr>
                    <td><?= $no++; ?></td>
                    <td><?= esc($item['pegawai_nama']); ?></td>
                    <td><?= esc($item['tanggal_mulai']); ?></td>
                    <td><?= esc($item['tanggal_selesai']); ?></td>
                    <td><?= esc($item['total_hari']); ?> Hari</td>
                    <td><?= esc($item['alasan']); ?></td>
                    <td>
                        <span class="badge bg-warning text-dark"><?= esc(strtoupper(str_replace('_', ' ', $item['status']))); ?></span>
                    </td>
                    <td>
                        <a href="/approval/approve-teman/<?= esc($item['id']); ?>" class="btn btn-success btn-sm">Approve</a>

                        <button type="button" class="btn btn-danger btn-sm btn-trigger-reject" data-id="<?= esc($item['id']); ?>">
                            Reject
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="8" class="text-center text-muted">Belum ada request approval teman.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formReject" action="" method="post">
                <?= csrf_field(); ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectModalLabel" style="font-weight: 700;">Alasan Penolakan Cuti</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="catatan" class="form-label" style="font-weight: 600;">Berikan Alasan/Catatan Penolakan:</label>
                        <textarea class="form-control" id="catatan" name="catatan" rows="4" placeholder="Tulis alasan menolak..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Kirim Penolakan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
        const formReject = document.getElementById('formReject');
        const rejectButtons = document.querySelectorAll('.btn-trigger-reject');

        rejectButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                formReject.setAttribute('action', '/approval/reject-teman/' + id);
                rejectModal.show();
            });
        });
    });
</script>

<?= $this->endSection(); ?>