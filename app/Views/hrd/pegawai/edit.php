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
    <h2 class="my-3">Form ubah Pegawai</h2>
    <form action="/pegawai/update/<?= $pegawai['id'] ?>" method="post">
        <?= csrf_field(); ?>
        <div class="mb-3">
            <label for="foto" class="form-label">Foto</label>
            <input type="text" class="form-control" id="foto" name="foto" value="<?= old('foto', $pegawai['foto']); ?>">
        </div>
        <div class="mb-3">
            <label for="nama" class="form-label">Nama</label>
            <input type="text" class="form-control <?= ($validation->hasError('nama')) ? 'is-invalid' : ''; ?>" id="nama" name="nama" autofocus value="<?= old('nama', $pegawai['nama']); ?>">
            <div class="invalid-feedback">
                <?= $validation->getError('nama'); ?>
            </div>
        </div>
        <div class="mb-3">
            <label for="nip" class="form-label">NIP</label>
            <input type="text" class="form-control" id="nip" name="nip" value="<?= old('nip', $pegawai['nip']); ?>">
        </div>
        <div class="mb-3 ">
            <label for="email" class="form-label">Email</label>
            <input type="text" class="form-control" id="email" name="email" value="<?= old('email', $pegawai['email']); ?>">
        </div>
        <div class="mb-3 ">
            <label for="no_hp" class="form-label">Ponsel</label>
            <input type="text" class="form-control" id="no_hp" name="no_hp" value="<?= old('no_hp', $pegawai['no_hp']); ?>">
        </div>
        <div class="mb-3 ">
            <label for="id_jabatan" class="form-label">Jabatan</label>
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
        <div class="mb-3">
            <label for="id_divisi" class="form-label">Divisi</label>
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
        <div class="mb-3">
            <label for="alamat" class="form-label">Alamat</label>
            <input type="text" class="form-control" id="alamat" name="alamat" value="<?= old('alamat', $pegawai['alamat']); ?>">
        </div>
        <button type="submit" class="btn btn-primary">Ubah pegawai</button>
        <a href="/pegawai" class="btn btn-secondary">
            Kembali
        </a>
    </form>
</div>
<?= $this->include('layout/footerhrd') ?>