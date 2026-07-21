<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval SPV</title>
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

        .topbar h1 {
            color: #7b573d;
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .topbar p {
            color: #9a7456;
            font-size: 16px;
            margin-bottom: 30px;
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

        @media (max-width: 992px) {
            .content {
                margin-left: 0;
                width: 100%;
                padding: 25px 22px;
            }
        }

.modal-content {
    background: #fffaf5;
    width: 450px;
    max-width: 90%;
    border-radius: 18px;
    border: 1px solid #ead7c4;
    box-shadow: 0 15px 40px rgba(0, 0, 0, .25);
}

.modal-header {
    border-bottom: 1px solid #ead7c4;
    padding: 18px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title {
    margin: 0;
    color: #7b573d;
    font-weight: 700;
}

.modal-body {
    padding: 22px 24px;
}

.modal-footer {
    border-top: 1px solid #ead7c4;
    padding: 18px 24px;
}

.form-control {
    width: 100%;
    min-height: 120px;
    border: 1px solid #e5cdb5;
    border-radius: 12px;
    padding: 12px 14px;
    resize: vertical;
    font-size: 14px;
    background: #fff;
}

.form-control:focus {
    border-color: #8b6b52;
    box-shadow: 0 0 0 3px rgba(139, 107, 82, .15);
}

.close-btn {
    border: none;
    background: transparent;
    color: #8b6b52;
    font-size: 28px;
    font-weight: bold;
    line-height: 1;
    cursor: pointer;
    padding: 0;
}

.close-btn:hover {
    color: #6f4e37;
}

.close-btn:focus {
    outline: none;
    box-shadow: none;
}

.modal-footer .btn-secondary {
    background: #b6b6b6;
    border: none;
    border-radius: 12px;
}

.modal-footer .btn-secondary:hover {
    background: #9b9b9b;
}

.modal-footer .btn-danger {
    border-radius: 12px;
}

.modal-footer .btn-danger:hover {
    background: #c82333;
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
                        <?php
                        $startNo = isset($page, $perPage) ? (($page - 1) * $perPage + 1) : 1;
                        foreach ($cuti as $c): ?>
                            <tr>
                                <td><?= $startNo++; ?></td>
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
                                    echo ucwords(str_replace('_', ' ', $status));
                                    ?>
                                </td>
                                <td>
                                    <a href="/approval/approve-spv/<?= esc($c['id']); ?>" class="btn btn-success">Approve</a>
                                    <button type="button" class="btn btn-danger btn-reject-modal" data-id="<?= esc($c['id']); ?>">Reject</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($total) && isset($totalPages) && $total > 0) : ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++) : ?>
                        <a href="/spv?page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="formReject" action="" method="post">
                    <?= csrf_field(); ?>
                    <div class="modal-header">
                        <h5 class="modal-title" id="rejectModalLabel" style="font-weight: 700;">Alasan Penolakan Cuti</h5>
                        <button type="button" class="close-btn" data-bs-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="catatan" class="form-label" style="color: #6f4e37; font-weight: 600;">Berikan Alasan/Catatan Penolakan:</label>
                            <textarea class="form-control" id="catatan" name="catatan" rows="4" placeholder="Tulis alasan penolakan di sini..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary rounded-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger rounded-3">Kirim & Tolak</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
            const formReject = document.getElementById('formReject');
            const rejectButtons = document.querySelectorAll('.btn-reject-modal');

            rejectButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    formReject.setAttribute('action', '/approval/reject-spv/' + id);
                    rejectModal.show();
                });
            });
        });
    </script>
</body>

</html>