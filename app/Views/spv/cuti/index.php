<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History Pengajuan Cuti SPV</title>
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

        .card-table {
            background: #fffaf5;
            border-radius: 20px;
            padding: 30px;
            border: 1px solid #f1e2d2;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }

        .card-table h1 {
            color: #7b573d;
            font-size: 30px;
            font-weight: 700;
            margin-bottom: 25px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table thead th {
            background: #f3e8dc;
            color: #6f4e37;
            font-weight: 600;
            border: none;
            padding: 15px;
            text-align: left;
        }

        .table tbody td {
            padding: 15px;
            color: #495057;
            border-bottom: 1px solid #ead7c4;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background: #fef8f3;
        }

        .btn-action {
            display: inline-block;
            background: #8b6b52;
            color: #fff;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 13px;
            border: none;
            transition: 0.3s ease;
        }

        .btn-action:hover {
            background: #6f4e37;
            color: #fff;
            transform: translateY(-1px);
        }

        .btn-batal {
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 16px;
            cursor: pointer;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 8px;
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

        .modal {
    display: none;
    position: fixed;
    z-index: 9999;
    inset: 0;
    background: rgba(0, 0, 0, .45);
    backdrop-filter: blur(3px);
}

.modal-content {
    background: #fffaf5;
    width: 450px;
    max-width: 90%;
    margin: 80px auto;
    padding: 28px;
    border-radius: 18px;
    border: 1px solid #ead7c4;
    box-shadow: 0 15px 40px rgba(0,0,0,.25);
    position: relative;
}

.modal-content h3 {
    margin: 0 0 20px;
    color: #7b573d;
    font-size: 24px;
    font-weight: 700;
}

.modal-content label {
    display: block;
    margin-bottom: 8px;
    color: #6f4e37;
    font-weight: 600;
}

.modal-content textarea {
    width: 100%;
    min-height: 120px;
    border: 1px solid #e5cdb5;
    border-radius: 12px;
    padding: 12px 14px;
    resize: vertical;
    font-size: 14px;
    outline: none;
    transition: .3s;
    background: #fff;
    box-sizing: border-box;
}

.modal-content textarea:focus {
    border-color: #8b6b52;
    box-shadow: 0 0 0 3px rgba(139,107,82,.15);
}

.close {
    position: absolute;
    top: 15px;
    right: 18px;
    font-size: 28px;
    font-weight: bold;
    color: #8b6b52;
    cursor: pointer;
    line-height: 1;
}

.close:hover {
    color: #6f4e37;
}

.btn-batal {
    background: #dc3545;
    color: #fff;
    border: none;
    border-radius: 12px;
    padding: 12px;
    width: 100%;
    font-weight: 600;
    cursor: pointer;
    transition: .3s;
}

.btn-batal:hover {  
    background: #c82333;
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
        <div class="card-table">
            <h1>History Pengajuan Cuti SPV</h1>

            <?php if (session()->getFlashdata('success')) : ?>
                <div class="alert alert-success" role="alert">
                    <?= session()->getFlashdata('success') ?>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('error')) : ?>
                <div class="alert alert-danger" role="alert">
                    <?= session()->getFlashdata('error') ?>
                </div>
            <?php endif; ?>

            <div style="overflow-x:auto;">
                <table class="table table align-middle">
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
                                    <?= formatTanggalIndonesia($c['tanggal_mulai']); ?> s/d <?= formatTanggalIndonesia($c['tanggal_selesai']); ?>
                                </td>
                                <td>
                                    <?= $c['total_hari']; ?> Hari
                                </td>
                                <td>
                                    <?= esc($c['alasan']); ?>
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
                                        'batal', 'dibatalkan' => 'Dibatalkan',
                                        default => ucwords(str_replace('_', ' ', $status))
                                    };
                                    echo $statusLabel;
                                    ?>
                                </td>
                                <td>
                                    <a class="btn-action" href="/spv/cuti/detail/<?= $c['id']; ?>">Detail</a>
                                    <?php $bolehBatal = in_array($status, ['pending', 'pending_spv', 'pending_teman_sejawat', 'pending_hrd', 'pending_direktur']); ?>
                                    <?php if ($bolehBatal) : ?>
                                        <br><br>
                                        <button type="button" class="btn-batal" onclick="openModal(<?= $c['id']; ?>)">Batalkan</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total > 0) : ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++) : ?>
                        <a href="/spv/cuti?page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php foreach ($cuti as $c): ?>
        <div class="modal" id="modal<?= $c['id']; ?>">
            <div class="modal-content">
                <span class="close" onclick="closeModal(<?= $c['id']; ?>)">&times;</span>
                <h3>Batalkan Pengajuan Cuti</h3>
                <form action="<?= base_url('spv/cuti/batal/' . $c['id']); ?>" method="post">
                    <?= csrf_field(); ?>
                    <label>Alasan Pembatalan</label>
                    <textarea name="alasan_batal" required placeholder="Masukkan alasan pembatalan..."></textarea>
                    <br><br>
                    <button type="submit" class="btn-batal" onclick="return confirm('Yakin ingin membatalkan pengajuan cuti ini?')">Batalkan Pengajuan</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>

    <script>
        function openModal(id) {
            document.getElementById('modal' + id).style.display = 'block';
        }

        function closeModal(id) {
            document.getElementById('modal' + id).style.display = 'none';
        }

        window.onclick = function(event) {
            document.querySelectorAll('.modal').forEach(function(modal) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        };
    </script>
</body>

</html>