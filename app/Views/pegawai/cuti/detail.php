<?php $title = 'Detail Pengajuan Cuti'; ?>
<?= $this->extend('pegawai/layout/sidebar'); ?>

<?= $this->section('content'); ?>

<style>
    .page-title {
        margin-bottom: 30px;
    }

    .card {
        background: #fffaf5;
        border-radius: 20px;
        padding: 30px;
        border: 1px solid #f1e2d2;
        box-shadow: 0 5px 10px rgba(0, 0, 0, 0.4);
        overflow: hidden;
        margin-bottom: 30px;
    }

    .card h3 {
        margin-bottom: 20px;
        color: #7b573d;
        font-size: 24px;
        font-weight: 700;
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

    .status-pill {
        display: inline-block;
        padding: 6px 14px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 700;
        color: #fff;
        text-transform: uppercase;
    }

    .status-pill.approved {
        background: #008000;
    }

    .status-pill.pending {
        background: #f59e0b;
    }

    .status-pill.rejected,
    .status-pill.canceled {
        background: #dc2626;
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

    @media (max-width: 992px) {
        .detail-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="page-title">Detail Pengajuan Cuti</h1>
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
            <strong><?= $cuti['alasan']; ?></strong>
        </div>
        <div class="detail-item">
            <label>Status Pengajuan</label>
            <?php
            $statusClass = 'pending';
            if (in_array($cuti['status'], ['approve', 'approved', 'diterima'])) {
                $statusClass = 'approved';
            }
            if (in_array($cuti['status'], ['rejected', 'ditolak', 'dibatalkan', 'batal'])) {
                $statusClass = 'rejected';
            }

            $isCanceled = in_array($cuti['status'], ['batal', 'dibatalkan']);

            $statusLabel = match ($cuti['status']) {
                'pending' => 'MENUNGGU TEMAN SEJAWAT',
                'pending_spv' => 'MENUNGGU SPV',
                'pending_hrd' => 'MENUNGGU HRD',
                'pending_direktur' => 'MENUNGGU DIREKTUR',
                'pending_teman', 'pending_teman_sejawat' => 'MENUNGGU TEMAN SEJAWAT',
                'approve', 'approved', 'diterima' => 'DISETUJUI',
                'rejected', 'ditolak' => 'DITOLAK',
                'dibatalkan', 'batal' => 'DIBATALKAN',
                default => strtoupper($cuti['status'])
            };
            ?>
            <div class="status-box <?= $statusClass; ?>">
                <?= $statusLabel; ?>
            </div>
        </div>
        <?php if ($isCanceled): ?>
            <div class="detail-item">
                <label>Alasan Pembatalan</label>
                <?php
                $cancelReason = !empty($cuti['alasan_batal'])
                    ? $cuti['alasan_batal']
                    : (!empty($cuti['catatan'])
                        ? $cuti['catatan']
                        : 'Pengajuan dibatalkan otomatis karena sudah melewati tanggal mulai cuti tanpa kejelasan persetujuan.');
                ?>
                <strong><?= esc($cancelReason); ?></strong>
            </div>
        <?php endif; ?>
        <div class="detail-item">
            <?php
            $requestDate = '-';
            if (!empty($cuti['created_at'])) {
                $requestDate = formatTanggalIndonesia($cuti['created_at']);
            } elseif (!empty($cuti['tanggal_mulai'])) {
                $requestDate = formatTanggalIndonesia($cuti['tanggal_mulai']);
            }
            ?>
            <label>Tanggal Pengajuan</label>
            <strong><?= esc($requestDate); ?></strong>
        </div>
    </div>
</div>


<?php if ($isCanceled): ?>
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
<?php else: ?>
    <div class="card">
        <h3>Status Approval Lengkap</h3>
        <table class="table">
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
                // Inisialisasi data filter tracking di awal
                $detailTeman = array_filter($tracking, fn($t) => ($t['level_approval'] ?? $t['role_approver'] ?? '') === 'teman');
                
                $detailSpv = array_filter($tracking, fn($t) => ($t['level_approval'] ?? $t['role_approver'] ?? '') === 'spv');
                $detailSpv = reset($detailSpv);

                $detailHrd = array_filter($tracking, fn($t) => ($t['level_approval'] ?? $t['role_approver'] ?? '') === 'hrd');
                $detailHrd = reset($detailHrd);

                $detailDir = array_filter($tracking, fn($t) => ($t['level_approval'] ?? $t['role_approver'] ?? '') === 'direktur');
                $detailDir = reset($detailDir);

                $currentStatus = strtolower($cuti['status']);
                $isGlobalRejected = in_array($currentStatus, ['rejected', 'ditolak']);
                $isGlobalCanceled = in_array($currentStatus, ['batal', 'dibatalkan']);

                // Menghitung akumulasi suara dari Teman Sejawat
                $jumlahApprove = 0;
                $jumlahReject = 0;
                foreach ($detailTeman as $t) {
                    $statusT = strtolower($t['status'] ?? '');
                    if ($statusT === 'approved' || $statusT === 'approve') {
                        $jumlahApprove++;
                    } elseif ($statusT === 'rejected' || $statusT === 'ditolak') {
                        $jumlahReject++;
                    }
                }

                $requiredApprovals = (new \App\Models\PegawaiModel())->countTemanSejawat($cuti['pegawai_id']);
                $showTemanRow = $requiredApprovals > 0 || in_array($currentStatus, ['pending_teman_sejawat', 'pending_teman', 'batal', 'dibatalkan']);
                ?>

                <?php if ($showTemanRow): ?>
                    <?php
                    $temanStatus = "MENUNGGU ($jumlahApprove/$requiredApprovals)";
                    $temanClass = 'pending';

                    if ($isGlobalCanceled) {
                        $temanStatus = 'DIBATALKAN';
                        $temanClass = 'rejected';
                    } elseif ($jumlahReject > 0 || $isGlobalRejected) {
                        $temanStatus = 'DITOLAK';
                        $temanClass = 'rejected';
                    } elseif (in_array($currentStatus, ['pending_spv', 'pending_hrd', 'pending_direktur', 'approved', 'approve', 'diterima'])) {
                        $temanStatus = 'DISETUJUI';
                        $temanClass = 'approved';
                    }
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
                <?php endif; ?>

                <?php
                $spvStatus = 'MENUNGGU';
                $spvClass = 'pending';
                $spvNama = '-';
                $spvCatatan = '-';

                if (!empty($detailSpv)) {
                    $spvNama = esc($detailSpv['nama'] ?? '-');
                    if (in_array(strtolower($detailSpv['status']), ['approved', 'approve'])) {
                        $spvStatus = 'DISETUJUI';
                        $spvClass = 'approved';
                    } elseif (in_array(strtolower($detailSpv['status']), ['rejected', 'ditolak'])) {
                        $spvStatus = 'DITOLAK';
                        $spvClass = 'rejected';
                        $spvCatatan = !empty(trim($detailSpv['catatan'] ?? '')) ? esc($detailSpv['catatan']) : '-';
                    }
                } elseif ($isGlobalRejected || $jumlahReject > 0) { 
                    // Jika teman sejawat mereject atau status utama ditolak, otomatis ikut DITOLAK
                    $spvStatus = 'DITOLAK';
                    $spvClass = 'rejected';
                } elseif ($isGlobalCanceled) {
                    $spvStatus = 'DIBATALKAN';
                    $spvClass = 'rejected';
                }
                ?>
                <tr>
                    <td>Menunggu SPV</td>
                    <td><span class="status-box <?= $spvClass; ?>"><?= $spvStatus; ?></span></td>
                    <td><?= $spvNama; ?></td>
                    <td><?= $spvCatatan; ?></td>
                </tr>

                <!-- BARIS Level: HRD -->
                <?php
                $hrdStatus = 'MENUNGGU';
                $hrdClass = 'pending';
                $hrdNama = '-';
                $hrdCatatan = '-';

                if (!empty($detailHrd)) {
                    $hrdNama = esc($detailHrd['nama'] ?? '-');
                    if (in_array(strtolower($detailHrd['status']), ['approved', 'approve'])) {
                        $hrdStatus = 'DISETUJUI';
                        $hrdClass = 'approved';
                    } elseif (in_array(strtolower($detailHrd['status']), ['rejected', 'ditolak'])) {
                        $hrdStatus = 'DITOLAK';
                        $hrdClass = 'rejected';
                        $hrdCatatan = !empty(trim($detailHrd['catatan'] ?? '')) ? esc($detailHrd['catatan']) : '-';
                    }
                } elseif ($isGlobalRejected || $jumlahReject > 0) {
                    $hrdStatus = 'DITOLAK';
                    $hrdClass = 'rejected';
                } elseif ($isGlobalCanceled) {
                    $hrdStatus = 'DIBATALKAN';
                    $hrdClass = 'rejected';
                }
                ?>
                <tr>
                    <td>Menunggu HRD</td>
                    <td><span class="status-box <?= $hrdClass; ?>"><?= $hrdStatus; ?></span></td>
                    <td><?= $hrdNama; ?></td>
                    <td><?= $hrdCatatan; ?></td>
                </tr>

                <!-- BARIS Level: DIREKTUR -->
                <?php
                $dirStatus = 'MENUNGGU';
                $dirClass = 'pending';
                $dirNama = '-';
                $dirCatatan = '-';

                if (!empty($detailDir)) {
                    $dirNama = esc($detailDir['nama'] ?? '-');
                    if (in_array(strtolower($detailDir['status']), ['approved', 'approve'])) {
                        $dirStatus = 'DISETUJUI';
                        $dirClass = 'approved';
                    } elseif (in_array(strtolower($detailDir['status']), ['rejected', 'ditolak'])) {
                        $dirStatus = 'DITOLAK';
                        $dirClass = 'rejected';
                        $dirCatatan = !empty(trim($detailDir['catatan'] ?? '')) ? esc($detailDir['catatan']) : '-';
                    }
                } elseif ($isGlobalRejected || $jumlahReject > 0) {
                    $dirStatus = 'DITOLAK';
                    $dirClass = 'rejected';
                } elseif ($isGlobalCanceled) {
                    $dirStatus = 'DIBATALKAN';
                    $dirClass = 'rejected';
                }
                ?>
                <tr>
                    <td>Menunggu Direktur</td>
                    <td><span class="status-box <?= $dirClass; ?>"><?= $dirStatus; ?></span></td>
                    <td><?= $dirNama; ?></td>
                    <td><?= $dirCatatan; ?></td>
                </tr>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- CARD 3: TIMELINE APPROVAL -->
<div class="card">
    <h3>Timeline Approval</h3>
    <div class="timeline">
        <?php
        $idCuti = $cuti['id'] ?? $id;
        $statusUtama = $cuti['status'];

        $detailStatusModel = new \App\Models\DetailStatusCutiModel();
        $timelineEntries = $detailStatusModel
            ->select('detail_status_cuti.*, pegawai.nama')
            ->join('pegawai', 'pegawai.id = detail_status_cuti.approved_by', 'left')
            ->where('pengajuan_id', $idCuti)
            ->where('detail_status_cuti.status !=', 'pending')
            ->orderBy('detail_status_cuti.approved_at', 'ASC')
            ->findAll();

        $roleLabels = [
            'teman' => 'TEMAN SEJAWAT',
            'spv' => 'SUPERVISOR',
            'hrd' => 'HRD',
            'direktur' => 'DIREKTUR',
        ];
        ?>

        <?php if (!empty($timelineEntries)): ?>
            <?php
            $hasCanceledEntry = false;
            foreach ($timelineEntries as $entry) {
                if (in_array(strtolower($entry['status'] ?? ''), ['batal', 'dibatalkan'])) {
                    $hasCanceledEntry = true;
                    break;
                }
            }
            ?>
            <?php if (in_array($statusUtama, ['batal', 'dibatalkan']) && !$hasCanceledEntry): ?>
                <div class="timeline-item canceled">
                    <h4>Pengajuan ini telah dibatalkan.</h4>
                    <p><strong>Alasan Pembatalan:</strong> <?= esc($cuti['alasan_batal'] ?? $cuti['catatan'] ?? 'Pengajuan cuti dibatalkan otomatis karena sudah melewati tanggal mulai cuti tanpa kejelasan persetujuan.'); ?></p>
                </div>
            <?php endif; ?>
            <?php foreach ($timelineEntries as $entry): ?>
                <?php
                $role = strtolower($entry['level_approval'] ?? '');
                $label = $roleLabels[$role] ?? strtoupper($role);
                $status = strtolower($entry['status'] ?? '');
                $displayStatus = 'REJECTED';
                $timelineClass = '';
                $statusClass = 'rejected';
                $displayNote = '-';

                if (in_array($status, ['approved', 'approve'])) {
                    $displayStatus = 'APPROVED';
                    $statusClass = 'approved';
                } elseif (in_array($status, ['pending', 'pending_spv', 'pending_hrd', 'pending_direktur', 'pending_teman', 'pending_teman_sejawat'])) {
                    $displayStatus = 'PENDING';
                    $statusClass = 'pending';
                } elseif (in_array($status, ['batal', 'dibatalkan'])) {
                    $displayStatus = 'DIBATALKAN';
                    $timelineClass = ' canceled';
                    $statusClass = 'rejected';
                }

                if (in_array($status, ['rejected', 'ditolak', 'batal', 'dibatalkan'])) {
                    $displayNote = !empty(trim($entry['catatan'] ?? '')) ? esc($entry['catatan']) : '-';
                }
                ?>
                <div class="timeline-item<?= $timelineClass; ?>">
                    <h4><?= esc($label); ?></h4>
                    <p>Tanggal: <strong><?= formatTanggalIndonesia($entry['approved_at']); ?></strong></p>
                    <p>Status: <span class="status-pill <?= $statusClass; ?>"><?= esc($displayStatus); ?></span></p>
                    <p>Oleh: <strong><?= esc($entry['nama'] ?? 'System'); ?></strong></p>
                    <p>Catatan: <strong><?= $displayNote; ?></strong></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <?php if ($statusUtama === 'batal' || $statusUtama === 'dibatalkan'): ?>
                <?php
                $cancelReasonText = !empty($cuti['alasan_batal'])
                    ? $cuti['alasan_batal']
                    : (!empty($cuti['catatan'])
                        ? $cuti['catatan']
                        : 'Pengajuan cuti dibatalkan otomatis karena sudah melewati tanggal mulai cuti tanpa kejelasan persetujuan.');
                ?>
                <div class="timeline-item">
                    <h4>Pengajuan ini telah dibatalkan.</h4>
                    <p><strong>Alasan Pembatalan:</strong> <?= esc($cancelReasonText); ?></p>
                </div>
            <?php elseif (in_array($statusUtama, ['pending_teman_sejawat', 'pending_teman'])): ?>
                <p class="text-muted">Belum ada approval.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection(); ?>