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

            <?php
            $dbApproval = new \App\Models\ApprovalModel();
            $idCuti = $cuti['id'] ?? $id;
            $statusUtama = $cuti['status'];

            $checkSpv = $dbApproval->where('cuti_id', $idCuti)->where('role_approver', 'spv')->first();
            $checkHrd = $dbApproval->where('cuti_id', $idCuti)->where('role_approver', 'hrd')->first();
            $checkDir = $dbApproval->where('cuti_id', $idCuti)->where('role_approver', 'direktur')->first();
            ?>

            <tr>
                <td>Menunggu SPV</td>
                <td>
                    <?php
                    if (in_array($statusUtama, ['pending_hrd', 'pending_direktur', 'approve', 'approved', 'diterima']) || ($checkSpv && $checkSpv['status'] === 'approved')) {
                        echo '<span class="status-box approved">DISETUJUI</span>';
                    } elseif (($checkSpv && $checkSpv['status'] === 'rejected') || ($statusUtama === 'rejected' && ($checkSpv || isset($detailSpv['catatan'])))) {
                        echo '<span class="status-box rejected">DITOLAK</span>';
                    } else {
                        echo '<span class="status-box pending">MENUNGGU</span>';
                    }
                    ?>
                </td>
                <td>
                    <?php
                    if (in_array($statusUtama, ['pending_hrd', 'pending_direktur', 'approve', 'approved', 'diterima']) || ($checkSpv && $checkSpv['status'] === 'approved')) {
                        $spvLogData = $dbApproval->select('pegawai.nama')
                            ->join('pegawai', 'pegawai.id = ' . $dbApproval->table . '.approver_id', 'left')
                            ->where('cuti_id', $idCuti)
                            ->where('role_approver', 'spv')
                            ->first();
                        echo esc($spvLogData['nama'] ?? 'Supervisor');
                    } else {
                        echo '-';
                    }
                    ?>
                </td>
                <td>
                    <?php
                    if (in_array($statusUtama, ['pending_hrd', 'pending_direktur', 'approve', 'approved', 'diterima'])) {
                        echo '-';
                    } elseif ($checkSpv && $checkSpv['status'] === 'rejected') {
                        echo esc($checkSpv['catatan']);
                    } elseif ($statusUtama === 'rejected' && isset($detailSpv['catatan'])) {
                        echo esc($detailSpv['catatan']);
                    } else {
                        echo '-';
                    }
                    ?>
                </td>
            </tr>

            <tr>
                <td>Menunggu HRD</td>
                <td>
                    <?php
                    if (in_array($statusUtama, ['pending_direktur', 'approve', 'approved', 'diterima']) || ($checkHrd && $checkHrd['status'] === 'approved')) {
                        echo '<span class="status-box approved">DISETUJUI</span>';
                    } elseif (($checkHrd && $checkHrd['status'] === 'rejected') || ($statusUtama === 'rejected' && $statusUtama !== 'pending_spv' && $checkHrd)) {
                        echo '<span class="status-box rejected">DITOLAK</span>';
                    } else {
                        echo '<span class="status-box pending">MENUNGGU</span>';
                    }
                    ?>
                </td>
                <td>
                    <?php
                    if (in_array($statusUtama, ['pending_direktur', 'approve', 'approved', 'diterima']) || ($checkHrd && $checkHrd['status'] === 'approved')) {
                        // Mengambil nama langsung dari log tracking array yang murni lolos dari database
                        $searchHrd = array_filter($tracking, fn($t) => strtolower($t['level_approval'] ?? $t['role_approver'] ?? '') === 'hrd');
                        $hrdData = reset($searchHrd);

                        echo esc($hrdData['nama'] ?? 'HRD Team');
                    } else {
                        echo '-';
                    }
                    ?>
                </td>
                <td>
                    <?php
                    if (in_array($statusUtama, ['pending_direktur', 'approve', 'approved', 'diterima'])) {
                        echo '-';
                    } elseif ($checkHrd && $checkHrd['status'] === 'rejected') {
                        echo esc($checkHrd['catatan']);
                    } elseif ($statusUtama === 'rejected' && isset($detailHrd['catatan'])) {
                        echo esc($detailHrd['catatan']);
                    } else {
                        echo '-';
                    }
                    ?>
                </td>
            </tr>

            <tr>
                <td>Menunggu Direktur</td>
                <td>
                    <?php
                    if (in_array($statusUtama, ['approve', 'approved', 'diterima']) || ($checkDir && $checkDir['status'] === 'approved')) {
                        echo '<span class="status-box approved">DISETUJUI</span>';
                    } elseif (($checkDir && $checkDir['status'] === 'rejected') || ($statusUtama === 'rejected' && $checkDir)) {
                        echo '<span class="status-box rejected">DITOLAK</span>';
                    } else {
                        echo '<span class="status-box pending">MENUNGGU</span>';
                    }
                    ?>
                </td>
                <td>
                    <?php
                    if (in_array($statusUtama, ['approve', 'approved', 'diterima']) || ($checkDir && $checkDir['status'] === 'approved')) {
                        $dirLogData = $dbApproval->select('pegawai.nama')
                            ->join('pegawai', 'pegawai.id = ' . $dbApproval->table . '.approver_id', 'left')
                            ->where('cuti_id', $idCuti)
                            ->where('role_approver', 'direktur')
                            ->first();
                        echo esc($dirLogData['nama'] ?? 'Direktur');
                    } else {
                        echo '-';
                    }
                    ?>
                </td>
                <td>
                    <?php
                    if (in_array($statusUtama, ['approve', 'approved', 'diterima'])) {
                        echo '-';
                    } elseif ($checkDir && $checkDir['status'] === 'rejected') {
                        echo esc($checkDir['catatan']);
                    } elseif ($statusUtama === 'rejected' && isset($detailDir['catatan'])) {
                        echo esc($detailDir['catatan']);
                    } else {
                        echo '-';
                    }
                    ?>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<div class="card">
    <h3>Timeline Approval</h3>
    <div class="timeline">
        <?php
        $dbApproval = new \App\Models\ApprovalModel();
        $idCuti = $cuti['id'] ?? $id;
        $statusUtama = $cuti['status'];

        $temanApprovals = $dbApproval->select($dbApproval->table . '.*, pegawai.nama as nama_approver')
            ->join('pegawai', 'pegawai.id = ' . $dbApproval->table . '.approver_id', 'left')
            ->where('cuti_id', $idCuti)
            ->where('role_approver', 'teman')
            ->orderBy($dbApproval->table . '.created_at', 'ASC')
            ->findAll();

        $spvLogData = $dbApproval->select('pegawai.nama')
            ->join('pegawai', 'pegawai.id = ' . $dbApproval->table . '.approver_id', 'left')
            ->where('cuti_id', $idCuti)
            ->where('role_approver', 'spv')
            ->first();
        $namaSpvDinamis = $spvLogData['nama'] ?? 'Supervisor';
        ?>

        <?php if (!empty($detailTeman)): ?>
            <?php foreach ($detailTeman as $t): ?>
                <?php
                $statusSaran = strtolower($t['status'] ?? '');
                if ($statusSaran === 'approved' || $statusSaran === 'approve'):
                ?>
                    <div class="timeline-item">
                        <h4>TEMAN SEJAWAT</h4>
                        <p>Status: <strong>APPROVED</strong></p>
                        <p>Oleh: <strong><?= esc($t['nama'] ?? '-'); ?></strong></p>
                        <p>Catatan: <strong>Disetujui Teman Sejawat</strong></p>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php
        $spvLogData = $dbApproval->select('pegawai.nama')
            ->join('pegawai', 'pegawai.id = ' . $dbApproval->table . '.approver_id', 'left')
            ->where('cuti_id', $idCuti)
            ->where('role_approver', 'spv')
            ->first();
        $namaSpvDinamis = $spvLogData['nama'] ?? 'Supervisor';
        ?>

        <?php if (in_array($statusUtama, ['pending_hrd', 'pending_direktur', 'approve', 'approved', 'diterima'])): ?>
            <div class="timeline-item">
                <h4>SUPERVISOR</h4>
                <p>Status: <strong>APPROVED</strong></p>
                <p>Oleh: <strong><?= esc($namaSpvDinamis); ?></strong></p>
                <p>Catatan: <strong>Disetujui oleh SPV</strong></p>
            </div>
        <?php elseif ($statusUtama === 'rejected' && $checkSpv && $checkSpv['status'] === 'rejected'): ?>
            <div class="timeline-item">
                <h4>SUPERVISOR</h4>
                <p>Status: <strong>REJECTED</strong></p>
                <p>Oleh: <strong><?= esc($namaSpvDinamis); ?></strong></p>
                <p>Catatan: <strong><?= esc($checkSpv['catatan'] ?? 'Ditolak oleh SPV'); ?></strong></p>
            </div>
        <?php endif; ?>

        <?php
        $hrdLogData = $dbApproval->select('pegawai.nama')
            ->join('pegawai', 'pegawai.id = ' . $dbApproval->table . '.approver_id', 'left')
            ->where('cuti_id', $idCuti)
            ->where('role_approver', 'hrd')
            ->first();

        if (!empty($hrdLogData['nama'])) {
            $namaHrdDinamis = $hrdLogData['nama'];
        } else {
            $searchHrd = array_filter($tracking, fn($t) => strtolower($t['level_approval'] ?? $t['role_approver'] ?? '') === 'hrd');
            $hrdData = reset($searchHrd);
            $namaHrdDinamis = !empty($hrdData['nama']) ? $hrdData['nama'] : 'HRD Team';
        }
        ?>

        <?php if (in_array($statusUtama, ['pending_direktur', 'approve', 'approved', 'diterima'])): ?>
            <div class="timeline-item">
                <h4><?= strtoupper(esc($namaHrdDinamis)); ?></h4>
                <p>Status: <strong>APPROVED</strong></p>
                <p>Oleh: <strong><?= esc($namaHrdDinamis); ?></strong></p>
                <p>Catatan: <strong>Disetujui oleh HRD</strong></p>
            </div>
        <?php elseif ($statusUtama === 'rejected' && $checkHrd && $checkHrd['status'] === 'rejected'): ?>
            <div class="timeline-item">
                <h4><?= strtoupper(esc($namaHrdDinamis)); ?></h4>
                <p>Status: <strong>REJECTED</strong></p>
                <p>Oleh: <strong><?= esc($namaHrdDinamis); ?></strong></p>
                <p>Catatan: <strong><?= esc($checkHrd['catatan'] ?? 'Ditolak oleh HRD'); ?></strong></p>
            </div>
        <?php endif; ?>

        <?php
        $dirLogData = $dbApproval->select('pegawai.nama')
            ->join('pegawai', 'pegawai.id = ' . $dbApproval->table . '.approver_id', 'left')
            ->where('cuti_id', $idCuti)
            ->where('role_approver', 'direktur')
            ->first();

        if (!empty($dirLogData['nama'])) {
            $namaDirDinamis = $dirLogData['nama'];
        } else {
            $searchDir = array_filter($tracking, fn($t) => strtolower($t['level_approval'] ?? $t['role_approver'] ?? '') === 'direktur');
            $dirData = reset($searchDir);
            $namaDirDinamis = !empty($dirData['nama']) ? $dirData['nama'] : 'Direktur';
        }
        ?>

        <?php if (in_array($statusUtama, ['approve', 'approved', 'diterima'])): ?>
            <div class="timeline-item">
                <h4><?= strtoupper(esc($namaDirDinamis)); ?></h4>
                <p>Status: <strong>APPROVED</strong></p>
                <p>Oleh: <strong><?= esc($namaDirDinamis); ?></strong></p>
                <p>Catatan: <strong>Disetujui oleh Direktur</strong></p>
            </div>
        <?php elseif ($statusUtama === 'rejected' && $checkDir && $checkDir['status'] === 'rejected'): ?>
            <div class="timeline-item">
                <h4><?= strtoupper(esc($namaDirDinamis)); ?></h4>
                <p>Status: <strong>REJECTED</strong></p>
                <p>Oleh: <strong><?= esc($namaDirDinamis); ?></strong></p>
                <p>Catatan: <strong><?= esc($checkDir['catatan'] ?? 'Ditolak oleh Direktur'); ?></strong></p>
            </div>
        <?php endif; ?>

        <?php if (empty($temanApprovals) && $statusUtama === 'pending_teman'): ?>
            <p class="text-muted">Belum ada approval.</p>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection(); ?>