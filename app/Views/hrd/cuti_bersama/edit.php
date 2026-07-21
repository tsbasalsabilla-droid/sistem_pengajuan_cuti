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

    .card-table h2 {
        color: #7b573d;
        font-size: 30px;
        font-weight: 700;
        margin-bottom: 25px;
    }

    .form-control {
        height: 45px;
        border-radius: 10px;
    }

    .form-label {
        font-weight: 600;
        color: #6f4e37;
    }
</style>

<div class="card-table">
    <h2 class="my-3">Form ubah Cuti Bersama</h2>
    <form action="/cuti_bersama/update/<?= $cuti_bersama['id'] ?>" method="post">
        <?= csrf_field(); ?>

        <div class="mb-3">
            <label for="tanggal" class="form-label">Tanggal</label>
            <input type="date" class="form-control <?= ($validation->hasError('tanggal')) ? 'is-invalid' : ''; ?> " id="tanggal" name="tanggal" autofocus value="<?= $cuti_bersama['tanggal'] ?>">
            <div class="invalid-feedback">
                <?= $validation->getError('tanggal'); ?>
            </div>
            <label for="keterangan" class="form-label">Keterangan</label>
            <input type="text" class="form-control <?= ($validation->hasError('keterangan')) ? 'is-invalid' : ''; ?> " id="keterangan" name="keterangan" autofocus value="<?= $cuti_bersama['keterangan'] ?>">
            <div class="invalid-feedback">
                <?= $validation->getError('keterangan'); ?>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Ubah cuti bersama</button>
        <a href="/cuti_bersama" class="btn btn-secondary">
            Kembali
        </a>
    </form>
</div>

<?= $this->include('layout/footerhrd') ?>