<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requisição Inválida — Akti</title>
    <meta name="description" content="Requisição inválida — Akti, Gestão em Produção.">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#2c3e50">
    <meta property="og:title" content="Requisição Inválida — Akti">
    <meta property="og:description" content="A requisição não pôde ser processada por motivos de segurança.">
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
            padding: 2rem;
        }
        .error-icon {
            font-size: 5rem;
            margin-bottom: 1rem;
            opacity: 0.7;
        }
        .error-code {
            font-size: 6rem;
            font-weight: 700;
            color: #e74c3c;
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        .error-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 1rem;
        }
        .error-message {
            font-size: 1rem;
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        .error-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
        }
        .btn-primary {
            background-color: #3b82f6;
            color: #fff;
        }
        .btn-primary:hover {
            background-color: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        .btn-secondary {
            background-color: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
        .btn-secondary:hover {
            background-color: #e2e8f0;
            transform: translateY(-1px);
        }
        .logo {
            width: 48px;
            height: 48px;
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <img src="assets/logos/akti-icon-dark.svg" alt="Akti" class="logo">
        <div class="error-icon">🛡️</div>
        <div class="error-code">403</div>
        <h1 class="error-title">Requisição Inválida</h1>
        <p class="error-message">
            Sua requisição não pôde ser processada por motivos de segurança.<br>
            <strong>Atualize a página e tente novamente.</strong>
        </p>
        <div class="error-actions">
            <a href="javascript:location.reload()" class="btn btn-primary">
                🔄 Atualizar Página
            </a>
            <a href="?" class="btn btn-secondary">
                🏠 Página Inicial
            </a>
        </div>
    </div>
</body>
</html>
