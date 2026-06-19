<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval</title>

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

        .modal-content {
            background: #fffaf5;
            border-radius: 20px;
            border: 1px solid #f1e2d2;
        }

        .modal-header {
            border-bottom: 1px solid #ead7c4;
            color: #7b573d;
        }

        .modal-footer {
            border-top: 1px solid #ead7c4;
        }

        .form-control:focus {
            border-color: #7b573d;
            box-shadow: 0 0 0 0.25rem rgba(123, 87, 61, 0.25);
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
    <?php
    $userRole = session()->get('user')['role'] ?? 'pegawai';
    if ($userRole === 'hrd') {
        echo view('layout/sidebarhrd');
    } else {
        echo view('layout/sidebar');
    }
    ?>

    <div class="content">
        <?php if (session()->getFlashdata('success')) : ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= session()->getFlashdata('success'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')) : ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= session()->getFlashdata('error'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="topbar">
            <h1>Approval</h1>
            <p>Daftar pengajuan cuti yang menunggu persetujuan</p>
        </div>

        <div class="card-table">
            <h2>Daftar Pengajuan</h2>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Pegawai</th>
                            <th>Periode</th>
                            <th>Total Hari</th>
                            <th>Alasan Cuti</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($cuti)): ?>
                            <?php $no = 1;
                            foreach ($cuti as $item): ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= esc($item['nama_pegawai']); ?></td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($item['tanggal_mulai'])); ?>
                                        s/d
                                        <?= date('d/m/Y', strtotime($item['tanggal_selesai'])); ?>
                                    </td>
                                    <td><?= esc($item['total_hari']); ?> hari</td>
                                    <td><?= esc($item['alasan']); ?></td>
                                    <td>
                                        <?php
                                        $status = trim($item['status'] ?? '');
                                        $statusLabel = match ($status) {
                                            'pending_teman_sejawat' => 'Voting Teman Sejawat',
                                            'pending_spv'           => 'Menunggu SPV',
                                            'pending_hrd'           => 'Menunggu HRD',
                                            'pending_direktur'      => 'Menunggu Direktur',
                                            default                 => ucwords(str_replace('_', ' ', $status))
                                        };
                                        echo $statusLabel;
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($userRole === 'pegawai'): ?>
                                            <a href="<?= base_url('approval/approve-teman/' . esc($item['id'])); ?>" class="btn btn-success btn-sm rounded-3" onclick="return confirm('Apakah Anda yakin ingin menyetujui pengajuan ini?')">Approve</a>

                                            <button type="button" class="btn btn-danger btn-sm rounded-3 btn-reject-modal" data-id="<?= esc($item['id']); ?>">
                                                Tolak
                                            </button>

                                        <?php else: ?>
                                            <a href="<?= base_url('approval/approve-' . $userRole . '/' . esc($item['id'])); ?>" class="btn btn-success btn-sm rounded-3" onclick="return confirm('Apakah Anda yakin ingin menyetujui pengajuan ini?')">Approve</a>

                                            <button type="button" class="btn btn-danger btn-sm rounded-3 btn-reject-modal" data-id="<?= esc($item['id']); ?>">
                                                Tolak
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">Daftar pengajuan cuti kosong.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="formReject" action="" method="post">
                    <?= csrf_field(); ?>
                    <div class="modal-header">
                        <h5 class="modal-title" id="rejectModalLabel" style="font-weight: 700;">Alasan Penolakan Cuti</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="catatan" class="form-label" style="color: #6f4e37; font-weight: 600;">Berikan Alasan/Catatan:</label>
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

    <?php
    if ($userRole === 'hrd') {
        echo view('layout/footerhrd');
    } else {
        echo view('layout/footer');
    }
    ?>

    console.log("ROLE =", "<?= $userRole ?>");

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
            const formReject = document.getElementById('formReject');
            const rejectButtons = document.querySelectorAll('.btn-reject-modal');

            const userRole = "<?= $userRole; ?>".toLowerCase();

            rejectButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');

                    if (userRole === 'pegawai') {
                        formReject.action = '<?= base_url("approval/reject-teman/"); ?>' + id;
                    } else {
                        formReject.action = '<?= base_url("approval/reject-"); ?>' + userRole + '/' + id;
                    }

                    rejectModal.show();
                });
            });
        });
    </script>
</body>

</html>