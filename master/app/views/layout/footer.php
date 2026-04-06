            </div><!-- /content-area -->
        </main>
    </div><!-- /app-wrapper -->

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom JS -->
    <script src="assets/js/app.js"></script>

    <?php
    // Flash messages via SweetAlert2
    if (isset($_SESSION['success'])) {
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: " . json_encode($_SESSION['success']) . ",
                timer: 3000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        </script>";
        unset($_SESSION['success']);
    }
    if (isset($_SESSION['error'])) {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: " . json_encode($_SESSION['error']) . ",
                confirmButtonColor: '#1b3d6e'
            });
        </script>";
        unset($_SESSION['error']);
    }

    // Scripts específicos da página (definido nas views antes de incluir o footer)
    if (isset($pageScripts)) {
        echo $pageScripts;
    }
    ?>
</body>
</html>
