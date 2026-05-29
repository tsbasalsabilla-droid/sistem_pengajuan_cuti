<?= $this->include('layout/header') ?>

            <h2 class="my-3">Form tambah Cuti Bersama</h2>
<form action="/cuti_bersama/save" method="post">    
    <?= csrf_field(); ?>  
<div class="mb-3 row">
  <label for="tanggal" class="col-sm-2 col-form-label">Tanggal</label> 
    <input type="date" class="form-control <?= ($validation->hasError('tanggal')) ? 'is-invalid': ''; ?> " id="tanggal" name="tanggal" autofocus value="<?= old ('tanggal'); ?>"> 
  </div>
</div>
<div class="mb-3 row">
  <label for="keterangan" class="col-sm-2 col-form-label">Keterangan</label> 
    <input type="text" class="form-control <?= ($validation->hasError('keterangan')) ? 'is-invalid': ''; ?> " id="keterangan" name="keterangan" autofocus value="<?= old ('keterangan'); ?>"> 
    <div class="invalid-feedback">
        <?= $validation->getError('keterangan'); ?>
    </div>
  </div>
</div>
<button type="submit" class="btn btn-primary">Tambah cuti bersama</button> 
    </form>

<?= $this->include('layout/footer') ?>      