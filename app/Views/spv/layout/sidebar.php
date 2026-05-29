<!DOCTYPE html>
<html>
<head>
        <title><?= $title ?? 'Dashboard'; ?></title>

    <style>

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:Arial;
        }

        body{
            background:#f4f6f9;
        }

        .wrapper{
            display:flex;
        }

        /* SIDEBAR */

        .sidebar{
            width:250px;
            height:100vh;
            background:#1e293b;
            position:fixed;
            color:white;
            padding-top:20px;
        }

        .sidebar h2{
            text-align:center;
            margin-bottom:30px;
        }

        .sidebar ul{
            list-style:none;
        }

        .sidebar ul li{
            padding:15px 25px;
            transition:0.3s;
        }

        .sidebar ul li:hover{
            background:#334155;
        }

        .sidebar ul li a{
            color:white;
            text-decoration:none;
            display:block;
        }

        /* CONTENT */

        .content{
            margin-left:250px;
            width:100%;
            padding:30px;
        }

        /* CARD */

        .card-wrapper{
            display:grid;
            grid-template-columns:repeat(4,1fr);
            gap:20px;
        }

        .card{
            background:white;
            padding:20px;
            border-radius:10px;
            box-shadow:0 2px 10px rgba(0,0,0,0.1);
        }

        .card h3{
            color:#666;
            margin-bottom:10px;
        }

        .card h1{
            color:#2563eb;
        }

        /* TABLE */

        table{
            width:100%;
            border-collapse:collapse;
            background:white;
            margin-top:20px;
        }

        table th,
        table td{
            padding:15px;
            border:1px solid #ddd;
        }

        table th{
            background:#2563eb;
            color:white;
        }

        /* FORM */

        form{
            background:white;
            padding:20px;
            border-radius:10px;
        }

        input,
        textarea{
            width:100%;
            padding:12px;
            margin-top:10px;
            margin-bottom:20px;
            border:1px solid #ccc;
            border-radius:5px;
        }

        button{
            background:#2563eb;
            color:white;
            border:none;
            padding:12px 20px;
            border-radius:5px;
            cursor:pointer;
        }

        .badge{
            padding:6px 12px;
            border-radius:20px;
            color:white;
            font-size:12px;
        }

        .pending{
            background:orange;
        }

        .diterima{
            background:green;
        }

        .ditolak{
            background:red;
        }

    </style>
</head>
<body>

<div class="wrapper">

<div class="sidebar">

<h2>Dashboard</h2>

    <ul>

        <li>
            <a href="/spv/dashboard">
                Dashboard SPV
            </a>
        </li>

        <li>
            <a href="/spv/cuti/create">
                Pengajuan Cuti
            </a>
        </li>

        <li>
            <a href="/spv/cuti">
                History Cuti
            </a>
        </li>

    </ul>

</div>

<div class="content">

    <?= $this->renderSection('content'); ?>

</div>

</body>
</html>