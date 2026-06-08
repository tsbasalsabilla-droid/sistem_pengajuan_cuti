<?= $this->include('layout/header') ?>

<h2 class="my-3">Form ubah Cuti Bersama</h2>
<form action="/hrd/cuti_bersama/update/<?= $cuti_bersama['id'] ?>" method="post">

  <?= csrf_field(); ?>
  <label for="tanggal" class="col-sm-2 col-form-label">Tanggal</label>
  <div class="col-sm-10">
    <input type="date" class="form-control <?= ($validation->hasError('tanggal')) ? 'is-invalid' : ''; ?> " id="tanggal" name="tanggal" autofocus value="<?= $cuti_bersama['tanggal'] ?>">
    <div class="invalid-feedback">
      <?= $validation->getError('tanggal'); ?>
    </div>
  </div>
  </div>
  <label for="keterangan" class="col-sm-2 col-form-label">Keterangan</label>
  <div class="col-sm-10"></div>
  <input type="text" class="form-control <?= ($validation->hasError('keterangan')) ? 'is-invalid' : ''; ?> " id="keterangan" name="keterangan" autofocus value="<?= $cuti_bersama['keterangan'] ?>">
  <div class="invalid-feedback">
    <?= $validation->getError('keterangan'); ?>
  </div>
  </div>
  </div>
  <button type="submit" class="btn btn-primary">Ubah cuti bersama</button>
</form>

<?= $this->include('layout/footer') ?>