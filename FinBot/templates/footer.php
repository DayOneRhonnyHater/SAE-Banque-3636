

<!-- Scripts jQuery, Bootstrap et autres -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Script personnalisé -->
<script src="../assets/js/main.js"></script>

<!-- Script pour les messages flash -->
<?php if (isset($_SESSION['flash_message'])): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: '<?= $_SESSION['flash_message']['type'] ?>',
            title: '<?= $_SESSION['flash_message']['title'] ?>',
            text: '<?= $_SESSION['flash_message']['message'] ?>',
            timer: 3000,
            timerProgressBar: true
        });
    });
</script>
<?php unset($_SESSION['flash_message']); endif; ?>

</body>
</html>