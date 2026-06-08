<?= $this->include('layout/header') ?>

<h2 class="my-3">Form tambah divisi</h2>
<form action="/hrd/divisi/save" method="post">
  <?= csrf_field(); ?>
  <div class="mb-3 row">
    <label for="nama_divisi" class="col-sm-2 col-form-label">Divisi</label>
    <input type="text" class="form-control <?= ($validation->hasError('nama_divisi')) ? 'is-invalid' : ''; ?> " id="nama_divisi" name="nama_divisi" autofocus value="<?= old('nama_divisi'); ?>">
    <div class="invalid-feedback">
      <?= $validation->getError('nama_divisi'); ?>
    </div>
  </div>
  </div>
  <button type="submit" class="btn btn-primary">Tambah divisi</button>
</form>

<?= $this->include('layout/footer') ?>