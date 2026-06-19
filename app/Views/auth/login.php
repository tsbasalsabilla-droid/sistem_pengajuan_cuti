<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Sistem Cuti</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0
    }

    html,
    body {
      height: 100%;
      overflow: hidden
    }

    body {
      font-family: 'Segoe UI', sans-serif;
      display: flex;
      height: 100vh;
    }

    .left {
      width: 420px;
      min-width: 420px;
      background: #fff;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 52px 48px;
      z-index: 2;
      text-align: center;
    }

    .logo-box {
      width: 60px;
      height: 60px;
      background: linear-gradient(135deg, #6f4e37, #8b6b52);
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 28px;
      color: #fff;
      margin-bottom: 24px;
    }

    .left h1 {
      font-family: 'Segoe UI', sans-serif;
      font-size: 28px;
      font-weight: 700;
      color: #2c1810;
      margin-bottom: 4px;
      text-align: left;
      width: 100%;
      letter-spacing: normal;
    }

    .left .sub {
      font-size: 13px;
      color: #9a7456;
      margin-bottom: 32px;
      text-align: left;
      width: 100%;
    }

    .avatar-wrap {
      width: 90px;
      height: 90px;
      border-radius: 50%;
      background: #f5ede5;
      border: 2px solid #ead7c4;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 38px;
      color: #b58a68;
      margin: 0 auto 28px;
    }

    .field {
      margin-bottom: 14px;
      text-align: left;
    }

    .field label {
      display: block;
      font-size: 11px;
      font-weight: 500;
      color: #7b573d;
      margin-bottom: 6px;
      letter-spacing: .6px;
      text-transform: uppercase;
    }

    .input-wrap {
      position: relative;
    }

    .input-wrap i {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: #b58a68;
      font-size: 16px;
      pointer-events: none;
    }

    .field input {
      width: 100%;
      border: 1.5px solid #ead7c4;
      border-radius: 10px;
      padding: 12px 14px 12px 42px;
      font-size: 14px;
      font-family: 'Segoe UI', sans-serif;
      color: #2c1810;
      background: #fdfaf7;
      outline: none;
      transition: .2s;
    }

    .field input:focus {
      border-color: #8d6a4f;
      background: #fff;
      box-shadow: 0 0 0 3px rgba(141, 106, 79, .12);
    }

    .field input::placeholder {
      color: #c9aa90;
    }

    .btn-login {
      width: 100%;
      padding: 14px;
      background: linear-gradient(135deg, #8d6a4f, #6f4e37);
      color: #fff;
      border: none;
      border-radius: 10px;
      font-family: 'Segoe UI', sans-serif;
      font-size: 15px;
      font-weight: 500;
      cursor: pointer;
      transition: .2s;
      margin-top: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .btn-login:hover {
      opacity: .88;
      transform: translateY(-1px);
    }

    .alert {
      border: none;
      border-radius: 10px;
      padding: 10px 14px;
      font-size: 13px;
      margin-bottom: 18px;
      display: flex;
      align-items: center;
      gap: 8px;
      width: 100%;
      text-align: left;
    }

    .alert-error {
      background: #f8d7da;
      color: #721c24;
    }

    .alert-success {
      background: #d4edda;
      color: #155724;
    }

    .footer-text {
      text-align: center;
      margin-top: 18px;
      font-size: 13px;
      color: #9a7456;
    }

    .footer-text a {
      color: #6f4e37;
      font-weight: 600;
      text-decoration: none;
    }

    .footer-text a:hover {
      text-decoration: underline;
    }

    .right {
      flex: 1;
      position: relative;
      overflow: hidden;
      display: flex;
      align-items: center;
      background: #0e0a05;
    }

    .blob {
      position: absolute;
      border-radius: 50%;
      filter: blur(80px);
    }

    .blob-1 {
      width: 65%;
      height: 75%;
      top: 10%;
      left: 20%;
      background: radial-gradient(circle at 50% 50%, rgba(160, 100, 40, .75) 0%, rgba(100, 55, 15, .40) 45%, transparent 70%);
    }

    .blob-2 {
      width: 45%;
      height: 55%;
      top: 30%;
      left: 45%;
      background: radial-gradient(circle at 40% 45%, rgba(190, 130, 55, .55) 0%, rgba(130, 75, 20, .28) 50%, transparent 72%);
    }

    .blob-3 {
      width: 30%;
      height: 35%;
      top: 5%;
      left: 60%;
      background: radial-gradient(circle at 50% 50%, rgba(210, 150, 60, .35) 0%, transparent 65%);
    }

    .blob-4 {
      width: 25%;
      height: 30%;
      top: 55%;
      left: 15%;
      background: radial-gradient(circle at 50% 50%, rgba(120, 65, 18, .30) 0%, transparent 65%);
    }

    .right-nav {
      position: absolute;
      top: 36px;
      left: 56px;
      right: 56px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      z-index: 3;
    }

    .brand {
      font-size: 11px;
      font-weight: 500;
      color: rgba(255, 255, 255, .28);
      letter-spacing: 3px;
      text-transform: uppercase;
    }

    .nav-links {
      display: flex;
      gap: 24px;
      align-items: center;
    }

    .nav-links a {
      font-size: 11px;
      color: rgba(255, 255, 255, .22);
      text-decoration: none;
      letter-spacing: .3px;
      transition: .2s;
    }

    .nav-links a:hover {
      color: rgba(255, 255, 255, .55);
    }

    .nav-links .signup {
      background: #8d6a4f;
      color: #fff !important;
      padding: 6px 16px;
      border-radius: 6px;
      font-weight: 500;
    }

    .right-content {
      position: relative;
      z-index: 2;
      padding: 0 56px;
    }

    .tag {
      font-size: 10px;
      letter-spacing: 3px;
      text-transform: uppercase;
      color: rgba(181, 138, 104, .65);
      margin-bottom: 18px;
    }

    .welcome-text {
      font-family: 'Segoe UI', sans-serif;
      font-weight: 700;
      color: #f0e4d6;
      line-height: .9;
      letter-spacing: -4px;
      margin-bottom: 28px;
      font-size: clamp(60px, 7.5vw, 110px);
    }

    .welcome-text .ghost {
      display: block;
      color: transparent;
      -webkit-text-stroke: 1.5px rgba(240, 228, 214, .14);
    }

    .right-desc {
      font-size: 13px;
      color: rgba(255, 255, 255, .22);
      line-height: 1.9;
      max-width: 290px;
    }

    .dots {
      position: absolute;
      bottom: 36px;
      left: 56px;
      display: flex;
      gap: 8px;
      z-index: 3;
      align-items: center;
    }

    .dot {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: rgba(255, 255, 255, .12);
    }

    .dot.on {
      width: 24px;
      border-radius: 3px;
      background: #8d6a4f;
    }
  </style>
</head>

<body>

  <div class="left">
    <h1>Sistem Cuti</h1>
    <p class="sub">Management Leave System</p>

    <div class="avatar-wrap"><i class="bi bi-person"></i></div>

    <?php if (session()->getFlashdata('error')): ?>
      <div class="alert alert-error"><i class="bi bi-exclamation-circle"></i><?= session()->getFlashdata('error'); ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
      <div class="alert alert-success"><i class="bi bi-check-circle"></i><?= session()->getFlashdata('success'); ?></div>
    <?php endif; ?>

    <form action="/auth/doLogin" method="post" style="width:100%">
      <div class="field">
        <label>NIP</label>
        <div class="input-wrap">
          <i class="bi bi-person"></i>
          <input type="text" name="nip" required placeholder="NIP Anda">
        </div>
      </div>
      <div class="field">
        <label>Password</label>
        <div class="input-wrap">
          <i class="bi bi-lock"></i>
          <input type="password" name="password" required placeholder="Password Anda">
        </div>
      </div>
      <button type="submit" class="btn-login">
        <i class="bi bi-box-arrow-in-right"></i> Login
      </button>
    </form>

    <div class="footer-text">Belum punya akun? <a href="/auth/register">Daftar di sini</a></div>
  </div>

  <div class="right">

    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>
    <div class="blob blob-4"></div>

    <div class="right-content">
      <p class="tag">Management System</p>
      <div class="welcome-text">
        Selamat<br>
        <span class="ghost">Datang.</span>
      </div>
      <p class="right-desc">Kelola pengajuan dan persetujuan cuti pegawai dengan mudah, cepat, dan terpusat.</p>
    </div>

    <div class="dots">
      <div class="dot on"></div>
      <div class="dot"></div>
      <div class="dot"></div>
    </div>
  </div>

</body>

</html>