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
        margin-bottom: 30px;
    }

    .card-table h2,
    .card-table h3 {
        color: #7b573d;
        font-size: 24px;
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

    /* Grid layout untuk detail info */
    .detail-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }

    .detail-item {
        background: #fbf5ee;
        padding: 15px;
        border-radius: 8px;
        border: 1px solid #ead7c4;
    }

    .detail-item label {
        display: block;
        font-size: 14px;
        color: #6f4e37;
        font-weight: 600;
        margin-bottom: 5px;
    }

    .detail-item strong {
        font-size: 16px;
        color: #495057;
    }

    /* Timeline style disesuaikan tema */
    .timeline {
        margin-top: 20px;
    }

    .timeline-item {
        background: #fbf5ee;
        border-left: 5px solid #7b573d;
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 5px;
        border: 1px solid #ead7c4;
        border-left-width: 5px;
    }

    .timeline-item h4 {
        margin-bottom: 10px;
        color: #7b573d;
        font-weight: 600;
    }
</style>

<div class="card-table">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Detail Pengajuan Cuti</h1>
    </div>

    <h3>Informasi Pengajuan</h3>

    <div class="detail-grid">
        <div class="detail-item">
            <label>Tanggal Mulai</label>
            <strong><?= $cuti['tanggal_mulai']; ?></strong>
        </div>

        <div class="detail-item">
            <label>Tanggal Selesai</label>
            <strong><?= $cuti['tanggal_selesai']; ?></strong>
        </div>

        <div class="detail-item">
            <label>Total Hari</label>
            <strong><?= $cuti['total_hari']; ?> Hari</strong>
        </div>

        <div class="detail-item">
            <label>Tujuan Cuti</label>
            <strong><?= $cuti['alasan']; ?></strong>
        </div>

        <div class="detail-item">
            <label>Status Pengajuan</label>
            <div>
                <?php if ($cuti['status'] == 'diterima'): ?>
                    <span class="badge bg-success p-2"><?= strtoupper($cuti['status']); ?></span>
                <?php elseif ($cuti['status'] == 'ditolak'): ?>
                    <span class="badge bg-danger p-2"><?= strtoupper($cuti['status']); ?></span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark p-2"><?= strtoupper($cuti['status']); ?></span>
                <?php endif; ?>
            </div>
        </div>


    </div>
</div>

<div class="card-table">
    <h3>Status Approval Lengkap</h3>

    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th scope="col">Level Approval</th>
                <th scope="col">Status</th>
                <th scope="col">Disetujui Oleh</th>
                <th scope="col">Catatan Penolakan</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $approvalList = [
                'direktur' => 'Pending Direktur'
            ];
            ?>

            <?php foreach ($approvalList as $key => $label): ?>
                <?php
                $found = null;
                foreach ($tracking as $t) {
                    if ($t['level_approval'] == $key) {
                        $found = $t;
                        break;
                    }
                }
                ?>
                <tr>
                    <td class="text-center"><strong><?= $label; ?></strong></td>
                    <td class="text-center">
                        <?php if ($found): ?>
                            <?php if ($found['status'] == 'approved'): ?>
                                <span class="badge bg-success p-2">APPROVED</span>
                            <?php elseif ($found['status'] == 'rejected'): ?>
                                <span class="badge bg-danger p-2">REJECTED</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark p-2">PENDING</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge bg-secondary p-2">MENUNGGU</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $found['nama'] ?? '-'; ?></td>
                    <td><?= $found['catatan'] ?? '-'; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card-table">
    <h3>Timeline Approval</h3>

    <div class="timeline">
        <?php if ($tracking): ?>
            <?php foreach ($tracking as $t): ?>
                <div class="timeline-item">
                    <h4><?= strtoupper($t['level_approval']); ?></h4>
                    <p class="mb-1">
                        Status:
                        <?php if ($t['status'] == 'approved'): ?>
                            <span class="badge bg-success">APPROVED</span>
                        <?php elseif ($t['status'] == 'rejected'): ?>
                            <span class="badge bg-danger">REJECTED</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">PENDING</span>
                        <?php endif; ?>
                    </p>
                    <p class="mb-1">Oleh: <strong><?= $t['nama'] ?? '-'; ?></strong></p>
                    <p class="mb-0">Catatan: <strong><?= $t['catatan'] ?? '-'; ?></strong></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-muted">Belum ada approval.</p>
        <?php endif; ?>
    </div>
</div>

<?= $this->include('layout/footerhrd') ?>