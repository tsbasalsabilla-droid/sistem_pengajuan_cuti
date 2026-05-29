<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval SPV</title>

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
    </style>
</head>

<body>
    <?= view('layout/sidebar'); ?>

    <div class="content">
        <div class="topbar">
            <h1>Approval SPV</h1>
            <p>Daftar pengajuan cuti yang menunggu persetujuan SPV.</p>
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
                                    <?= date('d/m/Y', strtotime($c['tanggal_mulai'])); ?>
                                    s/d
                                    <?= date('d/m/Y', strtotime($c['tanggal_selesai'])); ?>
                                </td>
                                <td><?= $c['total_hari']; ?> hari</td>
                                <td><?= $c['alasan']; ?></td>
                                <td><?= ucfirst($c['status']); ?></td>
                                <td>
                                    <a href="/spv/approve/<?= $c['id']; ?>" class="btn-approve">Approve</a>
                                    <a href="/spv/reject/<?= $c['id']; ?>" class="btn-reject">Reject</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?= view('layout/footer'); ?>
</body>

</html>