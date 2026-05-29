<?= $this->include('layout/header') ?>

<h2 class="my-3">Form ubah Pegawai</h2>
<form action="/pegawai/update/<?= $pegawai['id'] ?>" method="post">

    <?= csrf_field(); ?>
    <div class="mb-3 row">
        <label for="foto" class="col-sm-2 col-form-label">Foto</label>
        <div class="col-sm-10">
            <input type="text" class="form-control" id="foto" name="foto" value="<?= old('foto', $pegawai['foto']); ?>">
        </div>
    </div>
    <div class="mb-3 row">
        <label for="nama" class="col-sm-2 col-form-label">Nama</label>
        <div class="col-sm-10">
            <input type="text" class="form-control <?= ($validation->hasError('nama')) ? 'is-invalid' : ''; ?>" id="nama" name="nama" autofocus value="<?= old('nama', $pegawai['nama']); ?>">
            <div class="invalid-feedback">
                <?= $validation->getError('nama'); ?>
            </div>
        </div>
    </div>
    <div class="mb-3 row">
        <label for="nip" class="col-sm-2 col-form-label">NIP</label>
        <div class="col-sm-10">
            <input type="text" class="form-control" id="nip" name="nip" value="<?= old('nip', $pegawai['nip']); ?>">
        </div>
    </div>
    <div class="mb-3 row">
        <label for="email" class="col-sm-2 col-form-label">Email</label>
        <div class="col-sm-10">
            <input type="text" class="form-control" id="email" name="email" value="<?= old('email', $pegawai['email']); ?>">
        </div>
    </div>
    <div class="mb-3 row">
        <label for="no_hp" class="col-sm-2 col-form-label">Ponsel</label>
        <div class="col-sm-10">
            <input type="text" class="form-control" id="no_hp" name="no_hp" value="<?= old('no_hp', $pegawai['no_hp']); ?>">
        </div>
    </div>
    <div class="mb-3 row">
        <label for="id_jabatan" class="col-sm-2 col-form-label">Jabatan</label>
        <div class="col-sm-10">
            <select class="form-select <?= ($validation->hasError('id_jabatan')) ? 'is-invalid' : ''; ?>" name="id_jabatan" id="id_jabatan">
                <option value="">-- Pilih Jabatan --</option>
                <?php foreach ($jabatan as $j) : ?>
                    <option value="<?= $j['id']; ?>" <?= (old('id_jabatan', $pegawai['id_jabatan']) == $j['id']) ? 'selected' : ''; ?>>
                        <?= $j['jabatan']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">
                <?= $validation->getError('id_jabatan'); ?>
            </div>
        </div>
    </div>
    <div class="mb-3 row">
        <label for="id_divisi" class="col-sm-2 col-form-label">Divisi</label>
        <div class="col-sm-10">
            <select class="form-select <?= ($validation->hasError('id_divisi')) ? 'is-invalid' : ''; ?>" name="id_divisi" id="id_divisi">
                <option value="">-- Pilih Divisi --</option>
                <?php foreach ($divisi as $d) : ?>
                    <option value="<?= $d['id']; ?>" <?= (old('id_divisi', $pegawai['id_divisi']) == $d['id']) ? 'selected' : ''; ?>>
                        <?= $d['nama_divisi']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">
                <?= $validation->getError('id_divisi'); ?>
            </div>
        </div>
    </div>
    <div class="mb-3 row">
        <label for="alamat" class="col-sm-2 col-form-label">Alamat</label>
        <div class="col-sm-10">
            <input type="text" class="form-control" id="alamat" name="alamat" value="<?= old('alamat', $pegawai['alamat']); ?>">
        </div>
    </div>
    <button type="submit" class="btn btn-primary">Ubah pegawai</button>
</form>

<?= $this->include('layout/footer') ?>