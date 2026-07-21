<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pengajuan Cuti</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .content {
            margin-left: 260px;
            width: calc(100% - 260px);
            padding: 35px 50px;
            box-sizing: border-box;
            min-height: 100vh;
            transition: margin-left 0.3s ease, width 0.3s ease;
        }

        .sidebar.collapsed+.content {
            margin-left: 82px;
            width: calc(100% - 82px);
        }

        .page-title {
            margin-bottom: 30px;
            color: #4f3a2c;
            font-size: 32px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 18px;
            margin-bottom: 30px;
            box-shadow: 0 16px 35px rgba(101, 61, 35, 0.08);
        }

        .card h3 {
            margin-bottom: 20px;
            color: #1e293b;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .detail-item {
            background: #f8fafc;
            padding: 18px;
            border-radius: 12px;
        }

        .detail-item label {
            display: block;
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
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
            border-radius: 10px;
        }

        .timeline-item.canceled {
            border-left-color: #dc3545;
            background: #fff1f0;
        }

        .timeline-item h4 {
            margin-bottom: 10px;
        }

        @media (max-width: 992px) {
            .content {
                margin-left: 0;
                width: 100%;
                padding: 25px 22px;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?= view('layout/sidebar'); ?>

    <div class="content">
        <h1 class="page-title">Detail Pengajuan Cuti</h1>

        <!-- INFORMASI CUTI -->
        <div class="card">
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
                    <label>Alasan</label>
                    <strong><?= $cuti['alasan']; ?></strong>
                </div>

                <div class="detail-item">
                    <label>Status Pengajuan</label>
                    <?php
                    $statusClass = 'pending';
                    if (in_array($cuti['status'], ['diterima', 'approved'])) {
                        $statusClass = 'approved';
                    }
                    if (in_array($cuti['status'], ['ditolak', 'rejected', 'batal', 'dibatalkan'])) {
                        $statusClass = 'rejected';
                    }
                    ?>
                    <div class="status-box <?= $statusClass; ?>">
                        <?php
                        $statusLabel = match ($cuti['status']) {
                            'pending' => 'MENUNGGU',
                            'pending_spv' => 'MENUNGGU SPV',
                            'pending_hrd' => 'MENUNGGU HRD',
                            'pending_direktur' => 'MENUNGGU DIREKTUR',
                            'pending_teman', 'pending_teman_sejawat' => 'MENUNGGU TEMAN SEJAWAT',
                            'approve', 'diterima' => 'DISETUJUI',
                            'rejected', 'ditolak' => 'DITOLAK',
                            default => strtoupper($cuti['status'])
                        };
                        ?>
                        <?= $statusLabel; ?>
                    </div>
                </div>


            </div>
        </div>

        <?php if (in_array($cuti['status'] ?? '', ['batal', 'dibatalkan'])): ?>
            <div class="card">
                <h3>Status Pengajuan</h3>
                <p><strong>Pengajuan ini telah dibatalkan.</strong></p>
                <?php if (!empty($cuti['catatan'])): ?>
                    <p><strong>Keterangan:</strong> <?= esc($cuti['catatan']); ?></p>
                <?php endif; ?>
                <?php if (!empty($cuti['alasan_batal'])): ?>
                    <p><strong>Alasan Pembatalan:</strong> <?= esc($cuti['alasan_batal']); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- STATUS APPROVAL -->
        <div class="card">
            <h3>Status Approval Lengkap</h3>
            <table>
                <tr>
                    <th>Level Approval</th>
                    <th>Status</th>
                    <th>Disetujui Oleh</th>
                    <th>Catatan Penolakan</th>
                </tr>
                <?php
                $approvalList = [
                    'hrd' => 'Menunggu HRD',
                    'direktur' => 'Menunggu Direktur'
                ];
                ?>
                <?php $statusUtama = $cuti['status'] ?? ''; ?>
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
                        <td><?= $label; ?></td>
                        <td>
                            <?php if (in_array($statusUtama, ['batal', 'dibatalkan'])): ?>
                                <span class="status-box rejected">DIBATALKAN</span>
                            <?php else: ?>
                                <?php if ($found): ?>
                                    <?php if ($found['status'] == 'approved'): ?>
                                        <span class="status-box approved">APPROVED</span>
                                    <?php elseif ($found['status'] == 'rejected'): ?>
                                        <span class="status-box rejected">REJECTED</span>
                                    <?php else: ?>
                                        <span class="status-box pending">MENUNGGU</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="status-box pending">MENUNGGU</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (in_array($statusUtama, ['batal', 'dibatalkan'])): ?>-
                        <?php else: ?>
                            <?= $found['nama'] ?? '-'; ?>
                        <?php endif; ?>
                        </td>
                        <td>
                            <?php if (in_array($statusUtama, ['batal', 'dibatalkan'])): ?>
                                <?= esc($cuti['alasan_batal'] ?? $cuti['catatan'] ?? '-'); ?>
                            <?php else: ?>
                                <?= $found['catatan'] ?? '-'; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="card">
            <h3>Timeline Approval</h3>
            <div class="timeline">
                <?php
                $trackStatus = in_array($statusUtama, ['batal', 'dibatalkan']);
                $hasCanceledEntry = false;
                foreach ($tracking as $t) {
                    if (in_array(strtolower($t['status'] ?? ''), ['batal', 'dibatalkan'])) {
                        $hasCanceledEntry = true;
                        break;
                    }
                }
                ?>
                <?php if ($tracking): ?>
                    <?php if ($trackStatus && !$hasCanceledEntry): ?>
                        <div class="timeline-item canceled">
                            <h4>Pengajuan ini telah dibatalkan.</h4>
                            <p><strong>Alasan Pembatalan:</strong> <?= esc($cuti['alasan_batal'] ?? $cuti['catatan'] ?? 'Pengajuan dibatalkan otomatis karena sudah melewati tanggal mulai cuti tanpa kejelasan persetujuan.'); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php foreach ($tracking as $t): ?>
                        <?php
                        $entryStatus = strtolower($t['status'] ?? '');
                        if ($trackStatus && !in_array($entryStatus, ['batal', 'dibatalkan'])) {
                            continue;
                        }
                        ?>
                        <div class="timeline-item<?= in_array($entryStatus, ['batal', 'dibatalkan']) ? ' canceled' : '' ?>">
                            <h4><?= strtoupper($t['level_approval']); ?></h4>
                            <p>Status:
                                <?php if ($entryStatus === 'approved'): ?>
                                    <span class="badge bg-success">APPROVED</span>
                                <?php elseif (in_array($entryStatus, ['rejected', 'ditolak'])): ?>
                                    <span class="badge bg-danger">REJECTED</span>
                                <?php elseif (in_array($entryStatus, ['batal', 'dibatalkan'])): ?>
                                    <span class="badge bg-danger">DIBATALKAN</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">PENDING</span>
                                <?php endif; ?>
                            </p>
                            <p>Oleh: <strong><?= $t['nama'] ?? '-'; ?></strong></p>
                            <p>Catatan: <strong><?= $t['catatan'] ?? '-'; ?></strong></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php if (in_array($statusUtama ?? ($cuti['status'] ?? ''), ['batal', 'dibatalkan'])): ?>
                        <div class="timeline-item canceled">
                            <h4>Pengajuan ini telah dibatalkan.</h4>
                            <p><strong>Alasan Pembatalan:</strong> <?= esc($cuti['alasan_batal'] ?? $cuti['catatan'] ?? 'Pengajuan dibatalkan otomatis karena sudah melewati tanggal mulai cuti tanpa kejelasan persetujuan.'); ?></p>
                        </div>
                    <?php else: ?>
                        <p>Belum ada approval.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>