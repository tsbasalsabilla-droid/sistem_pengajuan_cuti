<!DOCTYPE html>
<html>

<head>
    <title><?= $title ?? 'Dashboard'; ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    background:#f8f3ed;
    font-family:'Segoe UI',sans-serif;
}

.wrapper{
    display:flex;
}

        .sidebar{
    width:260px;
    height:100vh;
    position:fixed;
    left:0;
    top:0;
    padding:20px 18px;
    background:linear-gradient(180deg,#6f4e37,#8b6b52);
    box-shadow:4px 0 20px rgba(0,0,0,.08);
    color:#f8ede3;
    display:flex;
    flex-direction:column;
}


.logo{
    display:flex;
    align-items:center;
    gap:12px;
    margin-bottom:40px;
    padding:8px;
}

.logo-icon{
    width:50px;
    height:50px;
    flex-shrink:0;

    display:flex;
    align-items:center;
    justify-content:center;

    border-radius:14px;

    background:rgba(255,255,255,.15);
    border:1px solid rgba(255,255,255,.2);

    backdrop-filter:blur(8px);
    -webkit-backdrop-filter:blur(8px);

    box-shadow:0 4px 12px rgba(0,0,0,.15);

    color:#fff;
}

.logo-icon i{
    font-size:22px;
}

.logo-text h3{
    color:#fff;
    font-size:20px;
    font-weight:700;
    margin:0;
}

.logo-text p{
    color:#f5e6d3;
    font-size:11px;
    margin-top:2px;
    opacity:.9;
}

.sidebar ul{
    list-style:none;
    padding:0;
    margin:0;
}

.menu-title{
    color:#f5e6d3;
    font-size:12px;
    margin:20px 15px 10px;
    text-transform:uppercase;
    letter-spacing:1px;
    opacity:.75;
}

.sidebar a{
    display:flex;
    align-items:center;
    gap:14px;
    padding:10px 18px;
    margin-bottom:10px;

    color:#f8ede3;
    text-decoration:none;

    border-radius:16px;

    font-size:15px;
    font-weight:500;

    transition:.3s;
}

.sidebar a i{
    font-size:18px;
}

.sidebar a:hover{
    background:#d2b48c;
    color:#4b2e2e;
    transform:translateX(4px);
    box-shadow:0 5px 15px rgba(0,0,0,.12);
}

.sidebar a.active{
    background:#d2b48c;
    color:#4b2e2e;
    box-shadow:0 5px 15px rgba(0,0,0,.12);
}

.user-info{
    padding:12px 15px;
    color:#f8ede3;
    font-size:13px;
}

.user-info i{
    font-size:16px;
    margin-right:8px;
}

.user-role{
    display:inline-block;
    margin-top:4px;
    margin-left:24px;
    opacity:.8;
    font-size:12px;
}


.logout-link{
    margin-top:15px;
    padding-top:15px;
    border-top:1px solid rgba(255,255,255,.15);
    background:rgba(255,255,255,.05);
}

        .content {
            margin-left: 260px;
            width: 100%;
            padding: 40px;
        }

        .card-wrapper {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
        }

        .card {
            background: #fffaf5;
            border: none;
            border-radius: 24px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .card h3 {
            color: #8b6b52;
            margin-bottom: 12px;
            font-size: 16px;
            font-weight: 600;
        }

        .card h1 {
            color: #6f4e37;
            font-size: 32px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
            margin-top: 25px;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
        }

        table th,
        table td {
            padding: 16px 20px;
            border: 1px solid #f3e8dc;
        }

        table th {
            background: #f3e8dc !important;
            color: #6f4e37;
            font-weight: 600;
            text-align: left;
        }

        .modal form{
    background: transparent;
    padding: 0;
    border-radius: 0;
    box-shadow: none;
}

        input,
        textarea {
            width: 100%;
            padding: 14px;
            margin-top: 10px;
            margin-bottom: 20px;
            border: 1px solid #d2b48c;
            border-radius: 12px;
            background: #ffffff;
            font-family: inherit;
            transition: 0.2s;
        }

        input:focus,
        textarea:focus {
            outline: none;
            border-color: #8b6b52;
            box-shadow: 0 0 0 3px rgba(139, 107, 82, 0.15);
        }

        button {
            background: #8b6b52;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background: #6f4e37;
            box-shadow: 0 4px 12px rgba(111, 78, 55, 0.2);
        }

        .badge {
            padding: 6px 14px;
            border-radius: 20px;
            color: white;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .pending {
            background: #d97706;
        }

        .diterima {
            background: #6f4e37;
        }

        .ditolak {
            background: #a94442;
        }

        @media(max-width:992px){

    .wrapper{
        flex-direction:column;
    }

    .sidebar{
        position:static;
        width:100%;
        height:auto;
        padding:18px 14px;
        box-shadow:none;
    }

    .logo{
        margin-bottom:20px;
    }

    .sidebar a{
        padding:12px 14px;
        font-size:14px;
    }

    .content{
        margin-left:0;
        padding:20px;
    }
}
    </style>
</head>

<body>

    <?php
    $userRole = session()->get('user')['role'] ?? 'karyawan';
    ?>

    <div class="wrapper">

        <div class="sidebar">

            <div class="logo">

    <div class="logo-icon">
        <i class="bi bi-person-badge-fill"></i>
    </div>

    <div class="logo-text">
        <h3>Karyawan</h3>
        <p>Sistem Pengajuan Cuti</p>
    </div>

</div>

            <div class="menu-title">Main Menu</div>

            <ul>
                <li>
                    <a href="/pegawai/dashboard" class="<?= url_is('pegawai/dashboard') ? 'active' : ''; ?>">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="/pegawai/cuti/create" class="<?= url_is('pegawai/cuti/create') ? 'active' : ''; ?>">
                        <i class="bi bi-file-earmark-plus"></i> Pengajuan Cuti
                    </a>
                </li>
                <li>
                    <a href="/teman" class="<?= url_is('teman*') ? 'active' : ''; ?>">
                        <i class="bi bi-people"></i> Approval Teman
                    </a>
                </li>
                <li>
                    <a href="/pegawai/cuti" class="<?= url_is('pegawai/cuti') || url_is('pegawai/cuti/detail*') ? 'active' : ''; ?>">
                        <i class="bi bi-clock-history"></i> History Cuti
                    </a>
                </li>
            </ul>

            <hr style="border: 0; border-top: 1px solid rgba(255,255,255,0.1); margin: 20px 15px 10px 15px;">

            <div class="menu-title">User</div>

            <div style="padding: 12px 15px; color: #f8ede3; font-size: 13px;">
                <i class="bi bi-person-circle" style="margin-right: 8px; font-size: 16px; vertical-align: middle;"></i>
                <strong style="vertical-align: middle;"><?= session()->get('user')['nama'] ?? 'Guest'; ?></strong>
                <br>
                <span style="opacity: 0.8; font-size: 12px; display: inline-block; margin-top: 4px; margin-left: 24px;">
                    <?= ucfirst($userRole); ?>
                </span>
            </div>

            <a href="/logout" class="logout-link">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>

        </div>

        <div class="content">

            <?= $this->renderSection('content'); ?>

        </div>

    </div>

</body>

</html>