<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Expirado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-family: 'Segoe UI', sans-serif; 
        }
        .expired-card {
            background: #fff;
            border-radius: 20px;
            padding: 3rem 2.5rem;
            text-align: center;
            max-width: 420px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }
        .expired-icon {
            font-size: 4rem;
            color: #e74c3c;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="expired-card">
        <div class="expired-icon">
            <i class="fas fa-link-slash"></i>
        </div>
        <h2 class="fw-bold mb-3 text-dark-alt">Link Indisponível</h2>
        <p class="text-muted mb-4">
            Este link de catálogo não é válido, já expirou ou foi desativado.<br>
            Por favor, solicite um novo link ao profissional responsável.
        </p>
        <div class="d-grid">
            <a href="javascript:window.close()" class="btn btn-outline-secondary">
                <i class="fas fa-times me-2"></i>Fechar
            </a>
        </div>
    </div>
</body>
</html>
