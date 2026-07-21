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

        width: 100%;

    }

    .card-table h2 {
        color: #7b573d;
        font-size: 30px;
        font-weight: 700;
        margin-bottom: 25px;
    }

    .card-body {
        width: 100%;
        max-width: none;
    }

    .form-control,
    .form-select {
        height: 45px;
    }

    .form-label {
        font-weight: 600;
        color: #6f4e37;
    }
</style>

<div class="card-table">
    <div class="card-body">
        <h2 class="my-3">Form tambah pegawai</h2>
        <form action="/pegawai/save" method="post">
            <?= csrf_field(); ?>
            <div class="mb-3">
                <label for="foto" class="form-label">Foto</label>
                <input type="text" class="form-control" id="foto" name="foto" value="<?= old('foto'); ?>">
            </div>

            <div class="mb-3">
                <label for="nama" class="form-label">Nama</label>
                <input type="text"
                    class="form-control <?= ($validation->hasError('nama')) ? 'is-invalid' : ''; ?>"
                    id="nama"
                    name="nama"
                    value="<?= old('nama'); ?>"
                    autofocus>
                <div class="invalid-feedback">
                    <?= $validation->getError('nama'); ?>
                </div>
            </div>
            <div class="mb-3">
                <label for="nip" class="form-label">NIP</label>
                <input type="text" class="form-control" id="nip" name="nip" value="<?= old('nip'); ?>">
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="text" class="form-control" id="email" name="email" value="<?= old('email'); ?>">
            </div>

            <div class="mb-3">
                <label for="no_hp" class="form-label">Ponsel</label>
                <input type="text" class="form-control" id="no_hp" name="no_hp" value="<?= old('no_hp'); ?>">
            </div>
            <div class="mb-3">
                <label for="id_jabatan" class="form-label">Jabatan</label>
                <select class="form-select <?= ($validation->hasError('id_jabatan')) ? 'is-invalid' : ''; ?>"
                    name="id_jabatan"
                    id="id_jabatan">
                    <option value="">-- Pilih Jabatan --</option>
                    <?php foreach ($jabatan as $j) : ?>
                        <option value="<?= $j['id']; ?>"
                            <?= old('id_jabatan') == $j['id'] ? 'selected' : ''; ?>>
                            <?= $j['jabatan']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">
                    <?= $validation->getError('id_jabatan'); ?>
                </div>
            </div>
            <div class="mb-3">
                <label for="id_divisi" class="form-label">Divisi</label>
                <select class="form-select <?= ($validation->hasError('id_divisi')) ? 'is-invalid' : ''; ?>"
                    name="id_divisi"
                    id="id_divisi">
                    <option value="">-- Pilih Divisi --</option>
                    <?php foreach ($divisi as $d) : ?>
                        <option value="<?= $d['id']; ?>"
                            <?= old('id_divisi') == $d['id'] ? 'selected' : ''; ?>>
                            <?= $d['nama_divisi']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">
                    <?= $validation->getError('id_divisi'); ?>
                </div>
            </div>
            <div class="mb-4">
                <label for="alamat" class="form-label">Alamat</label>
                <input type="text" class="form-control" id="alamat" name="alamat" value="<?= old('alamat'); ?>">
            </div>
            <button type="submit" class="btn btn-primary">Tambah pegawai</button>
            <a href="/pegawai" class="btn btn-secondary">
                Kembali
            </a>

        </form>
    </div>
</div>

<?= $this->include('layout/footerhrd') ?>