<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History Pengajuan</title>
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

        h1 {
            color: #4f3a2c;
            margin-bottom: 24px;
            font-size: 32px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 18px 40px rgba(83, 55, 37, 0.06);
        }

        th,
        td {
            padding: 18px 20px;
            border-bottom: 1px solid #eee;
            color: #4f3a2c;
            text-align: left;
        }

        th {
            background: #f3e8dc;
            color: #6f4e37;
            font-weight: 700;
        }

        tr:nth-child(even) {
            background: #fbf6f0;
        }

        a.detail-link {
            display: inline-block;
            padding: 9px 16px;
            border-radius: 12px;
            background: #7c5c46;
            color: #fff;
            text-decoration: none;
            transition: background 0.3s ease;
        }

        a.detail-link:hover {
            background: #6a4b36;
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
        <h1>History Cuti SPV</h1>

        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Total Hari</th>
                    <th>Alasan</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cuti as $c): ?>
                    <tr>
                        <td>
                            <?= $c['tanggal_mulai']; ?> s/d <?= $c['tanggal_selesai']; ?>
                        </td>
                        <td>
                            <?= $c['total_hari']; ?> Hari
                        </td>
                        <td>
                            <?= $c['alasan']; ?>
                        </td>
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
                            <a class="detail-link" href="/spv/cuti/detail/<?= $c['id']; ?>">Detail</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>

</html>