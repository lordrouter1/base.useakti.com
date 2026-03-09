<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Akti - Gestão em Produção</title>
    <link rel="icon" type="image/x-icon" href="assets/logos/akti-icon-dark.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="assets/css/theme.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <?php
        $recaptchaSiteKey = \Akti\Models\LoginAttempt::getSiteKey();
        $captchaEnabled = !empty($recaptchaSiteKey) && !empty($showCaptcha);
    ?>
    <?php if ($captchaEnabled): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php endif; ?>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background: #1e293b;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow: hidden;
        }
        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: 
                radial-gradient(circle at 20% 50%, rgba(52, 152, 219, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(52, 152, 219, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 50% 80%, rgba(41, 128, 185, 0.08) 0%, transparent 50%);
            animation: bgShift 15s ease-in-out infinite alternate;
            z-index: 0;
        }
        @keyframes bgShift {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(-2%, -2%) rotate(3deg); }
        }
        .login-card {
            max-width: 960px;
            width: 100%;
            border-radius: 24px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255,255,255,0.05);
            overflow: hidden;
            display: flex;
            flex-direction: row;
            background: white;
            min-height: 560px;
            position: relative;
            z-index: 1;
        }
        .login-sidebar {
            background: linear-gradient(160deg, #1e293b 0%, #0f172a 60%, #020617 100%);
            width: 45%;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            flex-direction: column;
            text-align: center;
            padding: 50px 40px;
            overflow: hidden;
        }
        .login-sidebar::before {
            content: '';
            position: absolute;
            top: -80px;
            right: -80px;
            width: 250px;
            height: 250px;
            border-radius: 50%;
            background: rgba(59, 130, 246, 0.1);
        }
        .login-sidebar::after {
            content: '';
            position: absolute;
            bottom: -60px;
            left: -60px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(59, 130, 246, 0.06);
        }
        .login-sidebar-content {
            position: relative;
            z-index: 2;
        }
        .login-sidebar-content .logo-img {
            width: 180px;
            height: auto;
            margin-bottom: 30px;
            filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.3));
        }
        .login-sidebar-content h2 {
            font-size: 1.1rem;
            font-weight: 400;
            color: rgba(255, 255, 255, 0.7);
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 24px;
        }
        .login-sidebar-content p {
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.55);
            line-height: 1.7;
            max-width: 280px;
            margin: 0 auto;
        }
        .sidebar-divider {
            width: 50px;
            height: 2px;
            background: #3b82f6;
            margin: 0 auto 24px;
            border-radius: 2px;
        }
        .login-form-container {
            width: 55%;
            padding: 55px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-header {
            margin-bottom: 32px;
            text-align: left;
        }
        .login-header .mobile-logo {
            display: none;
        }
        .login-header h3 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 6px;
        }
        .login-header p {
            color: #64748b;
            font-size: 0.95rem;
        }
        .form-floating {
            position: relative;
        }
        .form-floating > label {
            padding-left: 2.75rem;
        }
        .form-floating > .form-control {
            padding-left: 2.75rem;
            border-radius: 12px;
            border: 1.5px solid #e2e8f0;
            height: 56px;
            font-size: 0.95rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-floating > .form-control:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
            border-color: #3b82f6;
        }
        .form-floating > .input-icon {
            position: absolute;
            top: 50%;
            left: 1rem;
            transform: translateY(-50%);
            color: #94a3b8;
            z-index: 4;
            font-size: 1rem;
            transition: color 0.2s;
        }
        .form-floating:focus-within > .input-icon {
            color: #3b82f6;
        }
        .btn-login {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            border: none;
            color: white;
            font-weight: 700;
            font-size: 1rem;
            padding: 14px;
            border-radius: 12px;
            letter-spacing: 0.5px;
            transition: transform 0.15s, box-shadow 0.2s;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.35);
        }
        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.45);
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            color: white;
        }
        .btn-login:active {
            transform: translateY(0);
        }
        .login-footer {
            text-align: center;
            color: #64748b;
            font-size: 0.8rem;
            margin-top: 8px;
        }
        @media (max-width: 768px) {
            body {
                padding: 20px;
            }
            .login-card {
                flex-direction: column;
                max-width: 420px;
                min-height: auto;
                border-radius: 20px;
            }
            .login-sidebar {
                display: none;
            }
            .login-form-container {
                width: 100%;
                padding: 40px 30px;
            }
            .login-header .mobile-logo {
                display: block;
                text-align: center;
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>

<div class="card login-card border-0">
    <!-- Sidebar Branding Section -->
    <div class="login-sidebar">
        <div class="login-sidebar-content">
            <img src="assets/logos/akti-square-light.svg" alt="Akti Logo" class="logo-img">
            <div class="sidebar-divider"></div>
            <p>Gerencie seus pedidos, clientes e produção de forma eficiente e profissional.</p>
        </div>
    </div>
    
    <!-- Login Form Section -->
    <div class="login-form-container">
        <div class="login-header">
            <div class="mobile-logo">
                <img src="assets/logos/akti-square-light.svg" width="250">
            </div>
            <h3>Bem-vindo(a)!</h3>
            <p>Faça login para acessar sua conta.</p>
        </div>

        <?php
            $tenantInfo = $_SESSION['tenant'] ?? null;
            $tenantLabel = (!empty($tenantInfo['key']) && empty($tenantInfo['has_error'])) ? $tenantInfo['key'] : 'ambiente padrão';
            $tenantBlocked = !empty($tenantInfo['has_error']);
        ?>
        <div class="alert alert-info py-2" style="border-radius: 10px;">
            <i class="fas fa-building me-2"></i>
            Cliente detectado pelo subdomínio: <strong><?= htmlspecialchars($tenantLabel) ?></strong>
        </div>

        <?php if (isset($error)): ?>
            <?php
                $isBlocked = !empty($lockout['blocked']);
                $alertClass = $isBlocked ? 'alert-warning' : 'alert-danger';
                $alertIcon  = $isBlocked ? 'fa-clock' : 'fa-exclamation-circle';
            ?>
            <div class="alert <?= $alertClass ?> py-2 d-flex align-items-center" style="border-radius: 10px;">
                <i class="fas <?= $alertIcon ?> me-2"></i>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['tenant_changed'])): ?>
            <div class="alert alert-warning py-2 d-flex align-items-center" style="border-radius: 10px;">
                <i class="fas fa-shield-alt me-2"></i>
                <div>Sessão encerrada por troca de subdomínio. Faça login novamente no cliente correto.</div>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['session_expired'])): ?>
            <div class="alert alert-warning py-2 d-flex align-items-center" style="border-radius: 10px;">
                <i class="fas fa-hourglass-end me-2"></i>
                <div>Sessão expirada por inatividade. Faça login novamente para continuar.</div>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['access_denied'])): ?>
            <div class="alert alert-warning py-2 d-flex align-items-center" style="border-radius: 10px;">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <div>Acesso restrito. Faça login.</div>
            </div>
        <?php endif; ?>

        <form method="POST" action="?page=login">
            <?= csrf_field() ?>
            <input type="hidden" name="tenant_key" value="<?= htmlspecialchars($tenantInfo['key'] ?? '') ?>">
            <?php
                $isBlocked = !empty($lockout['blocked']);
                $formDisabled = ($tenantBlocked || $isBlocked) ? 'disabled' : '';
            ?>
            <?php if ($tenantBlocked): ?>
                <input type="hidden" name="tenant_blocked" value="1">
            <?php endif; ?>
            <div class="form-floating mb-3">
                <i class="fas fa-envelope input-icon"></i>
                <input type="email" class="form-control" id="email" name="email" required placeholder="seu@email.com" autocomplete="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" <?= $formDisabled ?>>
                <label for="email">E-mail</label>
            </div>
            
            <div class="form-floating mb-4">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" class="form-control" id="password" name="password" required placeholder="Sua senha" autocomplete="current-password" <?= $formDisabled ?>>
                <label for="password">Senha</label>
            </div>

            <?php if ($captchaEnabled && !$isBlocked): ?>
            <div class="mb-3 d-flex justify-content-center">
                <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars($recaptchaSiteKey) ?>"></div>
            </div>
            <?php endif; ?>

            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-login btn-lg" <?= $formDisabled ?>>
                    <?php if ($isBlocked): ?>
                        <i class="fas fa-lock me-2"></i>BLOQUEADO
                    <?php else: ?>
                        <i class="fas fa-sign-in-alt me-2"></i>ENTRAR
                    <?php endif; ?>
                </button>
            </div>
            
            <div class="login-footer">
                &copy; <?= date('Y') ?> Akti - Gestão em Produção
            </div>
        </form>
    </div>
</div>

</body>
</html>
