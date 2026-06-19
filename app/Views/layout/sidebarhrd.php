<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRD Panel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="<?= base_url('css/stylesidebar.css') ?>">
</head>

<body>
    <div class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">
                <i class="ti ti-briefcase"></i>
            </div>
            <div class="brand-text">
                <h2>HRD Panel</h2>
                <span>Manajemen SDM</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <a href="/hrd/dashboard" class="nav-link-item <?= url_is('hrd/dashboard*') ? 'active' : '' ?>">
                <i class="ti ti-layout-dashboard"></i>
                <span>Dashboard</span>
            </a>

            <div class="nav-section-title">Master Data</div>
            <a href="/hrd/pegawai" class="nav-link-item <?= url_is('hrd/pegawai*') ? 'active' : '' ?>">
                <i class="ti ti-users"></i>
                <span>Data Pegawai</span>
            </a>
            <a href="/hrd/jabatan" class="nav-link-item <?= url_is('hrd/jabatan*') ? 'active' : '' ?>">
                <i class="ti ti-building"></i>
                <span>Data Jabatan</span>
            </a>
            <a href="/hrd/divisi" class="nav-link-item <?= url_is('hrd/divisi*') ? 'active' : '' ?>">
                <i class="ti ti-sitemap"></i>
                <span>Data Divisi</span>
            </a>

            <div class="nav-section-title">Pengaturan Cuti</div>
            <a href="/hrd/cuti_bersama" class="nav-link-item <?= url_is('hrd/cuti_bersama*') ? 'active' : '' ?>">
                <i class="ti ti-calendar-check"></i>
                <span>Cuti Bersama</span>
            </a>

            <div class="nav-section-title">Laporan</div>
            <a href="/hrd/laporan" class="nav-link-item <?= url_is('hrd/laporan*') ? 'active' : '' ?>">
                <i class="ti ti-file-analytics"></i>
                <span>Laporan Pengajuan</span>
            </a>

            <div class="nav-section-title">Cuti</div>
            <a href="<?= base_url('hrd/cuti/create') ?>" class="nav-link-item <?= url_is('hrd/cuti/create*') ? 'active' : '' ?>">
                <i class="ti ti-file-analytics"></i>
                <span>Cuti</span>
            </a>

            <a href="/hrd/cuti" class="nav-link-item <?= (url_is('hrd/cuti*') && !url_is('hrd/cuti/create*')) ? 'active' : '' ?>">
                <i class="ti ti-file-analytics"></i>
                <span>History Cuti</span>
            </a>

            <div class="nav-section-title">Approval</div>
            <a href="<?= base_url('hrd/approvalhrd/indexhrd') ?>" class="nav-link-item <?= url_is('hrd/approvalhrd*') ? 'active' : '' ?>">
                <i class="ti ti-file-analytics"></i>
                <span>Approval Pengajuan</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="/logout" class="footer-btn">
                <i class="ti ti-logout"></i>
                Logout
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="page-content">