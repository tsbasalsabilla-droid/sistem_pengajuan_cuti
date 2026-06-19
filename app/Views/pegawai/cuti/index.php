<?= $this->extend('pegawai/layout/sidebar'); ?>

<?= $this->section('content'); ?>

<h1>
    History Pengajuan Cuti
</h1>

<table>

    <tr>
        <th>Tanggal</th>
        <th>Total Hari</th>
        <th>Alasan Cuti</th>
        <th>Status</th>
        <th>Aksi</th>
    </tr>

    <?php foreach ($cuti as $c): ?>

        <tr>

            <td>
                <?= $c['tanggal_mulai']; ?>
                s/d
                <?= $c['tanggal_selesai']; ?>
            </td>

            <td>
                <?= $c['alasan']; ?>
            </td>
            <td>
                <?= $c['total_hari']; ?> Hari
            </td>

            <td>
                <?php
                $status = trim($c['status'] ?? '');
                if ($status === 'approve') $status = 'approved';
                if ($status === '') {
                    $approvalModel = new \App\Models\ApprovalModel();
                    $log = $approvalModel->where('cuti_id', $c['id'])->where('status', 'approved')->first();
                    $status = $log ? 'approved' : 'pending';
                }
                $statusLabel = match ($status) {
                    'pending' => 'Menunggu',
                    'pending_spv' => 'Menunggu SPV',
                    'pending_hrd' => 'Menunggu HRD',
                    'pending_direktur' => 'Menunggu Direktur',
                    'pending_teman', 'pending_teman_sejawat' => 'Menunggu Teman Sejawat',
                    default => ucwords(str_replace('_', ' ', $status))
                };
                echo $statusLabel;
                ?>
            </td>

            <td>
                <a href="/pegawai/cuti/detail/<?= $c['id']; ?>">
                    Detail
                </a>
            </td>

        </tr>

    <?php endforeach; ?>

</table>

<?= $this->endSection(); ?>