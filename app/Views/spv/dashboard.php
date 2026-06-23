<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard SPV</title>
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

        .topbar {
            margin-bottom: 35px;
        }

        .topbar h1 {
            color: #7b573d;
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .topbar p {
            color: #9a7456;
            font-size: 16px;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: linear-gradient(135deg, #6f4e37, #8b6b52);
            border-radius: 20px;
            padding: 25px;
            color: white;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-icon {
            font-size: 40px;
            opacity: 0.8;
        }

        .stat-content h3 {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 5px;
            opacity: 0.9;
        }

        .stat-content .number {
            font-size: 28px;
            font-weight: 700;
        }

        .card-table {
            background: #fffaf5;
            border-radius: 20px;
            padding: 30px;
            border: 1px solid #f1e2d2;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }

        .card-table h2 {
            color: #7b573d;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 25px;
        }

        .table {
            margin-bottom: 0;
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

        .table tbody tr {
            border-bottom: 1px solid #ead7c4;
        }

        .table tbody tr:hover {
            background: #fef8f3;
        }

        .badge-status {
            padding: 8px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge-approved {
            background: #d4edda;
            color: #155724;
        }

        .badge-rejected {
            background: #f8d7da;
            color: #721c24;
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
        }

        .btn-reject:hover {
            background: #c82333;
            color: white;
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #9a7456;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.6;
        }

        @media (max-width: 992px) {
            .content {
                margin-left: 0;
                width: 100%;
                padding: 25px 22px;
            }
        }
    </style>
</head>

<body>
    <?= view('layout/sidebar'); ?>

    <div class="content">
        <div class="topbar">
            <h1>Dashboard SPV</h1>
            <p>Kelola dan approve pengajuan cuti tim Anda</p>
        </div>

        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div class="stat-content">
                    <h3>Menunggu Approval</h3>
                    <div class="number"><?= $pending; ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3>Sudah Disetujui</h3>
                    <div class="number"><?= $approved; ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-x-circle"></i>
                </div>
                <div class="stat-content">
                    <h3>Ditolak</h3>
                    <div class="number"><?= $rejected; ?></div>
                </div>
            </div>
        </div>

        <div class="card-table">
            <h2><i class="bi bi-list-check me-2"></i>Daftar Cuti yang Sudah Disetujui</h2>

            <?php if (empty($cuti)): ?>
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <p>Tidak ada data yang sudah disetujui.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Pegawai</th>
                                <th>Periode Cuti</th>
                                <th>Total Hari</th>
                                <th>Alasan</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; ?>
                            <?php foreach ($cuti as $c): ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= $c['nama_pegawai'] ?? 'Tidak Diketahui'; ?></td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($c['tanggal_mulai'])); ?> -
                                        <?= date('d/m/Y', strtotime($c['tanggal_selesai'])); ?>
                                    </td>
                                    <td><?= $c['total_hari']; ?> hari</td>
                                    <td><?= substr($c['alasan'], 0, 50); ?><?= strlen($c['alasan']) > 50 ? '...' : ''; ?></td>
                                    <td>
                                        <?php
                                        $status = trim($c['status'] ?? '');
                                        if ($status === 'approve') $status = 'approved';

                                        if ($status === '') {
                                            $status = 'pending';
                                        }
                                        $badgeClass = str_replace('_', '-', strtolower($status));
                                        ?>
                                        <span class="badge-status badge-<?= $badgeClass; ?>">
                                            <?php
                                            echo match ($status) {
                                                'pending' => 'Menunggu',
                                                'pending_spv' => 'Menunggu SPV',
                                                'pending_hrd' => 'Menunggu HRD',
                                                'pending_direktur' => 'Menunggu Direktur',
                                                'pending_teman', 'pending_teman_sejawat' => 'Menunggu Teman Sejawat',
                                                default => ucwords(str_replace('_', ' ', $status))
                                            };
                                            ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>