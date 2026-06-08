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

 <div class="sidebar">

     <!-- Brand -->
     <div class="sidebar-brand">
         <div class="brand-icon">
             <i class="ti ti-briefcase"></i>
         </div>
         <div class="brand-text">
             <h2>HRD Panel</h2>
             <span>Manajemen SDM</span>
         </div>
     </div>

     <!-- Navigasi -->
     <nav class="sidebar-nav">

         <a href="/hrd/dashboard" class="nav-link-item active">
             <i class="ti ti-layout-dashboard"></i>
             <span>Dashboard</span>
         </a>

         <div class="nav-section-title">Master Data</div>
         <a href="/hrd/pegawai" class="nav-link-item">
             <i class="ti ti-users"></i>
             <span>Data Pegawai</span>
         </a>
         <a href="/hrd/jabatan" class="nav-link-item">
             <i class="ti ti-building"></i>
             <span>Data Jabatan</span>
         </a>
         <a href="/hrd/divisi" class="nav-link-item">
             <i class="ti ti-sitemap"></i>
             <span>Data Divisi</span>
         </a>

         <div class="nav-section-title">Pengaturan Cuti</div>
         <a href="/hrd/cuti_bersama" class="nav-link-item">
             <i class="ti ti-calendar-check"></i>
             <span>Cuti Bersama</span>
         </a>

         <div class="nav-section-title">Laporan</div>
         <a href="/hrd/laporan" class="nav-link-item">
             <i class="ti ti-file-analytics"></i>
             <span>Laporan Pengajuan</span>
         </a>


         <div class="nav-section-title">Cuti</div>
         <a href="/hrd/cuti" class="nav-link-item">
             <i class="ti ti-file-analytics"></i>
             <span>Cuti</span>
         </a>
         <a href="/hrd/cuti/history" class="nav-link-item">
             <i class="ti ti-file-analytics"></i>
             <span>History Cuti</span>
         </a>


         <div class="nav-section-title">Approval</div>
         <a href="/approvalhrd" class="nav-link-item">
             <i class="ti ti-file-analytics"></i>
             <span>Approval Pengajuan</span>
         </a>
     </nav>

     <!-- Footer -->
     <div class="sidebar-footer">
         <a href="/profile" class="footer-btn">
             <i class="ti ti-user-circle"></i>
             Profile
         </a>
         <a href="/logout" class="footer-btn">
             <i class="ti ti-logout"></i>
             Logout
         </a>
     </div>

 </div>

 <!-- MAIN CONTENT -->
<div class="main-content">

    <div class="page-content">

        <?= $this->renderSection('content'); ?>

    </div>

</div>

 <script>
     // Auto active berdasarkan URL
     const navLinks = document.querySelectorAll('.nav-link-item');
     const currentPath = window.location.pathname;

     navLinks.forEach(link => {
         if (link.getAttribute('href') === currentPath) {
             navLinks.forEach(l => l.classList.remove('active'));
             link.classList.add('active');
         }
     });
 </script>

 </body>

 </html>