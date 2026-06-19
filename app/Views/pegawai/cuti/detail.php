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

<h1 class="page-title">Detail Pengajuan Cuti</h1>

<div class="card">
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
            <?php
            $statusClass = 'pending';
            if ($cuti['status'] == 'approve' || $cuti['status'] == 'approved') {
                $statusClass = 'approved';
            }
            if (in_array($cuti['status'], ['rejected', 'ditolak'])) {
                $statusClass = 'rejected';
            }

            $statusLabel = match ($cuti['status']) {
                'pending' => 'MENUNGGU TEMAN SEJAWAT',
                'pending_spv' => 'MENUNGGU SPV',
                'pending_hrd' => 'MENUNGGU HRD',
                'pending_direktur' => 'MENUNGGU DIREKTUR',
                'approve', 'approved', 'diterima' => 'DISETUJUI',
                'rejected', 'ditolak' => 'DITOLAK',
                default => strtoupper($cuti['status'])
            };
            ?>
            <div class="status-box <?= $statusClass; ?>">
                <?= $statusLabel; ?>
            </div>
        </div>
        <div class="detail-item">
            <label>Tanggal Pengajuan</label>
            <strong><?= $cuti['tanggal_mulai']; ?></strong>
        </div>
    </div>
</div>

<div class="card">
    <h3>Status Approval Lengkap</h3>
    <table>
        <thead>
            <tr>
                <th>Level Approval</th>
                <th>Status</th>
                <th>Disetujui Oleh</th>
                <th>Catatan Penolakan</th>
            </tr>
        </thead>
        <tbody>
            <?php

            $listApprovalTeman = array_filter($tracking, fn($t) => strtolower($t['level_approval'] ?? '') === 'teman');

            $jumlahApprove = 0;
            $jumlahReject = 0;

            foreach ($listApprovalTeman as $t) {
                if (strtolower($t['status'] ?? '') === 'approved' || strtolower($t['status'] ?? '') === 'approve') {
                    $jumlahApprove++;
                } elseif (strtolower($t['status'] ?? '') === 'rejected' || strtolower($t['status'] ?? '') === 'ditolak') {
                    $jumlahReject++;
                }
            }

            $totalSuaraMasuk = $jumlahApprove + $jumlahReject;

            $temanStatus = 'MENUNGGU';
            $temanClass = 'pending';

            if ($totalSuaraMasuk < 3) {
                $temanStatus = "VOTING ($totalSuaraMasuk/3)";
                $temanClass = 'pending';
            } else {
                if ($jumlahApprove > $jumlahReject) {
                    $temanStatus = 'DISETUJUI';
                    $temanClass = 'approved';
                } else {
                    $temanStatus = 'DITOLAK';
                    $temanClass = 'rejected';
                }
            }


            $currentStatus = $cuti['status'];

            $spvLog = array_filter($tracking, fn($t) => ($t['level_approval'] ?? $t['role_approver'] ?? '') === 'spv');
            $spvLog = reset($spvLog);

            $spvStatus = 'MENUNGGU';
            $spvClass = 'pending';
            if ($spvLog) {
                if (strtolower($spvLog['status']) === 'approved') {
                    $spvStatus = 'DISETUJUI';
                    $spvClass = 'approved';
                } else {
                    $spvStatus = 'DITOLAK';
                    $spvClass = 'rejected';
                }
            } elseif ($currentStatus === 'rejected' || $currentStatus === 'ditolak') {
                $spvStatus = 'DITOLAK';
                $spvClass = 'rejected';
            }

            $hrdLog = array_filter($tracking, fn($t) => ($t['level_approval'] ?? $t['role_approver'] ?? '') === 'hrd');
            $hrdLog = reset($hrdLog);

            $hrdStatus = 'MENUNGGU';
            $hrdClass = 'pending';
            if ($hrdLog) {
                if (strtolower($hrdLog['status']) === 'approved') {
                    $hrdStatus = 'DISETUJUI';
                    $hrdClass = 'approved';
                } else {
                    $hrdStatus = 'DITOLAK';
                    $hrdClass = 'rejected';
                }
            } elseif ($currentStatus === 'rejected' || $currentStatus === 'ditolak') {
                $hrdStatus = 'DITOLAK';
                $hrdClass = 'rejected';
            }

            $dirLog = array_filter($tracking, fn($t) => ($t['level_approval'] ?? $t['role_approver'] ?? '') === 'direktur');
            $dirLog = reset($dirLog);

            $dirStatus = 'MENUNGGU';
            $dirClass = 'pending';
            if ($dirLog) {
                if (strtolower($dirLog['status']) === 'approved') {
                    $dirStatus = 'DISETUJUI';
                    $dirClass = 'approved';
                } else {
                    $dirStatus = 'DITOLAK';
                    $dirClass = 'rejected';
                }
            } elseif ($currentStatus === 'rejected' || $currentStatus === 'ditolak') {
                $dirStatus = 'DITOLAK';
                $dirClass = 'rejected';
            }


            $detailTeman = array_filter($tracking, fn($t) => ($t['level_approval'] ?? $t['role_approver'] ?? '') === 'teman');

            $detailSpv = array_filter($tracking, fn($t) => ($t['level_approval'] ?? $t['role_approver'] ?? '') === 'spv');
            $detailSpv = reset($detailSpv);

            $detailHrd = array_filter($tracking, fn($t) => ($t['level_approval'] ?? $t['role_approver'] ?? '') === 'hrd');
            $detailHrd = reset($detailHrd);

            $detailDir = array_filter($tracking, fn($t) => ($t['level_approval'] ?? $t['role_approver'] ?? '') === 'direktur');
            $detailDir = reset($detailDir);
            ?>

            <tr>
                <td>Menunggu Teman Sejawat</td>
                <td><span class="status-box <?= $temanClass; ?>"><?= $temanStatus; ?></span></td>
                <td>
                    <?php if (!empty($detailTeman)): ?>
                        <?php
                        $namaTeman = array_map(fn($t) => trim($t['nama'] ?? ''), $detailTeman);
                        $namaTemanSaring = array_filter($namaTeman, fn($nama) => $nama !== '' && $nama !== '-');
                        echo !empty($namaTemanSaring) ? implode(', ', $namaTemanSaring) : '-';
                        ?>
                    <?php else: ?> - <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($detailTeman)): ?>
                        <?php
                        $catatanTemanArr = [];
                        foreach ($detailTeman as $t) {
                            $statusSaran = strtolower($t['status'] ?? '');
                            if (($statusSaran === 'rejected' || $statusSaran === 'ditolak') && !empty($t['catatan']) && trim($t['catatan']) !== '-') {
                                $catatanTemanArr[] = ($t['nama'] ?? 'Teman') . ': ' . $t['catatan'];
                            }
                        }
                        echo !empty($catatanTemanArr) ? implode(', ', $catatanTemanArr) : '-';
                        ?>
                    <?php else: ?> - <?php endif; ?>
                </td>
            </tr>

            <tr>
                <td>Menunggu SPV</td>
                <td><span class="status-box <?= $spvClass; ?>"><?= $spvStatus; ?></span></td>
                <td><?= !empty($detailSpv['nama']) && $detailSpv['nama'] !== '-' ? $detailSpv['nama'] : ($spvStatus === 'DITOLAK' ? '-' : '-'); ?></td>
                <td><?= $detailSpv['catatan'] ?? '-'; ?></td>
            </tr>

            <tr>
                <td>Menunggu HRD</td>
                <td><span class="status-box <?= $hrdClass; ?>"><?= $hrdStatus; ?></span></td>
                <td><?= !empty($detailHrd['nama']) && $detailHrd['nama'] !== '-' ? $detailHrd['nama'] : ($hrdStatus === 'DITOLAK' ? '-' : '-'); ?></td>
                <td><?= $detailHrd['catatan'] ?? '-'; ?></td>
            </tr>

            <tr>
                <td>Menunggu Direktur</td>
                <td><span class="status-box <?= $dirClass; ?>"><?= $dirStatus; ?></span></td>
                <td><?= !empty($detailDir['nama']) && $detailDir['nama'] !== '-' ? $detailDir['nama'] : ($dirStatus === 'DITOLAK' ? '-' : '-'); ?></td>
                <td><?= $detailDir['catatan'] ?? '-'; ?></td>
            </tr>
        </tbody>
    </table>
</div>

<div class="card">
    <h3>Timeline Approval</h3>
    <div class="timeline">
        <?php if (!empty($tracking)): ?>
            <?php
            $hasVisibleTimeline = false;
            foreach ($tracking as $t):
                $namaEksekutor = isset($t['nama']) ? trim($t['nama']) : '';

                if ($namaEksekutor === '' || $namaEksekutor === '-' || empty($namaEksekutor)) {
                    continue;
                }
                $hasVisibleTimeline = true;
            ?>
                <div class="timeline-item">
                    <h4><?= strtoupper($t['level_approval'] === 'teman' ? 'TEMAN SEJAWAT' : $t['level_approval']); ?></h4>
                    <p>Status: <strong><?= strtoupper($t['status']); ?></strong></p>
                    <p>Oleh: <strong><?= esc($namaEksekutor); ?></strong></p>
                    <p>Catatan: <strong><?= esc($t['catatan'] ?? '-'); ?></strong></p>
                </div>
            <?php endforeach; ?>

            <?php if (!$hasVisibleTimeline): ?>
                <p class="text-muted">Belum ada history approval yang divalidasi.</p>
            <?php endif; ?>
        <?php else: ?>
            <p class="text-muted">Belum ada approval.</p>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection(); ?>