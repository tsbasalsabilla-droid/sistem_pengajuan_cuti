<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Cuti SPV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body {
            background-color: #fcf8f4; /* Warna dasar halaman agar matching */
            margin: 0;
            font-family: system-ui, -apple-system, sans-serif;
        }

        /* Container utama disesuaikan agar posisinya pas di sebelah kanan sidebar */
        .content {
            margin-left: 260px;
            width: calc(100% - 260px);
            padding: 40px;
            box-sizing: border-box;
            min-height: 100vh;
            transition: margin-left 0.3s ease, width 0.3s ease;
        }

        .sidebar.collapsed+.content {
            margin-left: 82px;
            width: calc(100% - 82px);
        }

        /* Style card-table agar lebar otomatis (fluid) dan desain persis seperti gambar */
        .card-table {
            background: #fffaf5;
            border-radius: 20px;
            padding: 40px;
            border: 1px solid #f1e2d2;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.4);
            overflow: hidden;
            width: 100%; /* Membuat card mengikuti lebar kontainer content */
        }

        .card-table h1 {
            color: #7b573d;
            font-size: 30px;
            font-weight: 700;
            margin-bottom: 25px;
        }

        .form-label {
            color: #6f4e37;
            font-weight: 600;
            margin-top: 15px;
            margin-bottom: 5px;
            display: block;
        }

        .form-control {
            border-radius: 10px;
            border: 1px solid #ead7c4;
            padding: 12px 15px;
            background-color: #ffffff;
            color: #495057;
            width: 100%;
        }

        .form-control:focus {
            border-color: #7b573d;
            box-shadow: 0 0 0 0.25rem rgba(123, 87, 61, 0.25);
            background-color: #ffffff;
        }

        .btn-submit {
            background-color: #7b573d;
            color: #ffffff;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            background-color: #6f4e37;
            color: #ffffff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Pengajuan Cuti SPV</h1>
            </div>

            <?php if (session()->getFlashdata('pesan')) : ?>
                <div class="alert alert-info" role="alert">
                    <?= session()->getFlashdata('pesan') ?>
                </div>
            <?php endif; ?>

            <form action="<?= base_url('spv/cuti/store') ?>" method="post" class="needs-validation">
                <?= csrf_field() ?>
                
                <div class="mb-3">
                    <label class="form-label">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" class="form-control" required>
                </div>

                <div class="mb-4">
                    <label class="form-label">Alasan Cuti</label>
                    <textarea name="alasan" class="form-control" rows="4" placeholder="Tuliskan alasan atau tujuan cuti anda..." required></textarea>
                </div>

                <div class="d-flex justify-content-start">
                    <button type="submit" class="btn-submit">
                        Ajukan Cuti
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>