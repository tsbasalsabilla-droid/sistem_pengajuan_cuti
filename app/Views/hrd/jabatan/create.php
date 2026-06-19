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
    <h2 class="my-3">Form tambah jabatan</h2>

    <form action="/jabatan/save" method="post">
        <?= csrf_field(); ?>

        <div class="mb-3">
            <label for="jabatan" class="form-label">Jabatan</label>
            <input type="text" class="form-control <?= ($validation->hasError('jabatan')) ? 'is-invalid' : ''; ?>" id="jabatan" name="jabatan" value="<?= old('jabatan'); ?>" autofocus>
            <div class="invalid-feedback">
                <?= $validation->getError('jabatan'); ?>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Tambah jabatan</button>
        <a href="/jabatan" class="btn btn-secondary">
            Kembali
        </a>
    </form>
</div>
<?= $this->include('layout/footerhrd') ?>