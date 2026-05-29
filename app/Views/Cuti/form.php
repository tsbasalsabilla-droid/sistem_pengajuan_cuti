<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Halaman Tidak Tersedia</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f6f1eb;
            color: #4a4035;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .message {
            background: #fffaf5;
            border: 1px solid #e1d2c1;
            border-radius: 18px;
            padding: 40px;
            text-align: center;
            max-width: 520px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        .message h1 {
            color: #7b573d;
            margin-bottom: 16px;
            font-size: 32px;
        }

        .message p {
            margin-bottom: 24px;
            line-height: 1.6;
        }

        .message a {
            display: inline-block;
            padding: 12px 24px;
            background: #7b573d;
            color: #fff;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <div class="message">
        <h1>Halaman Tidak Tersedia</h1>
        <p>Form pengajuan cuti telah dihapus dari sistem dan tidak dapat diakses lagi.</p>
        <a href="/">Kembali ke Beranda</a>
    </div>
</body>

</html>
                        <!-- TANGGAL MULAI -->

                        <div class="col-md-6 mb-4">

                            <label class="form-label">
                                Tanggal Mulai
                            </label>

                            <div class="input-group">

                                <span class="input-group-text">
                                    <i class="bi bi-calendar-event"></i>
                                </span>

                                <input
                                    type="date"
                                    name="tanggal_mulai"
                                    class="form-control"
                                    required>

                            </div>

                        </div>

                        <!-- TANGGAL SELESAI -->

                        <div class="col-md-6 mb-4">

                            <label class="form-label">
                                Tanggal Selesai
                            </label>

                            <div class="input-group">

                                <span class="input-group-text">
                                    <i class="bi bi-calendar-check"></i>
                                </span>

                                <input
                                    type="date"
                                    name="tanggal_selesai"
                                    class="form-control"
                                    required>

                            </div>

                        </div>

                    </div>

                    <!-- ALASAN -->

                    <div class="mb-4">

                        <label class="form-label">
                            Alasan Cuti
                        </label>

                        <textarea
                            name="alasan"
                            class="form-control"
                            placeholder="Contoh : Keperluan keluarga, sakit, acara penting, dll..."
                            required></textarea>

                    </div>

                    <!-- BUTTON -->

                    <div class="d-flex gap-3">

                        <button type="submit" class="btn btn-submit">

                            <i class="bi bi-send-fill me-2"></i>

                            Ajukan Cuti

                        </button>

                    </div>

                </form>

            </div>

        </div>
    </div>

    <?= view('layout/footer'); ?>

</body>

</html>