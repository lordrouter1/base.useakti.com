<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página Não Encontrada — Akti</title>
    <meta name="description" content="Página não encontrada — Akti, Gestão em Produção.">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#2c3e50">
    <meta property="og:title" content="Página Não Encontrada — Akti">
    <meta property="og:description" content="A página que você tentou acessar não está disponível.">
    <meta property="og:image" content="assets/logos/akti-logo-dark.svg">
    <meta name="twitter:card" content="summary">
    <link rel="icon" type="image/x-icon" href="assets/logos/akti-icon-dark.ico">
    <link rel="manifest" href="manifest.json">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-container {
            text-align: center;
            max-width: 480px;
            padding: 2.5rem;
        }
        .error-illustration {
            width: 200px;
            height: 200px;
            margin: 0 auto 2rem;
        }
        .error-illustration svg {
            width: 100%;
            height: 100%;
        }
        .error-title {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #1e293b;
        }
        .error-text {
            font-size: 0.95rem;
            color: #64748b;
            margin-bottom: 2rem;
            line-height: 1.6;
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
            background: #3b82f6;
            color: #fff;
            box-shadow: 0 2px 8px rgba(59,130,246,0.25);
        }
        .btn:hover {
            background: #2563eb;
            box-shadow: 0 4px 16px rgba(59,130,246,0.35);
            transform: translateY(-1px);
        }
        .error-footer {
            margin-top: 3rem;
            font-size: 0.78rem;
            color: #94a3b8;
        }
        .error-footer img {
            height: 18px;
            opacity: 0.4;
            vertical-align: middle;
            margin-right: 0.375rem;
        }
        /* Questionmark floating animation */
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }
        .question-mark {
            animation: float 3s ease-in-out infinite;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-illustration">
            <svg viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Background circles -->
                <circle cx="100" cy="105" r="85" fill="#EEF2FF"/>
                <circle cx="100" cy="105" r="65" fill="#E0E7FF"/>
                <!-- Face -->
                <!-- Left eye -->
                <circle cx="78" cy="95" r="6" fill="#3b82f6"/>
                <!-- Right eye - raised eyebrow look -->
                <circle cx="122" cy="90" r="6" fill="#3b82f6"/>
                <!-- Left eyebrow - normal -->
                <line x1="68" y1="82" x2="88" y2="82" stroke="#3b82f6" stroke-width="3" stroke-linecap="round"/>
                <!-- Right eyebrow - raised (questioning) -->
                <path d="M112 74 Q122 70 132 76" stroke="#3b82f6" stroke-width="3" stroke-linecap="round" fill="none"/>
                <!-- Mouth - small confused "o" -->
                <ellipse cx="100" cy="118" rx="7" ry="8" stroke="#3b82f6" stroke-width="3" fill="#E0E7FF"/>
                <!-- Question mark floating above -->
                <g class="question-mark">
                    <text x="138" y="60" font-family="Inter, sans-serif" font-size="36" font-weight="700" fill="#93c5fd">?</text>
                </g>
            </svg>
        </div>
        <h1 class="error-title">Hmm, essa página não existe</h1>
        <p class="error-text">
            Parece que você tentou acessar algo que não está disponível. 
            Volte ao início para continuar navegando.
        </p>
        <a href="?" class="btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            Voltar ao Início
        </a>
        <div class="error-footer">
            <img src="assets/logos/akti-logo-dark.svg" alt="Akti">
            Akti — Gestão em Produção
        </div>
    </div>
</body>
</html>
