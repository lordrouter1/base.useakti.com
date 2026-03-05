<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Em Manutenção — Akti</title>
    <meta name="description" content="Estamos realizando melhorias. Em breve tudo estará funcionando normalmente.">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#2c3e50">
    <meta property="og:title" content="Em Manutenção — Akti">
    <meta property="og:description" content="Estamos realizando melhorias. Em breve tudo estará funcionando normalmente.">
    <meta property="og:image" content="assets/logos/akti-logo-dark.svg">
    <meta name="twitter:card" content="summary">
    <link rel="icon" type="image/x-icon" href="assets/logos/akti-icon-dark.ico">
    <link rel="manifest" href="manifest.json">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #fefce8 0%, #fef9c3 50%, #fef3c7 100%);
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .maintenance-container {
            text-align: center;
            max-width: 480px;
            padding: 2.5rem;
        }
        .maintenance-illustration {
            width: 200px;
            height: 200px;
            margin: 0 auto 2rem;
        }
        .maintenance-illustration svg {
            width: 100%;
            height: 100%;
        }
        .maintenance-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #92400e;
        }
        .maintenance-text {
            font-size: 0.95rem;
            color: #78716c;
            margin-bottom: 2rem;
            line-height: 1.7;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.65rem 1.75rem;
            font-size: 0.9rem;
            font-weight: 500;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
            background: #f59e0b;
            color: #fff;
            box-shadow: 0 2px 8px rgba(245,158,11,0.3);
        }
        .btn:hover {
            background: #d97706;
            box-shadow: 0 4px 16px rgba(245,158,11,0.4);
            transform: translateY(-1px);
        }
        .maintenance-footer {
            margin-top: 3rem;
            font-size: 0.78rem;
            color: #a8a29e;
        }
        .maintenance-footer img {
            height: 18px;
            opacity: 0.4;
            vertical-align: middle;
            margin-right: 0.375rem;
        }
        /* Wrench animation */
        @keyframes wiggle {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(12deg); }
            75% { transform: rotate(-12deg); }
        }
        .wrench {
            animation: wiggle 2.5s ease-in-out infinite;
            transform-origin: 100px 110px;
        }
        /* Gear spin */
        @keyframes spin-slow {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }
        .gear {
            animation: spin-slow 8s linear infinite;
            transform-origin: 148px 68px;
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <div class="maintenance-illustration">
            <svg viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Background -->
                <circle cx="100" cy="105" r="85" fill="#FEF9C3"/>
                <circle cx="100" cy="105" r="65" fill="#FEF3C7"/>
                <!-- Gear top-right -->
                <g class="gear">
                    <circle cx="148" cy="68" r="14" stroke="#f59e0b" stroke-width="3" fill="#FDE68A"/>
                    <line x1="148" y1="51" x2="148" y2="55" stroke="#f59e0b" stroke-width="3" stroke-linecap="round"/>
                    <line x1="148" y1="81" x2="148" y2="85" stroke="#f59e0b" stroke-width="3" stroke-linecap="round"/>
                    <line x1="131" y1="68" x2="135" y2="68" stroke="#f59e0b" stroke-width="3" stroke-linecap="round"/>
                    <line x1="161" y1="68" x2="165" y2="68" stroke="#f59e0b" stroke-width="3" stroke-linecap="round"/>
                    <line x1="137" y1="57" x2="140" y2="60" stroke="#f59e0b" stroke-width="3" stroke-linecap="round"/>
                    <line x1="156" y1="76" x2="159" y2="79" stroke="#f59e0b" stroke-width="3" stroke-linecap="round"/>
                    <line x1="137" y1="79" x2="140" y2="76" stroke="#f59e0b" stroke-width="3" stroke-linecap="round"/>
                    <line x1="156" y1="60" x2="159" y2="57" stroke="#f59e0b" stroke-width="3" stroke-linecap="round"/>
                </g>
                <!-- Wrench -->
                <g class="wrench">
                    <path d="M88 95 L72 130 C70 135 74 139 79 137 L95 122" stroke="#d97706" stroke-width="4" stroke-linecap="round" fill="none"/>
                    <circle cx="92" cy="100" r="15" stroke="#d97706" stroke-width="3" fill="#FDE68A"/>
                    <circle cx="92" cy="100" r="6" fill="#f59e0b"/>
                </g>
                <!-- Hard hat -->
                <path d="M55 88 Q55 70 75 68 L77 88 Z" fill="#f59e0b" opacity="0.5"/>
                <!-- Small dots / sparkles -->
                <circle cx="52" cy="120" r="3" fill="#fbbf24" opacity="0.6"/>
                <circle cx="155" cy="130" r="2.5" fill="#fbbf24" opacity="0.5"/>
                <circle cx="60" cy="75" r="2" fill="#fcd34d" opacity="0.7"/>
            </svg>
        </div>
        <h1 class="maintenance-title">Estamos em manutenção</h1>
        <p class="maintenance-text">
            Estamos realizando melhorias para você. Em breve tudo estará funcionando normalmente. Agradecemos a compreensão!
        </p>
        <a href="?" class="btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            Voltar ao Início
        </a>
        <div class="maintenance-footer">
            <img src="assets/logos/akti-logo-dark.svg" alt="Akti">
            Akti — Gestão em Produção
        </div>
    </div>
</body>
</html>
