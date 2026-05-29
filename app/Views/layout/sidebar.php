<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        background: #f8f3ed;
        font-family: 'Segoe UI', sans-serif;
    }


    .sidebar {
        width: 260px;
        height: 100vh;
        background: linear-gradient(180deg, #6f4e37, #8b6b52);
        position: fixed;
        left: 0;
        top: 0;
        padding: 25px 18px;
        box-shadow: 4px 0 20px rgba(0, 0, 0, 0.08);
    }

    .logo {
        text-align: center;
        margin-bottom: 40px;
    }

    .logo h3 {
        color: white;
        font-weight: 700;
        margin-top: 10px;
        font-size: 24px;
    }

    .logo p {
        color: #f5e6d3;
        font-size: 13px;
    }

    .logo-icon {
        width: 70px;
        height: 70px;
        background: #d2b48c;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: auto;
        font-size: 30px;
        color: #6f4e37;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
    }


    .menu-title {
        color: #f5e6d3;
        font-size: 12px;
        margin: 20px 15px 10px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .sidebar a {
        display: flex;
        align-items: center;
        gap: 14px;
        color: #f8ede3;
        text-decoration: none;
        padding: 14px 18px;
        margin-bottom: 10px;
        border-radius: 16px;
        transition: 0.3s;
        font-size: 15px;
        font-weight: 500;
    }

    .sidebar a i {
        font-size: 18px;
    }

    .sidebar a:hover {
        background: #d2b48c;
        color: #4b2e2e;
        transform: translateX(4px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .sidebar a.active {
        background: #d2b48c;
        color: #4b2e2e;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }


    .card-custom {
        background: #fffaf5;
        border: none;
        border-radius: 24px;
        padding: 25px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    }


    .btn-primary {
        background: #8b6b52;
        border: none;
        border-radius: 12px;
        padding: 10px 22px;
        font-weight: 500;
    }

    .btn-primary:hover {
        background: #6f4e37;
    }


    table {
        background: white;
        border-radius: 20px;
        overflow: hidden;
    }

    th {
        background: #f3e8dc !important;
        color: #6f4e37;
    }


    footer {
        text-align: center;
        margin-top: 40px;
        color: #8b6b52;
        font-size: 14px;
    }

    @media(max-width: 992px) {
        .sidebar {
            position: static;
            width: 100%;
            height: auto;
            padding: 18px 14px;
            box-shadow: none;
        }

        .sidebar .logo {
            margin-bottom: 18px;
        }

        .sidebar a {
            padding: 12px 14px;
            font-size: 14px;
        }
    }
</style>

<div class="sidebar">

    <div class="logo">

        <div class="logo-icon">
            <i class="bi bi-calendar-check"></i>
        </div>

        <h3>Sistem Cuti</h3>

        <p>Management Leave System</p>

    </div>

    <div class="menu-title">
        Main Menu
    </div>

    <?php 
        $userRole = session()->get('user')['role'] ?? 'karyawan';
        $currentUrl = current_url(true)->getPath();
    ?>

    <?php if(in_array($userRole, ['karyawan'])): ?>
        <a href="/cuti" class="<?= str_contains($currentUrl, '/cuti') ? 'active' : ''; ?>">
            <i class="bi bi-file-earmark-plus"></i>
            Pengajuan Cuti
        </a>

        <a href="/cuti/history" class="<?= str_contains($currentUrl, 'history') ? 'active' : ''; ?>">
            <i class="bi bi-clock-history"></i>
            History Pengajuan
        </a>
    <?php endif; ?>

    <?php if(in_array($userRole, ['spv'])): ?>
        <a href="/spv/dashboard" class="<?= str_contains($currentUrl, '/spv/dashboard') ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i>
            Dashboard
        </a>

        <a href="/spv" class="<?= str_contains($currentUrl, '/spv') && !str_contains($currentUrl, '/spv/dashboard') ? 'active' : ''; ?>">
            <i class="bi bi-person-check"></i>
            Approval SPV
        </a>
    <?php endif; ?>

    <?php if(in_array($userRole, ['teman', 'karyawan'])): ?>
        <a href="/teman" class="<?= str_contains($currentUrl, '/teman') ? 'active' : ''; ?>">
            <i class="bi bi-people"></i>
            Approval Teman
        </a>
    <?php endif; ?>

    <?php if(in_array($userRole, ['hrd'])): ?>
        <a href="/hrd" class="<?= str_contains($currentUrl, '/hrd') ? 'active' : ''; ?>">
            <i class="bi bi-person-workspace"></i>
            Approval HRD
        </a>
    <?php endif; ?>

    <?php if(in_array($userRole, ['direktur'])): ?>
        <a href="/direktur/dashboard" class="<?= str_contains($currentUrl, '/direktur/dashboard') ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i>
            Dashboard
        </a>

        <a href="/direktur" class="<?= str_contains($currentUrl, '/direktur') && !str_contains($currentUrl, '/direktur/dashboard') ? 'active' : ''; ?>">
            <i class="bi bi-building-check"></i>
            Approval Direktur
        </a>
    <?php endif; ?>

    <div class="menu-title" style="margin-top: 30px;">
        User
    </div>

    <div style="padding: 12px 14px; color: #f8ede3; font-size: 13px;">
        <i class="bi bi-person-circle" style="margin-right: 8px;"></i>
        <strong><?= session()->get('user')['nama'] ?? 'Guest'; ?></strong>
        <br>
        <span style="opacity: 0.8; font-size: 12px;">
            <?= ucfirst($userRole); ?>
        </span>
    </div>

    <a href="/logout" style="border-top: 1px solid rgba(255,255,255,0.1); margin-top: 15px;">
        <i class="bi bi-box-arrow-right"></i>
        Logout
    </a>

</div>
