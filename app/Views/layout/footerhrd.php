<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<footer class="text-center mt-5 mb-3 text-secondary">
    © <?= date('Y'); ?> Sistem Informasi Cuti
</footer>

<script>
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