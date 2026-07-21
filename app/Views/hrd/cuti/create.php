<?= $this->include('layout/header') ?>
<?= $this->include('layout/sidebarhrd') ?>

<style>

    .card-table {
        background: #fffaf5;
        border-radius: 20px;
        padding: 30px;
        border: 1px solid #f1e2d2;
        box-shadow: 0 5px 10px rgba(0, 0, 0, 0.4);
        overflow: hidden;
    }

    .card-table h2,
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
</style>

<div class="card-table">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Pengajuan Cuti HRD</h1>
    </div>

    <?php if (session()->getFlashdata('pesan')) : ?>
        <div class="alert alert-info" role="alert">
            <?= session()->getFlashdata('pesan') ?>
        </div>
    <?php endif; ?>

    <form action="/hrd/cuti/store" method="post" class="needs-validation">
        <?= csrf_field(); ?> <div class="mb-3">
            <label class="form-label">Tanggal Mulai</label>
            <input type="date" name="tanggal_mulai" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Tanggal Selesai</label>
            <input type="date" name="tanggal_selesai" class="form-control" required>
        </div>

        <div class="mb-4">
            <label class="form-label">Tujuan Cuti</label>
            <textarea name="alasan" class="form-control" rows="4" placeholder="Tuliskan alasan atau tujuan cuti anda..." required></textarea>
        </div>

        <div class="d-flex justify-content-start">
            <button type="submit" class="btn-submit">
                Ajukan Cuti
            </button>
        </div>
    </form>
</div>

<?= $this->include('layout/footerhrd') ?>