<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Cuti</title>
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

        form {
            max-width: 700px;
            background: #ffffff;
            padding: 28px;
            border-radius: 24px;
            box-shadow: 0 18px 50px rgba(83, 55, 37, 0.08);
            border: 1px solid rgba(124, 92, 70, 0.12);
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #6a4b36;
            font-weight: 700;
            font-size: 14px;
        }

        input,
        textarea {
            width: 100%;
            padding: 14px 16px;
            margin-bottom: 20px;
            border: 1px solid #d8c9b5;
            border-radius: 14px;
            font-size: 14px;
            color: #3f2b1f;
            background: #faf4ed;
        }

        textarea {
            min-height: 140px;
            resize: vertical;
        }

        button {
            background: #7c5c46;
            color: #fff;
            border: none;
            border-radius: 14px;
            padding: 14px 24px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.3s ease;
        }

        button:hover {
            background: #6a4b36;
            transform: translateY(-1px);
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
        <h1>Pengajuan Cuti SPV</h1>

        <form action="<?= base_url('spv/cuti/store') ?>" method="post">
            <?= csrf_field() ?>
            <label for="tanggal_mulai">Tanggal Mulai</label>
            <input id="tanggal_mulai" type="date" name="tanggal_mulai" required>

            <label for="tanggal_selesai">Tanggal Selesai</label>
            <input id="tanggal_selesai" type="date" name="tanggal_selesai" required>

            <label for="alasan">Alasan Cuti</label>
            <textarea id="alasan" name="alasan" required></textarea>

            <button type="submit">Ajukan</button>
        </form>
    </div>
</body>

</html>