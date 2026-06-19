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
    <h2 class="my-3">Form ubah Divisi</h2>
    <form action="/divisi/update/<?= $divisi['id'] ?>" method="post">
        <?= csrf_field(); ?>

        <div class="mb-3">
            <label for="nama_divisi" class="form-label">Divisi</label>
            <input type="text" class="form-control <?= ($validation->hasError('nama_divisi')) ? 'is-invalid' : ''; ?> " id="nama_divisi" name="nama_divisi" autofocus value="<?= $divisi['nama_divisi'] ?>">
            <div class="invalid-feedback">
                <?= $validation->getError('nama_divisi'); ?>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Ubah divisi</button>
        <a href="/divisi" class="btn btn-secondary">
            Kembali
        </a>
    </form>
</div>

<?= $this->include('layout/footerhrd') ?>