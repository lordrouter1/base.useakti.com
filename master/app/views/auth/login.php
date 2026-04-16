<?php
/**
 * View: Login — Akti Master Admin
 * Layout split: painel escuro esquerdo + formulário branco direito
 */
$loginError = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Akti Master Admin</title>
    <link rel="icon" href="logos/akti-icon-dark.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #1a2332;
            padding: 20px;
        }

        .login-wrapper {
            display: flex;
            width: 100%;
            max-width: 920px;
            min-height: 520px;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(0,0,0,0.4);
        }

        /* ── Painel Esquerdo (escuro com bolhas) ── */
        .login-left {
            flex: 1;
            background: linear-gradient(160deg, #0f1c2e 0%, #1b3d6e 50%, #1a2f52 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 50px 40px;
            position: relative;
            overflow: hidden;
        }

        .login-left::before {
            content: '';
            position: absolute;
            width: 300px; height: 300px;
            border-radius: 50%;
            background: rgba(42, 82, 152, 0.35);
            top: -80px; right: -60px;
            filter: blur(2px);
        }

        .login-left::after {
            content: '';
            position: absolute;
            width: 200px; height: 200px;
            border-radius: 50%;
            background: rgba(42, 82, 152, 0.25);
            bottom: -40px; left: -40px;
            filter: blur(2px);
        }

        .login-left .logo-box {
            background: white;
            border-radius: 20px;
            padding: 30px 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            position: relative;
            z-index: 1;
            margin-bottom: 28px;
        }

        .login-left .logo-box img {
            height: 70px;
        }

        .login-left .divider {
            width: 40px; height: 3px;
            background: #4a90d9;
            border-radius: 3px;
            margin-bottom: 24px;
            position: relative;
            z-index: 1;
        }

        .login-left .tagline {
            color: rgba(255,255,255,0.65);
            text-align: center;
            font-size: 14px;
            line-height: 1.7;
            max-width: 260px;
            position: relative;
            z-index: 1;
        }

        /* ── Painel Direito (formulário) ── */
        .login-right {
            flex: 1;
            background: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 50px 44px;
        }

        .login-right h2 {
            font-size: 28px;
            font-weight: 800;
            color: #1a2332;
            margin-bottom: 6px;
        }

        .login-right .subtitle {
            color: #7a8a9e;
            font-size: 14px;
            margin-bottom: 28px;
        }

        .login-right .tenant-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #e6f7f0;
            border: 1px solid #b8e8d5;
            color: #1a6b47;
            border-radius: 12px;
            padding: 12px 18px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 24px;
        }

        .login-right .tenant-badge i {
            font-size: 18px;
            opacity: 0.7;
        }

        .login-right .tenant-badge strong {
            font-weight: 700;
        }

        .login-right .form-group {
            margin-bottom: 18px;
        }

        .login-right .input-field {
            display: flex;
            align-items: center;
            border: 2px solid #e4e8ee;
            border-radius: 14px;
            padding: 0 18px;
            height: 52px;
            transition: all 0.25s ease;
            background: white;
        }

        .login-right .input-field:focus-within {
            border-color: #1b3d6e;
            box-shadow: 0 0 0 3px rgba(27, 61, 110, 0.08);
        }

        .login-right .input-field i {
            color: #a0aec0;
            font-size: 16px;
            margin-right: 12px;
            transition: color 0.25s;
        }

        .login-right .input-field:focus-within i {
            color: #1b3d6e;
        }

        .login-right .input-field input {
            border: none;
            outline: none;
            flex: 1;
            font-size: 15px;
            color: #333;
            background: transparent;
            font-family: inherit;
        }

        .login-right .input-field input::placeholder {
            color: #b0bcc8;
        }

        .login-right .input-field .toggle-pw {
            background: none;
            border: none;
            color: #a0aec0;
            cursor: pointer;
            padding: 4px;
            font-size: 15px;
            transition: color 0.2s;
        }

        .login-right .input-field .toggle-pw:hover {
            color: #1b3d6e;
        }

        .btn-login {
            width: 100%;
            height: 52px;
            border: none;
            border-radius: 14px;
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: white;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 6px;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #1d4ed8, #2563eb);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.35);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .login-footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #a0aec0;
        }

        .alert-login {
            border: none;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 13px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ── Responsivo ── */
        @media (max-width: 767.98px) {
            .login-wrapper {
                flex-direction: column;
                max-width: 440px;
            }
            .login-left {
                padding: 36px 30px;
                min-height: auto;
            }
            .login-left .logo-box { padding: 20px 24px; }
            .login-left .logo-box img { height: 50px; }
            .login-right { padding: 36px 28px; }
            .login-right h2 { font-size: 24px; }
        }

        @media (max-width: 479px) {
            body { padding: 10px; }
            .login-wrapper { border-radius: 18px; }
            .login-right { padding: 28px 20px; }
        }
    </style>
<?php
$showCaptcha = $showCaptcha ?? false;
$isBlocked = $isBlocked ?? false;
$blockMinutes = $blockMinutes ?? 0;
?>
</head>
<body>

    <div class="login-wrapper">
        <!-- Painel Esquerdo — Branding -->
        <div class="login-left">
            <div class="logo-box">
                <img src="logos/akti-logo-light-nBg.svg" alt="Akti - Gestão em Produção">
            </div>
            <div class="divider"></div>
            <p class="tagline">
                Gerencie seus clientes, planos e bancos de dados de forma centralizada e profissional.
            </p>
        </div>

        <!-- Painel Direito — Formulário -->
        <div class="login-right">
            <h2>Bem-vindo(a)!</h2>
            <p class="subtitle">Faça login para acessar o painel master.</p>

            <?php if ($isBlocked): ?>
                <div class="alert alert-danger alert-login">
                    <i class="fas fa-ban"></i>
                    Acesso bloqueado por excesso de tentativas. Tente novamente em <strong><?= (int) $blockMinutes ?></strong> minuto(s).
                </div>
            <?php elseif ($loginError): ?>
                <div class="alert alert-danger alert-login">
                    <i class="fas fa-circle-exclamation"></i>
                    <?= htmlspecialchars($loginError) ?>
                </div>
            <?php endif; ?>

            <form action="?page=login&action=authenticate" method="POST" autocomplete="off">
                <?= master_csrf_field() ?>
                <div class="form-group">
                    <div class="input-field">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="E-mail" required autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-field">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="loginPassword" placeholder="Senha" required>
                        <button type="button" class="toggle-pw" onclick="togglePassword()" tabindex="-1">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <?php if ($showCaptcha && !$isBlocked): ?>
                <div class="form-group">
                    <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars(defined('RECAPTCHA_SITE_KEY') ? RECAPTCHA_SITE_KEY : '') ?>"></div>
                </div>
                <?php endif; ?>

                <button type="submit" class="btn-login" <?= $isBlocked ? 'disabled' : '' ?>>
                    <i class="fas fa-right-to-bracket"></i> ENTRAR
                </button>
            </form>

            <div class="login-footer">
                © <?= date('Y') ?> Akti - Gestão em Produção
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php if ($showCaptcha && !$isBlocked): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php endif; ?>
    <script>
        function togglePassword() {
            const input = document.getElementById('loginPassword');
            const icon = document.getElementById('toggleIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>
</html>
