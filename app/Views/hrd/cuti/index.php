<?= $this->extend('layout/sidebarhrd'); ?>
<?= $this->section('content'); ?>

<div class="container-fluid">

    <h1 class="mb-4">History Cuti HRD</h1>

    <div class="card shadow-sm">
        <div class="card-body">

            <table class="table table-bordered table-hover">

                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Total Hari</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($cuti as $c): ?>
                    <tr>

                        <td>
                            <?= $c['tanggal_mulai']; ?>
                            s/d
                            <?= $c['tanggal_selesai']; ?>
                        </td>

                        <td>
                            <?= $c['total_hari']; ?> Hari
                        </td>

                        <td>
                            <?= ucwords(str_replace('_', ' ', $c['status'])) ?>
                        </td>

                        <td>
                            <a href="/hrd/cuti/detail/<?= $c['id']; ?>"
                               class="btn btn-sm btn-primary">
                                Detail
                            </a>
                        </td>

                    </tr>
                    <?php endforeach; ?>
                </tbody>

            </table>

        </div>
    </div>

</div>

<?= $this->endSection(); ?>