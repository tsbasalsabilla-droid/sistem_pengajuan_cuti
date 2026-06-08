<?= $this->include('layout/header') ?>

<h2 class="my-3">Form ubah Divisi</h2>
<form action="/hrd/divisi/update/<?= $divisi['id'] ?>" method="post">

  <?= csrf_field(); ?>
  <label for="nama_divisi" class="col-sm-2 col-form-label">Divisi</label>
  <div class="col-sm-10">
    <input type="text" class="form-control <?= ($validation->hasError('nama_divisi')) ? 'is-invalid' : ''; ?> " id="nama_divisi" name="nama_divisi" autofocus value="<?= $divisi['nama_divisi'] ?>">
    <div class="invalid-feedback">
      <?= $validation->getError('nama_divisi'); ?>
    </div>
  </div>
  </div>
  <button type="submit" class="btn btn-primary">Ubah divisi</button>
</form>

<?= $this->include('layout/footer') ?>