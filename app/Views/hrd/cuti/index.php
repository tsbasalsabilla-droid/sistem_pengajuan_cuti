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

    .card-table h1 {
        color: #7b573d;
        font-size: 30px;
        font-weight: 700;
        margin-bottom: 25px;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
    }

    .table th {
        background: #f3e8dc;
        color: #6f4e37;
        font-weight: 600;
        border: none;
        padding: 15px;
        text-align: left;
    }

    .table td {
        padding: 15px;
        color: #495057;
        border-color: #ead7c4;
        background-color: transparent;
    }

    .table tbody tr:hover,
    .table tbody tr:hover>td {
        background-color: #f3e8dc !important;
        color: #3e2f20 !important;
    }

    .btn-action {
        display: inline-block;
        background: #8b6b52;
        color: #fff;
        text-decoration: none;
        padding: 8px 16px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 13px;
        border: none;
        transition: 0.3s ease;
    }

    .btn-action:hover {
        background: #6f4e37;
        color: #fff;
        transform: translateY(-1px);
    }

    .pagination {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-top: 20px;
        flex-wrap: wrap;
    }

    .pagination a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 42px;
        height: 42px;
        padding: 0 12px;
        border-radius: 4px;
        border: 1px solid #e5cdb5;
        background: #fff;
        color: #7b573d;
        text-decoration: none;
        font-weight: 600;
    }

    .pagination a.active {
        background: #7b573d;
        color: #fff;
        border-color: #7b573d;
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, .4);
    }

    .modal-content {
        background: #fff;
        width: 450px;
        margin: 8% auto;
        padding: 25px;
        border-radius: 10px;
    }

    .close {
        float: right;
        cursor: pointer;
        font-size: 22px;
    }

    textarea {
        width: 100%;
        height: 100px;
        margin: 10px 0;
    }

    .btn-batal {
        background: #dc3545;
        color: white;
        border: none;
        padding: 7px 12px;
        cursor: pointer;
        border-radius: 5px;
    }

    /* Modal */
.modal {
    display: none;
    position: fixed;
    z-index: 9999;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
    backdrop-filter: blur(3px);
    animation: fadeIn .25s ease;
}

.modal-content {
    width: 450px;
    max-width: 90%;
    margin: 80px auto;
    background: #fffaf5;
    border-radius: 18px;
    padding: 28px;
    border: 1px solid #ead7c4;
    box-shadow: 0 15px 40px rgba(0, 0, 0, .25);
    position: relative;
    animation: zoomIn .25s ease;
}

.modal-content h3 {
    margin: 0 0 20px;
    color: #7b573d;
    font-size: 24px;
    font-weight: 700;
}

.modal-content label {
    display: block;
    margin-bottom: 8px;
    color: #6f4e37;
    font-weight: 600;
}

.modal-content textarea {
    width: 100%;
    min-height: 120px;
    border: 1px solid #e5cdb5;
    border-radius: 12px;
    padding: 12px 14px;
    resize: vertical;
    font-size: 14px;
    outline: none;
    transition: .3s;
    background: #fff;
}

.modal-content textarea:focus {
    border-color: #8b6b52;
    box-shadow: 0 0 0 3px rgba(139, 107, 82, .15);
}

.modal-content button {
    width: 100%;
    background: #dc3545;
    color: #fff;
    border: none;
    border-radius: 12px;
    padding: 12px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: .3s;
}

.modal-content button:hover {
    background: #bb2d3b;
}

.close {
    position: absolute;
    top: 15px;
    right: 18px;
    font-size: 28px;
    font-weight: bold;
    color: #8b6b52;
    cursor: pointer;
    line-height: 1;
}

.close:hover {
    color: #dc3545;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }

    to {
        opacity: 1;
    }
}

@keyframes zoomIn {
    from {
        transform: scale(.9);
        opacity: 0;
    }

    to {
        transform: scale(1);
        opacity: 1;
    }
}
</style>

<div class="card-table">
    <h1>History Pengajuan Cuti</h1>

    <table class="table">

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
                    <?= formatTanggalIndonesia($c['tanggal_mulai']); ?>
                    s/d
                    <?= formatTanggalIndonesia($c['tanggal_selesai']); ?>
                </td>

                <td>
                    <?= $c['total_hari']; ?> Hari
                </td>

                <td>
                    <?= esc($c['alasan']); ?>
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
                        'batal', 'dibatalkan' => 'Dibatalkan',
                        default => ucwords(str_replace('_', ' ', $status))
                    };

                    $bolehBatal = in_array($status, [
                        'pending',
                        'pending_spv',
                        'pending_teman_sejawat',
                        'pending_hrd',
                        'pending_direktur'
                    ]);

                    echo $statusLabel;
                    ?>
                </td>

                <td>
                    <a href="/hrd/cuti/detail/<?= $c['id']; ?>" class="btn-action">
                        Detail
                    </a>
                    <?php if ($bolehBatal): ?>
                        <br><br>
                        <button type="button" class="btn-batal" onclick="openModal(<?= $c['id']; ?>)">
                            Batalkan
                        </button>
                    <?php endif; ?>
                </td>

            </tr>

        <?php endforeach; ?>

    </table>

    <?php if (!empty($total) && $total > 0) : ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++) : ?>
                <a href="/hrd/cuti/index?page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?php foreach ($cuti as $c): ?>
    <div class="modal" id="modal<?= $c['id']; ?>">
        <div class="modal-content">
            <span class="close" onclick="closeModal(<?= $c['id']; ?>)">&times;</span>
            <h3>Batalkan Pengajuan Cuti</h3>
            <form action="<?= base_url('hrd/cuti/batal/' . $c['id']); ?>" method="post">
                <?= csrf_field(); ?>
                <label>Alasan Pembatalan</label>
                <textarea name="alasan_batal" required placeholder="Masukkan alasan pembatalan..."></textarea>
                <br><br>
                <button type="submit" onclick="return confirm('Yakin ingin membatalkan pengajuan cuti ini?')">
                    Batalkan Pengajuan
                </button>
            </form>
        </div>
    </div>
<?php endforeach; ?>

<script>
    function openModal(id) {
        document.getElementById('modal' + id).style.display = 'block';
    }

    function closeModal(id) {
        document.getElementById('modal' + id).style.display = 'none';
    }

    window.onclick = function(event) {
        document.querySelectorAll('.modal').forEach(function(modal) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    };
</script>

<?= $this->include('layout/footerhrd') ?>