<?= $this->include('layout/header') ?>

            <h2 class="my-3">Form ubah Jabatan</h2>
<form action="/jabatan/update/<?= $jabatan['id'] ?>" method="post">   

    <?= csrf_field(); ?>  
  <label for="jabatan" class="col-sm-2 col-form-label">Jabatan</label> 
  <div class="col-sm-10">
    <input type="text" class="form-control <?= ($validation->hasError('jabatan')) ? 'is-invalid': ''; ?> " id="jabatan" name="jabatan" autofocus value="<?= $jabatan['jabatan'] ?>">
    <div class="invalid-feedback">
        <?= $validation->getError('jabatan'); ?> 
     </div>
  </div>
  </div>
<button type="submit" class="btn btn-primary">Ubah jabatan</button> 
    </form>

<?= $this->include('layout/footer') ?>  