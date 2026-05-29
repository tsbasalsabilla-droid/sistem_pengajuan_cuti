<?= $this->include('layout/header') ?>

            <h2 class="my-3">Form tambah jabatan</h2>
<form action="/jabatan/save" method="post">    
    <?= csrf_field(); ?>  
<div class="mb-3 row">
  <label for="jabatan" class="col-sm-2 col-form-label">Jabatan</label> 
    <input type="text" class="form-control <?= ($validation->hasError('jabatan')) ? 'is-invalid': ''; ?> " id="jabatan" name="jabatan" autofocus value="<?= old ('jabatan'); ?>"> 
    <div class="invalid-feedback">
        <?= $validation->getError('jabatan'); ?>
    </div>
  </div>
</div>
<button type="submit" class="btn btn-primary">Tambah jabatan</button> 
    </form>

<?= $this->include('layout/footer') ?>      