<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval Direktur</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body {
            background: #f6f1eb;
            font-family: 'Segoe UI', sans-serif;
            overflow-x: hidden;
        }

        .content {
            margin-left: 260px;
            width: calc(100% - 260px);
            padding: 35px 50px;
            box-sizing: border-box;
            min-height: 100vh;
        }

        .topbar h1 {
            color: #7b573d;
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .topbar p {
            color: #9a7456;
            font-size: 16px;
        }

        .card-table {
            background: #fffaf5;
            border-radius: 20px;
            padding: 20px 24px 14px;
            border: 1px solid #f1e2d2;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            max-width: 960px;
            margin: 0 auto;
        }

        .card-table h2 {
            color: #7b573d;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 25px;
        }

        .table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
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
            word-wrap: break-word;
        }

        .table th:nth-child(1),
        .table td:nth-child(1) {
            width: 60px;
        }

        .table th:nth-child(2),
        .table td:nth-child(2) {
            width: 180px;
        }

        .table th:nth-child(3),
        .table td:nth-child(3) {
            width: 120px;
        }

        .table th:nth-child(4),
        .table td:nth-child(4) {
            width: 200px;
        }

        .table th:nth-child(5),
        .table td:nth-child(5) {
            width: 140px;
        }

        .table th:nth-child(6),
        .table td:nth-child(6) {
            width: 130px;
        }

        .table tbody tr:hover {
            background: #fef8f3;
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
            transform: translateY(-2px);
        }

        .btn-reject {
            background: #dc3545;
            color: white;
        }

        .btn-reject:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        @media(max-width: 992px) {
            .content {
                margin-left: 0;
                width: 100%;
                padding: 25px 20px;
            }
        }

        .table-responsive {
            overflow-x: auto;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .pagination a,
        .pagination span {
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

        .pagination a:hover {
            background: #f3e8dc;
            color: #5f402f;
            border-color: #d8b89b;
        }

        .pagination a.active,
        .pagination a.active span {
            background: #7b573d;
            color: #fff;
            border-color: #7b573d;
        }
    </style>
</head>

<body>
    <?php $cuti = $cuti ?? [];
    $page = $page ?? 1;
    $perPage = $perPage ?? 10;
    $total = $total ?? 0;
    $totalPages = $totalPages ?? 0; ?>
    <?= view('layout/sidebar'); ?>

    <div class="content">
        <div class="topbar">
            <h1>Approval Direktur</h1>
            <p>Daftar pengajuan cuti yang menunggu persetujuan Direktur.</p>
        </div>

        <div class="card-table">
            <h2>Daftar Pengajuan</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Periode</th>
                            <th>Total Hari</th>
                            <th>Alasan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; ?>
                        <?php foreach ($cuti as $c): ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td>
                                    <?= formatTanggalIndonesia($c['tanggal_mulai']); ?>
                                    s/d
                                    <?= formatTanggalIndonesia($c['tanggal_selesai']); ?>
                                </td>
                                <td><?= $c['total_hari']; ?> hari</td>
                                <td><?= $c['alasan']; ?></td>
                                <td>
                                    <?php
                                    $status = trim($c['status'] ?? '');
                                    if ($status === 'approve') $status = 'approved';
                                    if ($status === '') {
                                        $approvalModel = new \App\Models\ApprovalModel();
                                        $log = $approvalModel->where('cuti_id', $c['id'])->where('status', 'approved')->first();
                                        $status = $log ? 'approved' : 'pending';
                                    }
                                    $statusLabel = match ($status) {
                                        'pending' => 'Menunggu',
                                        'pending_spv' => 'Menunggu SPV',
                                        'pending_hrd' => 'Menunggu HRD',
                                        'pending_direktur' => 'Menunggu Direktur',
                                        'pending_teman', 'pending_teman_sejawat' => 'Menunggu Teman Sejawat',
                                        default => ucwords(str_replace('_', ' ', $status))
                                    };
                                    echo $statusLabel;
                                    ?>
                                </td>
                                <td>
                                    <a href="/approval/approve-direktur/<?= $c['id']; ?>" class="btn-approve">Approve</a>
                                    <a href="/approval/reject-direktur/<?= $c['id']; ?>" class="btn-reject">Reject</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($total) && $total > 0) : ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++) : ?>
                        <a href="/direktur?page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?= view('layout/footer'); ?>
</body>

</html>