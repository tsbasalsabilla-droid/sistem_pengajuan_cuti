<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<footer class="text-center mt-5 mb-3 text-secondary">
    © <?= date('Y'); ?> Sistem Informasi Cuti
</footer>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const navLinks = document.querySelectorAll('.nav-link-item');
        const currentPath = window.location.pathname;

        navLinks.forEach(link => {
            const hrefAttr = link.getAttribute('href');
            
            if (hrefAttr && hrefAttr !== '/' && currentPath.includes(hrefAttr)) {
                navLinks.forEach(l => l.classList.remove('active'));
                link.classList.add('active');
            }
        });
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const toggleBtn = document.getElementById("toggleBtn");
        const sidebar = document.getElementById("sidebarMenu");
        const overlay = document.getElementById("sidebarOverlay");

        function toggleSidebar() {
            sidebar.classList.toggle("show");
            overlay.classList.toggle("show");
        }

        if (toggleBtn) {
            toggleBtn.addEventListener("click", toggleSidebar);
        }

        if (overlay) {
            overlay.addEventListener("click", toggleSidebar);
        }
    });
</script>
</body>
</html>