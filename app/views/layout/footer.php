</main>
    </div>
</div>

<!-- Footer do sistema -->
<footer class="app-footer mt-auto">
    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center py-3">
            <div class="d-flex align-items-center">
                <img src="assets/logos/akti-logo-dark.svg" alt="Akti" height="22" class="me-2 opacity-50">
                <span class="text-muted small">&copy; <?= date('Y') ?> Akti - Gestão em Produção</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <?php if (isset($_SESSION['user_id'])): ?>
                <a href="?page=walkthrough&action=manual" class="text-muted small text-decoration-none" title="Manual do Sistema">
                    <i class="fas fa-book me-1"></i>Manual
                </a>
                <button type="button" class="wt-footer-help" id="wtFooterHelp" title="Refazer o Tour Guiado do Sistema">
                    <i class="fas fa-question-circle wt-pulse-icon"></i>
                    <span>Tutorial</span>
                </button>
                <?php endif; ?>
                <span class="text-muted small">v1.0</span>
            </div>
        </div>
    </div>
</footer>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Walkthrough -->
<script src="assets/js/walkthrough.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Botão de tutorial no rodapé
        var wtBtn = document.getElementById('wtFooterHelp');
        if (wtBtn) {
            wtBtn.addEventListener('click', function() {
                if (window.aktiWalkthrough) {
                    window.aktiWalkthrough.start(0);
                }
            });
        }
        // Auto-start do tour para novos usuários
        if (window.aktiWalkthrough) {
            window.aktiWalkthrough.autoStart();
        }
    });
</script>
</body>
</html>
