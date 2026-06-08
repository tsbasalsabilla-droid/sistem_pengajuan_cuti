<?= $this->extend('pegawai/layout/sidebar'); ?>

<?= $this->section('content'); ?>

<style>
    .page-title {
        margin-bottom: 30px;
    }

    .card {
        background: white;
        padding: 25px;
        border-radius: 10px;
        margin-bottom: 30px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .card h3 {
        margin-bottom: 20px;
        color: #1e293b;
    }

    .detail-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }

    .detail-item {
        background: #f8fafc;
        padding: 15px;
        border-radius: 8px;
    }

    .detail-item label {
        display: block;
        font-size: 14px;
        color: #666;
        margin-bottom: 5px;
    }

    .detail-item strong {
        font-size: 16px;
        color: #111827;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        background: white;
    }

    table th,
    table td {
        border: 1px solid #ddd;
        padding: 15px;
        text-align: left;
    }

    table th {
        background: #2563eb;
        color: white;
    }

    .status-box {
        padding: 10px 15px;
        border-radius: 8px;
        color: white;
        font-weight: bold;
        display: inline-block;
    }

    .pending {
        background: orange;
    }

    .approved {
        background: green;
    }

    .rejected {
        background: red;
    }

    .timeline {
        margin-top: 20px;
    }

    .timeline-item {
        background: #f8fafc;
        border-left: 5px solid #2563eb;
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 5px;
    }

    .timeline-item h4 {
        margin-bottom: 10px;
    }
</style>

<h1 class="page-title">
    Detail Pengajuan Cuti
</h1>

<!-- INFORMASI CUTI -->
<div class="card">

    <h3>Informasi Pengajuan</h3>

    <div class="detail-grid">

        <div class="detail-item">
            <label>Tanggal Mulai</label>

            <strong>
                <?= $cuti['tanggal_mulai']; ?>
            </strong>
        </div>

        <div class="detail-item">
            <label>Tanggal Selesai</label>

            <strong>
                <?= $cuti['tanggal_selesai']; ?>
            </strong>
        </div>

        <div class="detail-item">
            <label>Total Hari</label>

            <strong>
                <?= $cuti['total_hari']; ?> Hari
            </strong>
        </div>

        <div class="detail-item">
            <label>Tujuan Cuti</label>

            <strong>
                <?= $cuti['alasan']; ?>
            </strong>
        </div>

        <div class="detail-item">
            <label>Status Pengajuan</label>

            <?php

            $statusClass = 'pending';

            if ($cuti['status'] == 'approve') {
                $statusClass = 'approved';
            }

            if ($cuti['status'] == 'rejected' || $cuti['status'] == 'ditolak') {
                $statusClass = 'rejected';
            }

            ?>

            <div class="status-box <?= $statusClass; ?>">
                <?= strtoupper($cuti['status']); ?>
            </div>

        </div>

        <div class="detail-item">
            <label>Tanggal Pengajuan</label>

            <strong>
                <?= $cuti['tanggal_mulai']; ?>
            </strong>
        </div>

    </div>

</div>

<!-- STATUS APPROVAL -->
<div class="card">

    <h3>Status Approval Lengkap</h3>

    <table>

        <tr>
            <th>Level Approval</th>
            <th>Status</th>
            <th>Disetujui Oleh</th>
            <th>Waktu Approval</th>
            <th>Catatan Penolakan</th>
        </tr>

        <?php

        $approvalList = [
            'spv' => 'Pending SPV',
            'hrd' => 'Pending HRD',
            'direktur' => 'Pending Direktur'
        ];

        // Gunakan tanggal_mulai sebagai identitas pengajuan, bukan current_step.
        $hasTemanApproval = false;
        foreach ($tracking as $t) {
            if ($t['level_approval'] === 'teman') {
                $hasTemanApproval = true;
                break;
            }
        }

        if ($hasTemanApproval) {
            $approvalList = [
                'spv' => 'Pending SPV',
                'teman' => 'Pending Teman Sejawat',
                'hrd' => 'Pending HRD',
                'direktur' => 'Pending Direktur'
            ];
        }

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

                <td>
                    <?= $label; ?>
                </td>

                <td>

                    <?php if ($found): ?>

                        <?php if ($found['status'] == 'approved'): ?>

                            <span class="status-box approved">
                                APPROVED
                            </span>

                        <?php elseif ($found['status'] == 'rejected'): ?>

                            <span class="status-box rejected">
                                REJECTED
                            </span>

                        <?php else: ?>

                            <span class="status-box pending">
                                PENDING
                            </span>

                        <?php endif; ?>

                    <?php else: ?>

                        <span class="status-box pending">
                            MENUNGGU
                        </span>

                    <?php endif; ?>

                </td>

                <td>
                    <?= $found['nama'] ?? '-'; ?>
                </td>

                <td>
                    <?= $found['approved_at'] ?? '-'; ?>
                </td>

                <td>
                    <?= $found['catatan'] ?? '-'; ?>
                </td>

            </tr>

        <?php endforeach; ?>

    </table>

</div>

<!-- TIMELINE -->
<div class="card">

    <h3>Timeline Approval</h3>

    <div class="timeline">

        <?php if ($tracking): ?>

            <?php foreach ($tracking as $t): ?>

                <div class="timeline-item">

                    <h4>
                        <?= strtoupper($t['level_approval']); ?>
                    </h4>

                    <p>
                        Status:
                        <strong>
                            <?= strtoupper($t['status']); ?>
                        </strong>
                    </p>

                    <p>
                        Oleh:
                        <strong>
                            <?= $t['nama'] ?? '-'; ?>
                        </strong>
                    </p>

                    <p>
                        Waktu:
                        <strong>
                            <?= $t['approved_at'] ?? '-'; ?>
                        </strong>
                    </p>

                    <p>
                        Catatan:
                        <strong>
                            <?= $t['catatan'] ?? '-'; ?>
                        </strong>
                    </p>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <p>
                Belum ada approval.
            </p>

        <?php endif; ?>

    </div>

</div>

<?= $this->endSection(); ?>