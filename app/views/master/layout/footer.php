            </div><!-- /content-area -->
        </main>
    </div><!-- /app-wrapper -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/master.js"></script>

    <script>
    const csrfToken = '<?= \Akti\Core\Security::getToken() ?>';
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrfToken } });
    </script>

    <?php
    if (isset($_SESSION['success'])) {
        echo "<script>
            Swal.mixin({ toast:true, position:'top-end', showConfirmButton:false, timer:2500, timerProgressBar:true }).fire({
                icon:'success',
                title:" . json_encode($_SESSION['success']) . "
            });
        </script>";
        unset($_SESSION['success']);
    }
    if (isset($_SESSION['error'])) {
        echo "<script>
            Swal.fire({
                icon:'error',
                title:'Erro',
                text:" . json_encode($_SESSION['error']) . ",
                confirmButtonColor:'#4f46e5'
            });
        </script>";
        unset($_SESSION['error']);
    }

    if (isset($pageScripts)) {
        echo $pageScripts;
    }
    ?>
</body>
</html>
