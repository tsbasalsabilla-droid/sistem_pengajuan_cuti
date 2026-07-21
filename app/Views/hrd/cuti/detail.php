<?= $this->include('layout/header') ?>
<?= $this->include('layout/sidebarhrd') ?>

<style>
    .card-table {
        background: #fffaf5;
        border-radius: 20px;
        padding: 30px;
        border: 1px solid #f1e2d2;
        box-shadow: 0 5px 10px rgba(0, 0, 0, 0.05); /* Diperhalus shadow-nya agar premium */
        overflow: hidden;
        margin-bottom: 30px;
    }

    .card-table h1 {
        color: #7b573d;
        font-size: 30px;
        font-weight: 650;
        margin-bottom: 25px;
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
        font-weight: 650;
        border: 1px solid #ead7c4;
        padding: 15px;
        text-align: center;
        vertical-align: middle;
        letter-spacing: 0.02em;
    }

    .table {
        border-radius: 15px;
        overflow: hidden;
        width: 100%;
        border-collapse: collapse;
        border: 1px solid #ead7c4;
    }

    .table th,
    .table td {
        border-right: 1px solid #ead7c4;
        border-bottom: 1px solid #ead7c4;
    }

    .table th:last-child,
    .table td:last-child {
        border-right: none;
    }

    .table tbody td {
        padding: 15px;
        color: #495057;
        border-color: #ead7c4;
    }

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
        font-size: 16px;
        color: #6f4e37;
        font-weight: 700;
        margin-bottom: 5px;
    }

    .detail-item strong {
        font-size: 14px;
        font-weight: 500;
        color: #6f4e37;
    }

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

    .timeline-item.canceled {
        background: #fff1f0;
        border-left-color: #dc3545;
    }

    .timeline-item h4 {
        margin-bottom: 10px;
        color: #7b573d;
        font-weight: 700;
    }

    .approval-cell {
        padding: 14px 12px;
        vertical-align: middle;
    }

    .approval-level {
        display: inline-block;
        font-weight: 600;
        font-size: 14px;
        color: #000000;
        text-transform: capitalize;
        letter-spacing: 0.02em;
    }

    .approval-status span {
        padding: 8px 14px;
        border-radius: 12px;
        display: inline-block;
        min-width: 110px;
        text-align: center;
    }

    .bg-warning {
        background-color: #ffa500 !important;
        color: #fff !important;
        font-weight: 550;
        font-size: 15px;
        border-radius: 10px;
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
            <strong><?= formatTanggalIndonesia($cuti['tanggal_mulai']); ?></strong>
        </div>

        <div class="detail-item">
            <label>Tanggal Selesai</label>
            <strong><?= formatTanggalIndonesia($cuti['tanggal_selesai']); ?></strong>
        </div>

        <div class="detail-item">
            <label>Total Hari</label>
            <strong><?= $cuti['total_hari']; ?> Hari</strong>
        </div>

        <div class="detail-item">
            <label>Tujuan Cuti</label>
            <strong><?= esc($cuti['alasan']); ?></strong>
        </div>

        <div class="detail-item">
            <label>Status Pengajuan</label>
            <div>
                <?php
                $statusDisplay = ucwords(str_replace('_', ' ', $cuti['status'] ?? ''));
                $statusUtama = strtolower($cuti['status'] ?? '');
                ?>
                <?php if (in_array($statusUtama, ['diterima', 'approved'])): ?>
                    <span class="badge bg-success p-2"><?= esc($statusDisplay); ?></span>
                <?php elseif (in_array($statusUtama, ['ditolak', 'rejected', 'batal', 'dibatalkan'])): ?>
                    <span class="badge bg-danger p-2"><?= esc($statusDisplay); ?></span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark p-2 mt-2"><?= esc($statusDisplay); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (in_array($statusUtama, ['batal', 'dibatalkan'])): ?>
    <div class="card-table" style="background: #fff1f0; border-color: #ffa39e;">
        <h3 style="color: #cf1322;">Status Pengajuan</h3>
        <p style="color: #cf1322;"><strong>Pengajuan ini telah dibatalkan otomatis karena belum mendapat persetujuan hingga tanggal mulai cuti.</strong></p>
        <?php if (!empty($cuti['catatan'])): ?>
            <p class="mb-1"><strong>Keterangan:</strong> <?= esc($cuti['catatan']); ?></p>
        <?php endif; ?>
        <?php if (!empty($cuti['alasan_batal'])): ?>
            <p class="mb-0"><strong>Alasan Pembatalan:</strong> <?= esc($cuti['alasan_batal']); ?></p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="card-table">
    <h3>Status Approval Lengkap</h3>

    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th scope="col">Level Approval</th>
                <th scope="col">Status</th>
                <th scope="col">Disetujui Oleh</th>
                <th scope="col">Catatan Penolakan / Pembatalan</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $approvalList = [
                'direktur' => 'Direktur'
            ];
            ?>

            <?php foreach ($approvalList as $key => $label): ?>
                <?php
                $found = null;
                for ($i = count($tracking) - 1; $i >= 0; $i--) {
                    if ($tracking[$i]['level_approval'] == $key) {
                        $found = $tracking[$i];
                        break;
                    }
                }
                ?>
                <tr>
                    <td class="text-center approval-cell"><strong class="approval-level"><?= $label; ?></strong></td>
                    <td class="text-center approval-cell approval-status">
                        <?php if (in_array($statusUtama, ['batal', 'dibatalkan'])): ?>
                            <span class="badge bg-danger text-white p-2">DIBATALKAN</span>
                        <?php else: ?>
                            <?php if ($found): ?>
                                <?php if (strtolower($found['status']) == 'approved'): ?>
                                    <span class="badge bg-success p-2">APPROVED</span>
                                <?php elseif (in_array(strtolower($found['status']), ['rejected', 'ditolak'])): ?>
                                    <span class="badge bg-danger p-2">REJECTED</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark p-2">PENDING</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-secondary p-2">MENUNGGU</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (in_array($statusUtama, ['batal', 'dibatalkan'])): ?>
                            -
                        <?php else: ?>
                            <?= $found['nama'] ?? '-'; ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (in_array($statusUtama, ['batal', 'dibatalkan'])): ?>
                            <span class="text-danger fw-semibold">Pengajuan otomatis dibatalkan.</span>
                        <?php else: ?>
                            <?= $found['catatan'] ?? '-'; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card-table">
    <h3>Timeline Approval</h3>

    <div class="timeline">
        <?php if (in_array($statusUtama, ['batal', 'dibatalkan'])): ?>
            <div class="timeline-item canceled" style="margin-bottom: 0;">
                <h4>SISTEM INFORMASI CUTI</h4>
                <p class="mb-1">Status: <span class="badge bg-danger">DIBATALKAN</span></p>
                <p class="mb-0">Catatan: <strong>Pengajuan otomatis dibatalkan karena belum mendapat persetujuan hingga tanggal mulai cuti.</strong></p>
            </div>
        <?php else: ?>
            <?php
            $latestTrackingByLevel = [];
            foreach ($tracking as $t) {
                $level = strtolower($t['level_approval'] ?? '');
                $latestTrackingByLevel[$level] = $t;
            }

            $approvalOrder = ['teman', 'spv', 'hrd', 'direktur'];
            $orderedTracking = [];
            foreach ($approvalOrder as $level) {
                if (isset($latestTrackingByLevel[$level])) {
                    $orderedTracking[] = $latestTrackingByLevel[$level];
                }
            }
            if (empty($orderedTracking)) {
                $orderedTracking = array_values($latestTrackingByLevel);
            }
            ?>

            <?php if (!empty($orderedTracking)): ?>
                <?php foreach ($orderedTracking as $t): ?>
                    <?php $entryStatus = strtolower($t['status'] ?? ''); ?>
                    <div class="timeline-item<?= in_array($entryStatus, ['rejected', 'ditolak']) ? ' canceled' : '' ?>">
                        <h4><?= strtoupper($t['level_approval']); ?></h4>
                        <p class="mb-1">
                            Status:
                            <?php if ($entryStatus === 'approved'): ?>
                                <span class="badge bg-success">APPROVED</span>
                            <?php elseif (in_array($entryStatus, ['rejected', 'ditolak'])): ?>
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
        <?php endif; ?>
    </div>
</div>

<?= $this->include('layout/footerhrd') ?>